<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Template;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateStructureNormalizer;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateStructureNormalizerTest extends TestCase
{
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testMergesOnlyCompleteSameStyleFragments(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T1">{{pos</text:span><text:span text:style-name="T1" foo="keep">ition}}</text:span><text:span text:style-name="T2"> after</text:span></text:p>');
        $result = (new TemplateStructureNormalizer())->normalize($dom);

        self::assertTrue($result->changed());
        self::assertSame('merge_same_style_fragments', $result->repairs()[0]['repair_type']);
        self::assertSame('{{position}}', $dom->documentElement->firstChild->firstChild->textContent);
        self::assertSame('T1', $dom->documentElement->firstChild->firstChild->getAttribute('text:style-name'));
        self::assertSame(2, $dom->documentElement->firstChild->childNodes->length);
        self::assertSame(' after', $dom->documentElement->firstChild->lastChild->textContent);
    }

    public function testLeavesDifferentStylesAndBookmarkIntersectionsUnchanged(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T1">{{na</text:span><text:span text:style-name="T2">me}}</text:span><text:bookmark-start text:name="Activity"/><text:span text:style-name="T1">{{ac</text:span><text:bookmark-end text:name="Activity"/><text:span text:style-name="T1">tivity}}</text:span></text:p>');
        $before = $dom->C14N();
        $result = (new TemplateStructureNormalizer())->normalize($dom);

        self::assertFalse($result->changed());
        self::assertSame($before, $dom->C14N());
        self::assertNotEmpty($result->skipped());
        self::assertContains('bookmark_intersects_template_expression', array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics()));
    }

    public function testNormalizationIsIdempotentAndPreservesSiblingOrder(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T1">{{pos</text:span><text:span text:style-name="T1">ition}}</text:span><text:bookmark-start text:name="Company"/><text:span>Company</text:span><text:bookmark-end text:name="Company"/></text:p>');
        $normalizer = new TemplateStructureNormalizer();
        $first = $normalizer->normalize($dom);
        $after = $dom->C14N();
        $second = $normalizer->normalize($dom);

        self::assertTrue($first->changed());
        self::assertFalse($second->changed());
        self::assertSame($after, $dom->C14N());
        self::assertLessThan(strpos($dom->C14N(), 'Company'), strpos($dom->C14N(), '{{position}}'));
    }

    public function testSample25LoadSavePreservesPositionAndHeaderStyles(): void
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt');
        $output = tempnam(sys_get_temp_dir(), 'odt-structure-');
        self::assertNotFalse($output);
        try {
            $template->save($output);
            $zip = new ZipArchive();
            self::assertSame(true, $zip->open($output));
            $xml = $zip->getFromName('content.xml');
            $zip->close();
            self::assertIsString($xml);
            foreach (['{{firstname}}', '{{lastname}}', '{{profession}}', '{{position}}'] as $expression) {
                self::assertStringContainsString($expression, $xml);
            }
            self::assertLessThan(strpos($xml, 'text:name="Company"'), strpos($xml, '{{position}}'));
            self::assertStringContainsString('text:style-name="T25">{{position}}</text:span>', $xml);
        } finally {
            if (is_file($output)) unlink($output);
        }
    }

    private function document(string $children): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="' . self::TEXT . '">' . $children . '</root>');
        return $dom;
    }
}
