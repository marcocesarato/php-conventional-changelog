<?php

namespace Tests;

use ConventionalChangelog\DefaultCommand;
use PHPUnit\Framework\TestCase;

class DefaultCommandTest extends TestCase
{
    /** @test */
    public function testSymfonyCommandReturnTypesAreCompatible(): void
    {
        $configure = new \ReflectionMethod(DefaultCommand::class, 'configure');
        $execute = new \ReflectionMethod(DefaultCommand::class, 'execute');

        $this->assertEquals('void', (string)$configure->getReturnType());
        $this->assertEquals('int', (string)$execute->getReturnType());
    }
}
