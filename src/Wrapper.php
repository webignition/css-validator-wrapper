<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\Configuration\Configuration;

class Wrapper
{
    private $commandFactory;
    private $outputParser;

    public function __construct(CommandFactory $commandFactory, OutputParser $outputParser)
    {
        $this->commandFactory = $commandFactory;
        $this->outputParser = $outputParser;
    }

    /**
     * @param string $url
     * @param Configuration $configuration
     *
     * @param OutputParserConfiguration|null $outputParserConfiguration
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     */
    public function validate(
        string $url,
        Configuration $configuration,
        ?OutputParserConfiguration $outputParserConfiguration = null
    ): OutputInterface {
        $command = $this->commandFactory->create(
            $url,
            $configuration->getJavaExecutablePath(),
            $configuration->getCssValidatorJarPath(),
            $configuration->getVendorExtensionSeverityLevel()
        );

        $validatorOutput = shell_exec($command);

        /* @var ValidationOutput $output */
        $output = $this->outputParser->parse(
            $validatorOutput,
            $outputParserConfiguration ?? new OutputParserConfiguration()
        );

        return $output;
    }
}
