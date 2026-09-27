<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

$localKeysFile = __DIR__ . '/stripe_keys.local.php';
$localKeys = file_exists($localKeysFile) ? require $localKeysFile : [];

$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($localKeys['secret_key'] ?? '');
$stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: ($localKeys['publishable_key'] ?? '');

if (!empty($stripeSecretKey) && class_exists(\Stripe\Stripe::class)) {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
}
