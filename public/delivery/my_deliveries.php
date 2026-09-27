<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['delivery', 'admin']);

$userRole = mff_role();
$currentDriverId = (int) ($_SESSION['user_id'] ?? 0);
$currentDriver = $_SESSION['user_name'] ?? 'Driver';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $pdo = mff_db();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'processing';

    if ($pdo !== null && in_array($status, ['processing', 'out_for_delivery', 'delivered'], true)) {
        if ($userRole === 'admin') {
            $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $orderId]);
            mff_set_flash('success', 'Order status updated to ' . str_replace('_', ' ', $status) . '.');
        } else {
          // Strict driver ownership check (DR-015, DR-018 IDOR protection)
            $check = $pdo->prepare('SELECT id, status, driver_id FROM orders WHERE id = :id');
            $check->execute(['id' => $orderId]);
            $order = $check->fetch();

            if (!$order) {
                mff_set_flash('error', 'Order not found.');
            } elseif ($order['driver_id'] !== null && (int) $order['driver_id'] !== $currentDriverId) {
                // Cross-driver tampering blocked
                mff_set_flash('error', 'Access restricted: You cannot update deliveries assigned to another driver.');
            } elseif ($order['status'] === 'delivered') {
                // One-way workflow guard (DR-026)
                mff_set_flash('error', 'Completed deliveries cannot be reverted.');
          } elseif (!in_array($status, [
            'processing' => ['processing', 'out_for_delivery'],
            'out_for_delivery' => ['out_for_delivery', 'delivered'],
            'delivered' => [],
          ][$order['status']] ?? [], true)) {
            mff_set_flash('error', 'Delivery status must progress from Processing to Out for Delivery to Delivered.');
          } elseif ((int) ($order['driver_id'] ?? 0) === $currentDriverId && $order['status'] === $status) {
            mff_set_flash('info', 'This delivery is already ' . str_replace('_', ' ', $status) . '.');
            } else {
            $ownership = empty($order['driver_id']) ? 'driver_id IS NULL' : 'driver_id = :current_driver';
            $stmt = $pdo->prepare("UPDATE orders SET status = :status, driver = :driver_name, driver_id = :driver_id WHERE id = :id AND status = :current_status AND $ownership");
            $params = [
              'status' => $status,
              'driver_name' => $currentDriver,
              'driver_id' => $currentDriverId,
              'id' => $orderId,
              'current_status' => $order['status'],
            ];
            if (!empty($order['driver_id'])) {
              $params['current_driver'] = $currentDriverId;
            }
            $stmt->execute($params);
            if ($stmt->rowCount() === 1) {
              mff_set_flash('success', 'Delivery marked as ' . str_replace('_', ' ', $status) . '.');
            } else {
              mff_set_flash('error', 'This delivery changed before your update. Refresh the queue and try again.');
            }
            }
        }
    } else {
        mff_set_flash('error', 'Invalid status update or database offline.');
    }
    header('Location: ' . BASE_URL . '/delivery/my_deliveries.php');
    exit;
}

