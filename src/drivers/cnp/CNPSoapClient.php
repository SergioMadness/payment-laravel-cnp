<?php namespace professionalweb\payment\drivers\cnp;


class CNPSoapClient extends \SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $location = str_replace('http://', 'https://', $location);

        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}