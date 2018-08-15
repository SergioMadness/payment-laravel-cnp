<?php namespace professionalweb\payment\drivers\cnp;

use professionalweb\payment\contracts\PayProtocol;

/**
 * Wrapper for CNP protocol
 * @package professionalweb\payment\drivers\cnp
 */
class CnpProtocol implements PayProtocol
{
    /**
     * Current payment URL
     *
     * @var string
     */
    private $paymentUrl;

    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $terminalId;

    public function __construct($url = '', $merchantId = '', $terminalId = '')
    {
        $this
            ->setPaymentUrl($url)
            ->setMerchantId($merchantId)
            ->setTerminalId($terminalId);
    }

    /**
     * Get payment URL
     *
     * @param mixed $params
     *
     * @return string
     */
    public function getPaymentUrl($params)
    {
        $params = $this->prepareParams($params);

        $soap = new CNPSoapClient($this->getPaymentGateUrl());
        $response = $soap->startTransaction(['transaction' => $params]);

        return '';
    }

    /**
     * Validate params
     *
     * @param mixed $params
     *
     * @return bool
     */
    public function validate($params)
    {
        return true;
    }

    /**
     * Get payment ID
     *
     * @return mixed
     */
    public function getPaymentId()
    {
        // TODO: Implement getPaymentId() method.
    }

    /**
     * Prepare response on notification request
     *
     * @param mixed $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getNotificationResponse($requestData, $errorCode)
    {
        return response('');
    }

    /**
     * Prepare response on check request
     *
     * @param array $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getCheckResponse($requestData, $errorCode)
    {
        return $this->getNotificationResponse($requestData, $errorCode);
    }

    /**
     * Set payment url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setPaymentUrl($url)
    {
        $this->paymentUrl = $url;

        return $this;
    }

    /**
     * Get payment gate URL
     *
     * @return string
     */
    public function getPaymentGateUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return $this
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTerminalId()
    {
        return $this->terminalId;
    }

    /**
     * @param string $terminalId
     *
     * @return $this
     */
    public function setTerminalId($terminalId)
    {
        $this->terminalId = $terminalId;

        return $this;
    }

    /**
     * Prepare parameters
     *
     * @param array $params
     *
     * @return array
     */
    public function prepareParams($params)
    {
        $accessParams = [
            'merchantId'            => $this->getMerchantId(),
            'merchantLocalDateTime' => date('d.m.Y H:i:s'),
//            'goodsList'             => [
//                [
//                    'nameOfGoods'  => 'test',
//                    'amount'       => 500,
//                    'currencyCode' => 398,
//                ],
//            ],
        ];
        if (!empty($terminalId = $this->getTerminalId())) {
            $accessParams['terminalId'] = $terminalId;
        }

        return array_merge($accessParams, $params);
    }
}