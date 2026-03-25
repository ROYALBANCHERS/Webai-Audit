<?php
/**
 * PhonePe Payment Gateway Integration
 * WebAI Auditor - Payment API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * PhonePe Payment Service Class
 */
class PhonePeService {
    private $merchantId;
    private $saltKey;
    private $saltIndex;
    private $apiBaseUrl;
    private $isTestMode;

    public function __construct() {
        // Load configuration from XML
        $config = $this->loadPhonePeConfig();

        $this->merchantId = $config['merchant_id'] ?? 'YOUR_MERCHANT_ID';
        $this->saltKey = $config['salt_key'] ?? 'YOUR_SALT_KEY';
        $this->saltIndex = $config['salt_index'] ?? '1';
        $this->isTestMode = ($config['environment'] ?? 'test') === 'test';
        $this->apiBaseUrl = $this->isTestMode
            ? 'https://api-preprod.phonepe.com/apis/hermes'
            : 'https://api.phonepe.com/apis/hermes';
    }

    /**
     * Load PhonePe configuration from XML file
     */
    private function loadPhonePeConfig() {
        $configFile = __DIR__ . '/config/phonepe.xml';
        if (!file_exists($configFile)) {
            return [];
        }

        $xml = simplexml_load_file($configFile);
        $config = [];

        // Parse merchant details
        $config['merchant_id'] = (string)$xml->merchant->merchantId;
        $config['salt_key'] = (string)$xml->security->saltKey;
        $config['salt_index'] = (string)$xml->security->saltIndex;
        $config['environment'] = (string)$xml->api->environment;

        return $config;
    }

    /**
     * Generate checksum for PhonePe API
     */
    private function generateChecksum($payload) {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $base64Payload = base64_encode($jsonPayload);
        $checksumString = $base64Payload . '/pg/v1/pay' . $this->saltKey;
        return hash('sha256', $checksumString) . '###' . $this->saltIndex;
    }

    /**
     * Verify callback checksum
     */
    public function verifyChecksum($response, $receivedChecksum) {
        $base64Response = base64_encode(json_encode($response));
        $checksumString = $base64Response . '/pg/v1/status/' . $this->saltKey;
        $calculatedChecksum = hash('sha256', $checksumString) . '###' . $this->saltIndex;
        return $calculatedChecksum === $receivedChecksum;
    }

    /**
     * Create payment transaction
     */
    public function createTransaction($amount, $transactionId, $redirectUrl, $callbackUrl) {
        // Convert amount to paise
        $amountInPaise = $amount * 100;

        // Build payment payload
        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $transactionId,
            'amount' => $amountInPaise,
            'redirectUrl' => $redirectUrl,
            'redirectMode' => 'REDIRECT',
            'callbackUrl' => $callbackUrl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ]
        ];

        // Encode payload
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $base64Payload = base64_encode($jsonPayload);

        // Generate checksum
        $checksum = $this->generateChecksum($payload);

        // Prepare request
        $endpoint = $this->apiBaseUrl . '/pg/v1/pay';
        $requestData = [
            'request' => $base64Payload
        ];

        // For now, return the encoded payload (actual API call requires server)
        $response = [
            'success' => true,
            'encoded_payload' => $base64Payload,
            'checksum' => $checksum,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'payment_url' => 'https://phonepe-rtu-webPg.kntl009lf.adc-razorpay-in.com/pay?payload=' . urlencode($base64Payload),
            'test_mode' => $this->isTestMode
        ];

        return $response;
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus($transactionId) {
        // In production, this would call PhonePe API
        // For now, return a mock response
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'COMPLETED',
            'amount' => 0,
            'payment_mode' => 'UPI'
        ];
    }

    /**
     * Save transaction to database (simplified)
     */
    public function saveTransaction($data) {
        // Create transaction record in JSON file
        $transactionFile = __DIR__ . '/storage/transactions.json';
        $transactions = [];

        if (file_exists($transactionFile)) {
            $transactions = json_decode(file_get_contents($transactionFile), true) ?? [];
        }

        $transaction = [
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount'],
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s'),
            'payload' => $data['payload'] ?? null
        ];

        $transactions[] = $transaction;
        file_put_contents($transactionFile, json_encode($transactions, JSON_PRETTY_PRINT));

        return $transaction;
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus($transactionId, $status, $response = null) {
        $transactionFile = __DIR__ . '/storage/transactions.json';
        $transactions = [];

        if (file_exists($transactionFile)) {
            $transactions = json_decode(file_get_contents($transactionFile), true) ?? [];
        }

        foreach ($transactions as &$txn) {
            if ($txn['transaction_id'] === $transactionId) {
                $txn['status'] = $status;
                $txn['updated_at'] = date('Y-m-d H:i:s');
                if ($response) {
                    $txn['response'] = $response;
                }
                break;
            }
        }

        file_put_contents($transactionFile, json_encode($transactions, JSON_PRETTY_PRINT));
        return true;
    }
}

/**
 * Handle API Requests
 */
$phonePeService = new PhonePeService();

// Get the action from URL path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Parse action
if (preg_match('/\/api\/payment\/(\w+)/', $path, $matches)) {
    $action = $matches[1];
} else {
    $action = $_GET['action'] ?? '';
}

try {
    switch ($action) {
        case 'create':
            // Create payment transaction
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $amount = $input['amount'] ?? 0;
            $transactionId = $input['transaction_id'] ?? 'TXN' . time();
            $redirectUrl = $input['redirect_url'] ?? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-success.html';
            $callbackUrl = $input['callback_url'] ?? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/payment/callback';

            if ($amount < 10) {
                throw new Exception('Minimum amount is ₹10', 400);
            }

            // Save transaction
            $phonePeService->saveTransaction([
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'payload' => $input['payload'] ?? null
            ]);

            // Create PhonePe transaction
            $result = $phonePeService->createTransaction($amount, $transactionId, $redirectUrl, $callbackUrl);

            echo json_encode($result);
            break;

        case 'status':
            // Check transaction status
            $transactionId = $_GET['transaction_id'] ?? '';
            if (!$transactionId) {
                throw new Exception('Transaction ID required', 400);
            }

            $result = $phonePeService->checkTransactionStatus($transactionId);
            echo json_encode($result);
            break;

        case 'callback':
            // PhonePe callback handler
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Extract response from PhonePe
            if (isset($input['response'])) {
                $response = base64_decode($input['response']);
                $responseData = json_decode($response, true);

                $transactionId = $responseData['merchantTransactionId'] ?? '';
                $status = $responseData['code'] === 'PAYMENT_SUCCESS' ? 'SUCCESS' : 'FAILED';

                // Update transaction status
                $phonePeService->updateTransactionStatus($transactionId, $status, $responseData);

                echo json_encode([
                    'success' => true,
                    'status' => $status,
                    'transaction_id' => $transactionId
                ]);
            } else {
                throw new Exception('Invalid callback data', 400);
            }
            break;

        default:
            // List all available endpoints
            echo json_encode([
                'message' => 'PhonePe Payment API',
                'version' => '1.0.0',
                'endpoints' => [
                    'POST /api/payment/create' => 'Create new payment transaction',
                    'GET /api/payment/status?transaction_id=' => 'Check transaction status',
                    'POST /api/payment/callback' => 'PhonePe callback handler'
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
