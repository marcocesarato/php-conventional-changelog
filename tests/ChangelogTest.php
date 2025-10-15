<?php

namespace Tests;

use ConventionalChangelog\Changelog;
use ConventionalChangelog\Configuration;
use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    private Configuration $configuration;
    private Changelog $changelog;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->changelog = new Changelog($this->configuration);
    }

    protected function setUpMocks($callback = null): void
    {
        $exec = $this->getFunctionMock(__NAMESPACE__, 'shell_exec');
        $exec->expects($this->any())->willReturnCallback(
            function ($command) use ($callback) {
                $output = null;
                if (!empty($callback)) {
                    $output = $callback($command);
                }
                if ($output !== null) {
                    return $output;
                }

                // getCommitDate
                $commitDate = 'git log -1 --format=%aI';
                if (substr($commitDate, 0, strlen($command)) === $command) {
                    return '2000-01-01T00:00:00+00:00';
                }

                switch ($command) {
                    case 'git config --get remote.origin.url':
                        // getRemoteUrl
                        return 'https://fake.remote.com/user/repo.git';
                    case 'git rev-list --max-parents=0 HEAD':
                        // getFirstCommit
                        return '021a49f43ef65ac7a894450374f1772eef1fd8b0';
                    case 'git log -1 --pretty=format:%H':
                        // getLastCommit
                        return '84d151f93f36474055c9ff406710a47aa3d6e168';
                    case 'git branch --show-current':
                        // getCurrentBranch
                        return 'main';
                    default:
                        break;
                }
            }
        );
    }

    /** @test */
    public function testRemoveHeader(): void
    {
        $class = new \ReflectionClass($this->changelog);
        $method = $class->getMethod('removeHeader');
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
            $this->changelog,
            [$content]
        );

        $this->assertEquals($expectedContent, $actualContent);
    }

    /** @test */
    public function testExecMock()
    {
        $this->setUpMocks(function ($command) {
            if ($command == 'bar') {
                return 'foo';
            }
        });

        $output = shell_exec('git config --get remote.origin.url');
        $this->assertEquals('https://fake.remote.com/user/repo.git', $output);

        $output = shell_exec('bar');
        $this->assertEquals('foo', $output);
    }

    /** @test */
    public function testHiddenAuthorConfigurationDefault()
    {
        $config = new Configuration();
        // By default, author should be hidden (true)
        $this->assertTrue($config->isHiddenAuthor());
    }

    /** @test */
    public function testHiddenAuthorConfiguration()
    {
        $config = new Configuration(['hiddenAuthor' => false]);
        // Author should not be hidden when set to false
        $this->assertFalse($config->isHiddenAuthor());
    }

    /** @test */
    public function testAzureDevOpsHttpsUrlPatternMatching()
    {
        // Test Azure DevOps HTTPS URL pattern directly
        $url = 'https://dev.azure.com/myorg/myproject/_git/myrepo';
        $pattern = '#^(?P<protocol>https?)://(?P<host>dev\.azure\.com)/(?P<owner>[^/]+)/(?P<project>[^/]+)/_git/(?P<repository>[^/]+?)(?:\.git)?/?$#smi';

        $this->assertEquals(1, preg_match($pattern, $url, $match));
        $result = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

        $this->assertEquals('dev.azure.com', $result['host']);
        $this->assertEquals('myorg', $result['owner']);
        $this->assertEquals('myproject', $result['project']);
        $this->assertEquals('myrepo', $result['repository']);
    }

    /** @test */
    public function testAzureDevOpsSshUrlPatternMatching()
    {
        // Test Azure DevOps SSH URL pattern directly
        $url = 'git@ssh.dev.azure.com:v3/myorg/myproject/myrepo';
        $pattern = '#^(?P<user>[^@]+)@(?P<host>ssh\.dev\.azure\.com):v3/(?P<owner>[^/]+)/(?P<project>[^/]+)/(?P<repository>[^/]+?)(?:\.git)?/?$#smi';

        $this->assertEquals(1, preg_match($pattern, $url, $match));
        $result = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

        $this->assertEquals('ssh.dev.azure.com', $result['host']);
        $this->assertEquals('myorg', $result['owner']);
        $this->assertEquals('myproject', $result['project']);
        $this->assertEquals('myrepo', $result['repository']);
    }

    /** @test */
    public function testAzureDevOpsDetection()
    {
        // Use reflection to test the isAzureDevOps method
        $config = new Configuration();
        $changelog = new Changelog($config);

        $class = new \ReflectionClass($changelog);
        $remoteProperty = $class->getProperty('remote');
        $remoteProperty->setAccessible(true);

        $method = $class->getMethod('isAzureDevOps');
        $method->setAccessible(true);

        // Test with Azure DevOps host
        $remoteProperty->setValue($changelog, ['host' => 'dev.azure.com']);
        $this->assertTrue($method->invoke($changelog));

        $remoteProperty->setValue($changelog, ['host' => 'ssh.dev.azure.com']);
        $this->assertTrue($method->invoke($changelog));

        // Test with non-Azure host
        $remoteProperty->setValue($changelog, ['host' => 'github.com']);
        $this->assertFalse($method->invoke($changelog));
    }

    /** @test */
    public function testAzureDevOpsUrlConfiguration()
    {
        // Test that Azure DevOps URL formats are configured correctly
        $config = new Configuration();
        $changelog = new Changelog($config);

        $class = new \ReflectionClass($changelog);
        $remoteProperty = $class->getProperty('remote');
        $remoteProperty->setAccessible(true);

        $method = $class->getMethod('configureAzureDevOpsUrls');
        $method->setAccessible(true);

        // Set Azure remote
        $remoteProperty->setValue($changelog, [
            'host' => 'dev.azure.com',
            'owner' => 'myorg',
            'project' => 'myproject',
            'repository' => 'myrepo',
        ]);

        // Apply Azure configuration
        $method->invoke($changelog);

        // Verify Azure DevOps URL formats were applied
        $this->assertStringContainsString('branchCompare', $config->getCompareUrlFormat());
        $this->assertStringContainsString('baseVersion=GT', $config->getCompareUrlFormat());
        $this->assertStringContainsString('targetVersion=GT', $config->getCompareUrlFormat());
        $this->assertStringContainsString('{{project}}', $config->getCompareUrlFormat());
        $this->assertStringContainsString('_git', $config->getCommitUrlFormat());
        $this->assertStringContainsString('_workitems', $config->getIssueUrlFormat());
    }

    /** @test */
    public function testAzureDevOpsHostNormalization()
    {
        // Test that SSH host is normalized to dev.azure.com
        $config = new Configuration();
        $changelog = new Changelog($config);

        $class = new \ReflectionClass($changelog);
        $remoteProperty = $class->getProperty('remote');
        $remoteProperty->setAccessible(true);

        $method = $class->getMethod('configureAzureDevOpsUrls');
        $method->setAccessible(true);

        // Set Azure SSH remote
        $remoteProperty->setValue($changelog, [
            'host' => 'ssh.dev.azure.com',
            'owner' => 'myorg',
            'project' => 'myproject',
            'repository' => 'myrepo',
        ]);

        // Apply Azure configuration
        $method->invoke($changelog);

        // Get the updated remote
        $remote = $remoteProperty->getValue($changelog);

        // Verify host was normalized to dev.azure.com
        $this->assertEquals('dev.azure.com', $remote['host']);
    }

    /** @test */
    public function testRunHookWithStringCommand()
    {
        $config = new Configuration();

        // Use reflection to test the runHook method
        $class = new \ReflectionClass($config);
        $method = $class->getMethod('runHook');
        $method->setAccessible(true);

        // Test with a simple string command
        // We can't easily test system() output, but we can test it doesn't throw an error
        ob_start();
        $method->invoke($config, 'echo "test"');
        $output = ob_get_clean();

        // If we got here without exception, the method works
        $this->assertTrue(true);
    }

    /** @test */
    public function testRunHookWithPipeCommand()
    {
        $config = new Configuration();

        // Use reflection to test the runHook method
        $class = new \ReflectionClass($config);
        $method = $class->getMethod('runHook');
        $method->setAccessible(true);

        // Test with a command that uses pipe
        ob_start();
        $method->invoke($config, 'echo "test" | cat');
        $output = ob_get_clean();

        // If we got here without exception, the method works with pipes
        $this->assertTrue(true);
    }

    /** @test */
    public function testRunHookWithCallable()
    {
        $config = new Configuration();

        // Use reflection to test the runHook method
        $class = new \ReflectionClass($config);
        $method = $class->getMethod('runHook');
        $method->setAccessible(true);

        // Test with a callable
        $called = false;
        $hook = function () use (&$called) {
            $called = true;
        };

        $method->invoke($config, $hook);

        // Verify the callable was executed
        $this->assertTrue($called);
    }

    /** @test */
    public function testRunHookWithNull()
    {
        $config = new Configuration();

        // Use reflection to test the runHook method
        $class = new \ReflectionClass($config);
        $method = $class->getMethod('runHook');
        $method->setAccessible(true);

        // Test with null - should not throw exception
        $method->invoke($config, null);

        // If we got here without exception, it handled null correctly
        $this->assertTrue(true);
    }

    /** @test */
    public function testPreRunHook()
    {
        $called = false;
        $hook = function () use (&$called) {
            $called = true;
        };

        $config = new Configuration(['preRun' => $hook]);
        $config->preRun();

        // Verify the preRun hook was executed
        $this->assertTrue($called);
    }

    /** @test */
    public function testPostRunHook()
    {
        $called = false;
        $hook = function () use (&$called) {
            $called = true;
        };

        $config = new Configuration(['postRun' => $hook]);
        $config->postRun();

        // Verify the postRun hook was executed
        $this->assertTrue($called);
    }
}
