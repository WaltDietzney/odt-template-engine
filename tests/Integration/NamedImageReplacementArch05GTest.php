<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class NamedImageReplacementArch05GTest extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $outputFile) {
            if (is_file($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    public function testHeightOnlyReplacementKeepsTheLegacyDefaultWidth(): void
    {
        $template = new OdtTemplate($this->templatePath('template_05_replaceImage.odt'));
        $template->replaceImageByName('Logo', $this->imagePath(), ['height' => '4cm']);

        $output = $this->saveTemplate($template, 'height-only');
        $styles = $this->readXmlEntry($output, 'styles.xml');
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($styles));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $frame = $xpath->query('//draw:frame[@draw:name="Logo"]')->item(0);

        self::assertNotNull($frame);
        self::assertSame('5cm', $frame->getAttribute('svg:width'));
        self::assertSame('4cm', $frame->getAttribute('svg:height'));
    }

    public function testNamedNonImageFrameKeepsLegacyDimensionMutationWithoutImagePayload(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->replaceImageByName('Textrahmen 1', $this->imagePath());

        $output = $this->saveTemplate($template, 'non-image-frame');
        $content = $this->readXmlEntry($output, 'content.xml');
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($content));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $frame = $xpath->query('//draw:frame[@draw:name="Textrahmen 1"]')->item(0);

        self::assertNotNull($frame);
        self::assertSame('5cm', $frame->getAttribute('svg:width'));
        self::assertSame('3cm', $frame->getAttribute('svg:height'));
        self::assertCount(0, $xpath->query('//draw:frame[@draw:name="Textrahmen 1"]/draw:image'));
    }

    public function testDuplicateNamedFramesAreAllUpdatedByTheLegacyPublicOperation(): void
    {
        $template = new NamedImageReplacementInspectableTemplate(
            $this->templatePath('template_05_replaceImage.odt')
        );
        $template->appendImageFrameToStyles('Logo');
        $template->replaceImageByName('Logo', $this->imagePath(), ['width' => '8cm', 'height' => '2cm']);

        $output = $this->saveTemplate($template, 'duplicate-frames');
        $styles = $this->readXmlEntry($output, 'styles.xml');
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($styles));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        $frames = $xpath->query('//draw:frame[@draw:name="Logo"]');

        self::assertNotFalse($frames);
        self::assertCount(2, $frames);
        foreach ($frames as $frame) {
            self::assertSame('8cm', $frame->getAttribute('svg:width'));
            self::assertSame('2cm', $frame->getAttribute('svg:height'));
            self::assertCount(1, $xpath->query('./draw:image[@xlink:href="Pictures/WaltDietzney.png"]', $frame));
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function imagePath(): string
    {
        $path = dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
        self::assertFileExists($path);

        return $path;
    }

    private function saveTemplate(OdtTemplate $template, string $suffix): string
    {
        $output = sys_get_temp_dir() . '/odt-arch05g-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $output;
        $template->save($output);

        return $output;
    }

    private function readXmlEntry(string $archivePath, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archivePath) === true);

        try {
            $xml = $zip->getFromName($entry);
            self::assertIsString($xml);

            return $xml;
        } finally {
            $zip->close();
        }
    }
}

final class NamedImageReplacementInspectableTemplate extends OdtTemplate
{
    public function appendImageFrameToStyles(string $name): void
    {
        $drawingNamespace = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
        $stylesDom = $this->documentContext()->stylesDom();
        $frame = $stylesDom->createElementNS($drawingNamespace, 'draw:frame');
        $frame->setAttributeNS($drawingNamespace, 'draw:name', $name);
        $image = $stylesDom->createElementNS($drawingNamespace, 'draw:image');
        $frame->appendChild($image);
        $stylesDom->documentElement->appendChild($frame);
    }
}
