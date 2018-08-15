<?php namespace professionalweb\payment\drivers\cnp;


class CNPSoapClient extends \SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
//        $location = str_replace('http://', 'https://', $location);
//        $location = str_replace([':8080', ':8443'], '', $location);

        return parent::__doRequest($request, 'https://test.processing.kz/CNPMerchantWebServices/services/CNPMerchantWebService.CNPMerchantWebServiceHttpSoap12Endpoint/', $action, $version, $one_way);
    }
}