<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FillResourceAuthoringTest extends TestCase
{
    public function testNamedBitmapIsPackagedOnceAndSurvivesRepeatedSave(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $path = 'tests/Fixtures/LegacySamples/templates/sample_23_image.png';
        $template->styles()->defineFillImage('CVBitmap', $path);
        $template->styles()->defineFillImage('CVBitmap', $path);
        $template->styles()->defineFillImage('OtherBitmap', $path);
        $output = tempnam(sys_get_temp_dir(), 'h02-fill-');
        try {
            for ($i = 0; $i < 2; $i++) {
                $template->save($output);
                $zip = new ZipArchive();
                self::assertTrue($zip->open($output));
                try {
                    $href = 'Pictures/' . basename($path);
                    self::assertSame(file_get_contents($path), $zip->getFromName($href));
                    $dom = new DOMDocument();
                    self::assertTrue($dom->loadXML($zip->getFromName('styles.xml')));
                    $matches = [];
                    foreach ($dom->getElementsByTagNameNS(
                        'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'fill-image'
                    ) as $node) {
                        if (in_array($node->getAttribute('draw:name'), ['CVBitmap', 'OtherBitmap'], true)) {
                            $matches[] = $node->getAttribute('xlink:href');
                        }
                    }
                    self::assertSame([$href, $href], $matches);
                    self::assertStringContainsString($href, $zip->getFromName('META-INF/manifest.xml'));
                } finally {
                    $zip->close();
                }
            }
        } finally {
            unlink($output);
        }
    }

    public function testInvalidImageFormatIsRejected(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $this->expectException(\InvalidArgumentException::class);
        $template->styles()->defineFillImage('Invalid', __FILE__);
    }
}
