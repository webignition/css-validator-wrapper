<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\AbstractIssueMessage;
use webignition\CssValidatorOutput\Model\AbstractMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\UrlSourceMap\SourceMap;

class OutputMutator
{
    public function setObservationResponseRef(ValidationOutput $output, string $webPageUri): ValidationOutput
    {
        $observationResponse = $output->getObservationResponse();
        $observationResponse = $observationResponse->withRef($webPageUri);

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }

    public function setMessagesRefFromSourceMap(
        ValidationOutput $output,
        SourceMap $localLinkedSources
    ): ValidationOutput {
        $mutator = function (AbstractMessage $message) use ($localLinkedSources) {
            if ($message instanceof AbstractIssueMessage) {
                $source = $localLinkedSources->getByMappedUri($message->getRef());

                if ($source) {
                    $message = $message->withRef($source->getUri());
                }
            }

            return $message;
        };

        return $this->modifyMessages(
            $output,
            function (MessageList $messageList) use ($mutator): MessageList {
                return $messageList->mutate($mutator);
            }
        );
    }

    public function setMessagesRefFromUrl(
        ValidationOutput $output,
        string $url
    ): ValidationOutput {
        $mutator = function (AbstractMessage $message) use ($url) {
            if ($message instanceof AbstractIssueMessage) {
                $message = $message->withRef($url);
            }

            return $message;
        };

        return $this->modifyMessages(
            $output,
            function (MessageList $messageList) use ($mutator): MessageList {
                return $messageList->mutate($mutator);
            }
        );
    }

    private function modifyMessages(ValidationOutput $output, callable $modifier)
    {
        $observationResponse = $output->getObservationResponse();
        $observationResponse = $observationResponse->withMessages($modifier($observationResponse->getMessages()));

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }
}
