<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin', 'logistics_manager', 'support_staff']);

$pdo = mff_db();
$userRole = mff_role();

// Fetch available drivers for assignment
$drivers = [];
if ($pdo !== null) {
  $driverStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'delivery' ORDER BY name ASC");
  $drivers = $driverStmt->fetchAll();
}

$statuses = ['pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
$statusLabels = [
    'pending'          => 'Pending',
    'processing'       => 'Processing',
    'out_for_delivery' => 'Out for delivery',
    'delivered'        => 'Delivered',
    'cancelled'        => 'Cancelled',
];

// Handle order status/driver update (support staff may only change status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? 'pending';
    $driverIdInput = trim($_POST['driver'] ?? '');
    $driverId = $driverIdInput !== '' ? (int) $driverIdInput : null;
    $driverName = null;
    $validDriver = true;

    if ($userRole !== 'support_staff' && $driverId !== null) {
      $driverStmt = $pdo?->prepare("SELECT name FROM users WHERE id = :id AND role = 'delivery'");
      $driverStmt?->execute(['id' => $driverId]);
      $driverName = $driverStmt?->fetchColumn();
      $validDriver = $driverName !== false;
    }

    if ($pdo === null) {
      mff_set_flash('info', 'No database connected — change not persisted (demo mode).');
    } elseif ($orderId <= 0 || !in_array($status, $statuses, true)) {
      mff_set_flash('error', 'Choose a valid order and status.');
    } elseif (!$validDriver) {
      mff_set_flash('error', 'Choose a registered delivery driver.');
    } else {
    if ($userRole === 'support_staff') {
      $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
      $stmt->execute(['status' => $status, 'id' => $orderId]);
    } else {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, driver = :driver, driver_id = :driver_id WHERE id = :id');
        $stmt->execute(['status' => $status, 'driver' => $driverName, 'driver_id' => $driverId, 'id' => $orderId]);
    }
        mff_set_flash('success', 'Order #MFF-' . $orderId . ' updated.');
    }
    header('Location: ' . BASE_URL . '/admin/manage_orders.php');
    exit;
}

// TC-SUP-03: Handle feedback response by support staff or admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'respond_feedback') {
    $feedbackId = (int) ($_POST['feedback_id'] ?? 0);
    $response   = trim($_POST['response'] ?? '');
    if ($feedbackId > 0 && $response !== '') {
        mff_respond_feedback($feedbackId, $response);
        mff_set_flash('success', 'Response submitted for feedback #' . $feedbackId . '.');
    } else {
        mff_set_flash('error', 'Response cannot be empty.');
    }
    header('Location: ' . BASE_URL . '/admin/manage_orders.php');
    exit;
}

