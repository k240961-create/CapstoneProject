<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

// SRS Section 5.5 Business Rule: Only registered users can order (C37, C25)
// Block both GET and POST for guests — no one can view or submit the checkout form without logging in.
if (mff_role() === 'guest' || !isset($_SESSION['user_id'])) {
    mff_set_flash('info', 'Please log in or register before checking out. Your basket has been saved.');
    header('Location: ' . BASE_URL . '/login.php?redirect=/checkout.php');
    exit;
}

  require_once file_exists(__DIR__ . '/../includes/stripe_config.php') ? __DIR__ . '/../includes/stripe_config.php' : __DIR__ . '/includes/stripe_config.php';
  $stripeConfigured = !empty($stripeSecretKey) && class_exists(\Stripe\Checkout\Session::class);

$cart = cart_contents();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($cart['items'])) {
        mff_set_flash('error', 'Your cart is empty — add something before checking out.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $instructions = trim($_POST['delivery_instructions'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash_on_delivery';

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($contactNumber === '') {
        $errors[] = 'Contact number is required.';
    } elseif (preg_match('/[a-zA-Z]/', $contactNumber)) {
        $errors[] = 'Contact number must contain numbers only (no alphabetic characters).';
    } elseif (!preg_match('/^[0-9\s\+\-\(\)]{8,20}$/', $contactNumber)) {
        $errors[] = 'Please enter a valid contact phone number (at least 8 digits).';
    }
    if ($address === '') $errors[] = 'Delivery address is required.';
    if (!in_array($paymentMethod, ['cash_on_delivery', 'credit_card'], true)) {
      $errors[] = 'Choose Stripe card payment or cash on delivery. PayPal is not currently available.';
    }
    if ($paymentMethod === 'credit_card' && !$stripeConfigured) {
      $errors[] = 'Stripe card payments are unavailable until test credentials are configured. Choose cash on delivery.';
    }

    if (empty($errors)) {
        if ($paymentMethod === 'credit_card') {
            require_once file_exists(__DIR__ . '/../includes/stripe_config.php') ? __DIR__ . '/../includes/stripe_config.php' : __DIR__ . '/includes/stripe_config.php';
            $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL;
            
            $lineItems = [];
            foreach ($cart['items'] as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                        'unit_amount' => (int) round($item['price'] * 100),
                    ],
                    'quantity' => $item['quantity'],
                ];
            }
            
            if ($cart['delivery_fee'] > 0) {
              $lineItems[] = [
                'price_data' => [
                  'currency' => 'aud',
                  'product_data' => ['name' => 'Delivery fee'],
                  'unit_amount' => (int) round($cart['delivery_fee'] * 100),
                ],
                'quantity' => 1,
              ];
            }

            if ($cart['tax'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => 'Tax (GST)',
                        ],
                        'unit_amount' => (int) round($cart['tax'] * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            try {
                $checkout_session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                  'client_reference_id' => (string) $_SESSION['user_id'],
                    'success_url' => $domain . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $domain . '/stripe_cancel.php',
                ]);
                
                $_SESSION['pending_checkout'] = [
                  'stripe_session_id' => $checkout_session->id,
                  'user_id' => (int) $_SESSION['user_id'],
                    'customer_name' => $fullName,
                    'contact_number' => $contactNumber,
                    'delivery_address' => $address,
                    'delivery_instructions' => $instructions,
                    'payment_method' => $paymentMethod,
                  'items' => $cart['items'],
                  'subtotal' => $cart['subtotal'],
                  'delivery_fee' => $cart['delivery_fee'],
                  'tax' => $cart['tax'],
                  'total' => $cart['total'],
                ];
                
                header("HTTP/1.1 303 See Other");
                header("Location: " . $checkout_session->url);
                exit;
            } catch (Exception $e) {
                error_log('[mff] stripe checkout error: ' . $e->getMessage());
                $errors[] = 'Stripe Error: Could not initialize payment.';
            }
        }
        
        if (empty($errors) && $paymentMethod !== 'credit_card') {
            $pdo = mff_db();
            $orderId = null;

            if ($pdo !== null) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare(
                        'INSERT INTO orders (user_id, customer_name, contact_number, delivery_address, delivery_instructions,
                                              payment_method, subtotal, delivery_fee, tax, total, status, created_at)
                         VALUES (:user_id, :name, :contact, :address, :instructions, :payment, :subtotal, :delivery_fee, :tax, :total, "pending", CURRENT_TIMESTAMP)'
                    );
                    $stmt->execute([
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'name' => $fullName, 'contact' => $contactNumber, 'address' => $address,
                        'instructions' => $instructions, 'payment' => $paymentMethod,
                        'subtotal' => $cart['subtotal'], 'delivery_fee' => $cart['delivery_fee'],
                        'tax' => $cart['tax'], 'total' => $cart['total'],
                    ]);
                    $orderId = (int) $pdo->lastInsertId();

                    $itemStmt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (:order_id, :product_id, :name, :price, :quantity)'
                    );
                    foreach ($cart['items'] as $item) {
                        $itemStmt->execute([
                            'order_id' => $orderId, 'product_id' => $item['product_id'],
                            'name' => $item['name'], 'price' => $item['price'], 'quantity' => $item['quantity'],
                        ]);
                      if (!mff_decrement_stock($pdo, (int) $item['product_id'], (int) $item['quantity'])) {
                        throw new RuntimeException('Insufficient stock for product ' . (int) $item['product_id']);
                        }
                    }

                    $pdo->commit();
                  } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                      $pdo->rollBack();
                    }
                    error_log('[mff] order insert failed: ' . $e->getMessage());
                    $errors[] = 'We could not place your order right now — please try again.';
                }
            }

            if (empty($errors)) {
                // No live DB (or insert succeeded) — either way, stash a session
                // receipt so order_confirmation.php has something to show even
                // in fallback mode.
                $_SESSION['last_order'] = [
                    'id' => $orderId ?? random_int(3000, 3999),
                    'customer_name' => $fullName,
                    'contact_number' => $contactNumber,
                    'delivery_address' => $address,
                    'delivery_instructions' => $instructions,
                    'payment_method' => $paymentMethod,
                    'items' => $cart['items'],
                    'subtotal' => $cart['subtotal'],
                    'delivery_fee' => $cart['delivery_fee'],
                    'tax' => $cart['tax'],
                    'total' => $cart['total'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                cart_clear();
                mff_set_role('customer');
                header('Location: ' . BASE_URL . '/order_confirmation.php');
                exit;
            }
        }
    }
}

