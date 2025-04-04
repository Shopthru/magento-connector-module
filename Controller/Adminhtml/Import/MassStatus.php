<?php

/**
 * MassStatus.php
 */
namespace Shopthru\Connector\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Shopthru\Connector\Model\ResourceModel\ImportLog\CollectionFactory;

class MassStatus extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Shopthru_Connector::import';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ImportLogRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ImportLogRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ImportLogRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $status = $this->getRequest()->getParam('status');
        $importLogsUpdated = 0;

        foreach ($collection as $importLog) {
            $importLog->setStatus($status);
            $this->orderRepository->save($importLog);
            $importLogsUpdated++;
        }

        if ($importLogsUpdated) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 import log(s) have been updated.', $importLogsUpdated)
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
