<?php

namespace webignition\CssValidatorWrapper\Mock;

use webignition\CssValidatorWrapper\LocalProxyResource as BaseLocalProxyResource;

class LocalProxyResource extends BaseLocalProxyResource {
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $webResource
     * @return string
     */
    protected function generatePath(\webignition\WebResource\WebResource $webResource) {
        return sys_get_temp_dir() . '/' . md5($webResource->getUrl()) . '.' . $this->getPathExtension($webResource);
    }    
}