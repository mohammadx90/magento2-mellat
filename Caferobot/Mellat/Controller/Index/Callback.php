<?php
namespace Caferobot\Mellat\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Caferobot\Mellat\Model\Service\Gateway;
use Caferobot\Mellat\Model\Service\OrderProcessor;

class Callback extends Action implements CsrfAwareActionInterface
{
    protected $gateway;
    protected $orderProcessor;
    protected $orderFactory;
    protected $checkoutSession;
    protected $customerSession;

    public function __construct(
        Context $context,
        Gateway $gateway,
        OrderProcessor $orderProcessor,
        OrderFactory $orderFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        $this->gateway = $gateway;
        $this->orderProcessor = $orderProcessor;
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        file_put_contents('/home/admin/domains/tameshkpi.com/public_html/store/eee.txt',print_r($params,true),FILE_APPEND | LOCK_EX);
        // 1. Basic Validation
        if (!isset($params['ResCode']) || !isset($params['SaleOrderId'])) {
            return $this->_redirect('/');
        }

        // 2. Load Order

        $order = $this->orderFactory->create()->load($params['SaleOrderId']);

        if (!$order->getId()) {
            $this->messageManager->addErrorMessage(__('Order not found.'));
            return $this->_redirect('checkout/cart');
        }

        // 3. Relogin if session destroyed
        if (!$this->customerSession->isLoggedIn() && $order->getCustomerId()) {
            $this->customerSession->loginById($order->getCustomerId());
        }

        // 4. Check Amount
        $orderAmount = (int)$order->getBaseGrandTotal();
        $bankAmount = (int)($params['FinalAmount'] ?? 0);

        if ($orderAmount !== $bankAmount) {
             $this->orderProcessor->cancelOrder($order, 'Amount Mismatch');
             $this->messageManager->addErrorMessage(__('Security Alert: Amount mismatch.'));
             return $this->_redirect('checkout/onepage/failure');
        }

        // 5. Verify & Settle
        try {
            if ($params['ResCode'] != '0') {
                throw new \Exception($this->gateway->getErrorMessage($params['ResCode']));
            }

            // Verify
            $this->gateway->verifyRequest(
                $params['SaleOrderId'], 
                $params['SaleOrderId'], 
                $params['SaleReferenceId']
            );

            // Settle
            $this->gateway->settleRequest(
                $params['SaleOrderId'], 
                $params['SaleOrderId'], 
                $params['SaleReferenceId']
            );

            // Success Logic
            
            /**
             * Restore checkout session
             * This is REQUIRED for success page
             */
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderIncrementId($order->getIncrementId());
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

            $this->orderProcessor->processSuccess($order, $params['SaleReferenceId'], $bankAmount);            
            $this->messageManager->addSuccessMessage(__('Payment Successful. Ref: %1', $params['SaleReferenceId']));


            return $this->_redirect('checkout/onepage/success');

        } catch (\Exception $e) {
            $this->orderProcessor->cancelOrder($order, $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('checkout/onepage/failure');
        }
    }

    // Required for POST callbacks bypassing CSRF token
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}