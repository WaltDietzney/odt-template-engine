<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceRequirementConflictException;
use OdtTemplateEngine\Document\FontFaceRequirementDiscovery;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class FontFaceRequirementDiscoveryTest extends TestCase
{
    public function testDiscoversIndependentIdentityAndQuotedFamily(): void
    {
        $result = (new FontFaceRequirementDiscovery())->discover($this->definition(
            StyleRequirement::PART_STYLES,
            ['style:font-name' => 'Liberation Sans1', 'fo:font-family' => "'Liberation Sans'"]
        ));

        self::assertNotNull($result);
        self::assertSame(FontFaceRequirement::PART_STYLES, $result->documentPart());
        self::assertSame('Liberation Sans1', $result->fontFaceName());
        self::assertSame('Liberation Sans', $result->fontFamily());
    }

    public function testMapsContentPartWithoutMaterializingAnything(): void
    {
        $result = (new FontFaceRequirementDiscovery())->discover($this->definition(
            StyleRequirement::PART_CONTENT,
            ['style:font-name' => 'ContentIdentity', 'fo:font-family' => 'Content Family']
        ));

        self::assertNotNull($result);
        self::assertSame(FontFaceRequirement::PART_CONTENT, $result->documentPart());
        self::assertSame('Content Family', $result->fontFamily());
    }

    public function testReferenceAndIncompleteFontPropertiesAreNotDiscovered(): void
    {
        $discovery = new FontFaceRequirementDiscovery();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, 'Named');

        self::assertNull($discovery->discover($reference));
        self::assertNull($discovery->discover($this->definition(StyleRequirement::PART_STYLES, ['style:font-name' => 'OnlyIdentity'])));
        self::assertNull($discovery->discover($this->definition(StyleRequirement::PART_STYLES, ['fo:font-family' => 'Only Family'])));
        self::assertNull($discovery->discover($this->definition(StyleRequirement::PART_STYLES, [])));
    }

    public function testMultipleDependenciesAndEquivalentDuplicatesRegisterThroughContext(): void
    {
        $context = $this->context();
        $discovery = new FontFaceRequirementDiscovery();
        $requirements = [
            $this->definition(StyleRequirement::PART_STYLES, ['style:font-name' => 'FontA', 'fo:font-family' => 'Family A']),
            $this->definition(StyleRequirement::PART_STYLES, ['style:font-name' => 'FontA', 'fo:font-family' => 'Family A']),
            $this->definition(StyleRequirement::PART_CONTENT, ['style:font-name' => 'FontA', 'fo:font-family' => 'Family B']),
            $this->definition(StyleRequirement::PART_STYLES, ['style:font-name' => 'FontB', 'fo:font-family' => 'Family A']),
        ];

        foreach ($discovery->discoverAll($requirements) as $fontRequirement) {
            $context->registerFontFaceRequirement($fontRequirement);
        }

        self::assertCount(3, $context->fontFaceRequirements()->requirements());
    }

    public function testConflictingDiscoveredDependencyIsDelegatedToRegistry(): void
    {
        $context = $this->context();
        $discovery = new FontFaceRequirementDiscovery();
        $context->registerFontFaceRequirement($discovery->discover($this->definition(
            StyleRequirement::PART_STYLES,
            ['style:font-name' => 'Shared', 'fo:font-family' => 'Family A']
        )));

        $this->expectException(FontFaceRequirementConflictException::class);
        $context->registerFontFaceRequirement($discovery->discover($this->definition(
            StyleRequirement::PART_STYLES,
            ['style:font-name' => 'Shared', 'fo:font-family' => 'Family B']
        )));
    }

    public function testDiscoveryIntegrationRegistersSemanticParagraphDependency(): void
    {
        $template = new class (dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt') extends OdtTemplate {
            public function exposedContext(): OdtDocumentContext
            {
                return $this->documentContext();
            }
        };
        $paragraph = new Paragraph('SR05C_Paragraph', ['font-family' => 'SR05C Family']);
        $template->setElement('my_list', $paragraph);

        $requirements = $template->exposedContext()->fontFaceRequirements()->requirements();

        self::assertCount(1, $requirements);
        self::assertSame('SR05C Family', $requirements[0]->fontFamily());
        self::assertSame(FontFaceRequirement::PART_STYLES, $requirements[0]->documentPart());
    }

    private function definition(string $part, array $properties): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'text',
            $part,
            'FontStyle',
            'Standard',
            ['style:text-properties' => $properties]
        );
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext($this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'), $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'), $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'));
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }
}
