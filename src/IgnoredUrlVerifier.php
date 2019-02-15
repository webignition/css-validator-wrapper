<?php

namespace webignition\CssValidatorWrapper;

use webignition\Uri\Host;
use webignition\Uri\Uri;

class IgnoredUrlVerifier
{
    public function isUrlIgnored(string $url, array $domainsToIgnore): bool
    {
        $uri = new Uri($url);
        $host = new Host($uri->getHost());

        foreach ($domainsToIgnore as $domainToIgnore) {
            if ($host->isEquivalentTo(new Host($domainToIgnore))) {
                return true;
            }
        }

        return false;
    }
}
