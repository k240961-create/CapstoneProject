<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

// C37: Guests must log in before accessing orders
mff_require_role(['customer', 'admin']);

$pdo = mff_db();

if (($_GET['tracking_status'] ?? '') === '1') {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['orders' => []]);
    exit;
  }

  if (mff_role() === 'admin') {
    $trackingStmt = $pdo->query('SELECT id, status FROM orders');
  } else {
    $trackingStmt = $pdo->prepare('SELECT id, status FROM orders WHERE user_id = :uid');
    $trackingStmt->execute(['uid' => $_SESSION['user_id'] ?? 0]);
  }
  echo json_encode(['orders' => $trackingStmt->fetchAll()]);
  exit;
}

// 1. Handle Order Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if ($pdo !== null) {
        // C38: verify this order belongs to the current user before cancelling (strict user_id check)
        $stmt = $pdo->prepare('UPDATE orders SET status = "cancelled" WHERE id = :id AND user_id = :uid AND status = "pending"');
        $stmt->execute(['id' => $orderId, 'uid' => $_SESSION['user_id'] ?? 0]);
        if ($stmt->rowCount() === 0) {
            mff_set_flash('error', 'Order could not be cancelled — it may not be yours or is no longer pending.');
            header('Location: ' . BASE_URL . '/my_orders.php');
            exit;
        }
    }
    mff_set_flash('success', 'Order #MFF-' . $orderId . ' has been cancelled.');
    header('Location: ' . BASE_URL . '/my_orders.php');
    exit;
}

// 2. Handle Feedback & Review Submission (SRS FR17 / TC12)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_feedback') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
  $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
  $comment = is_string($_POST['comment'] ?? null) ? trim($_POST['comment']) : '';
    $userId = $_SESSION['user_id'] ?? null;
    $customerName = $_SESSION['user_name'] ?? 'Customer';

  if (!is_int($rating) || $rating < 1 || $rating > 5 || $comment === '' || strlen($comment) > 1000) {
    mff_set_flash('error', 'Choose a rating from 1 to 5 and enter a comment of up to 1000 characters.');
  } elseif ($pdo === null) {
    mff_set_flash('error', 'Feedback could not be saved because the database is unavailable.');
  } else {
        // C38 IDOR fix: verify ownership strictly by user_id
        $check = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id AND user_id = :uid');
        $check->execute(['id' => $orderId, 'uid' => $userId]);
        $order = $check->fetch();

        if ($order && $order['status'] === 'delivered') {
      try {
        $saved = mff_submit_feedback($orderId, $userId, $customerName, $rating, $comment);
      } catch (Throwable $e) {
        error_log('[mff] feedback submission failed: ' . $e->getMessage());
        $saved = false;
      }
      if ($saved) {
        mff_set_flash('success', 'Thank you! Your feedback and ' . $rating . '-star rating have been submitted.');
      } else {
        mff_set_flash('error', 'Your feedback could not be saved. Please try again.');
      }
        } else {
            mff_set_flash('error', 'Feedback can only be submitted for your own delivered orders.');
        }
    }
    header('Location: ' . BASE_URL . '/my_orders.php');
    exit;
}

$pageTitle = 'My Orders & Tracking';
$activeNav = 'orders';

