<?php

declare(strict_types=1);

namespace H07Research;

use InvalidArgumentException;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeTextLayout;

/**
 * Research composition built exclusively from existing engine drawing elements.
 * Contact semantics and field selection are supplied by the consumer.
 */
final class ContactItem extends DrawTextBox
{
    public function __construct(string $name, array $item, array $style = [])
    {
        $s = array_replace([
            'width' => 5.4,
            'font' => 'Liberation Sans',
            'fontSize' => 8.5,
            'color' => '#24384B',
            'labelColor' => '#5D7890',
            'align' => 'left',
            'placement' => 'left',
            'backing' => null,
            'backingColor' => '#DCE8F2',
            'iconSize' => 0.28,
            'backingSize' => 0.40,
            'iconGap' => 0.14,
            'labelSize' => 6.5,
            'labelGap' => 0.025,
            'padding' => 0.015,
        ], $style);
        foreach (['width', 'fontSize', 'iconSize', 'backingSize'] as $key) {
            if (!is_numeric($s[$key]) || $s[$key] <= 0) {
                throw new InvalidArgumentException('Invalid contact dimension: ' . $key);
            }
        }
        foreach (['iconGap', 'padding', 'labelGap'] as $key) {
            if (!is_numeric($s[$key]) || $s[$key] < 0) {
                throw new InvalidArgumentException('Invalid contact spacing: ' . $key);
            }
        }
        if (!in_array($s['align'], ['left', 'center', 'right'], true)
            || !in_array($s['placement'], ['left', 'above'], true)) {
            throw new InvalidArgumentException('Unsupported contact alignment/placement.');
        }
        if ($s['placement'] === 'above' && $s['backing'] !== null) {
            throw new InvalidArgumentException('Above-icon variant currently supports unbacked icons only.');
        }
        if ($s['backing'] !== null && $s['backingSize'] < $s['iconSize']) {
            throw new InvalidArgumentException('Icon must fit inside its backing.');
        }
        parent::__construct($name, self::frameOptions('transparent', $s['padding']));
        $this->setTextBoxSizing(['min-height' => '0.1cm']);
        $this->setFrameLayout([
            'anchor' => 'as-char',
            'width' => self::cm($s['width']),
            'height' => '0.1cm',
        ]);
        $textStyle = [
            'font-family' => $s['font'],
            'font-size' => $s['fontSize'] . 'pt',
            'color' => $s['color'],
            'fo:line-height' => '112%',
            'fo:margin-top' => '0cm',
            'fo:margin-bottom' => '0cm',
            'fo:text-align' => $s['align'],
            'fo:padding' => '0cm',
        ];
        $symbolWidth = $s['backing'] === null ? $s['iconSize'] : $s['backingSize'];
        $drawings = [];
        if ($s['placement'] === 'above') {
            $symbolP = new Paragraph(null, [
                'fo:text-align' => 'center',
                'fo:margin-top' => '0cm',
                'fo:margin-bottom' => self::cm($s['iconGap']),
                'fo:line-height' => '100%',
                'font-size' => '1pt',
            ]);
            $symbolP->addElement(self::icon($item['icon'], $s['iconSize'], null, null));
            $this->addElement($symbolP);
        } else {
            $indent = $symbolWidth + $s['iconGap'];
            if ($indent >= $s['width'] - 0.3) {
                throw new InvalidArgumentException('Contact symbol leaves insufficient text width.');
            }
            $textStyle['fo:margin-left'] = self::cm($indent);

            $left = -$indent;
            if ($s['backing'] !== null) {
                $layout = DrawingLayout::fromArray([
                    'anchor' => 'paragraph',
                    'width' => self::cm($symbolWidth),
                    'height' => self::cm($symbolWidth),
                    'horizontal' => ['offset' => self::cm($left), 'relative-to' => 'paragraph-content'],
                    'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
                    'wrap' => 'run-through', 'run-through' => 'foreground',
                ]);
                $shape = new ShapeElement(
                    ShapeGeometry::preset($s['backing']),
                    ShapeAppearance::solid($s['backingColor']),
                    $layout
                );
                $shape->setTextLayout(new ShapeTextLayout('middle', [
                    'left' => '0cm', 'right' => '0cm', 'top' => '0cm', 'bottom' => '0cm',
                ], 'center'));
                $symbolLayout = DrawingLayout::fromArray([
                    'anchor' => 'paragraph', 'z-index' => 0,
                    'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
                    'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
                    'wrap' => 'run-through', 'run-through' => 'foreground',
                ]);
            }
            $offset = ($symbolWidth - $s['iconSize']) / 2;
            if ($s['backing'] !== null) {
                $drawings[] = new DrawingGroup($name . '-symbol', [
                    $shape, self::icon($item['icon'], $s['iconSize'], $left + $offset, $offset),
                ], $symbolLayout);
            } else {
                $drawings[] = self::icon($item['icon'], $s['iconSize'], $left + $offset, $offset);
            }
        }
        if (($item['label'] ?? '') !== '') {
            $labelStyle = array_replace($textStyle, [
                'font-size' => $s['labelSize'] . 'pt',
                'color' => $s['labelColor'],
                'bold' => true,
                'fo:margin-bottom' => self::cm($s['labelGap']),
            ]);
            $label = self::paragraph([$item['label']], $labelStyle, $drawings);
            $this->addElement($label);
            $drawings = [];
            $textStyle['fo:text-indent'] = '0cm';
        }
        $this->addElement(self::paragraph($item['tokens'], $textStyle, $drawings));
    }

