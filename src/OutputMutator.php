<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\AbstractIssueMessage;
use webignition\CssValidatorOutput\Model\AbstractMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ValidationOutput;

class OutputMutator
{
    public function setObservationResponseRef(ValidationOutput $output, string $webPageUri): ValidationOutput
    {
        $observationResponse = $output->getObservationResponse();
        $observationResponse = $observationResponse->withRef($webPageUri);

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }

    public function setMessagesRef(ValidationOutput $output, SourceMap $localLinkedSources): ValidationOutput
    {
        $mutator = function (AbstractMessage $message) use ($localLinkedSources) {
            if ($message instanceof AbstractIssueMessage) {
                $source = $localLinkedSources->getByLocalUri($message->getRef());

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

    public function removeMessagesWithRef(ValidationOutput $output, string $ref): ValidationOutput
    {
        $filter = function (AbstractMessage $message) use ($ref) {
            if (!$message instanceof AbstractIssueMessage) {
                return true;
            }

            return $ref !== $message->getRef();
        };

        return $this->modifyMessages(
            $output,
            function (MessageList $messageList) use ($filter): MessageList {
                return $messageList->filter($filter);
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
