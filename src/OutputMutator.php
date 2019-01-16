<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorOutput\Model\AbstractIssueMessage;
use webignition\CssValidatorOutput\Model\AbstractMessage;
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
        $observationResponse = $output->getObservationResponse();

        $messages = $observationResponse->getMessages();

        $messageMutator = function (AbstractMessage $message) use ($linkedResourcesMap) {
            if ($message instanceof AbstractIssueMessage) {
                $message = $message->withRef(
                    $linkedResourcesMap->getSourcePath($message->getRef())
                );
            }

            return $message;
        };

        $observationResponse = $observationResponse->withMessages($messages->mutate($messageMutator));

        $output = $output->withObservationResponse($observationResponse);

        return $output;
    }
}
