<?php
namespace Caferobot\Mellat\Model\Service;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class OrderProcessor
{
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    protected $scopeConfig;

    public function __construct(
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->scopeConfig = $scopeConfig;
    }

    public function processSuccess(Order $order, $refId, $amountPaid)
    {
        // Update Payment Info
        $payment = $order->getPayment();
        $payment->setBaseAmountPaidOnline($amountPaid);
        $payment->setTransactionId($refId);
        $payment->setLastTransId($refId);
        
        // Add Comment
        $order->addStatusHistoryComment(__('Successful Payment. RefId: %1', $refId));

        // Create Invoice
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            
            $transactionSave = $this->transaction->addObject($invoice)
                                                 ->addObject($invoice->getOrder());
            $transactionSave->save();
            
            try {
                $this->invoiceSender->send($invoice);
                $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
                      ->setIsCustomerNotified(true);
            } catch (\Exception $e) {
                // Log email failure but don't stop process
            }
        }

        // Set Status
        $successStatus = $this->scopeConfig->getValue(
            'payment/mellat/success_order_status', 
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        if ($order->hasInvoices() && $successStatus) {
            $order->setStatus($successStatus);
            $order->addStatusHistoryComment(__('Status updated to "%1" by system.', $successStatus)); 
        } 
        
        $order->save();
    }

    public function cancelOrder(Order $order, $reason = '')
    {
        if ($order->canCancel()) {
            //$order->cancel();
            $order->addStatusHistoryComment(__('Order Canceled. Reason: %1', $reason));
            $order->save();
        }
    }
}