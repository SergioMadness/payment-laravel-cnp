<?php namespace professionalweb\payment\drivers\cnp;

use Illuminate\Http\Response;
use professionalweb\payment\Form;
use professionalweb\payment\contracts\Receipt;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\interfaces\CnpService;
use professionalweb\payment\interfaces\CnpProtocol;
use professionalweb\payment\models\PayServiceOption;
use professionalweb\payment\contracts\PaymentApprove;
use professionalweb\payment\contracts\Form as IForm;

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
     * @param string     $paymentType
     * @param string     $successReturnUrl
     * @param string     $failReturnUrl
     * @param string     $description
     * @param array      $extraParams
     * @param Receipt    $receipt
     *
     * @return string
     */
    public function getPaymentLink($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_KZT_ISO,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): string
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
                    'merchantsGoodsID' => $product['id'] ?? '',
                    'nameOfGoods'      => $product['name'] ?? '',
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
    public function validate(array $data): bool
    {
        return $this->getTransport()->validate($data);
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig(): array
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
    public function setConfig(?array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return $this
     */
    public function setResponse(array $data): PayService
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
    public function getResponseParam(string $name, $default = '')
    {
        return $this->response[$name] ?? $default;
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return '';
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->isSuccess() ? 'success' : 'failed';
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getErrorCode() === 0;
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->getTransport()->getPaymentId();
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return 0;
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return 0;
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return self::PAYMENT_CNP;
    }

    /**
     * Get PAn
     *
     * @return string
     */
    public function getPan(): string
    {
        return '';
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime(): string
    {
        return '';
    }

    /**
     * Set transport/protocol wrapper
     *
     * @param PayProtocol $protocol
     *
     * @return $this
     */
    public function setTransport(PayProtocol $protocol): PayService
    {
        $this->transport = $protocol;

        return $this;
    }

    /**
     * Get transport
     *
     * @return CnpProtocol
     */
    public function getTransport(): PayProtocol
    {
        return $this->transport;
    }

    /**
     * Prepare response on notification request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getNotificationResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getNotificationResponse($this->response, $errorCode));
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getCheckResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getNotificationResponse($this->response, $errorCode));
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError(): int
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
    public function getParam(string $name)
    {
        return $this->getResponseParam($name);
    }

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName(): string
    {
        return self::PAYMENT_CNP;
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->getTransport()->getPaymentId();
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm(): bool
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param int     $orderId
     * @param int     $paymentId
     * @param float   $amount
     * @param string  $currency
     * @param string  $paymentType
     * @param string  $successReturnUrl
     * @param string  $failReturnUrl
     * @param string  $description
     * @param array   $extraParams
     * @param Receipt $receipt
     *
     * @return IForm
     */
    public function getPaymentForm($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_RUR,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): IForm
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
    public function approveTransaction($id): bool
    {
        $status = $this->getTransactionStatus($id);
        if ($status === 'AUTHORISED') {
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
    public function getTransactionStatus($id): string
    {
        return $this->getTransport()->getTransactionStatus($id);
    }

    /**
     * Get pay service options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Url')->setAlias('url'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Merchant Id')->setAlias('merchantId'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Terminal Id')->setAlias('terminalId'),
        ];
    }

    /**
     * Get payment currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return '';
    }

    /**
     * Get card type. Visa, MC etc
     *
     * @return string
     */
    public function getCardType(): string
    {
        return '';
    }

    /**
     * Get card expiration date
     *
     * @return string
     */
    public function getCardExpDate(): string
    {
        return '';
    }

    /**
     * Get cardholder name
     *
     * @return string
     */
    public function getCardUserName(): string
    {
        return '';
    }

    /**
     * Get card issuer
     *
     * @return string
     */
    public function getIssuer(): string
    {
        return '';
    }

    /**
     * Get e-mail
     *
     * @return string
     */
    public function getEmail(): string
    {
        return '';
    }

    /**
     * Get payment type. "GooglePay" for example
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        return '';
    }
}