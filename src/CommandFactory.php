<?php

namespace webignition\CssValidatorWrapper;

class CommandFactory
{
    const JAVA_JAR_FLAG = '-jar';
    const OUTPUT_FORMAT = 'ucn';

    private $javaExecutablePath;
    private $cssValidatorJarPath;


    public function __construct(string $javaExecutablePath, string $cssValidatorJarPath)
    {
        $this->javaExecutablePath = $javaExecutablePath;
        $this->cssValidatorJarPath = $cssValidatorJarPath;
    }

    public function create(string $url, string $vendorExtensionSeverityLevel): string
    {
        $options = [
            'output' => self::OUTPUT_FORMAT,
            'vextwarning' => VendorExtensionSeverityLevel::LEVEL_WARN === $vendorExtensionSeverityLevel
                ? 'true'
                : 'false'
        ];

        $commandParts = [
            $this->javaExecutablePath,
            self::JAVA_JAR_FLAG,
            $this->cssValidatorJarPath,
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