if (empty($cart['items']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    mff_set_flash('error', 'Your cart is empty — add something before checking out.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Checkout';
$activeNav = 'shop';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div>
  <p class="section-eyebrow">Almost done</p>
  <h1 class="section-title">Checkout</h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error" style="margin:1rem 0 0;max-width:none;">
    <ul style="margin:0;padding-left:1.1rem;">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; // (Guest check removed: guests are now blocked from reaching this page at all — see C37 fix above) ?>

<form method="post" action="<?= BASE_URL ?>/checkout.php" class="form-grid" style="margin-top:1.5rem;grid-template-columns:1.2fr .8fr;align-items:start;">
  <div class="card-artisan">
    <h2 class="section-title" style="font-size:1.75rem;">Delivery details</h2>
    <div class="form-grid">
      <div class="form-field">
        <label for="full-name">Full name</label>
        <input id="full-name" name="full_name" required type="text" value="<?= htmlspecialchars($_POST['full_name'] ?? $_SESSION['user_name'] ?? '') ?>">
      </div>
      <div class="form-field">
        <label for="contact-number">Contact number</label>
        <input 
          id="contact-number" 
          name="contact_number" 
          required 
          type="tel" 
          inputmode="numeric"
          pattern="[0-9\s\+\-\(\)]{8,20}"
          placeholder="e.g. 0412 345 678"
          oninput="this.value = this.value.replace(/[^0-9\+\s\-()]/g, '')"
          onkeypress="return /[0-9\+\s\-\(\)]/.test(event.key)"
          value="<?= htmlspecialchars($_POST['contact_number'] ?? $_SESSION['user_contact'] ?? '') ?>">
        <p style="font-size:0.75rem;color:#56715f;margin-top:0.25rem;">Only numbers are accepted (letters will not be entered).</p>
      </div>
      <div class="form-field form-field--full">
        <label for="delivery-address">Delivery address</label>
        <input id="delivery-address" name="delivery_address" required type="text" value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>">
      </div>
      <div class="form-field form-field--full">
        <label for="delivery-instructions">Delivery instructions</label>
        <textarea id="delivery-instructions" name="delivery_instructions" rows="3"><?= htmlspecialchars($_POST['delivery_instructions'] ?? '') ?></textarea>
      </div>
    </div>

    <fieldset style="margin-top:1.75rem;border:none;padding:0;">
      <legend style="font-size:.875rem;font-weight:700;margin-bottom:0.5rem;">Select Payment Method</legend>
      <div class="payment-options" style="display:flex;flex-direction:column;gap:0.75rem;">
        <label style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border:1px solid var(--line,#e2e8f0);border-radius:8px;cursor:pointer;background:#fff;">
          <input name="payment_method" value="credit_card" type="radio" <?= $stripeConfigured ? 'checked' : 'disabled' ?> onchange="updatePayButton()">
          <div>
            <span style="font-weight:600;<?= $stripeConfigured ? '' : 'color:#64748b;' ?>">Credit / Debit Card<?= $stripeConfigured ? '' : ' (unavailable)' ?></span>
            <span style="display:inline-block;margin-left:0.5rem;background:#635bff;color:#fff;font-size:0.68rem;padding:0.15rem 0.45rem;border-radius:4px;font-weight:700;letter-spacing:0.02em;">STRIPE</span>
            <p style="margin:0.25rem 0 0;font-size:0.75rem;color:#64748b;"><?= $stripeConfigured ? 'Pay securely via Stripe using Visa, Mastercard, AMEX, Apple Pay &amp; Google Pay.' : 'Add Stripe test keys to includes/stripe_keys.local.php to enable card payments.' ?></p>
          </div>
        </label>
        <label style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border:1px solid var(--line,#e2e8f0);border-radius:8px;cursor:pointer;background:#fff;">
          <input name="payment_method" value="cash_on_delivery" type="radio" <?= $stripeConfigured ? '' : 'checked' ?> onchange="updatePayButton()">
          <div>
            <span style="font-weight:600;">Cash on delivery</span>
            <p style="margin:0.25rem 0 0;font-size:0.75rem;color:#64748b;">Pay with exact cash or card when your driver arrives.</p>
          </div>
        </label>
        <label style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border:1px solid var(--line,#e2e8f0);border-radius:8px;cursor:pointer;background:#fff;">
          <input name="payment_method" value="paypal" type="radio" disabled>
          <div>
            <span style="font-weight:600;color:#64748b;">PayPal (unavailable)</span>
            <p style="margin:0.25rem 0 0;font-size:0.75rem;color:#64748b;">PayPal checkout is not configured yet.</p>
          </div>
        </label>
      </div>
    </fieldset>
  </div>

  <aside class="card-artisan">
    <h2 class="section-title" style="font-size:1.75rem;">Order summary</h2>
    <div style="margin-top:1.25rem;">
      <?php foreach ($cart['items'] as $item): ?>
        <div style="display:flex;justify-content:space-between;gap:.75rem;font-size:.875rem;margin-top:.6rem;">
          <span><?= (int) $item['quantity'] ?> &times; <?= htmlspecialchars($item['name']) ?></span>
          <strong><?= mff_money($item['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:1.25rem;border-top:1px solid var(--line);padding-top:1rem;font-size:.875rem;">
      <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span><?= mff_money($cart['subtotal']) ?></span></div>
      <div style="display:flex;justify-content:space-between;margin-top:.5rem;"><span>Delivery fee</span><span><?= mff_money($cart['delivery_fee']) ?></span></div>
      <div style="display:flex;justify-content:space-between;margin-top:.5rem;"><span>GST (10%)</span><span><?= mff_money($cart['tax']) ?></span></div>
      <div style="display:flex;justify-content:space-between;margin-top:.75rem;padding-top:.5rem;border-top:1px solid var(--line);font-size:1rem;font-weight:700;">
        <span>Order total</span><span><?= mff_money($cart['total']) ?></span>
      </div>
    </div>
    <button id="submit-order-btn" type="submit" class="btn-tomato" style="width:100%;justify-content:center;margin-top:1.5rem;">
      Pay with Stripe
    </button>
  </aside>
</form>

<script>
function updatePayButton() {
  const method = document.querySelector('input[name="payment_method"]:checked')?.value;
  const btn = document.getElementById('submit-order-btn');
  if (!btn) return;
  if (method === 'credit_card') {
    btn.textContent = 'Pay with Stripe (Secure Checkout)';
  } else {
    btn.textContent = 'Place order';
  }
}
document.addEventListener('DOMContentLoaded', updatePayButton);
</script>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
