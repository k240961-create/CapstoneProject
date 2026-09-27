<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

// Security check: Only an active Administrator can use the role switcher tool
if (mff_role() !== 'admin') {
    mff_set_flash('error', 'Access denied: Role switching is restricted to Administrators only.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetRole = $_POST['role'] ?? 'admin';
    if (in_array($targetRole, MFF_ROLES, true)) {
        mff_set_role($targetRole);
        mff_set_flash('info', 'Active role preview switched to ' . mff_role_label($targetRole) . '.');
    }
}

$redirect = $_POST['redirect'] ?? '/index.php';
// Only allow same-site relative redirects.
if (!str_starts_with($redirect, '/')) {
    $redirect = '/index.php';
}
header('Location: ' . $redirect);
exit;

