<?php

namespace webignition\Tests\Mock\CssValidatorWrapper;

use webignition\CssValidatorWrapper\CssValidatorWrapper as BaseCssValidatorWrapper;

class CssValidatorWrapper extends BaseCssValidatorWrapper {    
    
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