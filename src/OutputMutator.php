<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\AbstractIssueMessage;
use webignition\CssValidatorOutput\Model\AbstractMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ValidationOutput;

class OutputMutator
{
    public function setObservationResponseRef(ValidationOutput $output, string $webPageUri)
    {
        $observationResponse = $output->getObservationResponse();
        $observationResponse = $observationResponse->withRef($webPageUri);

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }

    public function setMessagesRef(ValidationOutput $output, SourceMap $linkedResourcesMap)
    {
        $modifier = function (MessageList $messageList) use ($linkedResourcesMap): MessageList {
            return $messageList->mutate(function (AbstractMessage $message) use ($linkedResourcesMap) {
                if ($message instanceof AbstractIssueMessage) {
                    $message = $message->withRef(
                        $linkedResourcesMap->getSourcePath($message->getRef())
                    );
                }

                return $message;
            });
        };

        return $this->modifyMessages($output, $modifier);
    }

    public function removeMessagesWithRef(ValidationOutput $output, string $ref)
    {
        $modifier = function (MessageList $messageList) use ($ref): MessageList {
            return $messageList->filter(function (AbstractMessage $message) use ($ref) {
                if (!$message instanceof AbstractIssueMessage) {
                    return true;
                }

                return $ref !== $message->getRef();
            });
        };

        return $this->modifyMessages($output, $modifier);
    }

    private function modifyMessages(ValidationOutput $output, callable $modifier)
    {
        $observationResponse = $output->getObservationResponse();
        $observationResponse = $observationResponse->withMessages($modifier($observationResponse->getMessages()));

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }
}
