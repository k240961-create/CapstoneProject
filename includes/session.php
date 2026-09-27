<?php
/**
 * session.php — role/auth helpers, cart state, and flash messages.
 * Include this before any output on every page (it starts the session).
 */

require_once __DIR__ . '/db.php';

/**
 * BASE_URL — the URL path prefix under which /public is being served.
 * Computed automatically from the request, so the app works whether it's
 * accessed at the domain root or nested under folders like
 * /capstone/maxi-fine-foods-php/public. All links/forms/assets should be
 * built as BASE_URL . '/something.php' instead of a hardcoded '/something.php'.
 */
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (($pos = strpos($scriptName, '/g4')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos + strlen('/g4')));
    } elseif (($pos = strpos($scriptName, '/public')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos + strlen('/public')));
    } elseif (($pos = strpos($scriptName, '/admin')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos));
    } elseif (($pos = strpos($scriptName, '/delivery')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos));
    } else {
        $dir = dirname($scriptName);
        define('BASE_URL', ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/\\'));
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 15-minute idle session timeout (SRS NFR11 / TC13-14 / TC-SUP-07)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        // Expired after 15 minutes of inactivity
        $savedCart = $_SESSION['cart'] ?? [];
        $_SESSION = [];
        $_SESSION['cart'] = $savedCart;
        $_SESSION['role'] = 'guest';
        mff_set_flash('info', 'Your session has expired after 15 minutes of inactivity. Please log in again.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // [product_id => quantity]
}
foreach ($_SESSION['cart'] as $mffCartKey => $mffCartVal) {
    if (!is_numeric($mffCartVal)) {
        $_SESSION['cart'] = [];
        break;
    }
}

unset($mffCartKey, $mffCartVal);
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest'; // guest | customer | admin | delivery
}

/* ---------------------------------------------------------------- roles */

const MFF_ROLES = [
    'guest',
    'customer',
    'delivery',
    'inventory_manager',
    'logistics_manager',
    'support_staff',
    'admin'
];
const MFF_FREE_DELIVERY_THRESHOLD = 60.00;
const MFF_DELIVERY_FEE = 8.50;

function mff_role(): string
{
    return $_SESSION['role'] ?? 'guest';
}

function mff_set_role(string $role): void
{
    if (in_array($role, MFF_ROLES, true)) {
        $_SESSION['role'] = $role;
    }
}

function mff_require_role(array $allowed): void
{
    if (!in_array(mff_role(), $allowed, true)) {
        mff_set_flash('error', "Access restricted — required role: " . implode(', ', $allowed));
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function mff_is_staff(): bool
{
    return in_array(mff_role(), ['admin', 'inventory_manager', 'logistics_manager', 'support_staff'], true);
}

function mff_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'logistics_manager' => 'Logistics & Fleet Manager',
        'inventory_manager' => 'Product & Inventory Manager',
        'support_staff' => 'Customer Service Staff',
        'delivery' => 'Delivery Driver',
        'customer' => 'Customer',
        default => 'Guest',
    };
}

/* ------------------------------------------------------------ flash msg */

function mff_set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function mff_get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/* ------------------------------------------------------------------ cart */

function cart_add(int $productId, int $quantity = 1): void
{
    $quantity = max(1, $quantity);
    $existing = $_SESSION['cart'][$productId] ?? 0;
    $existing = is_numeric($existing) ? (int) $existing : 0;
    $_SESSION['cart'][$productId] = $existing + $quantity;
}

function cart_set_qty(int $productId, int $quantity): void
{
    if ($quantity < 1) {
        cart_remove($productId);
        return;
    }
    $_SESSION['cart'][$productId] = $quantity;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    return array_sum(array_map(fn($q) => is_numeric($q) ? (int) $q : 0, $_SESSION['cart']));
}

function mff_cart_totals(float $subtotal): array
{
    $subtotal = round(max(0, $subtotal), 2);
    $deliveryFee = $subtotal > 0 && $subtotal < MFF_FREE_DELIVERY_THRESHOLD ? MFF_DELIVERY_FEE : 0.0;
    $tax = round(($subtotal + $deliveryFee) * 0.10, 2);

    return [
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'tax' => $tax,
        'total' => round($subtotal + $deliveryFee + $tax, 2),
    ];
}

/**
 * Resolves the cart against the live product catalog (or fallback) and
 * returns line items plus totals. Silently drops any product id that no
 * longer exists (e.g. deleted by an admin) rather than erroring.
 */
function cart_contents(): array
{
    $items = [];
    $subtotal = 0.0;

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $quantity = is_numeric($quantity) ? (int) $quantity : 0;
        if ($quantity < 1) {
            continue;
        }
        $product = mff_get_product((int) $productId);
        if ($product === null) {
            continue;
        }
        $lineTotal = (float) $product['price'] * $quantity;
        $subtotal += $lineTotal;
        $items[] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'image_url' => $product['image_url'] ?? null,
        ];
    }

    return [
        'items' => $items,
    ] + mff_cart_totals($subtotal);
}

/** True if the request is an XHR / fetch() call expecting JSON back. */
function mff_wants_json(): bool
{
    return (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    );
}

function mff_money(float $n): string
{
    return '$' . number_format($n, 2);
}
