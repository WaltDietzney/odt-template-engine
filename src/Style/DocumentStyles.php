<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use Closure;
use OdtTemplateEngine\Document\FontFaceRequirementDiscovery;
use OdtTemplateEngine\Document\FontFaceRequirementMaterializer;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleOptionSplitter;

/**
 * Public document-oriented style authoring facade.
 *
 * The facade owns no style state. Every operation resolves the current
 * document context through the provider supplied by the owning OdtTemplate.
 */
final class DocumentStyles
{
    /** @var Closure(): OdtDocumentContext */
    private Closure $contextProvider;

    /**
     * @param Closure(): OdtDocumentContext $contextProvider
     */
    public function __construct(Closure $contextProvider)
    {
        $this->contextProvider = $contextProvider;
    }

    /**
     * Define a reusable named paragraph style for the current logical document.
     *
     * @param array<string, mixed> $options
     */
    public function defineParagraph(string $name, array $options): void
    {
        $split = StyleOptionSplitter::split($options, 'paragraph');
        $propertyGroups = [];

        if ($split['paragraph'] !== []) {
            $propertyGroups['style:paragraph-properties'] = StyleMapper::mapParagraphStyle(
                $split['paragraph']
            );
        }

        if ($split['text'] !== []) {
            $propertyGroups['style:text-properties'] = StyleMapper::mapTextStyleOptions(
                $split['text']
            );
        }

        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            $name,
            'Standard',
            $propertyGroups
        );

        $context = ($this->contextProvider)();
        $context->styleContext()->registerRequirement($requirement);

        $fontRequirement = (new FontFaceRequirementDiscovery())->discover($requirement);
        if ($fontRequirement !== null) {
            $context->registerFontFaceRequirement($fontRequirement);
            (new FontFaceRequirementMaterializer())->materialize($context, $fontRequirement);
        }

        (new StyleRequirementMaterializer())->materialize($context, $requirement);
    }
}
