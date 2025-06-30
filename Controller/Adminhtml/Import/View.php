<?php

/**
 * View.php
 */
namespace Shopthru\Connector\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;

class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Shopthru_Connector::import';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ImportLogRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param ImportLogRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        ImportLogRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->orderRepository = $orderRepository;
    }

    /**
     * View import log
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        try {
            $importLog = $this->orderRepository->getById($id);
            $this->coreRegistry->register('current_import_log', $importLog);

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Shopthru_Connector::shopthru');
            $resultPage->addBreadcrumb(__('Shopthru'), __('Shopthru'));
            $resultPage->addBreadcrumb(__('Import Logs'), __('Import Logs'));
            $resultPage->addBreadcrumb(__('View Import Log'), __('View Import Log'));
            $resultPage->getConfig()->getTitle()->prepend(__('Import Log #%1', $importLog->getImportId()));

            return $resultPage;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This import log no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }
}
