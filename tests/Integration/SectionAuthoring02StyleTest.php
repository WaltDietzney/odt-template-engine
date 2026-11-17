<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\SectionMutationException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SectionAuthoring02StyleTest extends TestCase
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

    public function testSectionStylesMaterializeColumnsWidthsGapSeparatorBalanceAndColor(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->defineSection('SA02Columns', [
            'margin-left' => '1cm', 'margin-right' => '0.5cm', 'background-color' => '#123456',
            'columns' => [
                'count' => 3, 'gap' => '0.25cm', 'relative-widths' => [1, 2, 3], 'balance' => false,
                'separator' => ['style' => 'solid', 'width' => '0.03cm', 'color' => '#abcdef', 'height' => '100%'],
            ],
        ]);
        $template->appendSection((new SectionElement('SA02Styled'))->setSectionStyle('SA02Columns')
            ->addElement((new Paragraph())->addText('filled section')));

        $content = $this->workingEntry($template, 'content.xml');
        $xpath = $this->contentXPath($content);
        $style = $xpath->query('//office:automatic-styles/style:style[@style:name="SA02Columns" and @style:family="section"]')->item(0);
        self::assertNotNull($style);
        self::assertSame('true', $xpath->evaluate('string(style:section-properties/@text:dont-balance-text-columns)', $style));
        self::assertSame('3', $xpath->evaluate('string(style:section-properties/style:columns/@fo:column-count)', $style));
        self::assertSame('', $xpath->evaluate('string(style:section-properties/style:columns/@fo:column-gap)', $style));
        self::assertSame('10922*', $xpath->evaluate('string(style:section-properties/style:columns/style:column[1]/@style:rel-width)', $style));
        self::assertSame('21845*', $xpath->evaluate('string(style:section-properties/style:columns/style:column[2]/@style:rel-width)', $style));
        self::assertSame('32768*', $xpath->evaluate('string(style:section-properties/style:columns/style:column[3]/@style:rel-width)', $style));
        self::assertSame('0cm', $xpath->evaluate('string(style:section-properties/style:columns/style:column[1]/@fo:start-indent)', $style));
        self::assertSame('0.125cm', $xpath->evaluate('string(style:section-properties/style:columns/style:column[2]/@fo:start-indent)', $style));
        self::assertSame('solid', $xpath->evaluate('string(style:section-properties/style:columns/style:column-sep/@style:style)', $style));
        self::assertSame(3.0, $xpath->evaluate('count(style:section-properties/style:columns/style:column-sep/following-sibling::style:column)', $style));
        self::assertSame('#abcdef', $xpath->evaluate('string(style:section-properties/style:columns/style:column-sep/@style:color)', $style));
        self::assertSame('#123456', $xpath->evaluate('string(style:section-properties/@fo:background-color)', $style));
    }

    public function testAuthoredAutomaticStyleReferenceWorksAndMissingReferenceIsAtomic(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->appendSection((new SectionElement('SA02Authored'))->setSectionStyle('Sect1')
            ->addElement((new Paragraph())->addText('authored style')));
        self::assertStringContainsString('text:style-name="Sect1"', $this->workingEntry($template, 'content.xml'));
        $before = $this->workingEntry($template, 'content.xml');
        try {
            $template->appendSection((new SectionElement('SA02Missing'))->setSectionStyle('NoSuchSectionStyle'));
            self::fail('Expected missing Section style to fail.');
        } catch (SectionMutationException $exception) {
            self::assertStringContainsString('missing', $exception->reason());
        }
        self::assertSame($before, $this->workingEntry($template, 'content.xml'));
    }

    public function testAuthoredDefinitionCannotBeRedefinedAndLoadResetsGeneratedStyles(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $before = $this->workingEntry($template, 'content.xml');
        $this->expectException(\LogicException::class);
        try {
            $template->styles()->defineSection('Sect1', ['columns' => ['count' => 2]]);
        } finally {
            self::assertSame($before, $this->workingEntry($template, 'content.xml'));
        }
    }

    public function testEqualWidthsBalanceTrueAndNestedStyledSectionsRemainNative(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->defineSection('SA02Equal', [
            'columns' => ['count' => 2, 'relative-widths' => [1, 1], 'balance' => true],
        ]);
        $template->appendSection((new SectionElement('SA02Outer'))->setSectionStyle('SA02Equal')
            ->addSection((new SectionElement('SA02Inner'))->setSectionStyle('SA02Equal')
                ->addElement((new Paragraph())->addText('nested'))));
        $content = $this->workingEntry($template, 'content.xml');
        self::assertSame(2, substr_count($content, 'text:style-name="SA02Equal"'));
        self::assertStringContainsString('style:rel-width="32767*"', $content);
        self::assertStringContainsString('style:rel-width="32768*"', $content);
        self::assertStringContainsString('text:dont-balance-text-columns="false"', $content);
    }

    public function testEquivalentDefinitionIsIdempotentAndInvalidOptionsFailBeforeMutation(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $options = ['columns' => ['count' => 2, 'relative-widths' => [1, 1], 'balance' => true]];
        $template->styles()->defineSection('SA02Stable', $options);
        $template->styles()->defineSection('SA02Stable', $options);
        $before = $this->workingEntry($template, 'content.xml');
        try {
            $template->styles()->defineSection('SA02Stable', ['columns' => ['count' => 2, 'relative-widths' => [1, 0]]]);
            self::fail('Expected invalid relative width to fail.');
        } catch (\InvalidArgumentException) {
            self::assertSame($before, $this->workingEntry($template, 'content.xml'));
        }
    }

    public function testExplicitIndentsAndValidationAreBoundedAndAtomic(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->defineSection('SA02Indents', [
            'columns' => ['count' => 2, 'relative-widths' => [26214, 39321], 'column-indents' => [
                ['start' => '0cm', 'end' => '0.2cm'], ['start' => '0.3cm', 'end' => '0cm'],
            ]],
        ]);
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($this->workingEntry($template, 'content.xml')));
        $xpath = $this->contentXPath($dom->saveXML());
        $style = $xpath->query('//office:automatic-styles/style:style[@style:name="SA02Indents"]')->item(0);
        self::assertSame('26214*', $xpath->evaluate('string(style:section-properties/style:columns/style:column[1]/@style:rel-width)', $style));
        self::assertSame('0.2cm', $xpath->evaluate('string(style:section-properties/style:columns/style:column[1]/@fo:end-indent)', $style));
        $before = $this->workingEntry($template, 'content.xml');
        foreach ([
            ['columns' => ['count' => 0]],
            ['columns' => ['count' => 16384]],
            ['columns' => ['count' => 2, 'gap' => '1cm', 'column-indents' => [['start' => '0cm', 'end' => '0cm'], ['start' => '0cm', 'end' => '0cm']]]],
            ['columns' => ['count' => 2, 'separator' => ['style' => 'solid', 'height' => '1cm']]],
            ['columns' => ['count' => 2, 'separator' => ['style' => 'solid', 'color' => 'transparent']]],
        ] as $invalid) {
            try {
                $template->styles()->defineSection('SA02Invalid' . uniqid(), $invalid);
                self::fail('Expected invalid Section style options to fail.');
            } catch (\InvalidArgumentException) {
            }
        }
        self::assertSame($before, $this->workingEntry($template, 'content.xml'));
    }

    public function testEquivalentRatiosAreReducedBeforeMaterialization(): void
    {
        $template = new OdtTemplate($this->templatePath());
        foreach ([['SA02A', [1, 2]], ['SA02B', [2, 4]]] as [$name, $weights]) {
            $template->styles()->defineSection($name, ['columns' => ['count' => 2, 'relative-widths' => $weights]]);
        }
        $xpath = $this->contentXPath($this->workingEntry($template, 'content.xml'));
        foreach (['SA02A', 'SA02B'] as $name) {
            $nodes = $xpath->query(sprintf('//style:style[@style:name="%s"]/style:section-properties/style:columns/style:column/@style:rel-width', $name));
            self::assertSame(['21845*', '43690*'], array_map(static fn ($node): string => $node->nodeValue, iterator_to_array($nodes)));
        }
    }

    public function testNormalizerHandlesLargeEquivalentWeightsAndThreeColumnRatios(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $cases = [
            ['SA02Two', 2, [2, 3], ['26214*', '39321*']],
            ['SA02TwoScaled', 2, [2000, 3000], ['26214*', '39321*']],
            ['SA02Three', 3, [1, 2, 3], ['10922*', '21845*', '32768*']],
            ['SA02ThreeScaled', 3, [10000, 20000, 30000], ['10922*', '21845*', '32768*']],
            ['SA02Max', 2, [PHP_INT_MAX, PHP_INT_MAX], ['32767*', '32768*']],
        ];
        foreach ($cases as [$name, $count, $weights, $expected]) {
            $template->styles()->defineSection($name, ['columns' => ['count' => $count, 'relative-widths' => $weights]]);
            $xpath = $this->contentXPath($this->workingEntry($template, 'content.xml'));
            $nodes = $xpath->query(sprintf('//style:style[@style:name="%s"]/style:section-properties/style:columns/style:column/@style:rel-width', $name));
            self::assertSame($expected, array_map(static fn ($node): string => $node->nodeValue, iterator_to_array($nodes)));
        }
    }

    public function testZeroIdealWeightsAndIrreducibleOverflowAreRejected(): void
    {
        $template = new OdtTemplate($this->templatePath());
        foreach ([[1, 65535], [65535, 1], [PHP_INT_MAX, PHP_INT_MAX - 1]] as $weights) {
            $this->expectInvalidRelativeWidths($template, $weights);
        }
    }

    public function testCountOnlyColumnsHaveNoEmptyColumnChildrenAndZeroGapIsCanonical(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->defineSection('SA02CountOnly', ['columns' => ['count' => 3]]);
        $template->styles()->defineSection('SA02ZeroGap', ['columns' => ['count' => 2, 'gap' => '0']]);
        $xpath = $this->contentXPath($this->workingEntry($template, 'content.xml'));
        self::assertSame(0.0, $xpath->evaluate('count(//style:style[@style:name="SA02CountOnly"]//style:column)'));
        self::assertSame('0cm', $xpath->evaluate('string(//style:style[@style:name="SA02ZeroGap"]//style:columns/@fo:column-gap)'));
    }

    public function testAutomaticSeparatorAndImplicitEqualWeightsWithIndents(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->defineSection('SA02AutoSeparator', [
            'columns' => ['count' => 2, 'gap' => '0.4cm', 'separator' => ['style' => 'solid']],
        ]);
        $template->styles()->defineSection('SA02IndentOnly', [
            'columns' => ['count' => 2, 'column-indents' => [
                ['start' => '0cm', 'end' => '0.2cm'],
                ['start' => '0.3cm', 'end' => '0cm'],
            ]],
        ]);
        $template->styles()->defineSection('SA02MaxCount', ['columns' => ['count' => 16383]]);
        $xpath = $this->contentXPath($this->workingEntry($template, 'content.xml'));
        self::assertSame(0.0, $xpath->evaluate('count(//style:style[@style:name="SA02AutoSeparator"]//style:column)'));
        self::assertSame('solid', $xpath->evaluate('string(//style:style[@style:name="SA02AutoSeparator"]//style:column-sep/@style:style)'));
        $nodes = $xpath->query('//style:style[@style:name="SA02IndentOnly"]//style:column/@style:rel-width');
        self::assertSame(['32767*', '32768*'], array_map(static fn ($node): string => $node->nodeValue, iterator_to_array($nodes)));
        self::assertSame('0.3cm', $xpath->evaluate('string(//style:style[@style:name="SA02IndentOnly"]//style:column[2]/@fo:start-indent)'));
        self::assertSame('16383', $xpath->evaluate('string(//style:style[@style:name="SA02MaxCount"]//style:columns/@fo:column-count)'));
    }

    public function testTinySplitGapAndNonFiniteLengthAreRejected(): void
    {
        $template = new OdtTemplate($this->templatePath());
        foreach (['0.0000001cm', str_repeat('9', 400) . 'cm'] as $gap) {
            try {
                $template->styles()->defineSection('SA02Gap' . uniqid(), ['columns' => ['count' => 2, 'gap' => $gap, 'relative-widths' => [1, 2]]]);
                self::fail('Expected invalid gap to fail.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    /** @param list<int> $weights */
    private function expectInvalidRelativeWidths(OdtTemplate $template, array $weights): void
    {
        try {
            $template->styles()->defineSection('SA02Invalid' . uniqid(), ['columns' => ['count' => count($weights), 'relative-widths' => $weights]]);
            self::fail('Expected invalid relative widths to fail.');
        } catch (\InvalidArgumentException) {
            self::assertTrue(true);
        }
    }

    private function templatePath(): string
    {
        return __DIR__ . '/../fixtures/libreoffice-reference/odt/SECTION-AUTHORING-01-reference.odt';
    }

    private function workingEntry(OdtTemplate $template, string $entry): string
    {
        $path = sys_get_temp_dir() . '/sa02-' . uniqid('', true) . '.odt';
        $this->outputs[] = $path;
        $template->save($path);
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($entry);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function contentXPath(string $content): \DOMXPath
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new \DOMXPath($dom);
        foreach ([
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
            'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        ] as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }
        return $xpath;
    }
}
