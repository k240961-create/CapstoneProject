<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
require_once file_exists(__DIR__ . '/../includes/stripe_config.php') ? __DIR__ . '/../includes/stripe_config.php' : __DIR__ . '/includes/stripe_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function mff_chat_send_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mff_chat_faq_reply(string $message, array $products, string $paymentOptions): string
{
    $normalized = strtolower($message);

    if (preg_match('/\b(hello|hi|hey|good morning|good afternoon)\b/', $normalized)) {
        return 'Hello! I can help you find groceries, check delivery information, explain payment options, or show you where to track an order.';
    }
    if (preg_match('/\b(pay|payment|stripe|paypal|cash|card)\b/', $normalized)) {
        return 'Available payment options: ' . $paymentOptions . '. PayPal is not currently available.';
    }
    if (preg_match('/\b(track|tracking|where.*order|my order|order status)\b/', $normalized)) {
        return 'Sign in and open My Orders to see your order details and delivery progress. I cannot access individual order records in this chat.';
    }
    if (preg_match('/\b(deliver|delivery|shipping|zone|address|fee)\b/', $normalized)) {
        return 'Delivery details and service zones are listed on the Delivery Zones & Rates page. Orders can be tracked from My Orders after you sign in.';
    }
    if (preg_match('/\b(refund|return|cancel)\b/', $normalized)) {
        return 'Please review the Return & Refund Policy. Pending orders can be cancelled from My Orders after signing in.';
    }
    if (preg_match('/\b(help|support|contact|phone|email)\b/', $normalized)) {
        return 'Customer Care is available at 1800 629 436 or support@maxifinefoods.com.au.';
    }

    $stopWords = ['you', 'have', 'the', 'are', 'for', 'with', 'what', 'does', 'there', 'any', 'some', 'can', 'could', 'find', 'want', 'looking', 'about', 'product', 'products', 'please'];
    $terms = array_filter(
        preg_split('/[^a-z0-9]+/i', $normalized),
        static fn($term) => strlen($term) >= 3 && !in_array($term, $stopWords, true)
    );
    $matches = [];
    foreach ($products as $product) {
        $searchable = strtolower(($product['name'] ?? '') . ' ' . ($product['category'] ?? '') . ' ' . ($product['description'] ?? ''));
        $score = 0;
        foreach ($terms as $term) {
            if (str_contains($searchable, $term)) {
                $score++;
            }
        }
        if ($score > 0) {
            $matches[] = ['score' => $score, 'product' => $product];
        }
    }
    usort($matches, static fn($left, $right) => $right['score'] <=> $left['score']);
    if (!empty($matches)) {
        $suggestions = [];
        foreach (array_slice($matches, 0, 3) as $match) {
            $product = $match['product'];
            $suggestions[] = $product['name'] . ' (' . mff_money((float) $product['price']) . ')';
        }
        return 'These in-stock products may match: ' . implode('; ', $suggestions) . '. Prices are in AUD.';
    }

    return 'I can help with products, payment options, delivery, returns, and order tracking. Try asking about a product or one of those topics.';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mff_chat_send_json(405, ['error' => 'Send a chat message with POST.']);
}

$request = json_decode(file_get_contents('php://input'), true);
$message = is_array($request) && is_string($request['message'] ?? null) ? trim($request['message']) : '';
if ($message === '' || strlen($message) > 800) {
    mff_chat_send_json(422, ['error' => 'Enter a message of 1 to 800 characters.']);
}

$now = time();
$requestTimes = array_filter(
    $_SESSION['mff_chat_request_times'] ?? [],
    static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 60
);
if (count($requestTimes) >= 12) {
    mff_chat_send_json(429, ['error' => 'Please wait a moment before sending another message.']);
}
$requestTimes[] = $now;
$_SESSION['mff_chat_request_times'] = array_values($requestTimes);

$localConfigPath = __DIR__ . '/../includes/ai_config.local.php';
$localConfig = file_exists($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    $localConfig = [];
}
$apiKey = getenv('OPENAI_API_KEY') ?: ($localConfig['api_key'] ?? '');
$model = getenv('OPENAI_MODEL') ?: ($localConfig['model'] ?? 'gpt-4o-mini');
$stripeAvailable = !empty($stripeSecretKey) && class_exists(\Stripe\Checkout\Session::class);
$paymentOptions = $stripeAvailable ? 'cash on delivery and Stripe card payment' : 'cash on delivery';
$products = array_slice(mff_get_products(false), 0, 50);
$answer = null;

if (is_string($apiKey) && $apiKey !== '' && function_exists('curl_init')) {
    $catalog = array_map(static fn($product) => [
        'name' => $product['name'],
        'category' => $product['category'],
        'price_aud' => $product['price'],
    ], $products);
    $systemMessage = 'You are the Maxi Fine Foods shopping assistant. Answer only using the store context provided. Help with products, delivery, checkout, refunds, and site navigation. Do not claim to access accounts or individual orders, place or cancel orders, or process payments. Never ask for card numbers, passwords, or other secrets. Direct order tracking to My Orders after sign-in. Be concise. Current payment options: ' . $paymentOptions . '. Store context: ' . json_encode($catalog, JSON_UNESCAPED_UNICODE);
    $messages = [['role' => 'system', 'content' => $systemMessage]];
    $history = is_array($request['history'] ?? null) ? array_slice($request['history'], -6) : [];
    foreach ($history as $entry) {
        if (!is_array($entry) || !in_array($entry['role'] ?? '', ['user', 'assistant'], true)
            || !is_string($entry['content'] ?? null) || strlen($entry['content']) > 800) {
            continue;
        }
        $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 300,
        ]),
    ]);
    $responseBody = curl_exec($curl);
    $responseStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    $responseData = is_string($responseBody) ? json_decode($responseBody, true) : null;
    if ($responseStatus >= 200 && $responseStatus < 300 && is_string($responseData['choices'][0]['message']['content'] ?? null)) {
        $answer = trim($responseData['choices'][0]['message']['content']);
    }
}

if ($answer !== null && $answer !== '') {
    mff_chat_send_json(200, ['answer' => $answer, 'mode' => 'ai']);
}

mff_chat_send_json(200, [
    'answer' => mff_chat_faq_reply($message, $products, $paymentOptions),
    'mode' => 'faq',
]);