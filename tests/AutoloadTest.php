<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    /** @test */
    public function testComposerProxyAutoloadPathIsLoaded(): void
    {
        $autoloadPath = tempnam(sys_get_temp_dir(), 'composer-autoload-');
        file_put_contents($autoloadPath, '<?php $GLOBALS["composerProxyAutoloaded"] = true;');
        $_composer_autoload_path = $autoloadPath;

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoload.php';

        $this->assertTrue($GLOBALS['composerProxyAutoloaded'] ?? false);

        unlink($autoloadPath);
        unset($GLOBALS['composerProxyAutoloaded']);
    }
}
