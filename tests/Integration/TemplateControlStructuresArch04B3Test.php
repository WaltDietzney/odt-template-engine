<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class TemplateControlStructuresArch04B3Test extends TestCase
{
    public function testForeachRepeatsSiblingParagraphsAndPreservesStyledClones(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom(
                '<text:p>{{#foreach:items}}</text:p>'
                . '<text:p><text:span text:style-name="Emphasis">{{name}}</text:span></text:p>'
                . '<text:p>{{#endforeach}}</text:p>'
            );
            $template->applyRepeatingForTest($dom, 'items', [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ]);

            self::assertSame(2, $this->xpath($dom)->query('//*[name()="text:p"]')->length);
            self::assertStringContainsString('Alice', $dom->saveXML());
            self::assertStringContainsString('Bob', $dom->saveXML());
            self::assertSame(2, $this->xpath($dom)->query('//*[name()="text:span"]')->length);
            self::assertSame(2, substr_count($dom->saveXML(), 'text:style-name="Emphasis"'));
            self::assertStringNotContainsString('#foreach', $dom->saveXML());
            self::assertStringNotContainsString('#endforeach', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testForeachEmptyMissingAndMalformedBlocksPreserveCurrentBehavior(): void
    {
        $template = $this->template();

        try {
            foreach ([[], null] as $rows) {
                $dom = $this->dom(
                    '<text:p>{{#foreach:items}}</text:p>'
                    . '<text:p>{{name}}</text:p>'
                    . '<text:p>{{#endforeach}}</text:p>'
                );
                $template->applyRepeatingForTest($dom, 'items', $rows ?? []);

                self::assertStringNotContainsString('#foreach', $dom->saveXML());
                self::assertStringNotContainsString('#endforeach', $dom->saveXML());
                self::assertStringNotContainsString('{{name}}', $dom->saveXML());
            }

            $dom = $this->dom(
                '<text:p>{{#foreach:items}}</text:p><text:p>{{name}}</text:p>'
            );
            $template->applyRepeatingForTest($dom, 'items', [['name' => 'Alice']]);

            self::assertStringContainsString('#foreach', $dom->saveXML());
            self::assertStringContainsString('{{name}}', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testForeachRowReplacementIsTextBasedAndDoesNotApplyFiltersOrStructuralPasses(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom(
                '<text:p>{{#foreach:items}}</text:p>'
                . '<text:p>{{name}}|{{upper:name}}|{{nl2br:name}}|{{ul:name}}</text:p>'
                . '<text:p>{{#endforeach}}</text:p>'
            );
            $template->applyRepeatingForTest($dom, 'items', [['name' => "one\ntwo"]]);

            self::assertStringContainsString('one', $dom->saveXML());
            self::assertStringContainsString('two', $dom->saveXML());
            self::assertStringNotContainsString('{{', $dom->saveXML());
            self::assertSame(0, $dom->getElementsByTagName('text:line-break')->length);
        } finally {
            $template->cleanup();
        }
    }

    public function testOdtElementValuesAreNotSupportedByForeachRowReplacement(): void
    {
        $template = $this->template();

        try {
            self::expectException(\Error::class);
            $dom = $this->dom(
                '<text:p>{{#foreach:items}}</text:p>'
                . '<text:p>{{value}}</text:p>'
                . '<text:p>{{#endforeach}}</text:p>'
            );
            $template->applyRepeatingForTest($dom, 'items', [
                ['value' => (new RichText())->addText('Structured')],
            ]);
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionalsKeepOrRemoveWholeParagraphBranches(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom(
                '<text:p>{{#if:active}}</text:p>'
                . '<text:p><text:span text:style-name="Strong">kept</text:span></text:p>'
                . '<text:p>{{#else}}</text:p>'
                . '<text:p>removed</text:p>'
                . '<text:p>{{#endif}}</text:p>'
            );
            $template->applyConditionalsForTest($dom, ['active' => true]);

            self::assertStringContainsString('kept', $dom->saveXML());
            self::assertStringNotContainsString('removed', $dom->saveXML());
            self::assertSame(1, $this->xpath($dom)->query('//*[name()="text:span"]')->length);
            self::assertStringContainsString('text:style-name="Strong"', $dom->saveXML());
            self::assertStringNotContainsString('{{#', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionalsSupportIfnotElseIfAndElseOrdering(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom(
                '<text:p>{{#if:first}}</text:p><text:p>first</text:p>'
                . '<text:p>{{#elseif:second}}</text:p><text:p>second</text:p>'
                . '<text:p>{{#else}}</text:p><text:p>fallback</text:p>'
                . '<text:p>{{#endif}}</text:p>'
            );
            $template->applyConditionalsForTest($dom, ['first' => false, 'second' => true]);

            self::assertStringNotContainsString('first', $dom->saveXML());
            self::assertStringContainsString('second', $dom->saveXML());
            self::assertStringNotContainsString('fallback', $dom->saveXML());

            $dom = $this->dom(
                '<text:p>{{#if:first}}</text:p><text:p>first</text:p>'
                . '<text:p>{{#elseif:second}}</text:p><text:p>second</text:p>'
                . '<text:p>{{#else}}</text:p><text:p>fallback</text:p>'
                . '<text:p>{{#endif}}</text:p>'
            );
            $template->applyConditionalsForTest($dom, ['first' => false, 'second' => false]);

            self::assertStringContainsString('fallback', $dom->saveXML());
            self::assertStringNotContainsString('first', $dom->saveXML());
            self::assertStringNotContainsString('second', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionTruthinessMatchesCurrentEvaluation(): void
    {
        $template = $this->template();

        try {
            foreach ([
                'true string' => ['true', true],
                'false string' => ['false', false],
                'true boolean' => [true, true],
                'false boolean' => [false, false],
                'zero integer' => [0, false],
                'zero string' => ['0', false],
                'empty string' => ['', false],
            ] as [$value, $expected]) {
                self::assertSame($expected, $template->evaluateConditionForTest('value', ['value' => $value]));
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionComparisonsAndMissingValuesMatchCurrentEvaluation(): void
    {
        $template = $this->template();

        try {
            $values = ['price' => 10, 'name' => 'Anna'];
            self::assertTrue($template->evaluateConditionForTest('price == 10', $values));
            self::assertTrue($template->evaluateConditionForTest('price != 11', $values));
            self::assertTrue($template->evaluateConditionForTest('price > 9', $values));
            self::assertTrue($template->evaluateConditionForTest('price >= 10', $values));
            self::assertTrue($template->evaluateConditionForTest('price < 11', $values));
            self::assertTrue($template->evaluateConditionForTest('price <= 10', $values));
            self::assertTrue($template->evaluateConditionForTest('name == "Anna"', $values));
            self::assertTrue($template->evaluateConditionForTest('missing != 1', $values));
            self::assertFalse($template->evaluateConditionForTest('missing', $values));
        } finally {
            $template->cleanup();
        }
    }

    public function testMalformedConditionRemainsUnchangedAndStylesDomUsesSameParagraphPath(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom('<text:p>{{#if:active}}</text:p><text:p>body</text:p>');
            $template->applyConditionalsForTest($dom, ['active' => true]);
            self::assertStringContainsString('{{#if:active}}', $dom->saveXML());
            self::assertStringContainsString('body', $dom->saveXML());

            $styles = $this->dom('<style:style><text:p>{{#if:active}}</text:p><text:p>style body</text:p><text:p>{{#endif}}</text:p></style:style>');
            $template->applyConditionalsForTest($styles, ['active' => true]);
            self::assertStringContainsString('style body', $styles->saveXML());
            self::assertStringNotContainsString('{{#', $styles->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionInsideRepeatedBlockUsesOuterValuesNotRowValues(): void
    {
        $template = $this->template();

        try {
            $dom = $this->dom(
                '<text:p>{{#foreach:items}}</text:p>'
                . '<text:p>{{#if:active}}</text:p><text:p>row body</text:p><text:p>{{#endif}}</text:p>'
                . '<text:p>{{#endforeach}}</text:p>'
            );
            $template->applyRepeatingForTest($dom, 'items', [['active' => true]]);
            $template->applyConditionalsForTest($dom, []);

            self::assertStringContainsString('row body', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionFacadeAndEvaluatorOverridesRemainObservable(): void
    {
        $template = new Arch04B3ConditionalPolymorphismTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_01_simple_variables.odt'
        );

        try {
            $template->render();
            self::assertTrue($template->conditionHookCalled);

            $dom = $this->dom(
                '<text:p>{{#if:forced}}</text:p><text:p>forced branch</text:p>'
                . '<text:p>{{#endif}}</text:p>'
            );
            $template->applyConditionalsForTest($dom, []);

            self::assertTrue($template->evaluatorHookCalled);
            self::assertStringContainsString('forced branch', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    private function template(): Arch04B3InspectableTemplate
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/template_01_simple_variables.odt';
        self::assertFileExists($path);

        return new Arch04B3InspectableTemplate($path);
    }

    private function dom(string $body): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(
            '<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . $body
            . '</root>'
        );

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        return new DOMXPath($dom);
    }
}

final class Arch04B3InspectableTemplate extends OdtTemplate
{
    public function applyRepeatingForTest(DOMDocument $dom, string $key, array $rows): void
    {
        $this->applyRepeatingInDom($dom, $key, $rows);
    }

    public function applyConditionalsForTest(DOMDocument $dom, array $values): void
    {
        $this->applyConditionalsInDom($dom, $values);
    }

    public function evaluateConditionForTest(string $expression, array $values): bool
    {
        return $this->evaluateCondition($expression, $values);
    }
}

final class Arch04B3ConditionalPolymorphismTemplate extends OdtTemplate
{
    public bool $conditionHookCalled = false;
    public bool $evaluatorHookCalled = false;

    public function applyConditionalsForTest(DOMDocument $dom, array $values): void
    {
        $this->applyConditionalsInDom($dom, $values);
    }

    protected function applyConditionalsInDom(DOMDocument $dom, array $values): void
    {
        $this->conditionHookCalled = true;
        parent::applyConditionalsInDom($dom, $values);
    }

    protected function evaluateCondition(string $expression, array $values): bool
    {
        $this->evaluatorHookCalled = true;

        if ($expression === 'forced') {
            return true;
        }

        return parent::evaluateCondition($expression, $values);
    }
}
