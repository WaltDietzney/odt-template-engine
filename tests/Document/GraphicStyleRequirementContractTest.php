<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Style\StyleContext;
use PHPUnit\Framework\TestCase;

final class GraphicStyleRequirementContractTest extends TestCase
{
    public function testGraphicDefinitionCarriesOnlyGraphicPropertyGroup(): void
    {
        $requirement = $this->graphicDefinition('GraphicAppearance', [
            'fo:background-color' => '#123456',
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
            'fo:border-bottom' => '0.05cm solid #abcdef',
            'fo:padding' => '0.1cm',
        ]);

        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('graphic', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('Frame', $requirement->parentStyleName());
        self::assertSame([
            'graphic-properties' => [
                'fo:background-color' => '#123456',
                'draw:fill' => 'solid',
                'draw:fill-color' => '#123456',
                'fo:border-bottom' => '0.05cm solid #abcdef',
                'fo:padding' => '0.1cm',
            ],
        ], $requirement->propertyGroups());
    }

    public function testEquivalentGraphicDefinitionsAreIdempotent(): void
    {
        $context = new StyleContext();
        $first = $this->graphicDefinition('GraphicAppearance', [
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
        ]);
        $second = $this->graphicDefinition('GraphicAppearance', [
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
        ]);

        $context->registerRequirement($first);
        $context->registerRequirement($second);

        self::assertCount(1, $context->semanticDefinitions());
        self::assertSame([$first], array_values($context->semanticDefinitions()));
    }

    public function testConflictingGraphicDefinitionsWithSameSemanticIdentityAreRejected(): void
    {
        $context = new StyleContext();
        $context->registerRequirement($this->graphicDefinition('GraphicAppearance', [
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
        ]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Graphic style "GraphicAppearance" is already registered with a different definition'
        );

        $context->registerRequirement($this->graphicDefinition('GraphicAppearance', [
            'draw:fill' => 'solid',
            'draw:fill-color' => '#654321',
        ]));
    }

    public function testDrawingStructureDoesNotCreateDistinctSemanticGraphicDefinition(): void
    {
        $firstDrawingState = [
            'draw:name' => 'PhotoA',
            'svg:width' => '3cm',
            'svg:height' => '3cm',
            'text:anchor-type' => 'paragraph',
            'style:horizontal-pos' => 'left',
            'draw:z-index' => '0',
        ];
        $secondDrawingState = [
            'draw:name' => 'PhotoB',
            'svg:width' => '6cm',
            'svg:height' => '4cm',
            'text:anchor-type' => 'as-char',
            'style:horizontal-pos' => 'right',
            'draw:z-index' => '5',
        ];

        self::assertNotSame($firstDrawingState, $secondDrawingState);

        $first = $this->graphicDefinition('SharedGraphicAppearance', [
            'draw:fill' => 'none',
            'draw:stroke' => 'none',
        ]);
        $second = $this->graphicDefinition('SharedGraphicAppearance', [
            'draw:fill' => 'none',
            'draw:stroke' => 'none',
        ]);

        $context = new StyleContext();
        $context->registerRequirement($first);
        $context->registerRequirement($second);

        self::assertCount(1, $context->semanticDefinitions());
        self::assertSame([$first], array_values($context->semanticDefinitions()));
    }

    /**
     * @param array<string, mixed> $graphicProperties
     */
    private function graphicDefinition(string $name, array $graphicProperties): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            $name,
            'Frame',
            ['graphic-properties' => $graphicProperties]
        );
    }
}
