<?php
namespace Caferobot\Mellat\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\View\Result\PageFactory;
use Caferobot\Mellat\Model\Service\Gateway;
use Caferobot\Mellat\Model\Service\OrderProcessor;

class Start extends Action
{
    protected $checkoutSession;
    protected $customerSession;
    protected $orderFactory;
    protected $orderCollectionFactory;
    protected $resultPageFactory;
    protected $gateway;
    protected $orderProcessor;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        OrderFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        PageFactory $resultPageFactory,
        Gateway $gateway,
        OrderProcessor $orderProcessor
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->gateway = $gateway;
        $this->orderProcessor = $orderProcessor;
        parent::__construct($context);
    }

    public function execute()
    {

        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);

        // Fallback: If Checkout Session is lost, search by Customer ID
        if (!$order->getId() && $this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            
            $timeLimit = date('Y-m-d H:i:s', strtotime('-5 minutes'));

            $order = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('status', ['in' => ['pending', 'pending_payment']])
                ->addFieldToFilter('created_at', ['gte' => $timeLimit])
                ->setOrder('created_at', 'DESC')
                ->getFirstItem();
        }

        if (!$order->getId()) {
            $this->messageManager->addErrorMessage(__('No valid order found to process payment.'));
            return $this->_redirect('checkout/cart');
        }

        $createdAt = strtotime($order->getCreatedAt());
        if ((time() - $createdAt) > 300) { // 5 minutes
             $this->messageManager->addErrorMessage(__('Order validation time expired.'));
             return $this->_redirect('checkout/cart');
        }

        // Prepare Gateway Data
        try {
            
            $reqData = [
                'orderId'     => $orderId,
                'amount'      => (int)$order->getBaseGrandTotal(),
                'callBackUrl' => $this->_url->getUrl('mellat/index/callback'),
                'payerId'     => 0,
                'additionalData' => $order->getIncrementId(),
                'mobile'      => '98'.ltrim($this->customerSession->getCustomer()->getData('mobile'), '0')
            ];

            $refId = $this->gateway->requestPayment($reqData);

            // Save RefId to session or registry to pass to Block
            $this->checkoutSession->setMellatRefId($refId);
            $this->checkoutSession->setMellatMobile($reqData['mobile']);

        } catch (\Exception $e) {
            //$this->orderProcessor->cancelOrder($order, $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('checkout/cart');
        }

        // Render Form
        return $this->resultPageFactory->create();
    }
}