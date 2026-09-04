<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class DrawTextBoxSemanticGraphicProducerTest extends TestCase
{
    /** @var list<OdtTemplate> */
    private array $templates = [];

    protected function tearDown(): void
    {
        foreach ($this->templates as $template) {
            $template->cleanup();
        }
    }

    public function testProducerEmitsOnlyApprovedGraphicAppearanceProperties(): void
    {
        $box = new DrawTextBox('SemanticBox', [
            'width' => '6cm',
            'height' => '2cm',
            'anchor' => 'as-char',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
            'vertical-pos' => 'top',
            'vertical-rel' => 'paragraph',
            'allow-overlap' => 'true',
            'rx' => '0.2cm',
            'background-color' => '#123456',
            'border-bottom' => '0.05cm solid #abcdef',
            'padding' => '0.1cm',
        ]);

        $requirements = iterator_to_array($box->getOwnStyleRequirements(), false);
        self::assertCount(1, $requirements);

        $requirement = $requirements[0];
        self::assertInstanceOf(StyleRequirement::class, $requirement);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('graphic', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('Frame', $requirement->parentStyleName());
        self::assertSame([
            'style:graphic-properties' => [
                'draw:fill' => 'solid',
                'draw:fill-color' => '#123456',
                'fo:background-color' => '#123456',
                'fo:border-bottom' => '0.05cm solid #abcdef',
                'fo:padding' => '0.1cm',
            ],
        ], $requirement->propertyGroups());
    }

    public function testSemanticIdentityIgnoresDrawingPlacementAndGeometry(): void
    {
        $left = new DrawTextBox('LeftBox', [
            'width' => '4cm',
            'height' => '2cm',
            'anchor' => 'paragraph',
            'horizontal-pos' => 'left',
            'horizontal-rel' => 'paragraph',
            'background-color' => '#123456',
        ]);
        $right = new DrawTextBox('RightBox', [
            'width' => '8cm',
            'height' => '5cm',
            'anchor' => 'as-char',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'page',
            'background-color' => '#123456',
        ]);

        $leftSemantic = iterator_to_array($left->getOwnStyleRequirements(), false)[0];
        $rightSemantic = iterator_to_array($right->getOwnStyleRequirements(), false)[0];

        self::assertSame($leftSemantic->name(), $rightSemantic->name());
        self::assertSame($leftSemantic->propertyGroups(), $rightSemantic->propertyGroups());

        $leftLegacy = array_key_first($left->getOwnFrameStyleRequirements());
        $rightLegacy = array_key_first($right->getOwnFrameStyleRequirements());
        self::assertNotSame($leftLegacy, $rightLegacy);
    }

    public function testStructureOnlyTextBoxDoesNotInventSemanticGraphicDefinition(): void
    {
        $box = new DrawTextBox('StructureOnly', [
            'width' => '6cm',
            'height' => '2cm',
            'anchor' => 'paragraph',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
        ]);

        self::assertSame([], iterator_to_array($box->getOwnStyleRequirements(), false));
        self::assertNotSame([], $box->getOwnFrameStyleRequirements());
    }

    public function testSetElementRegistersSemanticGraphicRequirementWhileLegacyStyleRemainsRendered(): void
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            /** @return array<string, StyleRequirement> */
            public function semanticDefinitionsForTest(): array
            {
                return $this->documentContext()->styleContext()->semanticDefinitions();
            }
        };
        $this->templates[] = $template;

        $box = new DrawTextBox('SemanticBox', [
            'width' => '6cm',
            'height' => '2cm',
            'background-color' => '#123456',
        ]);
        $semantic = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        $legacyName = (string) array_key_first($box->getOwnFrameStyleRequirements());

        $template->setElement('test1', $box);

        $definitions = $template->semanticDefinitionsForTest();
        self::assertArrayHasKey(
            implode("\0", ['graphic', $semantic->name(), StyleRequirement::SCOPE_COMMON, StyleRequirement::PART_STYLES]),
            $definitions
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $box->toDomNode($dom);
        if ($frame->nodeName === 'text:p') {
            $frame = $frame->firstChild;
        }
        self::assertSame($legacyName, $frame?->attributes?->getNamedItem('draw:style-name')?->nodeValue);
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }
}
