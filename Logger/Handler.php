<?php
/**
 * Handler.php
 */
namespace Shopthru\Connector\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Handler extends StreamHandler
{
    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
                        $filePath = null
    ) {
        $logFile = $filePath ?? BP . '/var/log/shopthru_connector.log';
        parent::__construct($logFile, Logger::DEBUG);
    }
}
