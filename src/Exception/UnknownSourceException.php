<?php

namespace webignition\CssValidatorWrapper\Exception;

class UnknownSourceException extends \Exception
{
    const MESSAGE = 'Unknown source "%s"';
    const CODE = 1;

    public function __construct(string $source)
    {
        parent::__construct(sprintf(self::MESSAGE, $source), self::CODE);
    }
}
