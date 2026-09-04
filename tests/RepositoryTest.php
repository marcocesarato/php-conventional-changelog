<?php

namespace Tests;

use ConventionalChangelog\Git\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /** @test */
    public function testTagEscapesNameAndAnnotation(): void
    {
        $commands = [];
        $exec = $this->getFunctionMock('ConventionalChangelog\Git', 'exec');
        $exec->expects($this->once())->willReturnCallback(
            function ($command, &$output = null, &$exitCode = null) use (&$commands) {
                $commands[] = $command;
                $output = [];
                $exitCode = 0;

                return '';
            }
        );

        $name = 'v1.0.0$(echo unsafe)';
        $annotation = 'Release "$(echo unsafe)"';

        Repository::tag($name, $annotation);

        $this->assertSame(
            'git tag -a -m ' . escapeshellarg($annotation) . ' -- ' . escapeshellarg($name),
            $commands[0]
        );
    }

    /** @test */
    public function testCommitReturnsFalseWhenGitFails(): void
    {
        $exec = $this->getFunctionMock('ConventionalChangelog\Git', 'exec');
        $exec->expects($this->once())->willReturnCallback(
            function ($command, &$output = null, &$exitCode = null) {
                $output = ['commit failed'];
                $exitCode = 1;

                return '';
            }
        );

        $this->assertFalse(Repository::commit('chore(release): 1.0.0'));
    }

    /** @test */
    public function testAddEscapesFilePaths(): void
    {
        $commands = [];
        $system = $this->getFunctionMock('ConventionalChangelog\Git', 'system');
        $system->expects($this->once())->willReturnCallback(
            function ($command, &$exitCode = null) use (&$commands) {
                $commands[] = $command;
                $exitCode = 0;

                return '';
            }
        );

        $path = 'CHANGELOG.md; echo unsafe';

        $this->assertTrue(Repository::add([$path]));
        $this->assertSame('git add -- ' . escapeshellarg($path), $commands[0]);
    }
}