$pdo = mff_db();
if ($pdo !== null) {
    if ($userRole === 'admin') {
      $stmt = $pdo->query("SELECT o.*, u.name AS driver_name FROM orders o LEFT JOIN users u ON u.id = o.driver_id WHERE o.status IN ('processing','out_for_delivery') ORDER BY o.created_at ASC");
        $orders = $stmt->fetchAll();
    } else {
      $stmt = $pdo->prepare("SELECT o.*, u.name AS driver_name FROM orders o LEFT JOIN users u ON u.id = o.driver_id WHERE (o.driver_id = :driver OR o.driver_id IS NULL) AND o.status IN ('processing','out_for_delivery') ORDER BY o.created_at ASC");
      $stmt->execute(['driver' => $currentDriverId]);
        $orders = $stmt->fetchAll();
    }

    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $itemStmt->execute(['id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    unset($order);
} else {
    $orders = array_filter(mff_orders_fallback(), fn($o) => in_array($o['status'], ['processing', 'out_for_delivery'], true));
}

$statusLabels = ['processing' => 'Processing', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];

$pageTitle = 'Driver Delivery Console';
$activeNav = 'delivery';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
  <div>
    <p class="section-eyebrow">Fleet Management &middot; Real-Time Dispatch</p>
    <h1 class="section-title">Delivery Driver Queue</h1>
    <p style="font-size:0.875rem;color:#56715f;margin-top:0.25rem;">
      Logged in as: <strong><?= htmlspecialchars($currentDriver) ?></strong>
    </p>
  </div>
</div>

<div class="product-grid" style="grid-template-columns:1fr;max-width:44rem;">
  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <i data-lucide="package-check" class="icon-md" style="color:#56715f;"></i>
      <h2 class="empty-state__title">No active deliveries</h2>
      <p class="empty-state__copy">You're completely caught up! New dispatch assignments will appear here automatically.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($orders as $order): ?>
    <?php 
      $orderId = (int) $order['id'];
      $isAssignedToMe = (int) ($order['driver_id'] ?? 0) === $currentDriverId;
      $isUnassigned = empty($order['driver_id']);
    ?>
    <article class="card-artisan" style="border-radius:1rem;box-shadow:0 2px 10px rgba(0,0,0,0.04);">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding-bottom:0.75rem;">
        <div>
          <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:.825rem;font-weight:800;background:var(--ink);color:#fff;padding:0.2rem 0.6rem;border-radius:6px;">
              #MFF-<?= $orderId ?>
            </span>
            <?php if ($isUnassigned): ?>
              <span style="font-size:0.75rem;font-weight:700;background:#fef3c7;color:#92400e;padding:0.15rem 0.5rem;border-radius:999px;">
                Unassigned (Available to Claim)
              </span>
            <?php else: ?>
              <span style="font-size:0.75rem;font-weight:700;background:#dcfce7;color:#166534;padding:0.15rem 0.5rem;border-radius:999px;">
                Assigned to <?= htmlspecialchars($order['driver_name'] ?? '') ?>
              </span>
            <?php endif; ?>
          </div>
          <h2 style="margin-top:.45rem;font-size:1.2rem;font-weight:700;color:var(--ink);"><?= htmlspecialchars($order['customer_name']) ?></h2>
        </div>
        <span class="status status-<?= htmlspecialchars($order['status']) ?>">
          <?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?>
        </span>
      </div>

      <!-- Address and Instructions -->
      <div style="margin-top:1.25rem;background:#fcfbf9;border-left:3px solid var(--gold);padding:0.85rem 1rem;border-radius:0 8px 8px 0;">
        <p style="font-size:0.95rem;font-weight:700;color:var(--ink);margin:0;">
          📍 <?= htmlspecialchars($order['delivery_address']) ?>
        </p>
        <?php if (!empty($order['delivery_instructions'])): ?>
          <p style="margin:0.4rem 0 0;font-size:0.85rem;color:#b45309;font-weight:500;">
            ⚠️ <em>Customer instructions: <?= htmlspecialchars($order['delivery_instructions']) ?></em>
          </p>
        <?php endif; ?>
      </div>

      <div style="margin-top:0.75rem;display:flex;justify-content:space-between;align-items:center;font-size:.875rem;">
        <span>
          📞 <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $order['contact_number'])) ?>" style="font-weight:700;color:var(--leaf);text-decoration:underline;">
            Call <?= htmlspecialchars($order['contact_number']) ?>
          </a>
        </span>
        <span style="font-weight:700;color:var(--ink);">
          Order Value: <?= mff_money($order['total']) ?> (<?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>)
        </span>
      </div>

      <!-- Items breakdown -->
      <?php if (!empty($order['items'])): ?>
        <div style="margin-top:1rem;border-top:1px dashed var(--line);padding-top:0.75rem;font-size:0.85rem;color:#475569;">
          <strong>Order Contents:</strong>
          <ul style="margin:0.35rem 0 0;padding-left:1.25rem;">
            <?php foreach ($order['items'] as $item): ?>
              <li><?= (int)$item['quantity'] ?> &times; <?= htmlspecialchars($item['name']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Status Transition Controls -->
      <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--line);">
        <p style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;margin:0 0 0.5rem;">
          Update Delivery Status:
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;">
          <form method="post" action="<?= BASE_URL ?>/delivery/my_deliveries.php" style="display:inline;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="hidden" name="status" value="processing">
            <button type="submit" class="<?= $order['status'] === 'processing' ? 'btn-tomato' : 'btn-outline' ?>" style="font-size:0.825rem;padding:0.4rem 0.85rem;">
              1. Processing &amp; Packing
            </button>
          </form>

          <form method="post" action="<?= BASE_URL ?>/delivery/my_deliveries.php" style="display:inline;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="hidden" name="status" value="out_for_delivery">
            <button type="submit" class="<?= $order['status'] === 'out_for_delivery' ? 'btn-tomato' : 'btn-outline' ?>" style="font-size:0.825rem;padding:0.4rem 0.85rem;">
              2. Out for Delivery
            </button>
          </form>

          <form method="post" action="<?= BASE_URL ?>/delivery/my_deliveries.php" style="display:inline;" onsubmit="return confirm('Confirm delivery of order #MFF-<?= $orderId ?>?');">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="hidden" name="status" value="delivered">
            <button type="submit" class="btn-outline" style="font-size:0.825rem;padding:0.4rem 0.85rem;color:#059669;border-color:#10b981;">
              ✓ Mark Delivered
            </button>
          </form>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
