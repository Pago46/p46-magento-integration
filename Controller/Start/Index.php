<?php

namespace Pago46\Cashin\Controller\Start;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Http\Client\Curl;
use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $resultJsonFactory;
    protected $logger;
    protected $scopeConfig;
    protected $merchantKey;
    protected $merchantSecret;
    protected $testMode;
    protected $requestPath;
    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Pago46\Cashin\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\ResultFactory $resultFactory        
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->timeout = $this->scopeConfig->getValue('payment/pago46method/timeout', ScopeInterface::SCOPE_STORE);
        $this->merchantKey = $this->scopeConfig->getValue('payment/pago46method/merchant_key', ScopeInterface::SCOPE_STORE);
        $this->merchantSecret = $this->scopeConfig->getValue('payment/pago46method/merchant_secret', ScopeInterface::SCOPE_STORE);
        $this->testMode = $this->scopeConfig->getValue('payment/pago46method/test_mode', ScopeInterface::SCOPE_STORE);
        $this->countryCode = $this->scopeConfig->getValue('payment/pago46method/country_code', ScopeInterface::SCOPE_STORE);
        $this->requestPath = "/merchant/orders/";

        parent::__construct($context);
    }
    
    /**
     * Get store base URL
     *
     * @return object
     */
    private function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();
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
     * Encodes string
     *
     * @return object
     */
    private function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    /**
     * Start checkout by requesting checkout code and dispatching customer to Pago46.

     * @return object
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $order_id = $this->checkoutSession->getLastRealOrderId();
        $date = time();
        $params_string = '';

        try {
            $params = [
                'country_code' => $this->countryCode,
                'currency' => $order->getOrderCurrencyCode(),
                'description' => "Pedido " . $order_id,
                'email' => $order->getCustomerEmail(),
                'merchant_order_id' => $order_id,
                'notify_url' => $this->getBaseUrl() . 'rest/V1/pago46/webhook',
                'price' => intval($order->getGrandTotal()),
                'return_url' => $this->getBaseUrl(),
                'timeout' => $this->timeout
            ];
            
            foreach($params as $key=>$value) {
                $params_string .= $key.'='. $this->encodeURIComponent($value).'&'; 
            }

            $params_string = rtrim($params_string, '&');
            $encrypt_base = $this->merchantKey . '&' . $date .'&POST&' . $this->encodeURIComponent($this->requestPath) . '&'. $params_string;
            $hash = hash_hmac('sha256',$encrypt_base, $this->merchantSecret);
            $orders_url = $this->getPaymentApiBaseUrl() . $this->requestPath;

            $this->logger->info("---------------------------------------------------------------");
            $this->logger->info("Start Payment - merchant key: " . $this->merchantKey);
            $this->logger->info("Start Payment - merchant secret: " . $this->merchantSecret);
            $this->logger->info("Start Payment - params_string: " . $params_string);
            $this->logger->info("Start Payment - encrypt_base: " . $encrypt_base);
            $this->logger->info("Start Payment - hash: " . $hash);
            $this->logger->info("Start Payment - Url: " . $orders_url);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->getPaymentApiBaseUrl() . $this->requestPath);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);

            $headers = [
                'merchant-key: '. $this->merchantKey,
                'message-hash: '. $hash,
                'message-date:'. $date,
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $body = curl_exec ($ch);
            curl_close ($ch);

            $response = json_decode($body, true);
            $this->logger->info("Start Payment - Url: ");
            $this->logger->info($body);

            $redirect_url = $response['redirect_url'];
            
            if (!empty($redirect_url)) {
                $order->setState(Order::STATE_PENDING_PAYMENT, true);
                $order->setStatus(Order::STATE_PENDING_PAYMENT, true);
                $order->addStatusHistoryComment('Payment URL: ' . $redirect_url . "\n" . "Orden#: " . $response['id']);
                $order->setData('pago46_payment_url', $redirect_url);
                $order->save();
                
                $result = $this->resultJsonFactory->create();
                return $result->setData(['redirectUrl' => 'onepage/success']);   
            }
            else {
                $order->registerCancellation('Canceled due to errors')->save();
                $this->checkoutSession->restoreQuote();
                throw new LocalizedException(__('Something went wrong while receiving Pago46 API Response'));
            }
        } catch (\Throwable $th) {
            $this->logger->critical('Start Payment', ["exception" => $th]);
            $order->registerCancellation('Canceled due to errors')->save();
            $this->checkoutSession->restoreQuote();
            throw new LocalizedException(__('Something went wrong while receiving Pago46 API Response'));
        }
    }
}
