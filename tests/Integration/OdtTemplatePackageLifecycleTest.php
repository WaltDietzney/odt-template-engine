<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class OdtTemplatePackageLifecycleTest extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testPublicFacadeKeepsIndependentWorkspacesAndIsolatedCleanup(): void
    {
        $templatePath = $this->templatePath('template_01_simple_variables.odt');
        $first = new InspectableOdtTemplate($templatePath);
        $second = new InspectableOdtTemplate($templatePath);
        $firstOutput = $this->newOutputPath('isolated-first');
        $secondOutput = $this->newOutputPath('isolated-second');

        try {
            $first->assign(['name' => 'First isolated document']);
            $first->render();
            $first->save($firstOutput);
            $second->assign(['name' => 'Isolated document']);
            $second->render();
            $second->save($secondOutput);

            self::assertFileExists($firstOutput);
            self::assertFileExists($secondOutput);
            self::assertNotSame(file_get_contents($firstOutput), file_get_contents($secondOutput));
        } finally {
            $first->cleanup();
            $second->cleanup();
        }
    }

    public function testAssignRenderSaveAndReopenRemainCompatible(): void
    {
        $template = new OdtTemplate($this->templatePath('template_01_simple_variables.odt'));
        $output = $this->newOutputPath('reopen');

        $template->assign([
            'name' => 'Public facade',
            'datum' => '2026-08-24',
        ]);
        $template->render();
        $template->save($output);
        $template->cleanup();

        $reopened = new InspectableOdtTemplate($output);
        try {
            self::assertStringContainsString('Public facade', $reopened->contentXml());
            self::assertStringNotContainsString('{{name}}', $reopened->contentXml());
        } finally {
            $reopened->cleanup();
        }
    }

    public function testMetadataRoundTripRemainsCompatible(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $output = $this->newOutputPath('metadata');

        $template->setMeta([
            'title' => 'ARCH-02 facade metadata',
            'author' => 'Package context test',
            'language' => 'en',
        ]);
        $template->save($output);
        $template->cleanup();

        $reopened = new OdtTemplate($output);
        try {
            $metadata = $reopened->getMeta();
            self::assertSame('ARCH-02 facade metadata', $metadata['title'] ?? null);
            self::assertSame('Package context test', $metadata['author'] ?? null);
            self::assertSame('en', $metadata['language'] ?? null);
        } finally {
            $reopened->cleanup();
        }
    }

    public function testLegacyMetadataAliasesAndStringKeywordInputRemainCompatible(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $output = $this->newOutputPath('metadata-legacy-characterization');

        $before = $template->getMeta();
        self::assertSame(['odt emplate metadata richtext', 'php sample'], $before['keywords'] ?? null);
        self::assertArrayNotHasKey('creator', $before);
        self::assertArrayNotHasKey('initial_creator', $before);

        $template->setMeta([
            'author' => 'Legacy Current Creator',
            'initial_author' => 'Legacy Original Creator',
            'keywords' => 'finance,report,2026',
            'not_a_supported_key' => 'ignored',
        ]);

        $metadata = $template->getMeta();
        self::assertSame('Legacy Current Creator', $metadata['author'] ?? null);
        self::assertSame('Legacy Original Creator', $metadata['initial_author'] ?? null);
        self::assertSame('Legacy Current Creator', $metadata['creator'] ?? null);
        self::assertSame('Legacy Original Creator', $metadata['initial_creator'] ?? null);
        self::assertSame(['finance,report,2026'], $metadata['keywords'] ?? null);
        self::assertArrayNotHasKey('not_a_supported_key', $metadata);

        $template->save($output);
        $template->cleanup();

        $archive = new ZipArchive();
        self::assertTrue($archive->open($output) === true);
        try {
            $metaXml = $archive->getFromName('meta.xml');
            self::assertIsString($metaXml);
            $dom = new \DOMDocument();
            self::assertTrue($dom->loadXML($metaXml));
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
            $keywords = $xpath->query('//meta:keyword');
            self::assertNotFalse($keywords);
            self::assertSame(1, $keywords->length);
            self::assertSame('finance,report,2026', $keywords->item(0)?->textContent);
            self::assertSame(0, $xpath->query('//*[local-name()="not_a_supported_key"]')->length);
        } finally {
            $archive->close();
        }

        $reopened = new OdtTemplate($output);
        try {
            $afterReload = $reopened->getMeta();
            self::assertSame('Legacy Current Creator', $afterReload['author'] ?? null);
            self::assertSame('Legacy Original Creator', $afterReload['initial_author'] ?? null);
            self::assertSame('Legacy Current Creator', $afterReload['creator'] ?? null);
            self::assertSame('Legacy Original Creator', $afterReload['initial_creator'] ?? null);
            self::assertSame(['finance,report,2026'], $afterReload['keywords'] ?? null);
        } finally {
            $reopened->cleanup();
        }
    }

    public function testImageEmbeddingAndManifestSynchronizationRemainCompatible(): void
    {
        $template = new OdtTemplate($this->templatePath('template_06_imageSettings.odt'));
        $output = $this->newOutputPath('image');
        $imagePath = dirname(__DIR__, 2) . '/assets/banner.png';

        $template->setImage('image', $imagePath, [
            'width' => '6cm',
            'anchor' => 'paragraph',
            'wrap' => 'none',
        ]);
        $template->save($output);
        $template->cleanup();

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        try {
            self::assertNotFalse($zip->locateName('Pictures/banner.png'));
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            self::assertIsString($manifest);
            self::assertStringContainsString('Pictures/banner.png', $manifest);
            self::assertStringContainsString('image/png', $manifest);
        } finally {
            $zip->close();
        }
    }

    public function testLoadResetsFromTheOriginalTemplate(): void
    {
        $template = new InspectableOdtTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['name' => 'Unsaved value']);
            $template->render();
            self::assertStringContainsString('Unsaved value', $template->contentXml());

            $template->load();

            self::assertStringContainsString('{{name}}', $template->contentXml());
            self::assertStringNotContainsString('Unsaved value', $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testRefreshKeepsItsLegacyResetBehavior(): void
    {
        $template = new InspectableOdtTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['name' => 'Refresh value']);
            $template->render();
            $template->refresh();

            self::assertStringContainsString('{{name}}', $template->contentXml());
            self::assertStringNotContainsString('Refresh value', $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-template-package-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }
}

final class InspectableOdtTemplate extends OdtTemplate
{
    public function contentXml(): string
    {
        return $this->documentContext()->contentDom()->saveXML() ?: '';
    }
}