    private static function paragraph(array $tokens, array $options, array $drawings): Paragraph
    {
        $paragraph = new Paragraph(null, $options);
        foreach ($drawings as $drawing) {
            $paragraph->addDrawing($drawing);
        }
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $paragraph->addUserField($token['name']);
            } elseif ($token === "\n") {
                $paragraph->addLineBreak();
            } else {
                $paragraph->addText($token);
            }
        }
        return $paragraph;
    }

    public static function frameOptions(string $background = 'transparent', float $padding = 0.015): array
    {
        $options = [
            'fo:border' => 'none',
            'fo:padding-left' => self::cm($padding),
            'fo:padding-right' => self::cm($padding),
            'fo:padding-top' => self::cm($padding),
            'fo:padding-bottom' => self::cm($padding),
            'fo:margin-left' => '0cm',
            'fo:margin-right' => '0cm',
            'fo:margin-top' => '0cm',
            'fo:margin-bottom' => '0cm',
            'draw:auto-grow-height' => 'true',
            'draw:auto-grow-width' => 'false',
            'style:vertical-pos' => 'top',
            'style:vertical-rel' => 'text',
            'style:wrap' => 'none',
        ];
        if ($background === 'transparent') {
            $options['draw:fill'] = 'none';
        } else {
            $options['background-color'] = $background;
        }
        return $options;
    }

    private static function icon(string $path, float $size, ?float $x, ?float $y): ImageElement
    {
        $image = new ImageElement($path, [
            'width' => self::cm($size),
            'height' => self::cm($size),
            'style:run-through' => 'foreground',
        ]);
        if ($x === null) {
            return $image->setFrameLayout([
                'anchor' => 'as-char', 'width' => self::cm($size), 'height' => self::cm($size),
                'wrap' => 'run-through', 'run-through' => 'foreground',
            ]);
        }
        return $image->setFrameLayout([
            'anchor' => 'paragraph', 'width' => self::cm($size), 'height' => self::cm($size),
            'horizontal' => ['offset' => self::cm($x), 'relative-to' => 'paragraph-content'],
            'vertical' => ['offset' => self::cm($y), 'relative-to' => 'paragraph-content'],
            'wrap' => 'run-through', 'run-through' => 'foreground',
        ]);
    }

    public static function cm(float $number): string
    {
        return rtrim(rtrim(sprintf('%.4f', $number), '0'), '.') . 'cm';
    }
}

