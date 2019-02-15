<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Parser\Flags;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;

class CommandExecutor
{
    private $outputParser;

    public function __construct(OutputParser $outputParser)
    {
        $this->outputParser = $outputParser;
    }

    /**
     * @param string $command
     * @param int $flags
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     */
    public function execute(string $command, int $flags = Flags::NONE): OutputInterface
    {
        $validatorOutput = shell_exec($command);

        return $this->outputParser->parse($validatorOutput, $flags);
    }
}
