<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

unset($_SESSION['pending_checkout']);
mff_set_flash('error', 'Payment was cancelled. You can try again or choose a different payment method.');
header('Location: ' . BASE_URL . '/checkout.php');
exit;
