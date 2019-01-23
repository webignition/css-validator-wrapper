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
    private $sourceStorage;
    private $outputMutator;
    private $commandFactory;
    private $outputParser;
    private $javaExecutablePath;
    private $cssValidatorJarPath;

    public function __construct(
        SourceStorage $sourceStorage,
        OutputMutator $outputMutator,
        CommandFactory $commandFactory,
        OutputParser $outputParser,
        string $javaExecutablePath,
        string $cssValidatorJarPath
    ) {
        $this->sourceStorage = $sourceStorage;
        $this->outputMutator = $outputMutator;
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
        $sourceInspector = $sourceHandler->getInspector();

        $webPageUri = (string) $webPage->getUri();
        $webPageSource = $sourceMap->getByUri($webPageUri);

        if (empty($webPageSource)) {
            throw new UnknownSourceException($webPageUri);
        }

        $embeddedStylesheetUrls = $sourceInspector->findStylesheetUrls();
        foreach ($embeddedStylesheetUrls as $stylesheetUrl) {
            if (!$sourceMap->getByUri($stylesheetUrl)) {
                throw new UnknownSourceException($stylesheetUrl);
            }
        }

        $importedStylesheetUrls = [];
        $importSources = $sourceMap->byType(SourceType::TYPE_IMPORT);
        foreach ($importSources as $importSource) {
            $importedStylesheetUrls[] = $importSource->getUri();
        }

        $stylesheetUrls = array_unique(array_merge($embeddedStylesheetUrls, $importedStylesheetUrls));

        $sourceMutator = $sourceHandler->getMutator();

        $stylesheetReferences = $sourceInspector->findStylesheetReferences();
        $mutatedWebPage = $sourceMutator->replaceStylesheetUrls($stylesheetReferences);

        $this->sourceStorage->store($mutatedWebPage, $sourceMap, $stylesheetUrls);

        $localSources = $this->sourceStorage->getSources();
        $webPageLocalSource = $localSources[$webPageUri];

        $webPageLocalUri = 'file:' . $webPageLocalSource->getMappedUri();

        $command = $this->commandFactory->create(
            $webPageLocalUri,
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

        if ($output->isValidationOutput()) {
            $output = $this->outputMutator->setObservationResponseRef($output, $webPageUri);
            $output = $this->outputMutator->setMessagesRef($output, $localSources);
        }

        $this->sourceStorage->deleteAll();

        return $output;
    }
}
