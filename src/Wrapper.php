<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Flags;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\IgnoredUrlVerifier\IgnoredUrlVerifier;
use webignition\ResourceStorage\SourcePurger;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\ContentEncodingValidator;
use webignition\WebResource\WebPage\WebPage;

class Wrapper
{
    private $sourceInspector;
    private $sourceMutator;
    private $sourceStorage;
    private $outputMutator;
    private $commandFactory;
    private $commandExecutor;

    public function __construct(
        SourceInspector $sourceInspector,
        SourceMutator $sourceMutator,
        SourceStorage $sourceStorage,
        OutputMutator $outputMutator,
        CommandFactory $commandFactory,
        CommandExecutor $commandExecutor
    ) {
        $this->sourceInspector = $sourceInspector;
        $this->sourceMutator = $sourceMutator;
        $this->sourceStorage = $sourceStorage;
        $this->outputMutator = $outputMutator;
        $this->commandFactory = $commandFactory;
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * @param WebPage $webPage
     * @param SourceMap $remoteSources
     * @param string $vendorExtensionSeverityLevel
     * @param array $hostsToExclude
     * @param int $outputParserFlags
     *
     * @return OutputInterface
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    public function validate(
        WebPage $webPage,
        SourceMap $remoteSources,
        string $vendorExtensionSeverityLevel,
        array $hostsToExclude = [],
        int $outputParserFlags = Flags::NONE
    ): OutputInterface {
        $contentEncodingValidator = new ContentEncodingValidator();
        if (!$contentEncodingValidator->isValid($webPage)) {
            $webPage = $contentEncodingValidator->convertToUtf8($webPage);
        }

        $webPageUri = (string) $webPage->getUri();
        $ignoredUrlVerifier = new IgnoredUrlVerifier();

        $embeddedStylesheetUrls = $this->sourceInspector->findStylesheetUrls($webPage);
        foreach ($embeddedStylesheetUrls as $stylesheetUrl) {
            $isUrlIgnored = $ignoredUrlVerifier->isUrlIgnored($stylesheetUrl, [
                IgnoredUrlVerifier::EXCLUSIONS_HOSTS => $hostsToExclude,
            ]);

            if (!$isUrlIgnored && !$remoteSources->getByUri($stylesheetUrl)) {
                throw new UnknownSourceException($stylesheetUrl);
            }
        }

        $importedStylesheetUrls = [];
        $importSources = $remoteSources->byType(SourceType::TYPE_IMPORT);
        foreach ($importSources as $importSource) {
            $importedStylesheetUrls[] = $importSource->getUri();
        }

        $stylesheetUrls = array_unique(array_merge($embeddedStylesheetUrls, $importedStylesheetUrls));
        $stylesheetReferences = $this->sourceInspector->findStylesheetReferences($webPage);

        $localSources = new SourceMap();
        $localSources = $this->sourceStorage->storeCssResources($remoteSources, $localSources, $stylesheetUrls);

        $mutatedWebPage = $this->sourceMutator->replaceStylesheetUrls($webPage, $localSources, $stylesheetReferences);

        $localSources = $this->sourceStorage->storeWebPage($mutatedWebPage, $localSources);
        $webPageLocalSource = $localSources[$webPageUri];

        $command = $this->commandFactory->create($webPageLocalSource->getMappedUri(), $vendorExtensionSeverityLevel);
        $output = $this->commandExecutor->execute($command, $outputParserFlags);

        if ($output instanceof ValidationOutput) {
            foreach ($importedStylesheetUrls as $importedStylesheetUrl) {
                if ($ignoredUrlVerifier->isUrlIgnored($importedStylesheetUrl, $hostsToExclude)) {
                    continue;
                }

                $stylesheetLocalSource = $localSources->getByUri($importedStylesheetUrl);

                $command = $this->commandFactory->create(
                    $stylesheetLocalSource->getMappedUri(),
                    $vendorExtensionSeverityLevel
                );

                $importedStylesheetOutput = $this->commandExecutor->execute($command, $outputParserFlags);

                if ($importedStylesheetOutput instanceof ValidationOutput) {
                    $importedStylesheetOutput = $this->outputMutator->setMessagesRefFromUrl(
                        $importedStylesheetOutput,
                        $stylesheetLocalSource->getMappedUri()
                    );

                    $output = $output->withObservationResponse(
                        $output->getObservationResponse()->withMessages(
                            $output->getMessages()->merge($importedStylesheetOutput->getMessages())
                        )
                    );
                }
            }

            $output = $this->outputMutator->setObservationResponseRef($output, $webPageUri);
            $output = $this->outputMutator->setMessagesRefFromSourceMap($output, $localSources);
        }

        $sourcePurger = new SourcePurger();
        $sourcePurger->purgeLocalResources($localSources);

        return $output;
    }
}
