<?php
/**
 * Index.php
 */
namespace Shopthru\Connector\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Shopthru_Connector::import';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Shopthru_Connector::shopthru');
        $resultPage->addBreadcrumb(__('Shopthru'), __('Shopthru'));
        $resultPage->addBreadcrumb(__('Import Logs'), __('Import Logs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Shopthru Import Logs'));

        return $resultPage;
    }
}
