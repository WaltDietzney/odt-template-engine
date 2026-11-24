<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LogicException;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * H04-A current-behavior evidence for generated table-column identities.
 *
 * These tests preserve the observed coN behavior as a characterization
 * boundary. They do not authorize a collision-renaming implementation.
 */
final class H04TableColumnIdentityCharacterizationTest extends TestCase
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

    /** @var list<OdtTemplate> */
    private array $templates = [];

    /** @var list<string> */
    private array $outputs = [];

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

    #[RunInSeparateProcess]
    public function testDifferentAbsoluteDefinitionsCollideAtDocumentScope(): void
    {
        $template = $this->template();
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['5cm']));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Table-column style "co0" is already registered with a different definition');
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['7cm']));
    }

    #[RunInSeparateProcess]
    public function testAbsoluteAndRelativeDefinitionsCollideAtDocumentScope(): void
    {
        $template = $this->template();
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['5cm']));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Table-column style "co0" is already registered with a different definition');
        $template->setElement('tableblock', $this->tableWithRelativeWidths([1]));
    }

    #[RunInSeparateProcess]
    public function testIdenticalDefinitionsAreReusedAndMaterializedOnce(): void
    {
        $template = $this->template();
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['2cm', '4cm']));
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['2cm', '4cm']));

        $output = $this->outputPath('identical-definitions');
        $template->save($output);
        $content = $this->entry($output, 'content.xml');

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame('2cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertSame('4cm', $this->columnProperty($content, 'co1', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testAuthoredCo0RemainsAuthoritativeOverGeneratedDefinition(): void
    {
        $template = $this->template();
        $this->appendAuthoredColumnStyle($template, 'co0', '9cm');
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['2cm']));

        $output = $this->outputPath('authored-co0');
        $template->save($output);
        $content = $this->entry($output, 'content.xml');

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame('9cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertSame(['co0'], $this->tableColumnStyleReferences($content));
    }

    public function testDirectDomUsesPositionalNamesAndEmitsResolvedAbsoluteGeometry(): void
    {
        $dom = $this->contentDom();
        $table = $this->tableWithAbsoluteWidths(['3cm', '6cm']);
        $dom->documentElement->appendChild($table->toDomNode($dom));
        $content = $dom->saveXML() ?: '';

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(['co0', 'co1'], $this->tableColumnStyleReferences($content));
        self::assertSame('3cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertSame('6cm', $this->columnProperty($content, 'co1', 'column-width'));
    }

    public function testDirectDomAppendsASecondConflictingCo0Definition(): void
    {
        $dom = $this->contentDom();
        $first = $this->tableWithAbsoluteWidths(['2cm']);
        $second = $this->tableWithAbsoluteWidths(['7cm']);
        $dom->documentElement->appendChild($first->toDomNode($dom));
        $dom->documentElement->appendChild($second->toDomNode($dom));
        $content = $dom->saveXML() ?: '';

        self::assertSame(2, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(['co0', 'co0'], $this->tableColumnStyleReferences($content));
        self::assertSame(['2cm', '7cm'], $this->columnProperties($content, 'co0', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testRepeatedSaveKeepsOneDefinitionAndResolvedGeometry(): void
    {
        $template = $this->template();
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['2cm', '4cm']));
        $first = $this->outputPath('repeated-save-first');
        $second = $this->outputPath('repeated-save-second');
        $template->save($first);
        $template->save($second);

        foreach ([$first, $second] as $output) {
            $content = $this->entry($output, 'content.xml');
            self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
            self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
            self::assertSame('2cm', $this->columnProperty($content, 'co0', 'column-width'));
            self::assertSame('4cm', $this->columnProperty($content, 'co1', 'column-width'));
        }
    }

    #[RunInSeparateProcess]
    public function testReloadedDocumentPreservesResolvedGeometry(): void
    {
        $template = $this->template();
        $template->setElement('tableblock', $this->tableWithAbsoluteWidths(['2cm', '4cm']));
        $first = $this->outputPath('reload-first');
        $second = $this->outputPath('reload-second');
        $template->save($first);

        $reloaded = new OdtTemplate($first);
        $this->templates[] = $reloaded;
        $reloaded->save($second);
        $content = $this->entry($second, 'content.xml');

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(['co0', 'co1'], $this->tableColumnStyleReferences($content));
        self::assertSame('2cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertSame('4cm', $this->columnProperty($content, 'co1', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testConstructionKeepsTwoIndependentTablesWithEqualDefinitions(): void
    {
        $template = $this->template();
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm', '4cm'])->setTableName('H04A'));
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm', '4cm'])->setTableName('H04B'));
        $template->render();
        $template->render();
        $output = $this->outputPath('construction-equal');
        $template->save($output);
        $content = $this->entry($output, 'content.xml');
        foreach (['H04A', 'H04B'] as $name) {
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="' . $name . '"]')->length);
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="' . $name . '"]/table:table-column[@table:style-name="co0"]')->length);
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="' . $name . '"]/table:table-column[@table:style-name="co1"]')->length);
        }
        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame('2cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertSame('4cm', $this->columnProperty($content, 'co1', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testConstructionRejectsDifferentGeometryAndRetainsFirstTable(): void
    {
        foreach ([$this->tableWithAbsoluteWidths(['7cm']), $this->tableWithRelativeWidths([1, 1])] as $second) {
            $template = $this->template();
            $template->appendBody($this->tableWithAbsoluteWidths(['5cm'])->setTableName('H04A'));
            try {
                $template->appendBody($second->setTableName('H04B'));
                self::fail('Expected positional column conflict');
            } catch (LogicException $exception) {
                self::assertStringContainsString('co0', $exception->getMessage());
            }
            $output = $this->outputPath('construction-conflict');
            $template->save($output);
            $content = $this->entry($output, 'content.xml');
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="H04A"]/table:table-column[@table:style-name="co0"]')->length);
            self::assertSame(0, $this->xpath($content)->query('//table:table[@table:name="H04B"]')->length);
            self::assertSame('5cm', $this->columnProperty($content, 'co0', 'column-width'));
        }
    }

    public function testDirectRelativeReferencesAreNotMaterializedBySerializer(): void
    {
        $dom = $this->contentDom();
        $dom->documentElement->appendChild($this->tableWithRelativeWidths([1, 1])->toDomNode($dom));
        $content = $dom->saveXML() ?: '';
        self::assertSame(['co0', 'co1'], $this->tableColumnStyleReferences($content));
        self::assertSame(0, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(0, $this->styleCount($content, 'co1', 'table-column'));
    }

    public function testDirectMixedGeometryReusesAbsoluteReference(): void
    {
        $dom = $this->contentDom();
        $dom->documentElement->appendChild($this->tableWithAbsoluteWidths(['5cm'])->toDomNode($dom));
        $dom->documentElement->appendChild($this->tableWithRelativeWidths([1])->toDomNode($dom));
        $content = $dom->saveXML() ?: '';
        self::assertSame(['co0', 'co0'], $this->tableColumnStyleReferences($content));
        self::assertSame('5cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertNull($this->columnProperty($content, 'co0', 'rel-column-width'));
    }

    public function testDirectDomReusesNamespaceConformantAuthoredDefinition(): void
    {
        $dom = $this->contentDom();
        $style = $dom->createElementNS(self::STYLE_NS, 'style:style');
        $style->setAttributeNS(self::STYLE_NS, 'style:name', 'co0');
        $style->setAttributeNS(self::STYLE_NS, 'style:family', 'table-column');
        $properties = $dom->createElementNS(self::STYLE_NS, 'style:table-column-properties');
        $properties->setAttributeNS(self::STYLE_NS, 'style:column-width', '9cm');
        $style->appendChild($properties);
        $dom->getElementsByTagNameNS(self::OFFICE_NS, 'automatic-styles')->item(0)->appendChild($style);
        $dom->documentElement->appendChild($this->tableWithAbsoluteWidths(['2cm'])->toDomNode($dom));
        $content = $dom->saveXML() ?: '';
        self::assertSame(['co0'], $this->tableColumnStyleReferences($content));
        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame('9cm', $this->columnProperty($content, 'co0', 'column-width'));
    }

    public function testPublicWriterRetainsPositionalReturnAndDuplicates(): void
    {
        $dom = $this->contentDom();
        self::assertSame(['co0'], \OdtTemplateEngine\Utils\StyleWriter::writeColumnStyles($dom, ['2cm']));
        self::assertSame(['co0'], \OdtTemplateEngine\Utils\StyleWriter::writeColumnStyles($dom, ['7cm']));
        self::assertSame(['2cm', '7cm'], $this->columnProperties($dom->saveXML() ?: '', 'co0', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testIndependentDocumentsKeepSeparateGeometry(): void
    {
        $first = $this->template();
        $second = $this->template();
        foreach ([[$first, '2cm'], [$second, '7cm']] as [$template, $width]) {
            $template->appendBody($this->tableWithAbsoluteWidths([$width])->setTableName('H04Independent'));
            $output = $this->outputPath('independent');
            $template->save($output);
            $content = $this->entry($output, 'content.xml');
            self::assertSame($width, $this->columnProperty($content, 'co0', 'column-width'));
            self::assertSame(0, $this->styleCount($this->entry($output, 'styles.xml'), 'co0', 'table-column'));
        }
    }

    #[RunInSeparateProcess]
    public function testSameNameInStylesPartDoesNotSuppressContentDefinition(): void
    {
        $template = $this->template();
        $dom = $template->documentContextForTest()->stylesDom();
        $automatic = $dom->getElementsByTagNameNS(self::OFFICE_NS, 'automatic-styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $automatic);
        $style = $dom->createElementNS(self::STYLE_NS, 'style:style');
        $style->setAttributeNS(self::STYLE_NS, 'style:name', 'co0');
        $style->setAttributeNS(self::STYLE_NS, 'style:family', 'table-column');
        $properties = $dom->createElementNS(self::STYLE_NS, 'style:table-column-properties');
        $properties->setAttributeNS(self::STYLE_NS, 'style:column-width', '9cm');
        $style->appendChild($properties);
        $automatic->appendChild($style);
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm']));
        $output = $this->outputPath('part-isolation');
        $template->save($output);
        self::assertSame('2cm', $this->columnProperty($this->entry($output, 'content.xml'), 'co0', 'column-width'));
        self::assertSame('9cm', $this->columnProperty($this->entry($output, 'styles.xml'), 'co0', 'column-width'));
    }

    private function template(): OdtTemplate
    {
        $template = new H04InspectableTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt');
        $this->templates[] = $template;

        return $template;
    }

    /** @param list<string> $widths */
    private function tableWithAbsoluteWidths(array $widths): RichTable
    {
        $table = new RichTable();
        $table->setColumnWidths($widths);
        return $table->addRow(array_fill(0, count($widths), 'A'));
    }

    /** @param list<int> $ratios */
    private function tableWithRelativeWidths(array $ratios): RichTable
    {
        $table = new RichTable();
        $table->setColumnWidthRatios($ratios);
        return $table->addRow(array_fill(0, count($ratios), 'A'));
    }

    private function appendAuthoredColumnStyle(OdtTemplate $template, string $name, string $width): void
    {
        $dom = $template->documentContextForTest()->contentDom();
        $automatic = $dom->getElementsByTagNameNS(self::OFFICE_NS, 'automatic-styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $automatic);
        $style = $dom->createElementNS(self::STYLE_NS, 'style:style');
        $style->setAttributeNS(self::STYLE_NS, 'style:name', $name);
        $style->setAttributeNS(self::STYLE_NS, 'style:family', 'table-column');
        $properties = $dom->createElementNS(self::STYLE_NS, 'style:table-column-properties');
        $properties->setAttributeNS(self::STYLE_NS, 'style:column-width', $width);
        $style->appendChild($properties);
        $automatic->appendChild($style);
    }

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(self::OFFICE_NS, 'office:document-content');
        $dom->appendChild($root);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:style', self::STYLE_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:table', self::TABLE_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $root->appendChild($dom->createElementNS(self::OFFICE_NS, 'office:automatic-styles'));

        return $dom;
    }

    private function outputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/h04-a-' . $suffix . '-' . bin2hex(random_bytes(5)) . '.odt';
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

    private function styleCount(string $xml, string $name, string $family): int
    {
        $xpath = $this->xpath($xml);

        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
            $name,
            $family
        ))?->length ?? 0;
    }

    private function columnProperty(string $xml, string $name, string $property): ?string
    {
        $values = $this->columnProperties($xml, $name, $property);

        return $values[0] ?? null;
    }

    /** @return list<string> */
    private function columnProperties(string $xml, string $name, string $property): array
    {
        $xpath = $this->xpath($xml);
        $nodes = $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="table-column"]/style:table-column-properties/@style:%s',
            $name,
            $property
        ));
        $values = [];
        if ($nodes === false) {
            return $values;
        }
        foreach ($nodes as $node) {
            $values[] = $node->nodeValue ?? '';
        }

        return $values;
    }

    /** @return list<string> */
    private function tableColumnStyleReferences(string $xml): array
    {
        $xpath = $this->xpath($xml);
        $nodes = $xpath->query('//table:table-column/@table:style-name');
        $references = [];
        if ($nodes === false) {
            return $references;
        }
        foreach ($nodes as $node) {
            $references[] = $node->nodeValue ?? '';
        }

        return $references;
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('table', self::TABLE_NS);

        return $xpath;
    }
}

final class H04InspectableTemplate extends OdtTemplate
{
    public function documentContextForTest(): \OdtTemplateEngine\OdtDocumentContext
    {
        return $this->documentContext();
    }
}
