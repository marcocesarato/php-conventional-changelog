<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class GitHubActionTest extends TestCase
{
    /** @test */
    public function testActionMetadataDefinesSafeCompositeWrapper(): void
    {
        $actionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'action.yml';

        $this->assertFileExists($actionPath);

        $metadata = file_get_contents($actionPath);

        $this->assertIsString($metadata);
        $metadata = str_replace("\r\n", "\n", $metadata);
        $this->assertStringContainsString('name: PHP Conventional Changelog', $metadata);
        $this->assertStringContainsString('using: composite', $metadata);
        $this->assertStringContainsString('arguments:', $metadata);
        $this->assertStringContainsString("  php-version:\n    description:", $metadata);
        $this->assertStringContainsString('php-version: ${{ inputs.php-version }}', $metadata);
        $this->assertStringContainsString('working-directory:', $metadata);
        $this->assertStringContainsString('--no-dev --no-scripts', $metadata);
        $this->assertStringContainsString('"${arguments[@]}"', $metadata);
    }
}
