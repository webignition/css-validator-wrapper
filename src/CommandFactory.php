<?php

namespace webignition\CssValidatorWrapper;

class CommandFactory
{
    const JAVA_JAR_FLAG = '-jar';
    const OUTPUT_FORMAT = 'ucn';

    public function create(
        string $url,
        string $javaExecutablePath,
        string $cssValidatorJarPath,
        string $vendorExtensionSeverityLevel
    ): string {
        $options = [
            'output' => self::OUTPUT_FORMAT,
            'vextwarning' => VendorExtensionSeverityLevel::LEVEL_WARN === $vendorExtensionSeverityLevel
                ? 'true'
                : 'false'
        ];

        $commandParts = [
            $javaExecutablePath,
            self::JAVA_JAR_FLAG,
            $cssValidatorJarPath,
            $this->createOptionsString($options),
            '"'.str_replace('"', '\"', $url).'"',
            '2>&1'
        ];

        return implode(' ', $commandParts);
    }

    private function createOptionsString(array $options): string
    {
        $commandOptionsStrings = [];
        foreach ($options as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }

        return implode(' ', $commandOptionsStrings);
    }
}
