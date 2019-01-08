<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\Configuration\Configuration;

class Wrapper
{
    /**
     * @param Configuration $configuration
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     */
    public function validate(Configuration $configuration): OutputInterface
    {
        $rawValidatorOutput = shell_exec($configuration->createExecutableCommand());

        $validatorOutput = $rawValidatorOutput;

        $cssValidatorOutputParser = new OutputParser();

        /* @var ValidationOutput $output */
        $output = $cssValidatorOutputParser->parse(
            $validatorOutput,
            $configuration->getOutputParserConfiguration()
        );

        return $output;
    }
}
