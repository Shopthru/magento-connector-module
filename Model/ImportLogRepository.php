<?php

namespace Shopthru\Connector\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Shopthru\Connector\Model\ResourceModel\ImportLog as ImportLogResource;
use Shopthru\Connector\Model\ResourceModel\ImportLog\CollectionFactory;

class ImportLogRepository implements ImportLogRepositoryInterface
{
    /**
     * @var ImportLogResource
     */
    private $resource;

    /**
     * @var ImportLogFactory
     */
    private $importLogFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param ImportLogResource $resource
     * @param ImportLogFactory $importLogFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ImportLogResource $resource,
        ImportLogFactory $importLogFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->importLogFactory = $importLogFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(ImportLogInterface $importLog)
    {
        try {
            $this->resource->save($importLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $importLog;
    }

    /**
     * @inheritDoc
     */
    public function getById($importId): ImportLogInterface
    {
        $importLog = $this->importLogFactory->create();
        $this->resource->load($importLog, $importId);
        if (!$importLog->getId()) {
            throw new NoSuchEntityException(__('Import log with id "%1" does not exist.', $importId));
        }
        return $importLog;
    }

    /**
     * @inheritDoc
     */
    public function getByShopthruOrderId($shopthruOrderId): ImportLogInterface
    {
        $importLog = $this->importLogFactory->create();
        $this->resource->load($importLog, $shopthruOrderId, 'shopthru_order_id');
        if (!$importLog->getId()) {
            throw new NoSuchEntityException(__('Import log with Shopthru order id "%1" does not exist.', $shopthruOrderId));
        }
        return $importLog;
    }

    public function getByMagentoOrderId($magentoOrderId): ImportLogInterface
    {
        $importLog = $this->importLogFactory->create();
        $this->resource->load($importLog, $magentoOrderId, 'magento_order_id');
        if (!$importLog->getId()) {
            throw new NoSuchEntityException(__('Import log with Magento order id "%1" does not exist.', $magentoOrderId));
        }
        return $importLog;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $collection->load();

        $items = [];
        foreach ($collection as $importLog) {
            $items[] = $importLog;
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function delete(ImportLogInterface $importLog)
    {
        try {
            $this->resource->delete($importLog);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(array $importIds): ?int
    {
        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getMainTable();
            $where = $connection->quoteInto('import_id IN (?)', $importIds);
            $deleteCount = $connection->delete($tableName, $where);
            return $deleteCount;
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteById($importId)
    {
        return $this->delete($this->getById($importId));
    }
}
