<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Style\StyleContext;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextParagraphTextFallbackCharacterizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-style-fallback-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    #[RunInSeparateProcess]
    public function testModernParagraphAndTextDefinitionsAreDocumentLocalBeforeMaterialization(): void
    {
        $paragraphName = 'SC01B_ModernParagraph_' . bin2hex(random_bytes(4));
        $richText = (new RichText())->addParagraph(
            (new Paragraph($paragraphName, ['margin-left' => '1cm']))
                ->addText('local text', ['font-weight' => 'bold'])
        );
        $semantic = iterator_to_array((new \OdtTemplateEngine\Document\StyleRequirementCollector())->collectSemantic($richText));
        $families = array_map(static fn (StyleRequirement $requirement): string => $requirement->family(), $semantic);

        self::assertContains('paragraph', $families);
        self::assertContains('text', $families);

        $template = $this->template();
        $template->setElement('my_list', $richText);

        $definitions = $template->auditStyleContext()->semanticDefinitions();
        self::assertTrue($this->hasDefinition($definitions, 'paragraph', $paragraphName));
        self::assertTrue($this->hasDefinition($definitions, 'text', 'auto_'));
        self::assertArrayNotHasKey($paragraphName, StyleMapper::getParagraphStyles());

        $output = $this->temporaryDirectory . '/modern.odt';
        $template->save($output);
        $styles = $this->zipEntry($output, 'styles.xml');
        self::assertStringContainsString('style:name="' . $paragraphName . '"', $styles);
        self::assertStringContainsString('fo:margin-left="1cm"', $styles);
        self::assertStringContainsString('fo:font-weight="bold"', $styles);
    }

    #[RunInSeparateProcess]
    public function testParagraphReferenceUsesLegacyStyleRegistryOnlyWhenLocalDefinitionIsAbsent(): void
    {
        $name = 'SC01B_LegacyParagraph_' . bin2hex(random_bytes(4));
        $definition = ['margin-left' => '3cm'];
        StyleMapper::registerParagraphStyle($name, $definition);

        $context = new StyleContext();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, $name);
        $context->registerRequirement($reference);

        self::assertSame([], $context->paragraphStyles());
        self::assertSame('legacy', $context->referenceResolution($reference));
        $requirements = $context->materializationRequirements();
        self::assertCount(1, $requirements);
        self::assertSame('paragraph', $requirements[0]->family());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_STYLES, $requirements[0]->documentPart());
        self::assertSame(['fo:margin-left' => '3cm'], $requirements[0]->propertyGroups()['style:paragraph-properties']);

        $template = $this->template();
        $template->setElement('my_list', (new RichText())->addParagraph(new Paragraph($name)));
        $output = $this->temporaryDirectory . '/legacy-paragraph.odt';
        $template->save($output);
        self::assertStringContainsString('style:name="' . $name . '"', $this->zipEntry($output, 'styles.xml'));
    }

    #[RunInSeparateProcess]
    public function testTextReferenceUsesStyleMapperTextRegistryAndNotLegacyParagraphRegistry(): void
    {
        $name = 'SC01B_LegacyText_' . bin2hex(random_bytes(4));
        StyleMapper::setTextStyle($name, ['font-weight' => 'bold']);

        $context = new StyleContext();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, $name);
        $context->registerRequirement($reference);

        self::assertSame([], $context->textStyles());
        self::assertSame('legacy', $context->referenceResolution($reference));
        $requirements = $context->materializationRequirements();
        self::assertCount(1, $requirements);
        self::assertSame('text', $requirements[0]->family());
        self::assertSame(['fo:font-weight' => 'bold'], $requirements[0]->propertyGroups()['style:text-properties']);
        self::assertArrayNotHasKey($name, StyleMapper::getParagraphStyles());
    }

    #[RunInSeparateProcess]
    public function testUnrelatedLegacyParagraphIsNotSerializedBySecondDocument(): void
    {
        $name = 'SC01B_IsolatedParagraph_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($name, ['margin-left' => '4cm']);

        $documentA = $this->template();
        $documentA->setElement('my_list', (new RichText())->addParagraph(new Paragraph($name)));
        $outputA = $this->temporaryDirectory . '/a.odt';
        $documentA->save($outputA);

        $documentB = $this->template();
        self::assertArrayNotHasKey($name, $documentB->auditStyleContext()->semanticDefinitions());
        $outputB = $this->temporaryDirectory . '/b.odt';
        $documentB->save($outputB);

        self::assertStringContainsString('style:name="' . $name . '"', $this->zipEntry($outputA, 'styles.xml'));
        self::assertStringNotContainsString('style:name="' . $name . '"', $this->zipEntry($outputB, 'styles.xml'));
        self::assertArrayHasKey($name, StyleMapper::getParagraphStyles());
    }

    #[RunInSeparateProcess]
    public function testUnrelatedLegacyTextIsNotSerializedBySecondDocument(): void
    {
        $name = 'SC01B_IsolatedText_' . bin2hex(random_bytes(4));
        StyleMapper::setTextStyle($name, ['color' => '#123456']);

        $documentA = $this->template();
        $documentA->setElement('my_list', new StyleContextLegacyTextReferenceElement($name));
        $outputA = $this->temporaryDirectory . '/text-a.odt';
        $documentA->save($outputA);

        $documentB = $this->template();
        $outputB = $this->temporaryDirectory . '/text-b.odt';
        $documentB->save($outputB);

        self::assertStringContainsString('style:name="' . $name . '"', $this->zipEntry($outputA, 'styles.xml'));
        self::assertStringNotContainsString('style:name="' . $name . '"', $this->zipEntry($outputB, 'styles.xml'));
        self::assertArrayHasKey($name, StyleMapper::getTextStyles());
    }

    #[RunInSeparateProcess]
    public function testModernParagraphDefinitionWinsOverSameNameGlobalDefinition(): void
    {
        $name = 'SC01B_CollisionParagraph_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($name, ['margin-left' => '5cm']);

        $template = $this->template();
        $paragraph = (new Paragraph($name, ['margin-left' => '1cm']))->addText('local definition');
        $template->setElement('my_list', (new RichText())->addParagraph($paragraph));

        $definition = $this->findDefinition($template->auditStyleContext()->semanticDefinitions(), 'paragraph', $name);
        self::assertNotNull($definition);
        self::assertSame('1cm', $definition->propertyGroups()['style:paragraph-properties']['fo:margin-left']);

        $output = $this->temporaryDirectory . '/collision.odt';
        $template->save($output);
        $styles = $this->zipEntry($output, 'styles.xml');
        $styleXml = $this->styleDefinition($styles, $name);
        self::assertStringContainsString('fo:margin-left="1cm"', $styleXml);
        self::assertStringNotContainsString('fo:margin-left="5cm"', $styleXml);
    }

    #[RunInSeparateProcess]
    public function testModernTextDefinitionWinsOverSameNameGlobalTextDefinition(): void
    {
        $name = 'SC01B_CollisionText_' . bin2hex(random_bytes(4));
        StyleMapper::setTextStyle($name, ['color' => '#aa0000']);

        $context = new StyleContext();
        $definition = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'text',
            StyleRequirement::PART_STYLES,
            $name,
            'Standard',
            ['style:text-properties' => ['fo:color' => '#00aa00']]
        );
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, $name);
        $context->registerRequirement($definition);
        $context->registerRequirement($reference);

        self::assertSame('document-local', $context->referenceResolution($reference));
        self::assertSame('#00aa00', $context->materializationRequirements()[0]->propertyGroups()['style:text-properties']['fo:color']);
    }

    #[RunInSeparateProcess]
    public function testModernDocumentsWithSameNameRemainIndependent(): void
    {
        $name = 'SC01B_SharedModernParagraph_' . bin2hex(random_bytes(4));
        $templateA = $this->template();
        $templateA->setElement('my_list', (new RichText())->addParagraph(
            (new Paragraph($name, ['margin-left' => '1cm']))->addText('A')
        ));
        $templateB = $this->template();
        $templateB->setElement('my_list', (new RichText())->addParagraph(
            (new Paragraph($name, ['margin-left' => '2cm']))->addText('B')
        ));

        $outputA = $this->temporaryDirectory . '/modern-a.odt';
        $outputB = $this->temporaryDirectory . '/modern-b.odt';
        $templateA->save($outputA);
        $templateB->save($outputB);

        $styleA = $this->styleDefinition($this->zipEntry($outputA, 'styles.xml'), $name);
        $styleB = $this->styleDefinition($this->zipEntry($outputB, 'styles.xml'), $name);
        self::assertStringContainsString('fo:margin-left="1cm"', $styleA);
        self::assertStringNotContainsString('fo:margin-left="2cm"', $styleA);
        self::assertStringContainsString('fo:margin-left="2cm"', $styleB);
        self::assertStringNotContainsString('fo:margin-left="1cm"', $styleB);
    }

    #[RunInSeparateProcess]
    public function testAuthoredParagraphAndTextDefinitionsTakePrecedenceOverGlobalFallbacks(): void
    {
        $paragraphName = 'SC01B_AuthoredParagraph_' . bin2hex(random_bytes(4));
        $textName = 'SC01B_AuthoredText_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($paragraphName, ['margin-left' => '8cm']);
        StyleMapper::setTextStyle($textName, ['color' => '#aa0000']);

        $styles = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($styles->loadXML(
            '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles>' .
            '<style:style style:name="' . $paragraphName . '" style:family="paragraph"/>' .
            '<style:style style:name="' . $textName . '" style:family="text"/>' .
            '</office:styles></office:document-styles>'
        ));
        $context = new StyleContext(null, $styles);
        $paragraphReference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, $paragraphName);
        $textReference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, $textName);
        $context->registerRequirement($paragraphReference);
        $context->registerRequirement($textReference);

        self::assertSame('document', $context->referenceResolution($paragraphReference));
        self::assertSame('document', $context->referenceResolution($textReference));
        self::assertSame([], $context->materializationRequirements());
    }

    #[RunInSeparateProcess]
    public function testLegacyFallbackRepeatedSaveAndLoadKeepGlobalStateButDoNotDuplicateStyles(): void
    {
        $name = 'SC01B_RepeatedLegacyParagraph_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($name, ['margin-left' => '6cm']);
        $template = $this->template();
        $template->setElement('my_list', (new RichText())->addParagraph(new Paragraph($name)));

        $first = $this->temporaryDirectory . '/first.odt';
        $second = $this->temporaryDirectory . '/second.odt';
        $template->save($first);
        $template->save($second);
        self::assertSame(1, substr_count($this->zipEntry($second, 'styles.xml'), 'style:name="' . $name . '"'));

        $template->load();
        self::assertSame([], $template->auditStyleContext()->semanticDefinitions());
        self::assertArrayHasKey($name, StyleMapper::getParagraphStyles());
    }

    #[RunInSeparateProcess]
    public function testStyleMapperAndDirectStyleWriterCompatibilityRemainSeparateFromTemplateIsolation(): void
    {
        $name = 'SC01B_DirectWriterParagraph_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($name, ['margin-left' => '7cm']);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'));
        \OdtTemplateEngine\Utils\StyleWriter::writeAllStyles($dom);
        self::assertStringContainsString('style:name="' . $name . '"', $dom->saveXML());

        $template = $this->template();
        $output = $this->temporaryDirectory . '/filtered.odt';
        $template->save($output);
        self::assertStringNotContainsString('style:name="' . $name . '"', $this->zipEntry($output, 'styles.xml'));
    }

    private function template(): StyleContextProbeTemplate
    {
        return new StyleContextProbeTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
    }

    /** @param array<string, StyleRequirement> $definitions */
    private function hasDefinition(array $definitions, string $family, string $namePrefix): bool
    {
        foreach ($definitions as $definition) {
            if ($definition->family() === $family && str_starts_with($definition->name(), $namePrefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, StyleRequirement> $definitions */
    private function findDefinition(array $definitions, string $family, string $name): ?StyleRequirement
    {
        foreach ($definitions as $definition) {
            if ($definition->family() === $family && $definition->name() === $name) {
                return $definition;
            }
        }

        return null;
    }

    private function zipEntry(string $path, string $entry): string
    {
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

    private function styleDefinition(string $styles, string $name): string
    {
        $quotedName = preg_quote($name, '/');
        self::assertSame(1, preg_match('/<style:style\\b[^>]*style:name="' . $quotedName . '".*?<\/style:style>/s', $styles, $matches));

        return $matches[0];
    }
}

final class StyleContextProbeTemplate extends OdtTemplate
{
    public function auditStyleContext(): StyleContext
    {
        return $this->documentContext()->styleContext();
    }
}

final class StyleContextLegacyTextReferenceElement extends OdtElement
{
    public function __construct(private string $styleName)
    {
    }

    public function getOwnStyleRequirements(): iterable
    {
        yield new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, $this->styleName);
    }

    public function registerStyles(): void
    {
    }

    public function getStyleDefinitions(): array
    {
        return [];
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $span = $dom->createElement('text:span');
        $span->setAttribute('text:style-name', $this->styleName);
        $span->appendChild($dom->createTextNode('legacy text reference'));
        $paragraph->appendChild($span);

        return $paragraph;
    }
}