/** Vertical flow and theme, separate from the reusable single contact drawing. */
final class ContactBlock extends DrawTextBox
{
    public function __construct(string $name, array $items, array $style = [])
    {
        if ($items === []) {
            throw new InvalidArgumentException('Consumer must omit an empty contact block.');
        }
        $s = array_replace(['width' => 5.8, 'padding' => 0.18, 'rowGap' => 0.12, 'background' => 'transparent'], $style);
        if ($s['width'] <= 0 || $s['padding'] < 0 || $s['rowGap'] < 0) {
            throw new InvalidArgumentException('Invalid contact-block geometry.');
        }
        parent::__construct($name, ContactItem::frameOptions($s['background'], $s['padding']));
        $this->setTextBoxSizing(['min-height' => '0.1cm']);
        $this->setFrameLayout(['anchor' => 'as-char', 'width' => ContactItem::cm($s['width']), 'height' => '0.1cm']);
        foreach ($items as $i => $item) {
            $p = new Paragraph(null, [
                'font-size' => '1pt',
                'fo:line-height' => '100%',
                'fo:margin-top' => '0cm',
                'fo:margin-bottom' => $i === array_key_last($items) ? '0cm' : ContactItem::cm($s['rowGap']),
            ]);
            $p->addElement(new ContactItem($name . '-item-' . $i, $item, array_replace(
                $s,
                ['width' => $s['width'] - 2 * $s['padding'] - 0.03, 'padding' => $s['itemPadding'] ?? 0.015]
            )));
            $this->addElement($p);
        }
    }
}

/** Full-width bounded 1–3-column composition, using frames rather than tables. */
final class ContactStrip extends DrawTextBox
{
    public function __construct(string $name, array $items, array $style = [])
    {
        $count = count($items);
        if ($count < 1 || $count > 3) {
            throw new InvalidArgumentException('A contact strip supports one to three items.');
        }
        $s = array_replace(['width' => 18.0, 'placement' => 'above', 'fontSize' => 8.0], $style);
        parent::__construct($name, ContactItem::frameOptions('transparent', 0.0));
        $this->setTextBoxSizing(['min-height' => '0.1cm']);
        $this->setFrameLayout(['anchor' => 'as-char', 'width' => ContactItem::cm($s['width']), 'height' => '0.1cm']);
        $row = new Paragraph(null, ['font-size' => '1pt', 'fo:line-height' => '100%', 'fo:margin' => '0cm']);
        $columnWidth = ($s['width'] - 0.015) / $count;
        $alignments = $count === 3 ? ['left', 'center', 'right'] : ($count === 2 ? ['left', 'right'] : ['left']);
        foreach ($items as $i => $item) {
            $itemWidth = $item['width'] ?? $columnWidth - 0.04;
            if ($itemWidth > $columnWidth - 0.02) {
                throw new InvalidArgumentException('Item exceeds its contact-strip column.');
            }
            $column = new DrawTextBox($name . '-column-' . $i, ContactItem::frameOptions('transparent', 0.0));
            $column->setTextBoxSizing(['min-height' => '0.1cm']);
            $column->setFrameLayout(['anchor' => 'as-char', 'width' => ContactItem::cm($columnWidth), 'height' => '0.1cm']);
            $p = new Paragraph(null, [
                'fo:text-align' => $alignments[$i], 'fo:margin' => '0cm',
                'font-size' => '1pt', 'fo:line-height' => '100%',
            ]);
            $p->addElement(new ContactItem($name . '-item-' . $i, $item, array_replace($s, [
                'width' => $itemWidth,
                'align' => $alignments[$i],
            ])));
            $column->addElement($p);
            $row->addElement($column);
        }
        $this->addElement($row);
    }
}
