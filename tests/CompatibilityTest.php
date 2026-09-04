<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class CompatibilityTest extends TestCase
{
    /** @test */
    public function testCommitClassHasNoPhp84SignatureDeprecations(): void
    {
        $commitPath = dirname(__DIR__) . '/src/Git/Commit.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' -d error_reporting=E_ALL -d display_errors=1 -l '
            . escapeshellarg($commitPath)
            . ' 2>&1';
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
        $this->assertStringNotContainsString('Deprecated:', implode(PHP_EOL, $output));
    }
}
