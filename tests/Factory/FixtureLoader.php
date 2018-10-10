<?php

namespace webignition\Tests\CssValidatorWrapper\Factory;

class FixtureLoader
{
    public static function load(string $name)
    {
        $fixturePath = realpath(__DIR__ . '/../Fixtures/' . $name);

        if (empty($fixturePath)) {
            throw new \RuntimeException(sprintf(
                'Unknown fixture %s',
                $name
            ));
        }

        return file_get_contents($fixturePath);
    }
}
