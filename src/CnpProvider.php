<?php namespace professionalweb\payment;

use Illuminate\Support\ServiceProvider;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\drivers\cnp\CnpDriver;
use professionalweb\payment\interfaces\CnpService;
use professionalweb\payment\drivers\cnp\CnpProtocol;
use professionalweb\payment\contracts\PaymentFacade;

/**
 * upc.ua payment provider
 * @package professionalweb\payment
 */
class CnpProvider extends ServiceProvider
{

    public function boot()
    {
        app(PaymentFacade::class)->registerDriver(CnpService::PAYMENT_CNP, CnpService::class);
    }

    /**
     * Bind two classes
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CnpService::class, function ($app) {
            return (new CnpDriver(config('payment.upc')))->setTransport(
                new CnpProtocol(
                    config('payment.upc.url'),
                    config('payment.upc.merchantId'),
                    config('payment.upc.terminalId'),
                    config('payment.upc.pathToOurKey'),
                    config('payment.upc.pathToTheirKey')
                )
            );
        });
        $this->app->bind(PayService::class, function ($app) {
            return (new CnpDriver(config('payment.upc')))->setTransport(
                new CnpProtocol(
                    config('payment.upc.url'),
                    config('payment.upc.merchantId'),
                    config('payment.upc.terminalId'),
                    config('payment.upc.pathToOurKey'),
                    config('payment.upc.pathToTheirKey')
                )
            );
        });
        $this->app->bind(CnpDriver::class, function ($app) {
            return (new CnpDriver(config('payment.upc')))->setTransport(
                new CnpProtocol(
                    config('payment.upc.url'),
                    config('payment.upc.merchantId'),
                    config('payment.upc.terminalId'),
                    config('payment.upc.pathToOurKey'),
                    config('payment.upc.pathToTheirKey')
                )
            );
        });
    }
}