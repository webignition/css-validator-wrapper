<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
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
     * @param OutputParserConfiguration|null $outputParserConfiguration
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     */
    public function execute(
        string $command,
        ?OutputParserConfiguration $outputParserConfiguration = null
    ): OutputInterface {
        $validatorOutput = shell_exec($command);

        return $this->outputParser->parse(
            $validatorOutput,
            $outputParserConfiguration ?? new OutputParserConfiguration()
        );
    }
}
