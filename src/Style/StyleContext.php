<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use LogicException;

/**
 * Holds pending style requirements for one logical ODT document.
 *
 * Existing definitions in styles.xml remain authoritative document data. This
 * context only owns style requirements registered while the document is being
 * edited. Additional style families can be added as their migration semantics
 * are characterized.
 */
final class StyleContext
{
    /** @var array<string, array<string, mixed>> */
    private array $paragraphStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $textStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $frameStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $imageStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $fillImages = [];

    /**
     * Register one pending paragraph style definition.
     *
     * Re-registering an equivalent definition is idempotent. Reusing the same
     * name for a different definition is an explicit conflict.
     *
     * @param array<string, mixed> $definition
     */
    public function registerParagraphStyle(string $name, array $definition): void
    {
        if (!isset($this->paragraphStyles[$name])) {
            $this->paragraphStyles[$name] = $definition;

            return;
        }

        if ($this->paragraphStyles[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            'Paragraph style "%s" is already registered with a different definition.',
            $name
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function paragraphStyles(): array
    {
        return $this->paragraphStyles;
    }

    /**
     * Register one pending text style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerTextStyle(string $name, array $definition): void
    {
        if (!isset($this->textStyles[$name])) {
            $this->textStyles[$name] = $definition;

            return;
        }

        if ($this->textStyles[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            'Text style "%s" is already registered with a different definition.',
            $name
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function textStyles(): array
    {
        return $this->textStyles;
    }

    /**
     * Register one pending frame graphic style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerFrameStyle(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->frameStyles, 'Frame style', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function frameStyles(): array
    {
        return $this->frameStyles;
    }

    /**
     * Register one pending image graphic style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerImageStyle(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->imageStyles, 'Image style', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function imageStyles(): array
    {
        return $this->imageStyles;
    }

    /**
     * Register one pending fill-image declaration for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerFillImage(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->fillImages, 'Fill-image declaration', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function fillImages(): array
    {
        return $this->fillImages;
    }

    /**
     * Clear pending requirements after the logical document has been reset.
     */
    public function reset(): void
    {
        $this->paragraphStyles = [];
        $this->textStyles = [];
        $this->frameStyles = [];
        $this->imageStyles = [];
        $this->fillImages = [];
    }

    /**
     * @param array<string, array<string, mixed>> $requirements
     * @param array<string, mixed> $definition
     */
    private function registerGraphicRequirement(
        array &$requirements,
        string $family,
        string $name,
        array $definition
    ): void {
        if (!isset($requirements[$name])) {
            $requirements[$name] = $definition;

            return;
        }

        if ($requirements[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            '%s "%s" is already registered with a different definition.',
            $family,
            $name
        ));
    }
}