if ($pdo !== null) {
    $userId = $_SESSION['user_id'] ?? null;

    if (mff_role() === 'admin') {
      $stmt = $pdo->query('SELECT o.*, u.name AS driver_name FROM orders o LEFT JOIN users u ON u.id = o.driver_id ORDER BY o.created_at DESC');
    } else {
        // C38 fix: only retrieve orders owned by this user_id (strict ownership — no customer_name fallback)
      $stmt = $pdo->prepare('SELECT o.*, u.name AS driver_name FROM orders o LEFT JOIN users u ON u.id = o.driver_id WHERE o.user_id = :uid ORDER BY o.created_at DESC');
        $stmt->execute(['uid' => $userId]);
    }
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $itemStmt->execute(['id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        $fbStmt = $pdo->prepare('SELECT * FROM feedback WHERE order_id = :id');
        $fbStmt->execute(['id' => $order['id']]);
        $order['feedback'] = $fbStmt->fetch() ?: null;
    }
    unset($order);
} else {
    $orders = mff_orders_fallback();
}

$statusLabels = [
    'pending' => 'Order Placed & Confirmed',
    'processing' => 'Packing & Processing',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered to Doorstep',
    'cancelled' => 'Cancelled',
];
$statusProgress = ['pending' => 15, 'processing' => 50, 'out_for_delivery' => 80, 'delivered' => 100, 'cancelled' => 0];

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
  <div>
    <p class="section-eyebrow">Real-Time Order Tracking &amp; History</p>
    <h1 class="section-title">My Orders</h1>
  </div>
  <a href="<?= BASE_URL ?>/index.php" class="btn-outline" style="font-size:0.875rem;">+ Shop More Groceries</a>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state" style="margin-top:2rem;">
    <i data-lucide="package" class="icon-md" style="color:#56715f;"></i>
    <h2 class="empty-state__title">No orders found</h2>
    <p class="empty-state__copy">Once you place an order it will appear here with live dispatch tracking and receipts.</p>
    <a href="<?= BASE_URL ?>/index.php" class="btn-tomato" style="margin-top:1.25rem;">Start Shopping</a>
  </div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:1.75rem;">
  <?php foreach ($orders as $order): ?>
    <?php
      $items = $order['items'] ?? [];
      $status = $order['status'];
      $orderId = (int) $order['id'];
      $feedback = $order['feedback'] ?? null;
    ?>
    <article class="card-artisan" id="order-card-<?= $orderId ?>" data-order-id="<?= $orderId ?>" data-order-status="<?= htmlspecialchars($status) ?>" style="border-radius:1rem;box-shadow:0 4px 15px rgba(0,0,0,0.04);">
      <!-- Header Bar -->
      <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding-bottom:1rem;">
        <div>
          <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
            <span style="font-size:.825rem;font-weight:800;background:var(--ink);color:#fff;padding:0.25rem 0.65rem;border-radius:6px;letter-spacing:0.02em;">
              #MFF-<?= $orderId ?>
            </span>
            <span style="font-size:.825rem;color:#56715f;font-weight:600;">
              📅 <?= htmlspecialchars(date('l, j M Y &middot; g:i A', strtotime($order['created_at']))) ?>
            </span>
          </div>
          <h2 style="margin-top:.45rem;font-size:1.15rem;font-weight:700;color:var(--ink);">
            <?= htmlspecialchars($order['customer_name']) ?>
          </h2>
        </div>
        <div style="text-align:right;">
          <span class="status status-<?= htmlspecialchars($status) ?>" data-live-status style="font-weight:700;font-size:0.825rem;">
            <?= htmlspecialchars($statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status))) ?>
          </span>
          <p style="margin-top:0.35rem;font-size:1.15rem;font-weight:800;color:var(--ink);">
            <?= mff_money($order['total']) ?>
          </p>
        </div>
      </div>

      <!-- Live Tracking Progress Timeline (SRS FR32 / C32, C33) -->
      <?php if ($status !== 'cancelled'): ?>
        <div data-live-timeline style="margin-top:1.5rem;background:#fdfcf9;border:1px solid var(--line);border-radius:0.75rem;padding:1.25rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;">
            <span style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--leaf);letter-spacing:0.04em;">
              🚚 Live Delivery Status
            </span>
            <?php if (!empty($order['driver_id']) && !empty($order['driver_name'])): ?>
              <span style="font-size:0.825rem;font-weight:600;color:#92400e;background:#fef3c7;padding:0.2rem 0.6rem;border-radius:999px;">
                Assigned Driver: <strong><?= htmlspecialchars($order['driver_name']) ?></strong>
              </span>
            <?php endif; ?>
          </div>
          
          <div class="timeline-track" style="margin:0.5rem 0 0.75rem;height:8px;border-radius:4px;background:#e2e8f0;">
            <div class="timeline-fill" data-live-progress style="width:<?= (int) $statusProgress[$status] ?>%;background:linear-gradient(90deg, #10b981, #059669);"></div>
          </div>
          
          <div style="display:grid;grid-template-columns:repeat(4, 1fr);font-size:0.75rem;color:#64748b;font-weight:600;text-align:center;">
            <span data-stage-step="1" style="<?= in_array($status, ['pending','processing','out_for_delivery','delivered'], true) ? 'color:#065f46;font-weight:700;' : '' ?>">1. Confirmed</span>
            <span data-stage-step="2" style="<?= in_array($status, ['processing','out_for_delivery','delivered'], true) ? 'color:#065f46;font-weight:700;' : '' ?>">2. Packing</span>
            <span data-stage-step="3" style="<?= in_array($status, ['out_for_delivery','delivered'], true) ? 'color:#065f46;font-weight:700;' : '' ?>">3. Out for Delivery</span>
            <span data-stage-step="4" style="<?= $status === 'delivered' ? 'color:#065f46;font-weight:700;' : '' ?>">4. Delivered</span>
          </div>
        </div>
      <?php else: ?>
        <div style="margin-top:1rem;background:#fee2e2;border:1px solid #fecdd3;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;">
          ❌ This order was cancelled.
        </div>
      <?php endif; ?>

      <!-- Detailed Items & Receipt Section (C31 / TC-SUP-04) -->
      <div style="margin-top:1.25rem;">
        <details style="background:#fafaf9;border:1px solid var(--line);border-radius:0.75rem;padding:0.85rem 1.15rem;" open>
          <summary style="font-weight:700;cursor:pointer;font-size:0.9rem;color:var(--ink);user-select:none;">
            📦 Order Items &amp; Delivery Details (Click to Toggle)
          </summary>
          <div style="margin-top:1rem;border-top:1px dashed var(--line);padding-top:0.75rem;">
            <!-- Line Items Table -->
            <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
              <thead>
                <tr style="color:#56715f;border-bottom:1px solid var(--line);text-align:left;">
                  <th style="padding:0.4rem 0;">Item</th>
                  <th style="padding:0.4rem 0;text-align:center;">Qty</th>
                  <th style="padding:0.4rem 0;text-align:right;">Price</th>
                  <th style="padding:0.4rem 0;text-align:right;">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:0.5rem 0;font-weight:600;"><?= htmlspecialchars($item['name']) ?></td>
                    <td style="padding:0.5rem 0;text-align:center;"><?= (int) $item['quantity'] ?></td>
                    <td style="padding:0.5rem 0;text-align:right;color:#64748b;"><?= mff_money($item['price']) ?></td>
                    <td style="padding:0.5rem 0;text-align:right;font-weight:700;"><?= mff_money($item['price'] * $item['quantity']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <!-- Financial Summary -->
            <div style="margin-top:0.75rem;display:flex;justify-content:flex-end;">
              <div style="width:240px;font-size:0.85rem;line-height:1.6;">
                <div style="display:flex;justify-content:space-between;color:#56715f;">
                  <span>Subtotal:</span>
                  <span><?= mff_money($order['subtotal']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;color:#56715f;">
                  <span>Delivery fee:</span>
                  <span><?= mff_money($order['delivery_fee'] ?? 0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;color:#56715f;">
                  <span>GST (10%):</span>
                  <span><?= mff_money($order['tax']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1rem;color:var(--ink);border-top:1px solid var(--line);padding-top:0.35rem;margin-top:0.35rem;">
                  <span>Order Total:</span>
                  <span><?= mff_money($order['total']) ?></span>
                </div>
              </div>
            </div>

            <!-- Delivery & Payment Metadata -->
            <div style="margin-top:1rem;background:#fff;border:1px solid var(--line);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.825rem;color:#475569;display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:0.75rem;">
              <div>
                <strong style="color:var(--ink);">📍 Delivery Address:</strong>
                <p style="margin:0.2rem 0 0;"><?= htmlspecialchars($order['delivery_address']) ?></p>
                <?php if (!empty($order['delivery_instructions'])): ?>
                  <p style="margin:0.2rem 0 0;color:#64748b;"><em>Note: <?= htmlspecialchars($order['delivery_instructions']) ?></em></p>
                <?php endif; ?>
              </div>
              <div>
                <strong style="color:var(--ink);">📞 Phone Contact:</strong>
                <p style="margin:0.2rem 0 0;"><?= htmlspecialchars($order['contact_number']) ?></p>
                <strong style="color:var(--ink);display:block;margin-top:0.35rem;">💳 Payment Method:</strong>
                <span style="text-transform:capitalize;"><?= str_replace('_', ' ', htmlspecialchars($order['payment_method'])) ?></span>
              </div>
            </div>
          </div>
        </details>
      </div>

      <!-- Customer Feedback & Review Module (SRS FR17 / C34-C36, TC-SUP-02) -->
      <?php if ($status === 'delivered'): ?>
        <div style="margin-top:1.25rem;border-top:1px solid var(--line);padding-top:1.25rem;">
          <?php if ($feedback): ?>
            <!-- Existing Feedback Displayed -->
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.75rem;padding:1rem 1.25rem;">
              <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                  <span style="font-size:1.15rem;letter-spacing:2px;color:#eab308;">
                    <?= str_repeat('★', (int)$feedback['rating']) . str_repeat('☆', 5 - (int)$feedback['rating']) ?>
                  </span>
                  <span style="font-size:0.8rem;font-weight:700;text-transform:uppercase;padding:0.2rem 0.5rem;border-radius:999px;background:#dcfce7;color:#166534;">
                    <?= htmlspecialchars(ucfirst($feedback['sentiment'])) ?>
                  </span>
                </div>
                <span style="font-size:0.75rem;color:#166534;">Reviewed on <?= htmlspecialchars(date('j M Y', strtotime($feedback['created_at']))) ?></span>
              </div>
              <?php if (!empty($feedback['comment'])): ?>
                <p style="margin:0.5rem 0 0;font-size:0.875rem;color:#14532d;font-style:italic;">
                  &ldquo;<?= htmlspecialchars($feedback['comment']) ?>&rdquo;
                </p>
              <?php endif; ?>
              <?php if (!empty($feedback['admin_response'])): ?>
                <div style="margin-top:0.75rem;background:#fff;border-left:3px solid var(--leaf);padding:0.6rem 0.85rem;border-radius:4px;font-size:0.825rem;">
                  <strong style="color:var(--leaf);">💬 Response from Maxi Customer Care:</strong>
                  <p style="margin:0.25rem 0 0;color:#334155;"><?= htmlspecialchars($feedback['admin_response']) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- Review Submission Form -->
            <div style="background:#fbfbfb;border:1px solid var(--line);border-radius:0.75rem;padding:1rem 1.25rem;">
              <h3 style="font-size:0.95rem;font-weight:700;color:var(--ink);margin:0 0 0.5rem;">
                ⭐ Rate this Delivery &amp; Grocery Experience
              </h3>
              <form method="post" action="<?= BASE_URL ?>/my_orders.php" style="display:grid;grid-template-columns:auto 1fr auto;gap:0.75rem;align-items:center;">
                <input type="hidden" name="action" value="submit_feedback">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                
                <div>
                  <label for="rating-<?= $orderId ?>" class="sr-only">Rating</label>
                  <select id="rating-<?= $orderId ?>" name="rating" style="padding:0.45rem 0.65rem;border-radius:6px;border:1px solid var(--line);font-size:0.875rem;background:#fff;font-weight:700;color:#854d0e;" required>
                    <option value="5">★★★★★ (5 - Excellent)</option>
                    <option value="4">★★★★☆ (4 - Good)</option>
                    <option value="3">★★★☆☆ (3 - Average)</option>
                    <option value="2">★★☆☆☆ (2 - Poor)</option>
                    <option value="1">★☆☆☆☆ (1 - Very Bad)</option>
                  </select>
                </div>

                <div>
                  <label for="comment-<?= $orderId ?>" class="sr-only">Feedback Comment</label>
                  <input id="comment-<?= $orderId ?>" name="comment" type="text" placeholder="Write a short review about packaging, produce freshness, or delivery speed..." style="width:100%;padding:0.45rem 0.75rem;border-radius:6px;border:1px solid var(--line);font-size:0.85rem;" required>
                </div>

                <button type="submit" class="btn-tomato" style="padding:0.45rem 1rem;font-size:0.85rem;white-space:nowrap;">
                  Submit Review
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Actions Footer -->
      <?php if ($status === 'pending'): ?>
        <div style="margin-top:1.25rem;display:flex;justify-content:flex-end;">
          <form method="post" action="<?= BASE_URL ?>/my_orders.php" onsubmit="return confirm('Are you sure you want to cancel Order #MFF-<?= $orderId ?>?');">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <button type="submit" class="btn-outline" style="color:var(--tomato);border-color:#fca5a5;">
              Cancel Order
            </button>
          </form>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>

<script>
(() => {
  const progress = { pending: 15, processing: 50, out_for_delivery: 80, delivered: 100 };
  const labels = {
    pending: 'Order Placed & Confirmed',
    processing: 'Packing & Processing',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered to Doorstep'
  };
  const stageCount = { pending: 1, processing: 2, out_for_delivery: 3, delivered: 4 };

  async function refreshOrderStatuses() {
    if (document.hidden) return;
    try {
      const response = await fetch(`${window.MFF_BASE_URL}/my_orders.php?tracking_status=1`, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });
      if (!response.ok) return;

      const data = await response.json();
      for (const order of data.orders) {
        const card = document.querySelector(`[data-order-id="${Number(order.id)}"]`);
        if (!card) continue;
        if (card.dataset.orderStatus === 'cancelled' || order.status === 'cancelled') {
          if (card.dataset.orderStatus !== order.status) window.location.reload();
          continue;
        }
        if (card.dataset.orderStatus === order.status) continue;

        card.dataset.orderStatus = order.status;
        const status = card.querySelector('[data-live-status]');
        status.textContent = labels[order.status] || order.status;
        status.className = `status status-${order.status}`;
        card.querySelector('[data-live-progress]').style.width = `${progress[order.status] || 0}%`;
        card.querySelectorAll('[data-stage-step]').forEach((step) => {
          const active = Number(step.dataset.stageStep) <= (stageCount[order.status] || 0);
          step.style.color = active ? '#065f46' : '#64748b';
          step.style.fontWeight = active ? '700' : '600';
        });
      }
    } catch (error) {
      // Keep the last displayed status when polling is temporarily unavailable.
    }
  }

  window.setInterval(refreshOrderStatuses, 15000);
})();
</script>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
