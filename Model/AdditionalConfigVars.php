<?php

namespace Pago46\Cashin\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigVars implements ConfigProviderInterface
{
    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig       Scope Configuration.
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

	public function getConfig()
	{
		$additionalVariables['pago46'] = array(
			'payment_options' => $this->getPaymentOptions($this->scopeConfig->getValue('payment/pago46method/country_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
		));

		return $additionalVariables;
	}

    public function getPaymentOptions($country_code) {

        $payment_options = "";
        switch ($country_code) {
            case "ARG":
                $payment_options = "https://static-magento.pago46.io/arg_network.png";
                break;
            case "CHL":
                $payment_options = "https://static-magento.pago46.io/chl_network.png";
                break;
            case "MEX":
                $payment_options = "https://static-magento.pago46.io/mex_network.png";
                break;
            case "URY":
                $payment_options = "https://static-magento.pago46.io/ury_network.png";
                break;
            case "ECU":
                $payment_options = "https://static-magento.pago46.io/ecu_network.png";
                break;
            case "PER":
                $payment_options = "https://static-magento.pago46.io/per_network.png";
                break;
                default:
                $payment_options = "https://static-magento.pago46.io/arg_network.png";
        }
        return $payment_options;
    }
}
