<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\Utils\TemporaryAssetRegistry;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class HtmlImportP2ATest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-html-p2a-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        TemporaryAssetRegistry::cleanup();
        $this->removeDirectory($this->temporaryDirectory);
    }

    public function testLocalImageStillImportsByDefault(): void
    {
        $localPath = dirname(__DIR__, 2) . '/assets/banner.png';
        $richText = HtmlImporter::fromHtml('<p>Local</p><img src="' . $localPath . '">');

        self::assertCount(1, $richText->getImageAssets());
        self::assertSame(realpath($localPath), $richText->getImageAssets()[0]['path']);
    }

    public function testBase64ImageCreatesTemporaryAssetAndIsEmbedded(): void
    {
        $source = dirname(__DIR__, 2) . '/assets/banner.png';
        $dataUrl = 'data:image/png;base64,' . base64_encode((string) file_get_contents($source));
        $richText = HtmlImporter::fromHtml('<img src="' . $dataUrl . '">');
        $assets = $richText->getImageAssets();

        self::assertCount(1, $assets);
        self::assertFileExists($assets[0]['path']);

        $output = $this->temporaryDirectory . '/base64.odt';
        $template = new \OdtTemplateEngine\OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt'
        );
        $template->setElement('my_list', $richText);
        $template->save($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        try {
            self::assertNotFalse($zip->locateName('Pictures/' . basename($assets[0]['path'])));
        } finally {
            $zip->close();
        }
    }

    public function testInvalidBase64ImageIsIgnoredSafely(): void
    {
        $richText = HtmlImporter::fromHtml(
            '<img src="data:image/png;base64,not-valid-base64!!!">'
        );

        self::assertSame([], $richText->getImageAssets());
    }

    public function testMissingLocalImageAndMalformedDataImageFailSafely(): void
    {
        self::assertSame([], HtmlImporter::fromHtml(
            '<img src="' . $this->temporaryDirectory . '/missing.png">'
        )->getImageAssets());

        self::assertSame([], HtmlImporter::fromHtml(
            '<img src="data:image/png;base64,@@@@">'
        )->getImageAssets());
    }

    public function testRemoteImagesAreDisabledByDefaultAndEnabledExplicitly(): void
    {
        $fixture = dirname(__DIR__, 2) . '/assets/banner.png';
        $server = $this->startHttpServer($fixture);

        try {
            $html = '<img src="' . $server['url'] . '/image.png">';

            self::assertSame([], HtmlImporter::fromHtml($html)->getImageAssets());
            self::assertFileDoesNotExist($server['request_marker']);

            $assets = HtmlImporter::fromHtml($html, ['allow_remote_images' => true])->getImageAssets();
            self::assertCount(1, $assets);
            self::assertFileExists($assets[0]['path']);
            self::assertFileExists($server['request_marker']);
        } finally {
            proc_terminate($server['process']);
            proc_close($server['process']);
        }
    }

    public function testRemoteResolverRejectsOversizedNonImageAndRedirectResponses(): void
    {
        $server = $this->startHttpServer(dirname(__DIR__, 2) . '/assets/banner.png');

        try {
            foreach (['/large.png', '/plain.txt', '/redirect'] as $path) {
                self::assertSame([], HtmlImporter::fromHtml(
                    '<img src="' . $server['url'] . $path . '">',
                    ['allow_remote_images' => true]
                )->getImageAssets(), 'Unexpected remote image accepted from ' . $path);
            }
        } finally {
            proc_terminate($server['process']);
            proc_close($server['process']);
        }
    }

    public function testTemporaryAssetRegistryToleratesDeletedFiles(): void
    {
        $path = $this->temporaryDirectory . '/temporary.png';
        self::assertSame(5, file_put_contents($path, 'asset'));

        TemporaryAssetRegistry::register($path);
        unlink($path);
        TemporaryAssetRegistry::cleanup();

        self::assertFileDoesNotExist($path);
    }

    /** @return array{process: resource, url: string, request_marker: string} */
    private function startHttpServer(string $fixture): array
    {
        $documentRoot = $this->temporaryDirectory . '/http';
        mkdir($documentRoot);
        copy($fixture, $documentRoot . '/image.png');
        file_put_contents($documentRoot . '/large.png', file_get_contents($fixture) . str_repeat("\0", 5_000_001));
        file_put_contents($documentRoot . '/plain.txt', 'not an image');
        $marker = $this->temporaryDirectory . '/remote-requested';
        $router = <<<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
file_put_contents(__MARKER__, "requested\n", FILE_APPEND);
if ($path === '/redirect') {
    header('Location: /image.png', true, 302);
    exit;
}
if ($path === '/plain.txt') {
    header('Content-Type: text/plain');
    readfile(__PLAIN__);
    exit;
}
if ($path === '/large.png') {
    readfile(__LARGE__);
    exit;
}
readfile(__IMAGE__);
PHP;
        $router = str_replace(
            ['__MARKER__', '__PLAIN__', '__LARGE__', '__IMAGE__'],
            [var_export($marker, true), var_export($documentRoot . '/plain.txt', true), var_export($documentRoot . '/large.png', true), var_export($documentRoot . '/image.png', true)],
            $router
        );
        $routerPath = $this->temporaryDirectory . '/router.php';
        file_put_contents($routerPath, $router);

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorNumber, $errorMessage);
        self::assertNotFalse($socket, $errorMessage);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        [, $port] = explode(':', (string) $address);

        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $routerPath],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        self::assertIsResource($process);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $connection = @fsockopen('127.0.0.1', (int) $port, $errno, $errstr, 0.1);
            if (is_resource($connection)) {
                fclose($connection);
                return [
                    'process' => $process,
                    'url' => 'http://127.0.0.1:' . $port,
                    'request_marker' => $marker,
                ];
            }
            usleep(50_000);
        }

        proc_terminate($process);
        proc_close($process);
        self::fail('Unable to start local HTTP fixture server.');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
