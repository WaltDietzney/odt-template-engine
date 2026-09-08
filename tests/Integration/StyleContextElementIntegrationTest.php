<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMElement;
use LogicException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextElementIntegrationTest extends TestCase
{
    public function testSetElementRegistersParagraphRequirementInCurrentDocument(): void
    {
        $style = '01D_Paragraph_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $this->richText($style, ['margin-left' => '1cm']));

        $definition = $this->semanticDefinition($template, $style);
        self::assertNotNull($definition);
        self::assertSame(['fo:margin-left' => '1cm'], $definition->propertyGroups()['style:paragraph-properties']);
    }

    public function testSetElementRegistersTextRequirementInCurrentDocument(): void
    {
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $textStyle = ['font-family' => '01FC Context Font', 'color' => '#123456'];
        $element = (new RichText())->addParagraph(
            (new Paragraph())->addText('Document-local text', $textStyle)
        );
        $style = null;
        foreach ($element->ownedElements() as $paragraph) {
            foreach ($paragraph->getOwnStyleRequirements() as $requirement) {
                if ($requirement->family() === 'text') {
                    $style = $requirement->name();
                    break 2;
                }
            }
        }

        self::assertIsString($style);
        $template->setElement('my_list', $element);

        self::assertNotNull($this->semanticDefinition($template, (string) $style));
    }

    public function testSetElementRegistersSemanticParagraphRequirementInCurrentDocument(): void
    {
        $style = '01D_Semantic_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $this->richText($style, ['margin-left' => '1cm']));

        $requirements = array_values($template->semanticDefinitions());
        self::assertCount(1, $requirements);
        self::assertSame('paragraph', $requirements[0]->family());
        self::assertSame($style, $requirements[0]->name());
        self::assertSame(['style:paragraph-properties' => ['fo:margin-left' => '1cm']], $requirements[0]->propertyGroups());
    }

    public function testSemanticParagraphAndTextDefinitionsDoNotCallLegacyPhysicalEnsurers(): void
    {
        $style = '01D_Owner_' . bin2hex(random_bytes(4));
        $template = new StyleContextMaterializationOwnershipProbe($this->templatePath());
        $element = (new RichText())->addParagraph(
            (new Paragraph($style, ['margin-left' => '1cm']))->addText('Owned text', ['color' => '#123456'])
        );
        $template->resetEnsureCounters();

        $template->setElement('my_list', $element);

        self::assertSame(0, $template->paragraphEnsureCalls);
        self::assertSame(0, $template->textEnsureCalls);
        self::assertCount(2, $template->semanticDefinitions());
    }

    public function testMissingSemanticParagraphReferenceRemainsNonFatal(): void
    {
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $template->setElement('my_list', new Paragraph('MissingSemanticStyle'));

        self::assertCount(1, $template->semanticReferences());
        self::assertCount(1, $template->unresolvedSemanticReferences());
        self::assertSame('MissingSemanticStyle', $template->semanticReferences()[0]->name());
    }

    public function testStructuredElementRequirementsAreDocumentIsolated(): void
    {
        $styleA = '01D_A_' . bin2hex(random_bytes(4));
        $styleB = '01D_B_' . bin2hex(random_bytes(4));
        $templateA = new StyleContextInspectableTemplate($this->templatePath());
        $templateB = new StyleContextInspectableTemplate($this->templatePath());

        $templateA->setElement('my_list', $this->richText($styleA, ['margin-left' => '2cm']));
        $templateB->setElement('my_list', $this->richText($styleB, ['margin-left' => '3cm']));

        self::assertNotNull($this->semanticDefinition($templateA, $styleA));
        self::assertNull($this->semanticDefinition($templateB, $styleA));
        self::assertNotNull($this->semanticDefinition($templateB, $styleB));
        self::assertNull($this->semanticDefinition($templateA, $styleB));
    }

    public function testEquivalentRepeatedRequirementsAreIdempotent(): void
    {
        $style = '01D_Equivalent_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $template->appendPlaceholder('my_list_second');
        $element = $this->richText($style, ['margin-left' => '4cm']);

        $template->setElement('my_list', $element);
        $template->setElement('my_list_second', $this->richText($style, ['margin-left' => '4cm']));

        self::assertCount(1, $template->semanticDefinitions());
        self::assertNotNull($this->semanticDefinition($template, $style));
    }

    public function testConflictingPendingRequirementFailsBeforeSecondElementMaterialization(): void
    {
        $style = '01D_Conflict_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $template->appendPlaceholder('my_list_second');
        $template->setElement('my_list', $this->richText($style, ['margin-left' => '5cm']));
        $before = $template->contentXml();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already registered with a different definition');

        try {
            $template->setElement('my_list_second', $this->richText($style, ['margin-left' => '6cm']));
        } finally {
            self::assertSame($before, $template->contentXml());
        }
    }

    public function testLoadResetsRequirementsCollectedThroughSetElement(): void
    {
        $style = '01D_Reset_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $this->richText($style, ['margin-left' => '7cm']));
        self::assertNotNull($this->semanticDefinition($template, $style));

        $template->load();

        self::assertNull($this->semanticDefinition($template, $style));
    }

    public function testSetElementPersistsRegisteredParagraphStyleInSavedStylesXml(): void
    {
        $style = '01D_Persisted_' . bin2hex(random_bytes(4));
        $output = sys_get_temp_dir() . '/odt-style-context-element-' . bin2hex(random_bytes(6)) . '.odt';

        try {
            $template = new StyleContextInspectableTemplate($this->templatePath());
            $template->setElement('my_list', $this->richText($style, ['margin-left' => '8cm']));
            $template->save($output);

            $archive = new ZipArchive();
            self::assertSame(true, $archive->open($output));

            try {
                $stylesXml = $archive->getFromName('styles.xml');

                self::assertIsString($stylesXml);
                self::assertStringContainsString('style:name="' . $style . '"', $stylesXml);
                self::assertStringContainsString('fo:margin-left="8cm"', $stylesXml);
            } finally {
                $archive->close();
            }
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    private function richText(string $style, array $options): RichText
    {
        $paragraph = new Paragraph($style, $options);
        $paragraph->addText('Structured paragraph');

        return (new RichText())->addParagraph($paragraph);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
    }

    private function semanticDefinition(StyleContextInspectableTemplate $template, string $name): ?\OdtTemplateEngine\Document\StyleRequirement
    {
        foreach ($template->semanticDefinitions() as $definition) {
            if ($definition->name() === $name) {
                return $definition;
            }
        }

        return null;
    }
}

final class StyleContextInspectableTemplate extends OdtTemplate
{
    /** @return array<string, \OdtTemplateEngine\Document\StyleRequirement> */
    public function semanticDefinitions(): array
    {
        return $this->documentContext()->styleContext()->semanticDefinitions();
    }

    /** @return list<\OdtTemplateEngine\Document\StyleRequirement> */
    public function semanticReferences(): array
    {
        return $this->documentContext()->styleContext()->semanticReferences();
    }

    /** @return list<\OdtTemplateEngine\Document\StyleRequirement> */
    public function unresolvedSemanticReferences(): array
    {
        return $this->documentContext()->styleContext()->unresolvedReferences();
    }

    public function appendPlaceholder(string $name): void
    {
        $dom = $this->documentContext()->contentDom();
        $paragraph = $dom->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'p'
        )->item(0);
        if (!$paragraph instanceof DOMElement) {
            throw new \RuntimeException('Template fixture has no paragraph.');
        }
        $paragraph->appendChild($dom->createTextNode('{{' . $name . '}}'));
    }

    public function contentXml(): string
    {
        return $this->documentContext()->contentDom()->saveXML() ?: '';
    }
}

final class StyleContextMaterializationOwnershipProbe extends OdtTemplate
{
    public int $paragraphEnsureCalls = 0;
    public int $textEnsureCalls = 0;

    public function resetEnsureCounters(): void
    {
        $this->paragraphEnsureCalls = 0;
        $this->textEnsureCalls = 0;
    }

    /** @return array<string, \OdtTemplateEngine\Document\StyleRequirement> */
    public function semanticDefinitions(): array
    {
        return $this->documentContext()->styleContext()->semanticDefinitions();
    }

    public function ensureParagraphStylesExist(array $styleMap): void
    {
        $this->paragraphEnsureCalls++;
        parent::ensureParagraphStylesExist($styleMap);
    }

    protected function ensureTextStylesExist(array $styleMap): void
    {
        $this->textEnsureCalls++;
        parent::ensureTextStylesExist($styleMap);
    }
}
