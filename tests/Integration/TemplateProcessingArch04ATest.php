<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use PHPUnit\Framework\TestCase;

final class TemplateProcessingArch04ATest extends TestCase
{
    public function testFiltersNl2brAndConditionalBranchRemainObservable(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_02_filter.odt'));

        try {
            $template->assign([
                'name' => 'Anna Beispiel',
                'email' => 'ANNA@EXAMPLE.COM',
                'kommentar' => "Line one\nLine two",
                'geburtstag' => '1995-08-15',
                'umsatz' => '1345.5',
                'is_admin' => true,
            ]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('ANNA BEISPIEL', $content);
            self::assertStringContainsString('anna@example.com', $content);
            self::assertStringContainsString('15.08.1995', $content);
            self::assertStringContainsString('1.345,50 €', $content);
            self::assertStringContainsString('Line one', $content);
            self::assertStringContainsString('Line two', $content);
            self::assertStringContainsString('text:line-break', $content);
            self::assertStringContainsString('Admin!', $content);
            self::assertStringNotContainsString('Hello User!', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionalComparisonIfnotAndMissingValuesRemainCompatible(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_03_logic_elements.odt'));

        try {
            $template->assign(['price' => 0]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('Bargain Hunter', $content);
            self::assertStringContainsString('No Access!', $content);
            self::assertStringContainsString('Hello User!', $content);
            self::assertStringNotContainsString('Premium Customer', $content);
            self::assertStringNotContainsString('Standard Customer', $content);
            self::assertStringNotContainsString('{{#if', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testRepeatingRowsReplaceRowPlaceholdersAfterNormalRendering(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assignRepeating('items', [
                ['produkt' => 'Coffee', 'preis' => '4.99'],
                ['produkt' => 'Tea', 'preis' => '3.49'],
            ]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('Name: Coffee', $content);
            self::assertStringContainsString('Preis: 4.99', $content);
            self::assertStringContainsString('Name: Tea', $content);
            self::assertStringContainsString('Preis: 3.49', $content);
            self::assertStringNotContainsString('{{#foreach:items}}', $content);
            self::assertStringNotContainsString('{{#endforeach}}', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testRenderingTwiceIsCurrentlyStableAfterPlaceholdersAreConsumed(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['name' => 'Repeat render']);
            $template->render();
            $firstRender = $template->contentXml();

            $template->render();

            self::assertSame($firstRender, $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testRemainingScalarFiltersKeepTheirCurrentCoercion(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            self::assertSame('trimmed', $template->applyFilterForTest('trim', '  trimmed  '));
            self::assertSame('☑', $template->applyFilterForTest('checkbox', '1'));
            self::assertSame('☐', $template->applyFilterForTest('checkbox', '0'));
            self::assertSame('☐', $template->applyFilterForTest('checkbox', ''));
            self::assertSame('unknown', $template->applyFilterForTest('unknown', 'unknown'));
        } finally {
            $template->cleanup();
        }
    }

    public function testScalarReplacementCoexistsWithOdtElementValues(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_18_ListStyles.odt'));

        try {
            $template->setValues([
                'my_list' => (new RichText())->addText('Structured value'),
            ]);
            $template->render();

            self::assertStringContainsString('Structured value', $template->contentXml());
            self::assertStringNotContainsString('{{my_list}}', $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testScalarReplacementAlsoProcessesStylesXml(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['foto' => 'Styles header value']);
            $template->render();

            self::assertStringContainsString('Styles header value', $template->stylesXml());
            self::assertStringNotContainsString('{{foto}}', $template->stylesXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testNl2brPreservesCurrentLineBreakAndMissingValueBehavior(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            foreach ([
                "one\ntwo" => ['one', 'two', 1],
                "one\r\ntwo" => ['one', 'two', 1],
                "one\rtwo" => ['one', 'two', 1],
                "one\n\ntwo" => ['one', 'two', 2],
                'single line' => ['single line', null, 0],
                '' => ['', null, 0],
            ] as $value => [$first, $second, $breakCount]) {
                $dom = $this->dom('<text:p>{{nl2br:value}}</text:p>');
                $template->replaceNl2brForTest($dom, ['value' => $value]);

                self::assertStringNotContainsString('{{nl2br:value}}', $dom->saveXML());
                self::assertStringContainsString($first, $dom->saveXML());
                if ($second !== null) {
                    self::assertStringContainsString($second, $dom->saveXML());
                }
                self::assertSame($breakCount, $dom->getElementsByTagName('text:line-break')->length);
            }

            $dom = $this->dom('<text:p>prefix {{nl2br:value}} suffix</text:p>');
            $template->replaceNl2brForTest($dom, ['value' => 'content']);

            self::assertStringContainsString('content', $dom->saveXML());
            self::assertStringNotContainsString('prefix', $dom->saveXML());
            self::assertStringNotContainsString('suffix', $dom->saveXML());
        } finally {
            $template->cleanup();
        }
    }

    public function testNl2brProcessesAStylesDom(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $dom = $this->dom('<style:style><style:text-properties>{{nl2br:value}}</style:text-properties></style:style>');
            $template->replaceNl2brForTest($dom, ['value' => "style one\nstyle two"]);

            self::assertStringContainsString('style one', $dom->saveXML());
            self::assertStringContainsString('style two', $dom->saveXML());
            self::assertSame(1, $dom->getElementsByTagName('text:line-break')->length);
        } finally {
            $template->cleanup();
        }
    }

    public function testListPlaceholdersPreserveCurrentListStructureAndEmptyLineBehavior(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            foreach ([
                'ul' => ['Bullet_20_Symbol', 3],
                'ol' => ['Numbering_20_Symbol', 3],
            ] as $type => [$styleName, $itemCount]) {
                $dom = $this->dom('<text:p>{{' . $type . ':items}}</text:p>');
                $template->replaceListsForTest($dom, ['items' => "first\r\n\r\nlast"]);

                $list = (new \DOMXPath($dom))->query('//*[name()="text:list"]')->item(0);
                self::assertNotNull($list);
                self::assertStringContainsString('text:style-name="' . $styleName . '"', $dom->saveXML());
                self::assertSame(
                    $itemCount,
                    (new \DOMXPath($dom))->query('//*[name()="text:list-item"]')->length
                );
                self::assertStringContainsString('first', $dom->saveXML());
                self::assertStringContainsString('last', $dom->saveXML());
                self::assertStringNotContainsString('{{' . $type . ':items}}', $dom->saveXML());
            }

            foreach (['', null] as $value) {
                $dom = $this->dom('<text:p>{{ul:items}}</text:p>');
                $template->replaceListsForTest($dom, $value === null ? [] : ['items' => $value]);

                self::assertSame(
                    1,
                    (new \DOMXPath($dom))->query('//*[name()="text:list-item"]')->length
                );
                self::assertSame('', trim($dom->textContent));
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testListPlaceholdersProcessAStylesDom(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $dom = $this->dom('<style:style><text:p>{{ul:items}}</text:p></style:style>');
            $template->replaceListsForTest($dom, ['items' => "style one\nstyle two"]);

            $xpath = new \DOMXPath($dom);
            self::assertSame(1, $xpath->query('//*[name()="text:list"]')->length);
            self::assertSame(2, $xpath->query('//*[name()="text:list-item"]')->length);
        } finally {
            $template->cleanup();
        }
    }

    public function testRenderPreservesProtectedStructuralHookDispatch(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_02_filter.odt'));

        try {
            $template->assign(['kommentar' => 'one\ntwo']);
            $template->render();

            self::assertTrue($template->nl2brHookCalled);
            self::assertTrue($template->listsHookCalled);
        } finally {
            $template->cleanup();
        }
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

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }
}

final class Arch04InspectableTemplate extends OdtTemplate
{
    public bool $nl2brHookCalled = false;
    public bool $listsHookCalled = false;

    public function applyFilterForTest(string $filter, string $value, ?string $option = null): string
    {
        return $this->applyFilter($filter, $value, $option);
    }

    public function contentXml(): string
    {
        return $this->domContent->saveXML() ?: '';
    }

    public function stylesXml(): string
    {
        return $this->domStyles->saveXML() ?: '';
    }

    public function replaceNl2brForTest(DOMDocument $dom, array $values): void
    {
        $this->replaceNl2brInDom($dom, $values);
    }

    public function replaceListsForTest(DOMDocument $dom, array $values): void
    {
        $this->replaceListsInDom($dom, $values);
    }

    protected function replaceNl2brInDom(DOMDocument $dom, array $values): void
    {
        $this->nl2brHookCalled = true;
        parent::replaceNl2brInDom($dom, $values);
    }

    protected function replaceListsInDom(DOMDocument $dom, array $values): void
    {
        $this->listsHookCalled = true;
        parent::replaceListsInDom($dom, $values);
    }
}
