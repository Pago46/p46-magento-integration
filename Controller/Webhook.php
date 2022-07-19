<?php

namespace Pago46\Cashin\Controller;

use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;


class Webhook
{
    protected $logger;
    protected $orderProc;
    protected $scopeConfig;
    protected $merchantKey;
    protected $merchantSecret;
    protected $requestPath;
    protected $testMode;

    public function __construct(
        \Pago46\Cashin\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Pago46\Cashin\Controller\OrderProcessing $orderProc
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderProc = $orderProc;
        $this->merchantKey = $this->scopeConfig->getValue('payment/pago46method/merchant_key', ScopeInterface::SCOPE_STORE);
        $this->merchantSecret = $this->scopeConfig->getValue('payment/pago46method/merchant_secret', ScopeInterface::SCOPE_STORE);
        $this->requestPath = "/merchant/notification/";
        $this->testMode = $this->scopeConfig->getValue('payment/pago46method/test_mode', ScopeInterface::SCOPE_STORE);

    }

    /**
     * Encodes string
     *
     * @return object
     */
    private function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    /**
     * Get store base URL
     *
     * @return object
     */
    private function getPaymentApiBaseUrl()
    {
        if ($this->testMode) {
            return "https://sandbox.pago46.io";
        } else {
            return "https://sandbox.pago46.io";
        }
    }
    
    /**
     * Process Webhook
     *
     * @return string
     */
    public function postWebhook()
    {
        try {
            $body = file_get_contents("php://input");


            $request = json_decode($body, true);
            $this->logger->info("WebHook: Body: " . $body);

            if (!isset($request) || !isset($request['notification_id'])) {
                $this->logger->critical('WebHook: notification_id missing in notification JSON (null)', []);
            }

            $notification_id = $request['notification_id'];
            if (empty($notification_id)) {
                return $this->logger->critical('WebHook: notification_id missing in notification JSON (empty)', []);
            }
            $date = time();
            $encrypt_base = $this->merchantKey . '&' . $date .'&GET&' . $this->encodeURIComponent($this->requestPath. $notification_id);

            $hash = hash_hmac('sha256',$encrypt_base, $this->merchantSecret);

            $this->logger->info("--------------------- Notification ------------------------------------");
            $this->logger->info("WebHook - merchant key: " . $this->merchantKey);
            $this->logger->info("WebHook - merchant secret: " . $this->merchantSecret);
            $this->logger->info("WebHook - encrypt_base: " . $encrypt_base);
            $this->logger->info("WebHook - hash: " . $hash);
            $this->logger->info("WebHook - Notification_id: " . $notification_id);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->getPaymentApiBaseUrl() . $this->requestPath . $notification_id);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

            $headers = [
                'merchant-key: '. $this->merchantKey,
                'message-hash: '. $hash,
                'message-date:'. $date,
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $body = curl_exec ($ch);
            curl_close ($ch);

            $response = json_decode($body, true);

            $this->logger->info("WebHook Get Order - Response Body from Notification GET: ");
            $this->logger->info($body);
            $this->logger->info("---------------------------------------------------------------");

                if (!isset($response) || !isset($response['merchant_order_id'])) {
                return $this->logger->critical('WebHook Get Order - merchant_order_id missing in notification JSON (null)', []);
            }

            $order_id = $response['merchant_order_id'];
            $status = $response['status'];

            if (empty($order_id)) {
                return $this->logger->critical('WebHook Get Order - order_id missing in notification JSON (empty)', []);
            }

            if ($status == 'cancel') {
                $this->orderProc->cancelOrder($order_id, "User cancel");
            } elseif ($status == 'expire') {
                $this->orderProc->cancelOrder($order_id, "Timeout expired");
            } elseif ($status == 'successful') {
                $this->orderProc->updateOrder($order_id, "Payment Date: " . $response['payment_date']);    
            }
        } catch (\Throwable $th) {
            $this->logger->critical('WebHook Get Order - payment validation failed ' . filter_input(INPUT_GET, 'order_id'), ["exception" => $th]);
            throw $th;
        }
    }
}
