<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * A Writer-editable native drawing group containing labeled, rounded skill bars.
 *
 * Coordinates are relative to the group's paragraph anchor, in centimeters.
 */
final class SkillBarGroup extends OdtElement
{
    /** @var list<OdtElement> */
    private array $children = [];

    private int $count = 0;

    public function __construct(
        private readonly string $name,
        private readonly float $x = 0.263,
        private readonly float $y = 0.0,
        private readonly float $width = 3.001,
        private readonly ?float $rowHeight = null,
        private readonly string $trackColor = '#DDE2E8',
        private readonly string $fillColor = '#2776B6',
        private readonly float $barHeight = 0.15,
        private readonly string $fontFamily = 'Liberation Sans',
        private readonly float $fontSize = 8.0,
        private readonly string $fontColor = '#FFFFFF',
        private readonly bool $fontBold = true,
        private readonly float $labelGap = 0.10,
        private readonly float $skillGap = 0.25
    ) {
        if (
            $name === '' || $width <= 0 || ($rowHeight !== null && $rowHeight <= 0)
            || $x < 0 || $y < 0 || $barHeight <= 0 || $fontSize <= 0
            || trim($fontFamily) === '' || $labelGap < 0 || $skillGap < 0
        ) {
            throw new InvalidArgumentException('Invalid SkillBarGroup layout.');
        }
        foreach ([$trackColor, $fillColor, $fontColor] as $color) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1) {
                throw new InvalidArgumentException('Invalid SkillBarGroup color.');
            }
        }
    }

    public function addSkill(string $label, int $percent): self
    {
        if ($label === '' || $percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Skill label and percent must be valid.');
        }

        $index = $this->count++;
        // Approximate one Writer text line (font points converted to cm).
        // Keep enough vertical space for the editable shape text.
        $labelHeight = $this->fontSize * 2.54 / 72 * 1.45;
        $step = $this->rowHeight
            ?? ($labelHeight + $this->labelGap + $this->barHeight + $this->skillGap);
        $baseY = $this->y + $index * $step;
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'width' => self::cm($this->width),
            'height' => self::cm($labelHeight),
            'horizontal' => ['offset' => self::cm($this->x), 'relative-to' => 'paragraph'],
            'vertical' => ['offset' => self::cm($baseY), 'relative-to' => 'paragraph'],
            'wrap' => 'run-through',
        ]);
        $text = new ShapeElement(ShapeGeometry::preset('rectangle'), ShapeAppearance::none('none'), $layout);
        $text->setTextLayout(new ShapeTextLayout(
            'middle',
            ['left' => '0cm', 'right' => '0cm', 'top' => '0cm', 'bottom' => '0cm'],
            'left'
        ));
        $text->addParagraph(
            (new Paragraph(null, ['text-align' => 'left']))->addText($label, [
                'color' => strtoupper($this->fontColor),
                'font-size' => self::number($this->fontSize) . 'pt',
                'font-family' => $this->fontFamily,
                'bold' => $this->fontBold,
            ])
        );
        $this->children[] = $text;

        $barY = self::cm($baseY + $labelHeight + $this->labelGap);
        $height = self::cm($this->barHeight);
        $radius = self::cm($this->barHeight / 2);
        $barX = self::cm($this->x);
        $this->children[] = new RoundedRectElement(
            $this->name . '-track-' . $index,
            $barX,
            $barY,
            self::cm($this->width),
            $height,
            $radius,
            $this->trackColor
        );
        if ($percent > 0) {
            $this->children[] = new RoundedRectElement(
                $this->name . '-fill-' . $index,
                $barX,
                $barY,
                self::cm($this->width * $percent / 100),
                $height,
                self::cm(min($this->barHeight / 2, $this->width * $percent / 200)),
                $this->fillColor
            );
        }

        return $this;
    }

    public function ownedElements(): iterable
    {
        return $this->children;
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $group = $dom->createElement('draw:g');
        $group->setAttribute('draw:name', $this->name);
        $group->setAttribute('draw:style-name', $this->styleName());
        $group->setAttribute('text:anchor-type', 'paragraph');
        $group->setAttribute('draw:z-index', '4');

        foreach ($this->children as $child) {
            $node = $child->toDomNode($dom);
            // Group children are positioned relative to their common group anchor.
            if ($node instanceof DOMElement) {
                $node->removeAttribute('text:anchor-type');
            }
            $group->appendChild($node);
        }

        return $group;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return StructuredInsertionMode::PRESERVE_TEXT_CONTAINER;
    }

    public function getOwnStyleRequirements(): iterable
    {
        $requirements = [new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_STYLES,
            $this->styleName(),
            null,
            ['style:graphic-properties' => $this->graphicProperties()]
        )];

        // This group can be inserted into styles.xml (for example in a
        // Writer sidebar frame). Its descendants need styles in that part,
        // even when their standalone default is content.xml.
        foreach ($this->children as $child) {
            foreach ($this->styleRequirementsForStylesPart($child) as $requirement) {
                $requirements[] = $requirement;
            }
        }

        return $requirements;
    }

    /** @return iterable<int, StyleRequirement> */
    private function styleRequirementsForStylesPart(OdtElement $element): iterable
    {
        foreach ($element->getOwnStyleRequirements() as $requirement) {
            if ($requirement->kind() === StyleRequirement::KIND_DEFINITION) {
                yield new StyleRequirement(
                    $requirement->kind(),
                    StyleRequirement::SCOPE_AUTOMATIC,
                    $requirement->family(),
                    StyleRequirement::PART_STYLES,
                    $requirement->name(),
                    $requirement->parentStyleName(),
                    $requirement->propertyGroups()
                );
            }
        }
        foreach ($element->ownedElements() as $child) {
            yield from $this->styleRequirementsForStylesPart($child);
        }
    }

    private function graphicProperties(): array
    {
        return [
            'style:wrap' => 'run-through',
            'style:run-through' => 'foreground',
            'style:horizontal-pos' => 'from-left',
            'style:horizontal-rel' => 'paragraph',
            'style:vertical-pos' => 'from-top',
            'style:vertical-rel' => 'paragraph',
        ];
    }

    private function styleName(): string
    {
        return StyleMapper::generateStyleName($this->graphicProperties());
    }

    private static function cm(float $value): string
    {
        return self::number($value) . 'cm';
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.6f', $value), '0'), '.');
    }
}
