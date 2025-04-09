<?php
namespace Shopthru\Connector\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;

interface ImportLogRepositoryInterface
{
    /**
     * Save import log
     *
     * @param \Shopthru\Connector\Api\Data\ImportLogInterface $importLog
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(ImportLogInterface $importLog);

    /**
     * Retrieve import log
     *
     * @param int $importId
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($importId);

    /**
     * Retrieve import logs matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete import log
     *
     * @param \Shopthru\Connector\Api\Data\ImportLogInterface $importLog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(ImportLogInterface $importLog);

    /**
     * Delete multiple import logs by ID
     *
     * @param array $importIds
     * @return int|null
     */
    public function deleteMultiple(array $importIds): ?int;

    /**
     * Delete import log by ID
     *
     * @param int $importId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($importId);
}
