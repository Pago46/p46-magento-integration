<?php

namespace Pago46\Cashin\Controller;

use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Http\Client\Curl;
use Magento\Sales\Model\Order;

class OrderProcessing
{
    protected $orderFactory;
    protected $logger;
    protected $scopeConfig;
    protected $merchantKey;
    protected $merchantSecret;
    

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Pago46\Cashin\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Curl $curl
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->merchantKey = $this->scopeConfig->getValue('payment/pago46method/merchant_key', ScopeInterface::SCOPE_STORE);
        $this->merchantSecret = $this->scopeConfig->getValue('payment/pago46method/merchant_secret', ScopeInterface::SCOPE_STORE);
    }
    

    public function cancelOrder($order_id, $reason)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $this->logger->info('OrderProcessing.updateOrder -> Missing order: ' . $order_id);
            return 'no-order';
        }
        
        try {
            if ($order->getId() && !$order->isCanceled()) {
                $order->registerCancellation('Canceled.  Reason ' . $reason)->save();
            }
            $this->logger->info("OrderProcessing.cancelOrder -> Order: " . $order_id );

        } catch (\Throwable $th) {
            $this->logger->critical('OrderProcessing.cancelOrder -> Error updating order status in Magento ' . $order_id . ' order_id', ["exception" => $th]);
        }
        
        return 'done';
    }

    public function updateOrder($order_id, $reason)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $this->logger->info('OrderProcessing.updateOrder -> Missing order: ' . $order_id);
            return 'no-order';
        }
        
        try {
                $next_status = $this->scopeConfig->getValue('payment/pago46method/next_status', ScopeInterface::SCOPE_STORE);

                if (isset($next_status)) {
                    $order->setState($next_status, true)->save();
                    $order->setStatus($next_status, true)->save();
                } else {
                    $order->setState(Order::STATE_PROCESSING, true)->save();
                    $order->setStatus(Order::STATE_PROCESSING, true)->save();
                }
                $order->addStatusHistoryComment($reason);
                $order->save();
                $this->logger->info("OrderProcessing.updateOrder -> Order: " . $order_id . " / Status: " . $next_status);

        } catch (\Throwable $th) {
            $this->logger->critical('OrderProcessing.updateOrder -> Error updating order status in Magento ' . $order_id . ' order_id', ["exception" => $th]);
        }
        
        return 'done';
    }
}
