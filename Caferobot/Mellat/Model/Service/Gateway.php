<?php
namespace Caferobot\Mellat\Model\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use SoapClient;

class Gateway
{
    const NAMESPACE_URL = 'http://interfaces.core.sw.bps.com/';
    const WSDL_URL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function requestPayment(array $data)
    {
        $client = $this->getSoapClient();
        
        $params = [
            'terminalId'   => $this->getConfig('terminalid'),
            'userName'     => $this->getConfig('username'),
            'userPassword' => $this->getConfig('password'),
            'orderId'      => $data['orderId'],
            'amount'       => $data['amount'],
            'localDate'    => date('Ymd'),
            'localTime'    => date('His'),
            'additionalData' => $data['additionalData'],
            'callBackUrl'  => $data['callBackUrl'],
            'payerId'      => $data['payerId'] ?? 0,
        ];

        // Add mobile if exists (logic from old code)
        if (!empty($data['mobile'])) {
            $params['mobileNo'] = $data['mobile'];
        }

        try {
            $result = $client->bpPayRequest($params, self::NAMESPACE_URL);
            $response = explode(',', $result->return);
            
            $resCode = trim($response[0]);
            
            if ($resCode !== '0') {
                throw new \Exception($this->getErrorMessage($resCode));
            }

            return trim($response[1]); // RefId

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function verifyRequest($orderId, $saleOrderId, $saleReferenceId)
    {
        $client = $this->getSoapClient();
        $params = $this->getSettleParams($orderId, $saleOrderId, $saleReferenceId);

        try {
            $result = $client->bpVerifyRequest($params, self::NAMESPACE_URL);
            $resCode = trim($result->return);
            
            if ($resCode !== '0') {
                throw new \Exception($this->getErrorMessage($resCode));
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function settleRequest($orderId, $saleOrderId, $saleReferenceId)
    {
        $client = $this->getSoapClient();
        $params = $this->getSettleParams($orderId, $saleOrderId, $saleReferenceId);

        try {
            $result = $client->bpSettleRequest($params, self::NAMESPACE_URL);
            $resCode = trim($result->return);

            // 0 = Success, 45 = Already Settled
            if ($resCode !== '0' && $resCode !== '45') {
                throw new \Exception($this->getErrorMessage($resCode));
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function getSettleParams($orderId, $saleOrderId, $saleReferenceId)
    {
        return [
            'terminalId'   => $this->getConfig('terminalid'),
            'userName'     => $this->getConfig('username'),
            'userPassword' => $this->getConfig('password'),
            'orderId'      => $orderId,
            'saleOrderId'  => $saleOrderId,
            'saleReferenceId' => $saleReferenceId
        ];
    }

    private function getSoapClient()
    {
        return new SoapClient(self::WSDL_URL, ['soap_version' => SOAP_1_1]);
    }

    public function getConfig($key)
    {
        return $this->scopeConfig->getValue('payment/mellat/' . $key, ScopeInterface::SCOPE_STORE);
    }

    public function getErrorMessage($code)
    {
        return __('Bank Error Code: %1', $code);
    }
}