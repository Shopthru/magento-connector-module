<?php

namespace Shopthru\Connector\Model\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Psr\Log\LoggerInterface;

class ClearFailedImportLogs
{
    /**
     * @param ImportLogRepositoryInterface $importLogRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ImportLogRepositoryInterface $importLogRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Clear all import logs with failed status
     *
     * @return int Number of deleted logs
     */
    public function execute(): int
    {
        try {
            // Create search criteria to find all failed logs
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(ImportLogInterface::STATUS, ImportLogInterface::STATUS_FAILED)
                ->create();

            // Get all failed logs
            $failedLogs = $this->importLogRepository->getList($searchCriteria);

            $deletedCount = 0;

            $importIds = [];
            foreach ($failedLogs as $failedLog) {
                $importIds[] = $failedLog->getImportId();
            }

            // Delete each failed log
            try {
                $deletedCount = $this->importLogRepository->deleteMultiple($importIds);
            } catch (CouldNotDeleteException $e) {
                $this->logger->error(
                    sprintf(
                        'Failed to delete failed import log ID %s: %s',
                        $e->getMessage()
                    )
                );
            }


            return $deletedCount;
        } catch (\Exception $e) {
            $this->logger->error('Error clearing failed import logs: ' . $e->getMessage());
            throw $e;
        }
    }
}
