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
    public function getPaymentUrl(array $params): string
    {
        $params = $this->prepareParams($params);
        $this->lastInvoiceId = '';
        $response = $this->getClient()->startTransaction(['transaction' => $params]);

        if (isset($response->return, $response->return->redirectURL) && $response !== null) {
            $this->lastInvoiceId = $response->return->customerReference;

            return $response->return->redirectURL;
        }

        return '';
    }

    /**
     * Create SOAP client
     *
     * @return \SoapClient
     */
    protected function getClient(): \SoapClient
    {
        $location = str_replace('?wsdl', '', $this->getPaymentGateUrl());

        return new \SoapClient($this->getPaymentGateUrl(), [
            'connection_timeout' => 60,
            'cache_wsdl'         => WSDL_CACHE_MEMORY,
            'trace'              => 1,
            'soap_version'       => 'SOAP 1.2',
            'encoding'           => 'UTF-8',
            'exceptions'         => true,
            'location'           => $location,
        ]);
    }

    /**
     * Validate params
     *
     * @param mixed $params
     *
     * @return bool
     */
    public function validate(array $params): bool
    {
        return true;
    }

    /**
     * Get payment ID
     *
     * @return mixed
     */
    public function getPaymentId(): string
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
    public function getNotificationResponse($requestData, $errorCode): string
    {
        return '';
    }

    /**
     * Prepare response on check request
     *
     * @param array $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getCheckResponse($requestData, $errorCode): string
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
    public function setPaymentUrl(?string $url): self
    {
        $this->paymentUrl = $url;

        return $this;
    }

    /**
     * Get payment gate URL
     *
     * @return string
     */
    public function getPaymentGateUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return $this
     */
    public function setMerchantId(?string $merchantId): self
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTerminalId(): string
    {
        return $this->terminalId;
    }

    /**
     * @param string $terminalId
     *
     * @return $this
     */
    public function setTerminalId(?string $terminalId): self
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
    public function prepareParams(array $params): array
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
    public function getTransactionStatus(string $id): string
    {
        $response = $this->getClient()->getTransactionStatusCode([
            'merchantId'  => $this->getMerchantId(),
            'referenceNr' => $id,
        ]);

        return isset($response->return, $response->return->transactionStatus) && $response !== null ?
            $response->return->transactionStatus : '';
    }

    /**
     * Approve transaction by id
     *
     * @param string $id
     *
     * @return bool
     */
    public function approveTransaction(string $id): bool
    {
        $response = $this->getClient()->completeTransaction([
            'merchantId'         => $this->getMerchantId(),
            'referenceNr'        => $id,
            'transactionSuccess' => true,
        ]);

        return $response;
    }
}