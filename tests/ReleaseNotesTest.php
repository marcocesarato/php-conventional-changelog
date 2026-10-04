<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ReleaseNotesTest extends TestCase
{
    /** @dataProvider releaseNotesProvider */
    public function testReleaseNotes(string $tag, string $changelog, int $expectedStatus, string $expectedOutput): void
    {
        $path = tempnam(sys_get_temp_dir(), 'release-notes-');
        file_put_contents($path, $changelog);

        try {
            $command = escapeshellarg(PHP_BINARY)
                . ' ' . escapeshellarg(dirname(__DIR__) . '/.github/scripts/release-notes.php')
                . ' ' . escapeshellarg($tag)
                . ' ' . escapeshellarg($path)
                . ' 2>&1';
            exec($command, $output, $status);

            $this->assertSame($expectedStatus, $status, implode(PHP_EOL, $output));
            $this->assertSame($expectedOutput, implode("\n", $output));
        } finally {
            unlink($path);
        }
    }

    public function releaseNotesProvider(): array
    {
        $section = "## [1.19.1](https://example.com/compare/v1.19.0...v1.19.1) (2026-10-04)\n\n### Bug Fixes\n\n* Ship required files\n\n\n---\n\n";
        $previous = "## [1.19.0](https://example.com/compare/v1.18.2...v1.19.0) (2026-09-06)\n\n* Previous release\n";

        return [
            'only the requested section' => ['v1.19.1', "# Changelog\n\n" . $section . $previous, 0, "### Bug Fixes\n\n* Ship required files"],
            'CRLF input' => ['v1.19.1', str_replace("\n", "\r\n", $section), 0, "### Bug Fixes\n\n* Ship required files"],
            'final section without separator' => ['v1.19.0', $previous, 0, '* Previous release'],
            'missing section' => ['v1.19.2', $section, 1, 'No changelog section found for v1.19.2.'],
            'empty section' => ['v1.19.1', "## [1.19.1](https://example.com)\n\n---\n", 1, 'The release notes are empty.'],
            'invalid tag' => ['1.19.1', $section, 1, 'Expected a stable vMAJOR.MINOR.PATCH tag.'],
            'prerelease tag' => ['v1.19.1-rc.1', $section, 1, 'Expected a stable vMAJOR.MINOR.PATCH tag.'],
            'leading zero' => ['v01.19.1', $section, 1, 'Expected a stable vMAJOR.MINOR.PATCH tag.'],
        ];
    }
}
