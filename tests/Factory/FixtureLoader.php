<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

class FixtureLoader
{
    public static function load(string $name)
    {
        return file_get_contents(self::getPath($name));
    }

    public static function getPath(string $name)
    {
        $fixturePath = realpath(__DIR__ . '/../Fixtures/' . $name);

        if (empty($fixturePath)) {
            throw new \RuntimeException(sprintf(
                'Unknown fixture %s',
                $name
            ));
        }

        return $fixturePath;
    }
}
