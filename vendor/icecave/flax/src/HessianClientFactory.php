<?php
namespace Icecave\Flax;

use Guzzle\Http\Client;

class HessianClientFactory
{
    /**
     * @param string $url
     */
    public function create($url)
    {
        $httpClient = new Client($url);
        $httpClient->setUserAgent(
            sprintf('Flax/%s', PackageInfo::VERSION)
        );

        $httpClient->setDefaultOption(
            'headers/Content-Type',
            'x-application/hessian'
        );

        return new HessianClient($httpClient);
    }
}
