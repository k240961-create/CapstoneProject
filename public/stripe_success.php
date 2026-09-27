<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
require_once file_exists(__DIR__ . '/../includes/stripe_config.php') ? __DIR__ . '/../includes/stripe_config.php' : __DIR__ . '/includes/stripe_config.php';

$sessionId = $_GET['session_id'] ?? '';
$errors = [];
$pending = $_SESSION['pending_checkout'] ?? null;

if ($sessionId === '' || !is_array($pending) || empty($pending['stripe_session_id'])
    || !hash_equals((string) $pending['stripe_session_id'], (string) $sessionId)) {
    mff_set_flash('error', 'Invalid or missing session information.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$refundPaidSession = static function ($paymentIntent): bool {
    if (empty($paymentIntent)) {
        return false;
    }
    try {
        \Stripe\Refund::create(['payment_intent' => $paymentIntent]);
        return true;
    } catch (Throwable $e) {
        error_log('[mff] stripe refund request failed: ' . $e->getMessage());
        return false;
    }
};

try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    $expectedAmount = (int) round(((float) ($pending['total'] ?? 0)) * 100);
    $validPayment = $session->payment_status === 'paid'
        && $session->mode === 'payment'
        && strtolower((string) $session->currency) === 'aud'
        && (int) $session->amount_total === $expectedAmount
        && (string) $session->client_reference_id === (string) ($pending['user_id'] ?? '');

    if ($validPayment) {
        $pdo = mff_db();
        $orderId = null;
        $cart = [
            'items' => $pending['items'],
            'subtotal' => $pending['subtotal'],
            'delivery_fee' => $pending['delivery_fee'],
            'tax' => $pending['tax'],
            'total' => $pending['total'],
        ];

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
                    'name' => $pending['customer_name'], 
                    'contact' => $pending['contact_number'], 
                    'address' => $pending['delivery_address'],
                    'instructions' => $pending['delivery_instructions'], 
                    'payment' => $pending['payment_method'],
                    'subtotal' => $cart['subtotal'], 
                    'delivery_fee' => $cart['delivery_fee'],
                    'tax' => $cart['tax'], 
                    'total' => $cart['total'],
                ]);
                $orderId = (int) $pdo->lastInsertId();

                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (:order_id, :product_id, :name, :price, :quantity)'
                );
                foreach ($cart['items'] as $item) {
                    $itemStmt->execute([
                        'order_id' => $orderId, 
                        'product_id' => $item['product_id'],
                        'name' => $item['name'], 
                        'price' => $item['price'], 
                        'quantity' => $item['quantity'],
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
                error_log('[mff] order insert failed on stripe success: ' . $e->getMessage());
                $errors[] = 'Payment succeeded, but stock changed before the order could be confirmed. Please contact support.';
            }
        } else {
            $errors[] = 'Payment succeeded, but the order database is unavailable.';
        }

        if (empty($errors)) {
            $_SESSION['last_order'] = [
                'id' => $orderId ?? random_int(3000, 3999),
                'customer_name' => $pending['customer_name'],
                'contact_number' => $pending['contact_number'],
                'delivery_address' => $pending['delivery_address'],
                'delivery_instructions' => $pending['delivery_instructions'],
                'payment_method' => $pending['payment_method'],
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'delivery_fee' => $cart['delivery_fee'],
                'tax' => $cart['tax'],
                'total' => $cart['total'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            cart_clear();
            unset($_SESSION['pending_checkout']);
            mff_set_role('customer');
            header('Location: ' . BASE_URL . '/order_confirmation.php');
            exit;
        }
        $refundRequested = $refundPaidSession($session->payment_intent ?? null);
        unset($_SESSION['pending_checkout']);
        $errors = [$refundRequested
            ? 'Payment was received but the order could not be confirmed. A refund was requested; contact support if it does not appear.'
            : 'Payment was received but the order could not be confirmed. Please contact support to arrange a refund.'];
    } elseif ($session->payment_status === 'paid') {
        $refundRequested = $refundPaidSession($session->payment_intent ?? null);
        unset($_SESSION['pending_checkout']);
        $errors[] = $refundRequested
            ? 'Payment details did not match this checkout. A refund was requested; contact support if it does not appear.'
            : 'Payment details did not match this checkout. Please contact support to arrange a refund.';
    } else {
         $errors[] = 'Payment not completed.';
    }
} catch (Exception $e) {
    error_log('[mff] stripe error on success: ' . $e->getMessage());
    $errors[] = 'Error validating payment.';
}

$pageTitle = 'Payment Error';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div>
  <h1 class="section-title">Payment Error</h1>
  <div class="flash flash--error">
    <?php foreach ($errors as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
  </div>
  <p><a href="<?= BASE_URL ?>/checkout.php">Return to Checkout</a></p>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
