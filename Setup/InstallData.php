<?php

namespace Pago46\Cashin\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    private $salesSetupFactory;

    public function __construct(
        SalesSetupFactory $salesSetupFactory
        ){
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function install(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context) {
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->removeAttribute('order', 'pago46_payment_url');

            $salesSetup->addAttribute('order', 'pago46_payment_url', [
                'type' => 'varchar',
                'required' => false,
                'visible' => false,
            ]);
    }
}
?>