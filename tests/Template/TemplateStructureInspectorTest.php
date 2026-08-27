<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Template;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateStructureInspection;
use OdtTemplateEngine\Template\TemplateStructureInspector;
use PHPUnit\Framework\TestCase;

final class TemplateStructureInspectorTest extends TestCase
{
    public function testProjectsSplitExpressionAndTransparentBookmarkWithoutMutation(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p><text:span text:style-name="T1">{{ac</text:span><text:bookmark-start text:name="Activity"/><text:span text:style-name="T1">tivity}}</text:span></text:p></root>');
        $before = $dom->C14N();

        $inspection = (new TemplateStructureInspector())->inspect($dom);

        self::assertInstanceOf(TemplateStructureInspection::class, $inspection);
        self::assertSame($before, $dom->C14N());
        $expression = $inspection->expressionsByVariable('activity')[0];
        self::assertSame('{{activity}}', $expression->rawText());
        self::assertSame(2, $expression->fragmentCount());
        self::assertSame(['T1'], $expression->styleNames());
        self::assertSame(['Activity'], $expression->bookmarkNames());
        self::assertSame('VALID', $expression->classification());
        self::assertSame('UNSAFE', $expression->physicalNormalization());
        self::assertSame(['bookmark_intersects_template_expression'], $expression->diagnostics());
    }

    public function testReportsStyleConflictAndUnsupportedSyntax(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p><text:span text:style-name="T1">{{na</text:span><text:span text:style-name="T2">me}}</text:span> {{unknown syntax}}</text:p></root>');

        $inspection = (new TemplateStructureInspector())->inspect($dom);

        self::assertSame('VALID', $inspection->expressions()[0]->classification());
        self::assertSame('UNSAFE', $inspection->expressions()[0]->physicalNormalization());
        self::assertContains('style_conflict_in_expression', $inspection->expressions()[0]->diagnostics());
        self::assertContains('unsupported_template_expression', array_map(static fn ($diagnostic): string => $diagnostic->code(), $inspection->diagnostics()));
    }

    public function testInspectsOriginalSample25DespiteLegacyLoadNormalization(): void
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt');
        $inspection = $template->inspectTemplateStructure();

        foreach (['firstname', 'lastname', 'profession', 'note', 'position', 'activity'] as $variable) {
            self::assertCount(1, $inspection->expressionsByVariable($variable), $variable);
        }
        $position = $inspection->expressionsByVariable('position')[0] ?? null;
        $activity = $inspection->expressionsByVariable('activity')[0] ?? null;
        self::assertNotNull($position);
        self::assertNotNull($activity);
        self::assertSame(['T25'], $position->styleNames());
        self::assertSame(['Activity'], $activity->bookmarkNames());
        self::assertSame(['T29', 'T30'], $activity->styleNames());
        self::assertContains('bookmark_intersects_template_expression', $activity->diagnostics());
        self::assertSame('text:p', $activity->scope());
    }

    public function testRecognizesKindsAndRejectsAParagraphBoundary(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p>{{upper:name}} {{#if:active}} {{#else}} {{#endif}} {{#foreach:items}} {{#endforeach}}</text:p><text:p>{{na</text:p><text:p>me}}</text:p></root>');

        $inspection = (new TemplateStructureInspector())->inspect($dom);
        $kinds = array_map(static fn ($expression): string => $expression->kind(), $inspection->expressions());

        self::assertContains('FILTERED_SCALAR', $kinds);
        self::assertContains('CONDITION_OPEN', $kinds);
        self::assertContains('CONDITION_ELSE', $kinds);
        self::assertContains('CONDITION_END', $kinds);
        self::assertContains('FOREACH_OPEN', $kinds);
        self::assertContains('FOREACH_END', $kinds);
        self::assertContains('expression_crosses_text_flow_boundary', array_map(static fn ($diagnostic): string => $diagnostic->code(), $inspection->diagnostics()));
    }

    public function testToArrayIsDeterministicAndDoesNotExposeDom(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p>{{name}}</text:p></root>');
        $inspection = (new TemplateStructureInspector())->inspect($dom);

        self::assertSame($inspection->toArray(), $inspection->toArray());
        self::assertArrayNotHasKey('node', $inspection->toArray()['expressions'][0]);
    }
}
