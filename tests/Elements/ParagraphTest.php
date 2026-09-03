<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;

final class ParagraphTest extends TestCase
{
    public function testRendersPlainTextLineBreakAndTab(): void
    {
        $paragraph = (new Paragraph())
            ->addText('Hello')
            ->addLineBreak()
            ->addTab()
            ->addText('World');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $paragraph->toDomNode($dom);

        self::assertSame('text:p', $node->nodeName);
        self::assertSame('Standard', $node->getAttribute('text:style-name'));
        self::assertSame('HelloWorld', $node->textContent);
        self::assertSame(1, $node->getElementsByTagName('text:line-break')->length);
        self::assertSame(1, $node->getElementsByTagName('text:tab')->length);
    }

    public function testRendersHyperlinkWithOdfLinkAttributes(): void
    {
        $paragraph = (new Paragraph())
            ->addHyperlink('Example', 'https://example.com');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $paragraph->toDomNode($dom);
        $link = $node->getElementsByTagName('text:a')->item(0);

        self::assertNotNull($link);
        self::assertSame('https://example.com', $link->getAttribute('xlink:href'));
        self::assertSame('simple', $link->getAttribute('xlink:type'));
        self::assertSame('new', $link->getAttribute('xlink:show'));
        self::assertSame('Example', $link->textContent);
    }

    public function testTracksInlineAndParagraphStyles(): void
    {
        $paragraph = new Paragraph('CustomParagraph', [
            'text-align' => 'center',
            'margin-top' => '0.2cm',
        ]);
        $paragraph->addText('Styled text', ['bold' => true]);

        self::assertCount(1, $paragraph->getRequiredStyles());
        self::assertSame(
            [
                'CustomParagraph' => [
                    'text-align' => 'center',
                    'margin-top' => '0.2cm',
                ],
            ],
            $paragraph->getRequiredParagraphStyles()
        );
    }

    public function testCanMarkParagraphAsBulletedOrNumbered(): void
    {
        $bullet = (new Paragraph())->setBulleted();
        $numbered = (new Paragraph())->setNumbered();

        self::assertTrue($bullet->isList());
        self::assertTrue($numbered->isList());

        $dom = new DOMDocument('1.0', 'UTF-8');

        self::assertSame(
            'Bullet_20_Symbol',
            $bullet->toDomNode($dom)->getAttribute('text:style-name')
        );
        self::assertSame(
            'Numbering_20_Symbol',
            $numbered->toDomNode($dom)->getAttribute('text:style-name')
        );
    }

    public function testExposesSemanticParagraphDefinitionWithTypedPropertyGroups(): void
    {
        $paragraph = new Paragraph('CustomParagraph', [
            'text-align' => 'center',
            'color' => '#cc0000',
            'font-weight' => 'bold',
        ]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertCount(1, $requirements);
        self::assertSame(
            [
                'style:paragraph-properties' => [
                    'fo:text-align' => 'center',
                ],
                'style:text-properties' => [
                    'fo:font-weight' => 'bold',
                    'fo:color' => '#cc0000',
                ],
            ],
            $requirements[0]->propertyGroups()
        );
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirements[0]->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirements[0]->scope());
        self::assertSame('paragraph', $requirements[0]->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirements[0]->documentPart());
        self::assertSame('CustomParagraph', $requirements[0]->name());
        self::assertSame('Standard', $requirements[0]->parentStyleName());
    }

    public function testExposesNameOnlyParagraphAsUnresolvedReference(): void
    {
        $requirements = iterator_to_array(
            (new Paragraph('CVMainHeading'))->getOwnStyleRequirements(),
            false
        );

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirements[0]->kind());
        self::assertNull($requirements[0]->scope());
        self::assertSame('paragraph', $requirements[0]->family());
        self::assertNull($requirements[0]->documentPart());
        self::assertSame('CVMainHeading', $requirements[0]->name());
        self::assertNull($requirements[0]->parentStyleName());
        self::assertSame([], $requirements[0]->propertyGroups());
    }

    public function testExposesGeneratedInlineTextStylesAsCommonDefinitions(): void
    {
        $paragraph = (new Paragraph())
            ->addText('red', ['color' => '#cc0000'])
            ->addText('bold', ['bold' => true]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertCount(2, $requirements);
        self::assertSame(
            ['style:text-properties' => ['fo:color' => '#cc0000']],
            $requirements[0]->propertyGroups()
        );
        self::assertSame(
            ['style:text-properties' => ['fo:font-weight' => 'bold']],
            $requirements[1]->propertyGroups()
        );
        foreach ($requirements as $requirement) {
            self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
            self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
            self::assertSame('text', $requirement->family());
            self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
            self::assertSame('Standard', $requirement->parentStyleName());
        }
    }

    public function testNativeInlineTextPropertiesArePreservedAsSemanticProperties(): void
    {
        $paragraph = (new Paragraph())->addText('native', [
            'fo:color' => '#123456',
            'fo:font-size' => '13pt',
            'fo:font-weight' => 'bold',
            'fo:font-style' => 'italic',
            'style:font-name' => 'Liberation Sans',
            'style:text-underline-style' => 'solid',
        ]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertSame([
            'style:text-properties' => [
                'fo:color' => '#123456',
                'fo:font-size' => '13pt',
                'fo:font-weight' => 'bold',
                'fo:font-style' => 'italic',
                'style:font-name' => 'Liberation Sans',
                'style:text-underline-style' => 'solid',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testSemanticDiscoveryDoesNotMutateLegacyStyleRegistries(): void
    {
        $paragraph = new Paragraph('SemanticOnlyParagraph', ['text-align' => 'center']);
        $paragraph->addText('text', ['color' => '#123456']);
        $paragraphStylesBefore = StyleMapper::getParagraphStyles();
        $textStylesBefore = StyleMapper::getTextStyles();

        iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertSame($paragraphStylesBefore, StyleMapper::getParagraphStyles());
        self::assertSame($textStylesBefore, StyleMapper::getTextStyles());
    }

    public function testSemanticTextNamesPreserveExistingGenerationAndDeduplication(): void
    {
        $paragraph = (new Paragraph())
            ->addText('one', ['color' => '#123456'])
            ->addText('two', ['color' => '#123456']);

        $legacyNames = array_keys($paragraph->getRequiredStyles());
        $semanticRequirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertCount(1, $legacyNames);
        self::assertCount(1, $semanticRequirements);
        self::assertSame($legacyNames[0], $semanticRequirements[0]->name());
    }
}
