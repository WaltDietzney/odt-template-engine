<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\Elements\SkillBarGroup;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class H03SkillBarGroupIntegrationTest extends TestCase
{
    public function testNativeRoundedSkillBarsAreGroupedAndPackaged(): void
    {
        $group = (new SkillBarGroup('Skills'))
            ->addSkill('Teamarbeit', 90)
            ->addSkill('Kommunikation', 72)
            ->addSkill('Organisation', 84);

        $node = $group->toDomNode(new DOMDocument());
        self::assertSame('draw:g', $node->nodeName);
        self::assertCount(9, $node->childNodes);
        self::assertSame('paragraph', $node->attributes->getNamedItem('text:anchor-type')?->nodeValue);

        $output = tempnam(sys_get_temp_dir(), 'h03-skillbars-');
        self::assertIsString($output);
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/sample_textfeld.odt');
        try {
            $template->setElement('test1', $group);
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true);
            try {
                $content = $zip->getFromName('content.xml');
                $styles = $zip->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                $dom = new DOMDocument();
                self::assertTrue($dom->loadXML($content));
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
                $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
                $xpath->registerNamespace('svg', 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0');

                self::assertSame(1, $xpath->query('//draw:g[@draw:name="Skills"]')->length);
                self::assertSame(3, $xpath->query('//draw:g[@draw:name="Skills"]/draw:custom-shape')->length);
                self::assertSame(6, $xpath->query('//draw:g[@draw:name="Skills"]/draw:rect')->length);
                self::assertSame(6, $xpath->query('//draw:g[@draw:name="Skills"]/draw:rect[@draw:corner-radius="0.075cm"]')->length);
                self::assertSame(3, $xpath->query('//draw:g[@draw:name="Skills"]/draw:custom-shape/text:p')->length);
                self::assertStringContainsString('Teamarbeit', $content);
                self::assertStringContainsString('Kommunikation', $content);
                self::assertStringContainsString('Organisation', $content);
                self::assertStringContainsString('2.7009cm', $content);
                self::assertStringContainsString('2.16072cm', $content);
                self::assertStringContainsString('2.52084cm', $content);
                self::assertStringContainsString('draw:textarea-horizontal-align="left"', $content);
                self::assertStringContainsString('fo:color="#FFFFFF"', $styles);
            } finally {
                $zip->close();
            }
        } finally {
            $template->cleanup();
            unlink($output);
        }
    }

    public function testCompactLayoutAndGroupOptionsControlGeometryAndTypography(): void
    {
        $group = (new SkillBarGroup(
            name: 'Compact',
            x: 0.2,
            y: 0.1,
            width: 4.0,
            trackColor: '#223344',
            fillColor: '#556677',
            barHeight: 0.12,
            fontFamily: 'DejaVu Sans',
            fontSize: 7.5,
            fontColor: '#ABCDEF',
            fontBold: false,
            labelGap: 0.08,
            skillGap: 0.18
        ))->addSkill('Hundert', 100)->addSkill('Null', 0);

        $dom = new DOMDocument();
        $dom->appendChild($group->toDomNode($dom));
        $xml = $dom->saveXML();
        self::assertIsString($xml);
        self::assertStringContainsString('svg:width="4cm"', $xml);
        self::assertStringContainsString('svg:height="0.12cm"', $xml);
        self::assertStringContainsString('draw:corner-radius="0.06cm"', $xml);
        self::assertStringContainsString('draw:name="Compact-fill-0"', $xml);
        self::assertStringNotContainsString('draw:name="Compact-fill-1"', $xml);

        $requirements = iterator_to_array($group->getOwnStyleRequirements());
        $serialized = json_encode(array_map(
            static fn ($requirement): array => $requirement->propertyGroups(),
            $requirements
        ));
        self::assertIsString($serialized);
        self::assertStringContainsString('#223344', $serialized);
        self::assertStringContainsString('#556677', $serialized);
        self::assertStringContainsString('DejaVu Sans', $serialized);
        self::assertStringContainsString('7.5pt', $serialized);
        self::assertStringContainsString('#ABCDEF', $serialized);

        $labels = [];
        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeName === 'draw:custom-shape') {
                $labels[] = $child;
            }
        }
        self::assertCount(2, $labels);
        $firstY = (float) $labels[0]->getAttribute('svg:y');
        $secondY = (float) $labels[1]->getAttribute('svg:y');
        self::assertEqualsWithDelta(
            0.1 + 7.5 * 2.54 / 72 * 1.45 + 0.08 + 0.12 + 0.18,
            $secondY,
            0.00001
        );
        self::assertEqualsWithDelta(0.1, $firstY, 0.000001);
    }

    public function testTinyFillRadiusNeverExceedsHalfFillWidth(): void
    {
        $group = (new SkillBarGroup('Tiny', width: 3.0, barHeight: 0.2))
            ->addSkill('Ein Prozent', 1);
        $dom = new DOMDocument();
        $dom->appendChild($group->toDomNode($dom));
        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeName === 'draw:rect' && $child->getAttribute('draw:name') === 'Tiny-fill-0') {
                self::assertSame('0.03cm', $child->getAttribute('svg:width'));
                self::assertSame('0.015cm', $child->getAttribute('draw:corner-radius'));
                return;
            }
        }
        self::fail('Tiny fill rectangle missing.');
    }

    public function testSkillBarRejectsInvalidValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SkillBarGroup('Skills'))->addSkill('Teamarbeit', 101);
    }

    public function testSkillBarCanRenderEmptyAndFullValues(): void
    {
        $group = (new SkillBarGroup('Skills'))
            ->addSkill('Keine', 0)
            ->addSkill('Voll', 100);
        $dom = new DOMDocument();
        $dom->appendChild($group->toDomNode($dom));
        $rectangles = 0;
        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeName === 'draw:rect') {
                $rectangles++;
            }
        }
        self::assertSame(3, $rectangles);
    }
}
