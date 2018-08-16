<?php namespace professionalweb\payment\drivers\cnp;

use professionalweb\payment\Form;
use Illuminate\Contracts\Support\Arrayable;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\interfaces\CnpService;
use professionalweb\payment\interfaces\CnpProtocol;
use professionalweb\payment\contracts\PaymentApprove;

/**
 * Payment service. Pay, Check, etc
 * @package professionalweb\payment\drivers\cnp
 */
class CnpDriver implements PayService, CnpService, PaymentApprove
{
    /**
     * CNP protocol object
     *
     * @var CnpProtocol
     */
    private $transport;

    /**
     * Module config
     *
     * @var array
     */
    private $config;

    /**
     * Notification info
     *
     * @var array
     */
    protected $response;

    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * Pay
     *
     * @param int        $orderId
     * @param int        $paymentId
     * @param float      $amount
     * @param int|string $currency
     * @param string     $successReturnUrl
     * @param string     $failReturnUrl
     * @param string     $description
     * @param array      $extraParams
     * @param Arrayable  $receipt
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentLink($orderId,
                                   $paymentId,
                                   $amount,
                                   $currency = self::CURRENCY_KZT_ISO,
                                   $paymentType = self::PAYMENT_TYPE_CARD,
                                   $successReturnUrl = '',
                                   $failReturnUrl = '',
                                   $description = '',
                                   $extraParams = [],
                                   $receipt = null)
    {
        $params = [
            'orderId'                           => $orderId,
            'currencyCode'                      => $currency,
            'totalAmount'                       => $amount * 100,
            'description'                       => $description,
            'merchantAdditionalInformationList' => $extraParams,
            'returnURL'                         => $successReturnUrl,
        ];
        if (isset($extraParams['locale'])) {
            $params['languageCode'] = $extraParams['locale'];
        }
        if (isset($extraParams['products'])) {
            $params['goodsList'] = [];
            foreach ($extraParams['products'] as $product) {
                $params['goodsList'][] = [
                    'merchantsGoodsID' => isset($product['id']) ? $product['id'] : '',
                    'nameOfGoods'      => isset($product['name']) ? $product['name'] : '',
                    'amount'           => isset($product['price']) ? $product['price'] * 100 : 0,
                    'currencyCode'     => $currency,
                ];
            }
        }

        return $this->getTransport()->getPaymentUrl($params);
    }

    /**
     * Validate request
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate($data)
    {
        return $this->getTransport()->validate($data);
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set driver configuration
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return mixed
     */
    public function setResponse($data)
    {
        $this->response = $data;

        return $this;
    }

    /**
     * Get response param by name
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|string
     */
    public function getResponseParam($name, $default = '')
    {
        return isset($this->response[$name]) ? $this->response[$name] : $default;
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId()
    {
        return '';
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->isSuccess() ? 'success' : 'failed';
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getErrorCode() === 0;
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getTransport()->getPaymentId();
        //$this->getResponseParam('Rrn');
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount()
    {
        return '';
    }

    /**
     * Get error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return '';
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider()
    {
        return self::PAYMENT_CNP;
    }

    /**
     * Get PAn
     *
     * @return string
     */
    public function getPan()
    {
        return '';
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime()
    {
        $result = '';

        return $result;
    }

    /**
     * Set transport/protocol wrapper
     *
     * @param PayProtocol $protocol
     *
     * @return $this
     */
    public function setTransport(PayProtocol $protocol)
    {
        $this->transport = $protocol;

        return $this;
    }

    /**
     * Get transport
     *
     * @return CnpProtocol
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Prepare response on notification request
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getNotificationResponse($errorCode = null)
    {
        return $this->getTransport()->getNotificationResponse($this->response, $errorCode);
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getCheckResponse($errorCode = null)
    {
        return $this->getTransport()->getNotificationResponse($this->response, $errorCode);
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError()
    {
        return 0;
    }

    /**
     * Get param by name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParam($name)
    {
        return $this->getResponseParam($name);
    }

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName()
    {
        return self::PAYMENT_CNP;
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId()
    {
        return $this->getTransport()->getPaymentId();
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm()
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param int       $orderId
     * @param int       $paymentId
     * @param float     $amount
     * @param string    $currency
     * @param string    $paymentType
     * @param string    $successReturnUrl
     * @param string    $failReturnUrl
     * @param string    $description
     * @param array     $extraParams
     * @param Arrayable $receipt
     *
     * @return string
     */
    public function getPaymentForm($orderId,
                                   $paymentId,
                                   $amount,
                                   $currency = self::CURRENCY_RUR,
                                   $paymentType = self::PAYMENT_TYPE_CARD,
                                   $successReturnUrl = '',
                                   $failReturnUrl = '',
                                   $description = '',
                                   $extraParams = [],
                                   $receipt = null)
    {
        return new Form();
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
        $status = $this->getTransactionStatus($id);
        if ($status === 'PAID' || $status === 'AUTHORISED') {
            return $this->getTransport()->approveTransaction($id);
        }

        return false;
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
        return $this->getTransport()->getTransactionStatus($id);
    }
}