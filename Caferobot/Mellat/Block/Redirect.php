<?php
namespace Caferobot\Mellat\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session;

class Redirect extends Template
{
    protected $checkoutSession;
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    public function getRefId()
    {
        return $this->checkoutSession->getMellatRefId();
    }

    public function getMobile()
    {
        return $this->checkoutSession->getMellatMobile();
    }

    public function getBankUrl()
    {
        return 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat'; 
    }
}