// Fetch orders with their items (TC-SUP-04: full order details)
$orders = [];
if ($pdo !== null) {
  $orderRows = $pdo->query('SELECT o.*, u.name AS driver_name FROM orders o LEFT JOIN users u ON u.id = o.driver_id ORDER BY o.created_at DESC')->fetchAll();
    foreach ($orderRows as &$order) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $itemStmt->execute(['id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        $fbStmt = $pdo->prepare('SELECT * FROM feedback WHERE order_id = :id');
        $fbStmt->execute(['id' => $order['id']]);
        $order['feedback'] = $fbStmt->fetch() ?: null;
    }
    unset($order);
    $orders = $orderRows;
} else {
    $orders = mff_orders_fallback();
}

$pageTitle = 'Manage Orders';
$activeNav = 'admin_orders';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
  <div>
    <p class="section-eyebrow">Order control &amp; customer support</p>
    <h1 class="section-title">Manage Orders</h1>
    <p style="font-size:0.875rem;color:#56715f;margin-top:0.25rem;">
      View full order details, update statuses, assign drivers and respond to feedback.
    </p>
  </div>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state" style="margin-top:2rem;">
    <i data-lucide="package" class="icon-md" style="color:#56715f;"></i>
    <h2 class="empty-state__title">No orders yet</h2>
    <p class="empty-state__copy">Orders placed by customers will appear here.</p>
  </div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:1.5rem;">
<?php foreach ($orders as $order):
  $orderId  = (int) $order['id'];
  $status   = $order['status'];
  $items    = $order['items'] ?? [];
  $feedback = $order['feedback'] ?? null;
?>
  <article class="card-artisan" style="border-radius:1rem;box-shadow:0 4px 15px rgba(0,0,0,0.04);">
    <!-- Order Header -->
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding-bottom:1rem;">
      <div>
        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
          <span style="font-size:.825rem;font-weight:800;background:var(--ink);color:#fff;padding:0.25rem 0.65rem;border-radius:6px;">
            #MFF-<?= $orderId ?>
          </span>
          <span style="font-size:.825rem;color:#56715f;font-weight:600;">
            📅 <?= htmlspecialchars(date('j M Y · g:i A', strtotime($order['created_at']))) ?>
          </span>
        </div>
        <h2 style="margin-top:.4rem;font-size:1.1rem;font-weight:700;color:var(--ink);">
          <?= htmlspecialchars($order['customer_name']) ?>
        </h2>
        <p style="font-size:.825rem;color:#56715f;margin-top:.2rem;">
          📞 <?= htmlspecialchars($order['contact_number']) ?>
          &nbsp;|&nbsp;
          💳 <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))) ?>
        </p>
      </div>
      <div style="text-align:right;">
        <span class="status status-<?= htmlspecialchars($status) ?>" style="font-weight:700;font-size:.825rem;">
          <?= htmlspecialchars($statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status))) ?>
        </span>
        <p style="margin-top:0.35rem;font-size:1.1rem;font-weight:800;color:var(--ink);"><?= mff_money($order['total']) ?></p>
        <?php if (!empty($order['driver_id']) && !empty($order['driver_name'])): ?>
          <span style="font-size:.75rem;background:#fef3c7;color:#92400e;padding:.2rem .55rem;border-radius:999px;font-weight:700;">
            🚚 <?= htmlspecialchars($order['driver_name']) ?>
          </span>
        <?php else: ?>
          <span style="font-size:.75rem;background:#f1f5f9;color:#64748b;padding:.2rem .55rem;border-radius:999px;font-weight:700;">
            Unassigned
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- TC-SUP-04: Full Delivery Details -->
    <div style="margin-top:1rem;background:#fafaf9;border:1px solid var(--line);border-radius:.75rem;padding:.85rem 1rem;font-size:.85rem;color:#475569;">
      <strong style="color:var(--ink);">📍 Delivery Address:</strong>
      <p style="margin:.2rem 0 0;"><?= htmlspecialchars($order['delivery_address']) ?></p>
      <?php if (!empty($order['delivery_instructions'])): ?>
        <p style="margin:.2rem 0 0;color:#64748b;"><em>Note: <?= htmlspecialchars($order['delivery_instructions']) ?></em></p>
      <?php endif; ?>
    </div>

    <!-- TC-SUP-04: Order Items Table -->
    <?php if (!empty($items)): ?>
      <details style="margin-top:1rem;background:#fafaf9;border:1px solid var(--line);border-radius:.75rem;padding:.85rem 1.15rem;">
        <summary style="font-weight:700;cursor:pointer;font-size:.9rem;color:var(--ink);">
          📦 Order Contents (<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>) — Click to expand
        </summary>
        <table style="width:100%;font-size:.875rem;border-collapse:collapse;margin-top:.75rem;">
          <thead>
            <tr style="color:#56715f;border-bottom:1px solid var(--line);text-align:left;">
              <th style="padding:.35rem 0;">Item</th>
              <th style="padding:.35rem 0;text-align:center;">Qty</th>
              <th style="padding:.35rem 0;text-align:right;">Unit Price</th>
              <th style="padding:.35rem 0;text-align:right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:.4rem 0;font-weight:600;"><?= htmlspecialchars($item['name']) ?></td>
                <td style="padding:.4rem 0;text-align:center;"><?= (int) $item['quantity'] ?></td>
                <td style="padding:.4rem 0;text-align:right;color:#64748b;"><?= mff_money($item['price']) ?></td>
                <td style="padding:.4rem 0;text-align:right;font-weight:700;"><?= mff_money($item['price'] * $item['quantity']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="padding:.4rem 0;text-align:right;font-size:.8rem;color:#56715f;">Delivery fee:</td>
              <td style="padding:.4rem 0;text-align:right;font-size:.8rem;color:#56715f;"><?= mff_money($order['delivery_fee'] ?? 0) ?></td>
            </tr>
            <tr>
              <td colspan="3" style="padding:.5rem 0;text-align:right;font-size:.8rem;color:#56715f;">GST (10%):</td>
              <td style="padding:.5rem 0;text-align:right;font-size:.8rem;color:#56715f;"><?= mff_money($order['tax'] ?? 0) ?></td>
            </tr>
            <tr>
              <td colspan="3" style="padding:.35rem 0;text-align:right;font-weight:800;font-size:.95rem;">Order Total:</td>
              <td style="padding:.35rem 0;text-align:right;font-weight:800;font-size:.95rem;color:var(--ink);"><?= mff_money($order['total']) ?></td>
            </tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>

    <!-- Status & Driver Update (Admin / Logistics / Support) -->
    <form method="post" action="<?= BASE_URL ?>/admin/manage_orders.php" style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-end;border-top:1px solid var(--line);padding-top:1rem;">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="order_id" value="<?= $orderId ?>">

      <div>
        <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:.3rem;">Status</label>
        <select name="status" style="padding:.4rem .7rem;border-radius:7px;border:1px solid var(--line);font-size:.875rem;background:#fff;">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $statusLabels[$s] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (in_array($userRole, ['admin', 'logistics_manager'], true)): ?>
        <div>
          <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:.3rem;">
            Driver (LFM-19: select Unassigned to remove)
          </label>
          <select name="driver" style="padding:.4rem .7rem;border-radius:7px;border:1px solid var(--line);font-size:.875rem;background:#fff;">
            <option value="">— Unassigned —</option>
            <?php foreach ($drivers as $driver): ?>
              <option value="<?= (int) $driver['id'] ?>" <?= (int) ($order['driver_id'] ?? 0) === (int) $driver['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($driver['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <!-- Support staff: driver assignment is read-only (TC-SUP: support shouldn't reassign drivers) -->
        <input type="hidden" name="driver" value="<?= (int) ($order['driver_id'] ?? 0) ?>">
        <?php if (!empty($order['driver_id']) && !empty($order['driver_name'])): ?>
          <div>
            <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:.3rem;">Assigned Driver</label>
            <span style="font-size:.875rem;font-weight:600;padding:.4rem .7rem;background:#fef3c7;border-radius:7px;">
              <?= htmlspecialchars($order['driver_name']) ?>
            </span>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <button type="submit" class="btn-outline" style="padding:.45rem 1.1rem;">Save Changes</button>
    </form>

    <!-- TC-SUP-02/03: Customer Feedback Viewer & Response -->
    <?php if ($feedback): ?>
      <div style="margin-top:1.25rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.75rem;padding:1rem 1.25rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
          <div style="display:flex;align-items:center;gap:.5rem;">
            <span style="font-size:1.1rem;letter-spacing:2px;color:#eab308;">
              <?= str_repeat('★', (int)$feedback['rating']) . str_repeat('☆', 5 - (int)$feedback['rating']) ?>
            </span>
            <span style="font-size:.8rem;font-weight:700;text-transform:uppercase;padding:.2rem .5rem;border-radius:999px;background:#dcfce7;color:#166534;">
              <?= htmlspecialchars(ucfirst($feedback['sentiment'] ?? 'neutral')) ?>
            </span>
            <span style="font-size:.8rem;color:#166534;">
              by <?= htmlspecialchars($feedback['customer_name'] ?? 'Customer') ?>
            </span>
          </div>
          <span style="font-size:.75rem;color:#166534;">Reviewed on <?= htmlspecialchars(date('j M Y', strtotime($feedback['created_at']))) ?></span>
        </div>
        <?php if (!empty($feedback['comment'])): ?>
          <p style="margin:.5rem 0 0;font-size:.875rem;color:#14532d;font-style:italic;">
            &ldquo;<?= htmlspecialchars($feedback['comment']) ?>&rdquo;
          </p>
        <?php endif; ?>

        <?php if (!empty($feedback['admin_response'])): ?>
          <div style="margin-top:.75rem;background:#fff;border-left:3px solid var(--leaf);padding:.6rem .85rem;border-radius:4px;font-size:.825rem;">
            <strong style="color:var(--leaf);">💬 Your Response:</strong>
            <p style="margin:.25rem 0 0;color:#334155;"><?= htmlspecialchars($feedback['admin_response']) ?></p>
          </div>
        <?php else: ?>
          <!-- TC-SUP-03: Allow support staff / admin to respond to feedback -->
          <form method="post" action="<?= BASE_URL ?>/admin/manage_orders.php" style="margin-top:.75rem;display:flex;gap:.6rem;align-items:flex-start;">
            <input type="hidden" name="action" value="respond_feedback">
            <input type="hidden" name="feedback_id" value="<?= (int)$feedback['id'] ?>">
            <textarea name="response" rows="2" required placeholder="Write a response to this customer review..."
              style="flex:1;padding:.4rem .7rem;border-radius:7px;border:1px solid var(--line);font-size:.85rem;resize:vertical;"></textarea>
            <button type="submit" class="btn-tomato" style="padding:.45rem .9rem;font-size:.85rem;white-space:nowrap;">
              💬 Respond
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php elseif ($status === 'delivered'): ?>
      <div style="margin-top:1rem;background:#fafaf9;border:1px dashed var(--line);border-radius:.75rem;padding:.75rem 1rem;font-size:.825rem;color:#64748b;">
        ⭐ No customer feedback submitted yet for this delivered order.
      </div>
    <?php endif; ?>

  </article>
<?php endforeach; ?>
</div>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
