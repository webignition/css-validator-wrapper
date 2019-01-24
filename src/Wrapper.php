<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;

class Wrapper
{
    private $sourceStorage;
    private $outputMutator;
    private $commandFactory;
    private $commandExecutor;

    public function __construct(
        SourceStorage $sourceStorage,
        OutputMutator $outputMutator,
        CommandFactory $commandFactory,
        CommandExecutor $commandExecutor
    ) {
        $this->sourceStorage = $sourceStorage;
        $this->outputMutator = $outputMutator;
        $this->commandFactory = $commandFactory;
        $this->commandExecutor = $commandExecutor;
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

        $command = $this->commandFactory->create($webPageLocalSource->getMappedUri(), $vendorExtensionSeverityLevel);
        $output = $this->commandExecutor->execute($command, $outputParserConfiguration);

        if ($output instanceof ValidationOutput) {
            $output = $this->outputMutator->setObservationResponseRef($output, $webPageUri);
            $output = $this->outputMutator->setMessagesRef($output, $localSources);
        }

        $this->sourceStorage->deleteAll();

        return $output;
    }
}
