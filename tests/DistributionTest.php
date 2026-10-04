<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DistributionTest extends TestCase
{
    public function testDistributionAllowlist(): void
    {
        $root = dirname(__DIR__);
        $shipped = ['bin/conventional-changelog', 'bin/autoload.php', 'src/DefaultCommand.php', 'composer.json', 'LICENSE', 'README.md', 'CHANGELOG.md'];
        $excluded = ['.changelog', '.github/workflows/php.yml', 'tests/AutoloadTest.php', 'composer.lock', '.gitattributes', 'docs/README.md'];

        foreach (array_merge($shipped, $excluded) as $path) {
            exec('git -C ' . escapeshellarg($root) . ' check-attr export-ignore -- ' . escapeshellarg($path), $output, $status);
            $this->assertSame(0, $status);
            $this->assertSame($path . ': export-ignore: ' . (in_array($path, $shipped, true) ? 'unset' : 'set'), array_pop($output));
        }
    }

    public function testSourceExecutableRuns(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/bin/conventional-changelog') . ' --version 2>&1', $output, $status);
        $this->assertSame(0, $status, implode(PHP_EOL, $output));
        $this->assertStringContainsString('conventional-changelog', implode(PHP_EOL, $output));
    }
}
