<?php

namespace Pago46\Cashin\Model;

class Pago46 extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = "pago46method";
    protected $_isOffline = true;
    protected $_supportedCurrencyCodes = array('ARS', 'MXN', 'CLP', 'USD');

    /**
     * Check if API key is set
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote = null Parameter description.
     *
     * @return object
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        $merchant_key = $this->_scopeConfig->getValue(
            'payment/pago46method/merchant_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $merchant_secret = $this->_scopeConfig->getValue(
            'payment/pago46method/merchant_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$merchant_key || !$merchant_secret) {
            return false;
        }

        if (!in_array($quote->getCurrency()->getBaseCurrencyCode(), $this->_supportedCurrencyCodes)) {
            return false;
        }

        
        return parent::isAvailable($quote);
    }

}
