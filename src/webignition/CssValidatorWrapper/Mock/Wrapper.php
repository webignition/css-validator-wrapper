<?php

namespace webignition\CssValidatorWrapper\Mock;

use webignition\CssValidatorWrapper\Wrapper as BaseCssValidatorWrapper;

class Wrapper extends BaseCssValidatorWrapper {    
    
    /**
     *
     * @var string
     */
    private $cssValidatorRawOutput = null;
    
    
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
        return explode("\n", $this->cssValidatorRawOutput);
    }    
    
}