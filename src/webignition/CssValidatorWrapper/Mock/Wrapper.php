<?php

namespace webignition\CssValidatorWrapper\Mock;

use webignition\CssValidatorWrapper\Wrapper as BaseCssValidatorWrapper;
use webignition\CssValidatorWrapper\Mock\LocalProxyResource;

class Wrapper extends BaseCssValidatorWrapper {    
    
    /**
     *
     * @var string
     */
    private $cssValidatorRawOutput = null;
    
    
    /**
     *
     * @var boolean
     */
    private $deferToParentIfNoRawOutput = false;    
    
    
    /**
     * 
     * @param string $rawOutput
     * @return \webignition\Tests\Mock\CssValidatorWrapper\CssValidatorWrapper
     */
    public function setCssValidatorRawOutput($rawOutput) {
        $this->cssValidatorRawOutput = $rawOutput;
        return $this;
    }
    
    
    /**
     * 
     * @return array
     */
    protected function getRawValidatorOutputLines() {        
        if (is_null($this->cssValidatorRawOutput)) {
            if ($this->deferToParentIfNoRawOutput) {
                return parent::getRawValidatorOutputLines();
            } 
            
            return null;
        }      
        
        return explode("\n", $this->cssValidatorRawOutput);
    }  
    
    /**
     * 
     * @return \webignition\HtmlValidator\Mock\Wrapper\Wrapper
     */
    public function enableDeferToParentIfNoRawOutput() {
        $this->deferToParentIfNoRawOutput = true;
        return $this;
    }
    
    
    /**
     * 
     * @return \webignition\HtmlValidator\Mock\Wrapper\Wrapper
     */
    public function disableDeferToParentIfNoRawOutput() {
        $this->deferToParentIfNoRawOutput = false;
        return $this;
    } 
    
    
    protected function createLocalProxyResource() {
        $this->localProxyResource = new LocalProxyResource($this->getConfiguration());
    }    
    
}