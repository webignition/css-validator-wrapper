<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\WebResourceInterfaces\WebPageInterface;

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
     * @param WebPageInterface $webPage
     * @param SourceMap $sourceMap
     * @param string $vendorExtensionSeverityLevel
     * @param OutputParserConfiguration|null $outputParserConfiguration
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    public function validate(
        WebPageInterface $webPage,
        SourceMap $sourceMap,
        string $vendorExtensionSeverityLevel,
        ?OutputParserConfiguration $outputParserConfiguration = null
    ): OutputInterface {
        $webPageUri = (string) $webPage->getUri();
        $webPageLocalPath = $sourceMap->getLocalPath($webPageUri);

        if (empty($webPageLocalPath)) {
            throw new UnknownSourceException($webPageUri);
        }

        $command = $this->commandFactory->create(
            (string) $webPage->getUri(),
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
