<?php

namespace Tests;

use ConventionalChangelog\Changelog;
use ConventionalChangelog\Configuration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ChangelogTest extends TestCase
{
    /** @test */
    public function can_have_current_changelog_without_header()
    {
        $configuration = $this->createMock(Configuration::class);

        $changelog = new Changelog($configuration);

        $class = new ReflectionClass($changelog);
        $method = $class->getMethod('extractCurrentChangelogWithoutHeader');
        $method->setAccessible(true);

        $content = <<<EOF
<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## 0.0.1 (1970-01-01)

### Features

* Here we go !

EOF;

        $expectedContent = <<<EOF


## 0.0.1 (1970-01-01)

### Features

* Here we go !

EOF;

        $actualContent = $method->invokeArgs(
            $changelog,
            [$content]
        );

        $this->assertEquals($expectedContent, $actualContent);
    }
}