<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Template;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateProcessor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class AdjacentExpressionWhitespaceTest extends TestCase
{
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testAdjacentExpressionsDoNotGainASeparator(): void
    {
        self::assertSame('FooBar', $this->replace('<text:p>{{a}}{{b}}</text:p>', ['a' => 'Foo', 'b' => 'Bar']));
    }

    public function testAuthoredSeparatorsRemainExact(): void
    {
        foreach ([
            ' ' => 'Foo Bar',
            '-' => 'Foo-Bar',
            '/' => 'Foo/Bar',
            ', ' => 'Foo, Bar',
        ] as $separator => $expected) {
            self::assertSame($expected, $this->replace('<text:p>{{a}}' . $separator . '{{b}}</text:p>', ['a' => 'Foo', 'b' => 'Bar']));
        }
    }

    public function testTextSpaceElementAndTextBoxRemainStructural(): void
    {
        $dom = $this->document('<draw:text-box xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"><text:p><text:span>{{a}}</text:span><text:s text:c="1"/><text:span>{{b}}</text:span></text:p></draw:text-box>');
        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['a' => 'Foo', 'b' => 'Bar'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);

        self::assertSame('FooBar', $dom->documentElement->textContent);
        self::assertSame('text:s', $dom->documentElement->firstChild->firstChild->childNodes->item(1)->nodeName);
    }

    public function testSameAndDifferentStyleAdjacentSpansRemainStyled(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T1">{{a}}</text:span><text:span text:style-name="T2">{{b}}</text:span></text:p>');
        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['a' => 'Foo', 'b' => 'Bar'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);
        $paragraph = $dom->documentElement->firstChild;

        self::assertSame('Foo', $paragraph->childNodes->item(0)->textContent);
        self::assertSame('Bar', $paragraph->childNodes->item(1)->textContent);
        self::assertSame('T1', $paragraph->childNodes->item(0)->getAttribute('text:style-name'));
        self::assertSame('T2', $paragraph->childNodes->item(1)->getAttribute('text:style-name'));
    }

    public function testSample25LoadSavePreservesAuthoredHeaderSeparator(): void
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt');
        $output = $this->tempFile('odt-adjacent-output-');
        $template->save($output);

        $xml = $this->zipEntry($output, 'content.xml');
        self::assertStringContainsString('{{firstname}}</text:span><text:span text:style-name="T2"> </text:span><text:span text:style-name="T3">{{lastname}}', $xml);
    }

    public function testNoSeparatorRoundTripPreservesAdjacency(): void
    {
        $templatePath = $this->noSeparatorTemplate();
        $template = new OdtTemplate($templatePath);
        $output = $this->tempFile('odt-adjacent-roundtrip-');
        $template->save($output);

        $xml = $this->zipEntry($output, 'content.xml');
        self::assertStringContainsString('{{firstname}}</text:span><text:span text:style-name="T3">{{lastname}}', $xml);
        self::assertStringNotContainsString('{{firstname}} {{lastname}}', $xml);
    }

    private function replace(string $content, array $values): string
    {
        $dom = $this->document($content);
        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, $values, static fn (string $filter, mixed $value, ?string $option): string => (string) $value);
        return $dom->documentElement->firstChild->textContent;
    }

    private function document(string $content): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML('<root xmlns:text="' . self::TEXT . '">' . $content . '</root>'));
        return $dom;
    }

    private function noSeparatorTemplate(): string
    {
        $source = dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt';
        $target = $this->tempFile('odt-adjacent-template-');
        $input = new ZipArchive();
        self::assertSame(true, $input->open($source));
        $output = new ZipArchive();
        self::assertSame(true, $output->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        for ($i = 0; $i < $input->numFiles; $i++) {
            $name = $input->getNameIndex($i);
            $content = $input->getFromIndex($i);
            if ($name === 'content.xml') {
                $content = str_replace(
                    '<text:span text:style-name="T3">{{firstname}}</text:span><text:span text:style-name="T2"> </text:span><text:span text:style-name="T3">{{lastname}}</text:span>',
                    '<text:span text:style-name="T3">{{firstname}}</text:span><text:span text:style-name="T3">{{lastname}}</text:span>',
                    $content
                );
            }
            $output->addFromString($name, $content);
        }
        $input->close();
        $output->close();
        return $target;
    }

    private function zipEntry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($path));
        $content = $zip->getFromName($name);
        $zip->close();
        self::assertIsString($content);
        return $content;
    }

    private function tempFile(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        self::assertNotFalse($path);
        $this->files[] = $path;
        return $path;
    }
}
