<?php
namespace Caferobot\Mellat\Model;

/**
 * Modern Payment Method Base
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'mellat';
    
    // Disable features the bank doesn't support
    protected $_isOffline = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
}