<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DevcontainerTest extends TestCase
{
    /** @test */
    public function testConfiguredVariantMatchesDockerfileDefault(): void
    {
        $projectRoot = dirname(__DIR__);
        $configuration = file_get_contents($projectRoot . '/.devcontainer/devcontainer.json');
        $dockerfile = file_get_contents($projectRoot . '/.devcontainer/Dockerfile');

        $this->assertIsString($configuration);
        $this->assertIsString($dockerfile);
        $this->assertMatchesRegularExpression('/"VARIANT"\s*:\s*"8\.4-bookworm"/', $configuration);
        $this->assertMatchesRegularExpression('/^ARG VARIANT=8\.4-bookworm\r?$/m', $dockerfile);
    }
}