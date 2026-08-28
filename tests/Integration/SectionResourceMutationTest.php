<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class SectionResourceMutationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testImageReplacementCopiesResourceAndManifestAndSurvivesReopen(): void
    {
        $template = new SectionResourceInspectableTemplate($this->templatePath());
        $template->addSection('Profile');
        $image = new ImageElement($this->imagePath());

        $section = $template->section('Profile');
        self::assertSame($section, $section->replaceContent($image));
        self::assertSame('Profile', $section->name());
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($template->contentXml()));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $sectionNode = $xpath->query('//text:section[@text:name="Profile"]')->item(0);
        self::assertSame('text:p', $sectionNode?->firstChild?->nodeName);
        self::assertSame('urn:oasis:names:tc:opendocument:xmlns:text:1.0', $sectionNode?->firstChild?->namespaceURI);
        self::assertSame(1, $xpath->query('//text:section[@text:name="Profile"]//draw:frame')->length);
        self::assertSame(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
            $xpath->query('//text:section[@text:name="Profile"]//draw:frame')->item(0)?->namespaceURI
        );
        self::assertSame(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
            $xpath->query('//text:section[@text:name="Profile"]//draw:image')->item(0)?->namespaceURI
        );

        $output = $this->outputPath();
        $template->save($output);

        $zip = $this->openArchive($output);
        try {
            self::assertNotFalse($zip->locateName('Pictures/Logo.png'));
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            self::assertIsString($manifest);
            self::assertSame(1, substr_count($manifest, 'Pictures/Logo.png'));

            $content = $zip->getFromName('content.xml');
            self::assertIsString($content);
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($content));
            self::assertSame(1, $dom->getElementsByTagNameNS(
                'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
                'image'
            )->length);
            self::assertStringContainsString('Pictures/Logo.png', $content);
        } finally {
            $zip->close();
        }

        $reopened = new OdtTemplate($output);
        self::assertSame('Profile', $reopened->section('Profile')->name());
    }

    public function testRepeatedImageReplacementReusesExistingPackageResource(): void
    {
        $template = new SectionResourceInspectableTemplate($this->templatePath());
        $template->addSection('Profile');
        $template->section('Profile')->replaceContent(new ImageElement($this->imagePath()));
        $template->section('Profile')->replaceContent(new ImageElement($this->imagePath()));

        $output = $this->outputPath();
        $template->save($output);
        $zip = $this->openArchive($output);
        try {
            self::assertSame(1, $this->archiveEntryCount($zip, 'Pictures/Logo.png'));
        } finally {
            $zip->close();
        }
    }

    public function testResourcePreparationRollsBackWhenALaterAssetFails(): void
    {
        $template = new SectionResourceInspectableTemplate($this->templatePath());
        $template->addSection('Profile');
        $before = $template->contentXml();
        $content = new FailingSectionImageElement($this->imagePath(), $this->imagePath() . '.missing');

        try {
            $template->section('Profile')->replaceContent($content);
            self::fail('Expected resource preparation to fail.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Image resource not found', $exception->getMessage());
        }

        self::assertSame($before, $template->contentXml());
        $output = $this->outputPath();
        $template->save($output);
        $zip = $this->openArchive($output);
        try {
            self::assertFalse($zip->locateName('Pictures/Logo.png'));
        } finally {
            $zip->close();
        }
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_01_simple_variables.odt';
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/Logo.png';
    }

    private function outputPath(): string
    {
        $output = sys_get_temp_dir() . '/odt-section-resource-' . uniqid('', true) . '.odt';
        $this->outputs[] = $output;
        return $output;
    }

    private function openArchive(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        return $zip;
    }

    private function archiveEntryCount(ZipArchive $zip, string $name): int
    {
        $count = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            if ($zip->getNameIndex($index) === $name) {
                $count++;
            }
        }
        return $count;
    }
}

final class SectionResourceInspectableTemplate extends OdtTemplate
{
    public function addSection(string $name): void
    {
        $dom = $this->documentContext()->contentDom();
        $section = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'text:section'
        );
        $section->setAttribute('text:name', $name);
        $section->appendChild($dom->createElement('text:p'));
        $dom->documentElement->appendChild($section);
    }

    public function contentXml(): string
    {
        return $this->documentContext()->contentDom()->saveXML();
    }
}

final class FailingSectionImageElement extends OdtElement
{
    public function __construct(private readonly string $valid, private readonly string $missing)
    {
    }

    public function toDomNode(DOMDocument $dom): \DOMNode
    {
        $frame = $dom->createElement('draw:frame');
        $image = $dom->createElement('draw:image');
        $image->setAttribute('xlink:href', 'Pictures/Logo.png');
        $frame->appendChild($image);
        return $frame;
    }

    public function getImageAssets(): array
    {
        return [['path' => $this->valid], ['path' => $this->missing]];
    }

    public function registerStyles(): void
    {
    }
}
