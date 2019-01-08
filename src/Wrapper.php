<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;

class Wrapper
{
    private $commandFactory;
    private $outputParser;
    private $javaExecutablePath;
    private $cssValidatorJarPath;

    public function __construct(
        CommandFactory $commandFactory,
        OutputParser $outputParser,
        string $javaExecutablePath,
        string $cssValidatorJarPath
    ) {
        $this->commandFactory = $commandFactory;
        $this->outputParser = $outputParser;
        $this->javaExecutablePath = $javaExecutablePath;
        $this->cssValidatorJarPath = $cssValidatorJarPath;
    }

    /**
     * @param string $url
     * @param string $vendorExtensionSeverityLevel
     * @param OutputParserConfiguration|null $outputParserConfiguration
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     */
    public function validate(
        string $url,
        string $vendorExtensionSeverityLevel,
        ?OutputParserConfiguration $outputParserConfiguration = null
    ): OutputInterface {
        $command = $this->commandFactory->create(
            $url,
            $this->javaExecutablePath,
            $this->cssValidatorJarPath,
            $vendorExtensionSeverityLevel
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
