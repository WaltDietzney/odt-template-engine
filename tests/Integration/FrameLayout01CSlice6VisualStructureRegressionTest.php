<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FrameLayout01CSlice6VisualStructureRegressionTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    /** @var list<OdtTemplate> */
    private array $templates = [];

    protected function tearDown(): void
    {
        foreach ($this->templates as $template) {
            $template->cleanup();
        }

        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testParagraphAnchoredImageElementRemainsInsideParagraphCarrier(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_17_textfield.odt'
        );
        $this->templates[] = $template;

        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'right',
                'relative-to' => 'paragraph',
            ],
            'vertical' => [
                'alignment' => 'top',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'left',
        ]);

        $template->setElement('test1', $image);

        $output = sys_get_temp_dir()
            . '/odt-frame-layout-slice6-'
            . bin2hex(random_bytes(6))
            . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($content));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(
            'text',
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0'
        );
        $xpath->registerNamespace(
            'draw',
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
        );
        $xpath->registerNamespace(
            'xlink',
            'http://www.w3.org/1999/xlink'
        );

        $frame = $xpath->query(
            '//draw:frame[draw:image[contains(@xlink:href, "' . basename($this->imagePath()) . '")]]'
        )->item(0);

        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertInstanceOf(DOMElement::class, $frame->parentNode);
        self::assertSame('p', $frame->parentNode->localName);
        self::assertSame(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            $frame->parentNode->namespaceURI
        );
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);

            return $value;
        } finally {
            $zip->close();
        }
    }
}
