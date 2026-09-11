<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FrameLayout01CSlice5CompatibilityPolicyIntegrationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    private ?OdtTemplate $template = null;

    protected function tearDown(): void
    {
        $this->template?->cleanup();

        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testEquivalentCompatibilityPolicyStylesDeduplicateAndRemainStableAcrossRepeatedSave(): void
    {
        $this->template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_17_textfield.odt'
        );

        $first = $this->policyBox('PolicyOne');
        $second = $this->policyBox('PolicyTwo');

        $firstRequirement = iterator_to_array($first->getOwnStyleRequirements(), false)[0];
        $secondRequirement = iterator_to_array($second->getOwnStyleRequirements(), false)[0];

        self::assertSame($firstRequirement->name(), $secondRequirement->name());
        $styleName = $firstRequirement->name();

        $this->template->setElement('FLOAT_RIGHT_BOX', $first);
        $this->template->setElement('CENTER_BOX', $second);

        $firstOutput = $this->outputPath('first');
        $secondOutput = $this->outputPath('second');

        $this->template->save($firstOutput);
        $this->template->save($secondOutput);

        foreach ([$firstOutput, $secondOutput] as $output) {
            $styles = $this->dom($this->entry($output, 'styles.xml'));
            $stylesXPath = $this->xpath($styles);

            self::assertSame(
                1,
                $stylesXPath->query(
                    '//style:style[@style:name="' . $styleName . '" and @style:family="graphic"]'
                )->length
            );

            $properties = $stylesXPath->query(
                '//style:style[@style:name="' . $styleName . '"]/style:graphic-properties'
            )->item(0);

            self::assertNotNull($properties);
            self::assertSame(
                'true',
                $properties->attributes?->getNamedItem('style:flow-with-text')?->nodeValue
            );
            self::assertSame(
                'once-concurrent',
                $properties->attributes?->getNamedItem('draw:wrap-influence-on-position')?->nodeValue
            );
            self::assertSame(
                'false',
                $properties->attributes?->getNamedItem('loext:allow-overlap')?->nodeValue
            );
            self::assertSame(
                'parallel',
                $properties->attributes?->getNamedItem('style:wrap')?->nodeValue
            );

            $content = $this->dom($this->entry($output, 'content.xml'));
            $contentXPath = $this->xpath($content);

            self::assertSame(
                2,
                $contentXPath->query('//draw:frame[@draw:style-name="' . $styleName . '"]')->length
            );
        }

        self::assertSame(
            $this->entry($firstOutput, 'styles.xml'),
            $this->entry($secondOutput, 'styles.xml')
        );
        self::assertSame(
            $this->entry($firstOutput, 'content.xml'),
            $this->entry($secondOutput, 'content.xml')
        );
    }

    private function policyBox(string $name): DrawTextBox
    {
        return (new DrawTextBox($name, [
            'wrap-influence' => 'once-concurrent',
        ]))
            ->flowWithText(true)
            ->setAllowOverlap(false)
            ->setFrameLayout([
                'anchor' => 'paragraph',
                'width' => '4cm',
                'height' => '2cm',
                'horizontal' => [
                    'alignment' => 'right',
                    'relative-to' => 'paragraph',
                ],
                'wrap' => 'parallel',
            ]);
    }

    private function outputPath(string $suffix): string
    {
        $path = sys_get_temp_dir()
            . '/odt-frame-layout-slice5-'
            . $suffix
            . '-'
            . bin2hex(random_bytes(6))
            . '.odt';

        $this->outputs[] = $path;

        return $path;
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

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(
            'style',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
        );
        $xpath->registerNamespace(
            'draw',
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
        );

        return $xpath;
    }
}
