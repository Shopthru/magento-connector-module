<?php

namespace Shopthru\Connector\Model\ResourceModel\ImportLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Shopthru\Connector\Model\ImportLog;
use Shopthru\Connector\Model\ResourceModel\ImportLog as ImportLogResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'import_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ImportLog::class, ImportLogResource::class);
    }
}
