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

    public function testArchiveContainsOnlyRuntimeAndDocumentationFiles(): void
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'changelog-dist-');
        unlink($archivePath);
        $archivePath .= '.tar';

        try {
            exec('git -C ' . escapeshellarg(dirname(__DIR__)) . ' archive --format=tar --worktree-attributes --output=' . escapeshellarg($archivePath) . ' HEAD 2>&1', $output, $status);
            $this->assertSame(0, $status, implode(PHP_EOL, $output));

            $archive = new \PharData($archivePath);
            foreach (['bin/conventional-changelog', 'bin/autoload.php', 'src/DefaultCommand.php', 'composer.json', 'LICENSE', 'README.md', 'CHANGELOG.md'] as $path) {
                $this->assertTrue(isset($archive[$path]), $path . ' must be shipped');
            }
            foreach (new \RecursiveIteratorIterator($archive) as $file) {
                $path = str_replace('phar://' . str_replace('\\', '/', $archivePath) . '/', '', str_replace('\\', '/', $file->getPathname()));
                $this->assertMatchesRegularExpression('#^(bin/|src/|composer\\.json$|LICENSE$|README\\.md$|CHANGELOG\\.md$)#', $path);
            }
            unset($archive);
        } finally {
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    public function testSourceExecutableRuns(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/bin/conventional-changelog') . ' --version 2>&1', $output, $status);
        $this->assertSame(0, $status, implode(PHP_EOL, $output));
        $this->assertStringContainsString('conventional-changelog', implode(PHP_EOL, $output));
    }
}
