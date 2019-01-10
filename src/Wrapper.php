<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;

class Wrapper
{
    private $sourcePreparer;
    private $commandFactory;
    private $outputParser;
    private $javaExecutablePath;
    private $cssValidatorJarPath;

    public function __construct(
        SourcePreparer $sourcePreparer,
        CommandFactory $commandFactory,
        OutputParser $outputParser,
        string $javaExecutablePath,
        string $cssValidatorJarPath
    ) {
        $this->sourcePreparer = $sourcePreparer;
        $this->commandFactory = $commandFactory;
        $this->outputParser = $outputParser;
        $this->javaExecutablePath = $javaExecutablePath;
        $this->cssValidatorJarPath = $cssValidatorJarPath;
    }

    /**
     * @param SourceHandler $sourceHandler
     * @param string $vendorExtensionSeverityLevel
     * @param OutputParserConfiguration|null $outputParserConfiguration
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    public function validate(
        SourceHandler $sourceHandler,
        string $vendorExtensionSeverityLevel,
        ?OutputParserConfiguration $outputParserConfiguration = null
    ): OutputInterface {
        $webPage = $sourceHandler->getWebPage();
        $sourceMap = $sourceHandler->getSourceMap();

        $webPageUri = (string) $webPage->getUri();
        $webPageLocalPath = $sourceMap->getLocalPath($webPageUri);

        if (empty($webPageLocalPath)) {
            throw new UnknownSourceException($webPageUri);
        }

        $resourceStorage = $this->sourcePreparer->prepare($webPage, $sourceMap);

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

        $resourceStorage->deleteAll();

        return $output;
    }
}
