<?php namespace professionalweb\payment\drivers\cnp;

use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\interfaces\CnpProtocol as ICnpProtocol;

/**
 * Wrapper for CNP protocol
 * @package professionalweb\payment\drivers\cnp
 */
class CnpProtocol implements PayProtocol, ICnpProtocol
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

    /**
     * @var string
     */
    private $lastInvoiceId;

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
        $this->lastInvoiceId = '';
        $response = $this->getClient()->startTransaction(['transaction' => $params]);

        if ($response !== null && isset($response->return) && isset($response->return->redirectURL)) {
            $this->lastInvoiceId = $response->return->customerReference;

            return $response->return->redirectURL;
        }

        return '';
    }

    /**
     * Create SOAP client
     *
     * @return CNPSoapClient
     */
    protected function getClient()
    {
        return new CNPSoapClient($this->getPaymentGateUrl());
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
        return $this->lastInvoiceId;
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
        ];
        if (!empty($terminalId = $this->getTerminalId())) {
            $accessParams['terminalId'] = $terminalId;
        }

        return array_merge($accessParams, $params);
    }

    /**
     * Get transaction status
     *
     * @param string $id
     *
     * @return string
     */
    public function getTransactionStatus($id)
    {
        $response = $this->getClient()->getTransactionStatusCode([
            'merchantId'  => $this->getMerchantId(),
            'referenceNr' => $id,
        ]);

        return $response !== null && isset($response->return) && isset($response->return->transactionStatus) ?
            $response->return->transactionStatus : '';
    }

    /**
     * Approve transaction by id
     *
     * @param string $id
     *
     * @return bool
     */
    public function approveTransaction($id)
    {
        $response = $this->getClient()->completeTransaction([
            'merchantId'         => $this->getMerchantId(),
            'referenceNr'        => $id,
            'transactionSuccess' => true,
        ]);

        return $response;
    }
}