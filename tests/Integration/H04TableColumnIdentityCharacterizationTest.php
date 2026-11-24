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
 * H04-B generated table-column identity regression coverage.
 *
 * H04-A evidence remains in the handoff document; these assertions cover the
 * approved definition-derived binding.
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
    public function testDifferentAbsoluteDefinitionsReceiveDistinctIdentities(): void
    {
        $template = $this->template();
        $template->appendBody($this->tableWithAbsoluteWidths(['5cm']));
        $template->appendBody($this->tableWithAbsoluteWidths(['7cm']));
        $output = $this->outputPath('different-definitions');
        $template->save($output);
        $content = $this->entry($output, 'content.xml');
        self::assertCount(2, $this->tableColumnStyleReferences($content));
        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testAbsoluteAndRelativeDefinitionsReceiveDistinctIdentities(): void
    {
        $template = $this->template();
        $template->appendBody($this->tableWithAbsoluteWidths(['5cm']));
        $template->appendBody($this->tableWithRelativeWidths([1]));
        $output = $this->outputPath('mixed-definitions');
        $template->save($output);
        self::assertCount(2, $this->tableColumnStyleReferences($this->entry($output, 'content.xml')));
    }

    #[RunInSeparateProcess]
    public function testIdenticalDefinitionsAreReusedAndMaterializedOnce(): void
    {
        $template = $this->template();
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm', '4cm']));
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm', '4cm']));

        $output = $this->outputPath('identical-definitions');
        $template->save($output);
        $content = $this->entry($output, 'content.xml');

        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertSame(2, count(array_unique($this->tableColumnStyleReferences($content))));
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

        self::assertContains('2cm', $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertSame('9cm', $this->columnProperty($content, 'co0', 'column-width'));
        self::assertCount(1, $this->tableColumnStyleReferences($content));
    }

    public function testDirectDomEmitsResolvedAbsoluteGeometry(): void
    {
        $dom = $this->contentDom();
        $table = $this->tableWithAbsoluteWidths(['3cm', '6cm']);
        $dom->documentElement->appendChild($table->toDomNode($dom));
        $content = $dom->saveXML() ?: '';

        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertCount(2, $this->tableColumnStyleReferences($content));
    }

    public function testDirectDomSeparatesDifferentDefinitions(): void
    {
        $dom = $this->contentDom();
        $first = $this->tableWithAbsoluteWidths(['2cm']);
        $second = $this->tableWithAbsoluteWidths(['7cm']);
        $dom->documentElement->appendChild($first->toDomNode($dom));
        $dom->documentElement->appendChild($second->toDomNode($dom));
        $content = $dom->saveXML() ?: '';

        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertCount(2, $this->tableColumnStyleReferences($content));
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
            self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
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

        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertCount(2, $this->tableColumnStyleReferences($content));
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
            self::assertSame(2, $this->xpath($content)->query('//table:table[@table:name="' . $name . '"]/table:table-column')->length);
        }
        self::assertCount(2, $this->columnPropertiesByWidth($content, 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testConstructionKeepsDifferentGeometryAndRetainsBothTables(): void
    {
        foreach ([$this->tableWithAbsoluteWidths(['7cm']), $this->tableWithRelativeWidths([1, 1])] as $second) {
            $template = $this->template();
            $template->appendBody($this->tableWithAbsoluteWidths(['5cm'])->setTableName('H04A'));
            $template->appendBody($second->setTableName('H04B'));
            $output = $this->outputPath('construction-conflict');
            $template->save($output);
            $content = $this->entry($output, 'content.xml');
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="H04A"]')->length);
            self::assertSame(1, $this->xpath($content)->query('//table:table[@table:name="H04B"]')->length);
            self::assertGreaterThanOrEqual(2, count($this->tableColumnStyleReferences($content)));
        }
    }

    public function testDirectRelativeDefinitionsAreMaterializedBySerializer(): void
    {
        $dom = $this->contentDom();
        $dom->documentElement->appendChild($this->tableWithRelativeWidths([1, 1])->toDomNode($dom));
        $content = $dom->saveXML() ?: '';
        self::assertCount(2, $this->tableColumnStyleReferences($content));
        self::assertCount(2, $this->columnPropertiesByWidth($content, 'rel-column-width'));
    }

    public function testDirectMixedGeometryUsesDistinctReferences(): void
    {
        $dom = $this->contentDom();
        $dom->documentElement->appendChild($this->tableWithAbsoluteWidths(['5cm'])->toDomNode($dom));
        $dom->documentElement->appendChild($this->tableWithRelativeWidths([1])->toDomNode($dom));
        $content = $dom->saveXML() ?: '';
        self::assertCount(2, $this->tableColumnStyleReferences($content));
        self::assertCount(1, $this->columnPropertiesByWidth($content, 'column-width'));
        self::assertCount(1, $this->columnPropertiesByWidth($content, 'rel-column-width'));
    }

    public function testDirectDomPreservesNamespaceConformantAuthoredDefinition(): void
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
        self::assertCount(1, $this->tableColumnStyleReferences($content));
        self::assertContains('2cm', $this->columnPropertiesByWidth($content, 'column-width'));
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
            self::assertSame($width, $this->columnPropertiesByWidth($content, 'column-width')[0]);
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
        self::assertSame('2cm', $this->columnPropertiesByWidth($this->entry($output, 'content.xml'), 'column-width')[0]);
        self::assertSame('9cm', $this->columnProperty($this->entry($output, 'styles.xml'), 'co0', 'column-width'));
    }

    #[RunInSeparateProcess]
    public function testReferencesResolveAcrossPositionsAndReload(): void
    {
        $template = $this->template();
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm', '4cm']));
        $template->appendBody($this->tableWithAbsoluteWidths(['4cm', '2cm']));
        $template->appendBody($this->tableWithRelativeWidths([1, 1]));
        $first = $this->outputPath('resolved-first');
        $template->save($first);
        $xml = $this->entry($first, 'content.xml');
        $refs = $this->tableColumnStyleReferences($xml);
        self::assertSame([$refs[0], $refs[1], $refs[1], $refs[0], $refs[4], $refs[5]], $refs);
        $this->assertResolvedGeometry($xml, ['2cm', '4cm', '4cm', '2cm', '32767*', '32768*']);
        $reloaded = new OdtTemplate($first);
        $this->templates[] = $reloaded;
        $reloaded->appendBody($this->tableWithAbsoluteWidths(['2cm']));
        $second = $this->outputPath('resolved-reload');
        $reloaded->save($second);
        $xml = $this->entry($second, 'content.xml');
        self::assertSame([...$refs, $refs[0]], $this->tableColumnStyleReferences($xml));
        $this->assertResolvedGeometry($xml, ['2cm', '4cm', '4cm', '2cm', '32767*', '32768*', '2cm']);
    }

    #[RunInSeparateProcess]
    public function testAuthoredCandidateCollisionPreservesDefinitionAndReferences(): void
    {
        $probe = $this->contentDom();
        $probe->documentElement->appendChild($this->tableWithAbsoluteWidths(['2cm'])->toDomNode($probe));
        $candidate = $this->tableColumnStyleReferences($probe->saveXML())[0];
        $template = $this->template();
        $this->appendAuthoredColumnStyle($template, $candidate, '9cm');
        $this->appendAuthoredColumnStyle($template, $candidate . '_1', '2cm');
        $dom = $template->documentContextForTest()->contentDom();
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->query('//style:style[@style:name="' . $candidate . '_1"]')->item(0)
            ->setAttributeNS(self::STYLE_NS, 'style:parent-style-name', 'AuthoredParent');
        $template->appendBody($this->tableWithAbsoluteWidths(['2cm']));
        $output = $this->outputPath('candidate-collision');
        $template->save($output);
        $xml = $this->entry($output, 'content.xml');
        self::assertSame([$candidate . '_2'], $this->tableColumnStyleReferences($xml));
        self::assertSame('9cm', $this->columnProperty($xml, $candidate, 'column-width'));
        $this->assertResolvedGeometry($xml, ['2cm']);
    }

    public function testExplicitColumnRequirementConflictsAreNotRenamed(): void
    {
        $context = new \OdtTemplateEngine\Style\StyleContext();
        $make = static fn(string $width) => new \OdtTemplateEngine\Document\StyleRequirement(
            'definition', 'automatic', 'table-column', 'content.xml', 'explicit', null,
            ['style:table-column-properties' => ['style:column-width' => $width]]
        );
        $context->registerRequirement($make('2cm'));
        $this->expectException(\LogicException::class);
        $context->registerRequirement($make('4cm'));
    }

    #[RunInSeparateProcess]
    public function testLateTableRendererFailureRollsBackColumnAllocation(): void
    {
        $template = $this->template();
        $before = $this->outputPath('rollback-before');
        $template->save($before);
        $table = new class extends RichTable {
            public function toDomNode(DOMDocument $dom): \DOMNode
            {
                parent::toDomNode($dom);
                throw new \RuntimeException('H04 late renderer failure');
            }
        };
        $table->setColumnWidths(['7cm']);
        $table->addRow(['A']);
        try {
            $template->appendBody($table);
            self::fail('Expected late renderer failure');
        } catch (\RuntimeException $exception) {
            self::assertSame('H04 late renderer failure', $exception->getMessage());
        }
        $after = $this->outputPath('rollback-after');
        $template->save($after);
        self::assertSame($this->entry($before, 'content.xml'), $this->entry($after, 'content.xml'));
        $template->appendBody($this->tableWithAbsoluteWidths(['7cm']));
        $output = $this->outputPath('rollback-retry');
        $template->save($output);
        $this->assertResolvedGeometry($this->entry($output, 'content.xml'), ['7cm']);
    }

    private function assertResolvedGeometry(string $xml, array $expected): void
    {
        $refs = $this->tableColumnStyleReferences($xml);
        self::assertCount(count($expected), $refs);
        foreach ($refs as $index => $name) {
            self::assertSame(1, $this->styleCount($xml, $name, 'table-column'));
            $property = str_ends_with($expected[$index], '*') ? 'rel-column-width' : 'column-width';
            self::assertSame($expected[$index], $this->columnProperty($xml, $name, $property));
        }
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
    private function columnPropertiesByWidth(string $xml, string $property): array
    {
        $xpath = $this->xpath($xml);
        $nodes = $xpath->query('//style:style[@style:family="table-column"]/style:table-column-properties/@style:' . $property);
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
