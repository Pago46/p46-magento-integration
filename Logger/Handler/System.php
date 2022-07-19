<?php 
namespace Pago46\Cashin\Logger\Handler;

use Monolog\Logger;

class System extends  \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/pago46.log';
}