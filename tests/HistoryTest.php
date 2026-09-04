<?php

namespace Tests;

use ConventionalChangelog\DefaultCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class HistoryTest extends TestCase
{
    private string $originalWorkingDirectory;
    private string $repositoryDirectory;

    protected function setUp(): void
    {
        $this->originalWorkingDirectory = getcwd();
        $this->repositoryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-conventional-changelog-' . uniqid();

        mkdir($this->repositoryDirectory, 0777, true);
        chdir($this->repositoryDirectory);

        $this->runGit('init --quiet');
        $this->runGit('config user.email "tests@example.com"');
        $this->runGit('config user.name "Test User"');
    }

    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);
        $this->removeDirectory($this->repositoryDirectory);
    }

    /** @test */
    public function testHistoryIncludesCommitsAfterLatestTag(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "chore: initial commit"');

        file_put_contents('tracked.txt', "tagged\n", FILE_APPEND);
        $this->runGit('commit --quiet -am "feat: tagged feature"');
        $this->runGit('tag v1.0.0');

        file_put_contents('tracked.txt', "unreleased\n", FILE_APPEND);
        $this->runGit('commit --quiet -am "fix: unreleased fix"');

        $changelog = $this->generateHistory();
        $this->assertStringContainsString('Tagged feature', $changelog);
        $this->assertStringContainsString('Unreleased fix', $changelog);
        $this->assertStringContainsString('1.0.1', $changelog);
    }

    /** @test */
    public function testHistoryIncludesRootCommitWithoutEmptyFutureRelease(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "feat: first feature"');
        $this->runGit('tag v1.0.0');

        $changelog = $this->generateHistory();

        $this->assertStringContainsString('First feature', $changelog);
        $this->assertStringContainsString('1.0.0', $changelog);
        $this->assertStringNotContainsString('1.0.1', $changelog);
    }

    /** @test */
    public function testHistoryDoesNotCreateReleaseForFilteredCommits(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "feat: first feature"');
        $this->runGit('tag v1.0.0');

        file_put_contents('tracked.txt', "maintenance\n", FILE_APPEND);
        $this->runGit('commit --quiet -am "chore: maintenance"');

        $changelog = $this->generateHistory();

        $this->assertStringNotContainsString('1.0.1', $changelog);
    }

    /** @test */
    public function testHistorySemanticallyBumpsPostTagFeatures(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "feat: first feature"');
        $this->runGit('tag v1.0.0');

        file_put_contents('tracked.txt', "feature\n", FILE_APPEND);
        $this->runGit('commit --quiet -am "feat: unreleased feature"');

        $changelog = $this->generateHistory();

        $this->assertStringContainsString('Unreleased feature', $changelog);
        $this->assertStringContainsString('1.1.0', $changelog);
        $this->assertStringNotContainsString('1.0.1', $changelog);
    }

    /** @test */
    public function testHistoryDoesNotDuplicateCommitsFromDivergentTags(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "chore: initial commit"');
        $currentBranch = trim(shell_exec('git branch --show-current'));

        $this->runGit('checkout --quiet -b old-release');
        file_put_contents('legacy.txt', "legacy\n");
        $this->runGit('add legacy.txt');
        $this->runGit('commit --quiet -m "feat: legacy feature"');
        $this->runGit('tag v1.0.0');

        $this->runGit('checkout --quiet ' . escapeshellarg($currentBranch));
        file_put_contents('current.txt', "current\n");
        $this->runGit('add current.txt');
        $this->runGit('commit --quiet -m "fix: current fix"');

        $changelog = $this->generateHistory();

        $this->assertSame(1, substr_count($changelog, 'Legacy feature'));
        $this->assertStringContainsString('Current fix', $changelog);
    }

    /** @test */
    public function testHistoryDoesNotCreateReleaseWhenOnlyFilteredCommitsRemain(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        file_put_contents('composer.json', json_encode(['version' => '1.0.0']));
        $this->runGit('add tracked.txt composer.json');
        $this->runGit('commit --quiet -m "feat: first feature"');
        $this->runGit('tag v1.0.0');

        file_put_contents('tracked.txt', "maintenance\n", FILE_APPEND);
        $this->runGit('commit --quiet -am "chore: maintenance"');

        $changelog = $this->generateHistory(true, true);
        $package = json_decode(file_get_contents('composer.json'), true);
        $tags = preg_split('/\r?\n/', trim(shell_exec('git tag --list')));

        $this->assertStringNotContainsString('1.0.1', $changelog);
        $this->assertEquals('1.0.0', $package['version']);
        $this->assertEquals(['v1.0.0'], $tags);
    }

    /** @test */
    public function testHistorySectionsAreDisjointAcrossDivergentTags(): void
    {
        file_put_contents('tracked.txt', "initial\n");
        $this->runGit('add tracked.txt');
        $this->runGit('commit --quiet -m "chore: initial commit"');
        $currentBranch = trim(shell_exec('git branch --show-current'));

        $this->runGit('checkout --quiet -b first-release');
        file_put_contents('first.txt', "first\n");
        $this->runGit('add first.txt');
        $this->runGit('commit --quiet -m "feat: first release feature"');
        $this->runGit('tag v1.0.0');

        $this->runGit('checkout --quiet ' . escapeshellarg($currentBranch));
        $this->runGit('checkout --quiet -b second-release');
        file_put_contents('second.txt', "second\n");
        $this->runGit('add second.txt');
        $this->runGit('commit --quiet -m "feat: second release feature"');
        $this->runGit('tag v2.0.0');

        $this->runGit('checkout --quiet ' . escapeshellarg($currentBranch));
        $this->runGit('merge --quiet --no-edit first-release');
        file_put_contents('current.txt', "current\n");
        $this->runGit('add current.txt');
        $this->runGit('commit --quiet -m "fix: current fix"');

        $changelog = $this->generateHistory();

        $this->assertSame(1, substr_count($changelog, 'First release feature'));
        $this->assertSame(1, substr_count($changelog, 'Second release feature'));
        $this->assertStringContainsString('Current fix', $changelog);
    }

    private function generateHistory(bool $packageBump = false, bool $commit = false): string
    {
        $configPath = $this->repositoryDirectory . DIRECTORY_SEPARATOR . '.changelog.php';
        $changelogPath = $this->repositoryDirectory . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
        file_put_contents(
            $configPath,
            '<?php return ' . var_export([
                'path' => $changelogPath,
                'packageBump' => $packageBump,
            ], true) . ';'
        );

        $application = new Application('test', '1.0.0');
        $application->setAutoExit(false);
        $command = new DefaultCommand();
        if (method_exists($application, 'addCommand')) {
            $application->addCommand($command);
        } else {
            $application->add($command);
        }

        $arguments = [
            'command' => 'changelog',
            'path' => $this->repositoryDirectory,
            '--config' => $configPath,
            '--history' => true,
        ];
        if ($commit) {
            $arguments['--commit'] = true;
        }
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput();

        $this->assertSame(0, $application->run($input, $output), $output->fetch());

        return file_get_contents($changelogPath);
    }

    private function runGit(string $arguments): void
    {
        exec('git ' . $arguments . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            chmod($path->getPathname(), 0777);
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        chmod($directory, 0777);
        rmdir($directory);
    }
}
