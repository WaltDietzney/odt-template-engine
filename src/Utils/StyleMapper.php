<?php

namespace OdtTemplateEngine\Utils;

/**
 * Stateless mapping and identity helpers.
 *
 * Document style ownership belongs to StyleContext and semantic requirements.
 */
class StyleMapper
{
    /**
     * Maps a set of paragraph style options to their corresponding ODF attributes.
     * 
     * This method maps paragraph-specific properties, such as 'text-align', 'margin-top', 'line-height', etc.,
     * to the appropriate attributes used in ODF formatting.
     * 
     * @param array $input The input paragraph style options.
     * @return array The mapped style attributes for paragraphs.
     */
    public static function mapParagraphStyle(array $options): array
    {
        $mapped = [];

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'margin-left':
                    $mapped['fo:margin-left'] = $value;
                    break;
                case 'margin-right':
                    $mapped['fo:margin-right'] = $value;
                    break;
                case 'margin-top':
                    $mapped['fo:margin-top'] = $value;
                    break;
                case 'margin-bottom':
                    $mapped['fo:margin-bottom'] = $value;
                    break;
                case 'text-align':
                    $mapped['fo:text-align'] = $value;
                    break;
                case 'text-indent':
                    $mapped['fo:text-indent'] = $value;
                    break;
                case 'line-height':
                    $mapped['fo:line-height'] = $value;
                    break;
                case 'background-color':
                    $mapped['fo:background-color'] = $value;
                    break;
                case 'keep-with-next':
                    $mapped['fo:keep-with-next'] = $value;
                    break;
                case 'break-before':
                    $mapped['fo:break-before'] = $value;
                    break;
                case 'break-after':
                    $mapped['fo:break-after'] = $value;
                    break;
                case 'writing-mode':
                    $mapped['style:writing-mode'] = $value;
                    break;
                case 'padding-left':
                    $mapped['fo:padding-left'] = $value;
                    break;
                case 'padding-right':
                    $mapped['fo:padding-right'] = $value;
                    break;
                case 'padding-top':
                    $mapped['fo:padding-top'] = $value;
                    break;
                case 'padding-bottom':
                    $mapped['fo:padding-bottom'] = $value;
                    break;
                case 'padding':
                    $mapped['fo:padding'] = $value;
                    break;
                case 'border-left':
                    $mapped['fo:border-left'] = $value;
                    break;
                case 'border-right':
                    $mapped['fo:border-right'] = $value;
                    break;
                case 'border-top':
                    $mapped['fo:border-top'] = $value;
                    break;
                case 'border-bottom':
                    $mapped['fo:border-bottom'] = $value;
                    break;
                case 'border':
                    $mapped['fo:border'] = $value;
                    break;
                case 'number-lines':
                    $mapped['style:number-lines'] = $value;
                    break;
                case 'line-number':
                    $mapped['style:line-number'] = $value;
                    break;
                case 'tab-stops':
                    $tabStops = [];
                    foreach ($value as $tab) {
                        $tabStops[] = [
                            'style:position' => $tab['position'] . 'cm',
                            'style:type' => $tab['alignment'] ?? 'left'
                        ];
                    }
                    $mapped['style:tab-stops'] = $tabStops;
                    break;
                // Optional: füge eigene benutzerdefinierte Attribute hinzu
                default:
                    // Erlaube custom-Namespace-Angaben
                    $mapped[$key] = $value;
                    break;
            }
        }

        return $mapped;
    }

    /**
     * Maps a set of text style options to their corresponding ODF attributes.
     * 
     * This method takes an array of input options, such as 'bold', 'italic', 'color', etc., and maps them to
     * the corresponding attributes in ODF formatting (e.g., `fo:font-weight`, `fo:color`).
     * 
     * @param array $input The input style options.
     * @return array The mapped style attributes.
     */

    public static function mapTextStyleOptions(array $options): array
    {
        $mapped = [];

        foreach ($options as $key => $value) {
            if (preg_match('/^(fo:|style:)/', (string) $key)) {
                $mapped[(string) $key] = $value;
            }
        }

        // Fett
        if (!empty($options['bold'])) {
            $mapped['fo:font-weight'] = 'bold';
        }

        // Kursiv
        if (!empty($options['italic'])) {
            $mapped['fo:font-style'] = 'italic';
        }
        if (!empty($options['font-weight'])) {
            $mapped['fo:font-weight'] = $options['font-weight'];
        }

        // Kursiv
        if (!empty($options['font-style'])) {
            $mapped['fo:font-style'] = $options['font-style'];
        }

        if (!empty($options['text-decoration'])) {
            $mapped['style:text-underline-style'] = 'solid';
            $mapped['style:text-underline-type'] = 'single';
            $mapped['style:text-underline-width'] = 'auto';
        }

        // Unterstrichen
        if (!empty($options['underline'])) {
            $mapped['style:text-underline-style'] = 'solid';
            $mapped['style:text-underline-type'] = 'single';
            $mapped['style:text-underline-width'] = 'auto';
        }

        // Farbe (Text)
        if (!empty($options['color'])) {
            $mapped['fo:color'] = $options['color'];
        }

        // Hintergrundfarbe
        if (!empty($options['background-color'])) {
            $mapped['fo:background-color'] = $options['background-color'];
        }

        // Schriftgröße
        if (!empty($options['font-size'])) {
            $value = strtolower($options['font-size']);
            $mappedSize = match ($value) {
                'xx-small' => '6pt',
                'x-small' => '7pt',
                'small' => '9pt',
                'medium' => '11pt',
                'large' => '13pt',
                'x-large' => '15pt',
                'xx-large' => '17pt',
                default => $value, // z. B. "12pt", "1.2em", etc.
            };
            $mapped['fo:font-size'] = $mappedSize;
        }


        // Schriftart
        if (!empty($options['font-family'])) {
            $mapped['style:font-name'] = $options['font-family'];
            $mapped['fo:font-family'] = $options['font-family'];
        }

        // Durchgestrichen (<del>, <s>)
        if (!empty($options['text-line-through']) || (!empty($options['text-decoration']) && $options['text-decoration'] === 'line-through')) {
            $mapped['style:text-line-through-style'] = 'solid';
        }

        // Hoch- oder tiefgestellt
        if (!empty($options['style:text-position'])) {
            $mapped['style:text-position'] = $options['style:text-position']; // 'sub' oder 'super'
        }

        // Klein (z. B. <small>) – optional, 80 %
        if (!empty($options['font-variant']) && $options['font-variant'] === 'small-caps') {
            $mapped['fo:font-variant'] = 'small-caps';
        }

        // Großbuchstaben (<tt>, <code>) – Schriftart optional setzen
        if (!empty($options['monospace']) && $options['monospace'] === true) {
            $mapped['style:font-name'] = 'Courier New';
            $mapped['fo:font-family'] = 'Courier New';
        }

        return $mapped;
    }



    /**
     * Mappt Eingabeoptionen auf ODT-kompatible Frame-Properties.
     */
    public static function mapFrameStyleOptions(array $options): array
    {
        $mapped = [];

        foreach ($options as $key => $value) {
            switch ($key) {
                // Hintergrund
                case 'background-color':
                case 'fo:background-color':
                    $mapped['fo:background-color'] = $value;
                    if (!isset($mapped['draw:fill'])) {
                        $mapped['draw:fill'] = 'solid';
                    }
                    if (!isset($mapped['draw:fill-color'])) {
                        $mapped['draw:fill-color'] = $value;
                    }
                    break;

                // Rahmen
                case 'border':
                    $mapped['fo:border'] = $value;
                    break;
                case 'border-top':
                    $mapped['fo:border-top'] = $value;
                    break;
                case 'border-right':
                    $mapped['fo:border-right'] = $value;
                    break;
                case 'border-bottom':
                    $mapped['fo:border-bottom'] = $value;
                    break;
                case 'border-left':
                    $mapped['fo:border-left'] = $value;
                    break;

                // Abrundung der Ecken (SVG rx/ry)
                case 'corner-radius-x':
                case 'rx':
                    $mapped['svg:rx'] = $value;
                    break;
                case 'corner-radius-y':
                case 'ry':
                    $mapped['svg:ry'] = $value;
                    break;

                // Padding (Innenabstand)
                case 'padding':
                    // alle Seiten
                    $mapped['fo:padding'] = $value;
                    break;
                case 'padding-top':
                    $mapped['fo:padding-top'] = $value;
                    break;
                case 'padding-right':
                    $mapped['fo:padding-right'] = $value;
                    break;
                case 'padding-bottom':
                    $mapped['fo:padding-bottom'] = $value;
                    break;
                case 'padding-left':
                    $mapped['fo:padding-left'] = $value;
                    break;

                // Position & Layout
                case 'fill':
                case 'draw:fill':
                    $mapped['draw:fill'] = $value;
                    break;
                case 'fill-color':
                case 'draw:fill-color':
                    $mapped['draw:fill-color'] = $value;
                    break;
                case 'wrap-influence':
                    $mapped['draw:wrap-influence-on-position'] = $value;
                    break;
                case 'allow-overlap':
                    $mapped['loext:allow-overlap'] = $value;
                    break;
                case 'vertical-pos':
                    $mapped['style:vertical-pos'] = $value;
                    break;
                case 'vertical-rel':
                    $mapped['style:vertical-rel'] = $value;
                    break;
                case 'horizontal-pos':
                    $mapped['style:horizontal-pos'] = $value;
                    break;
                case 'horizontal-rel':
                    $mapped['style:horizontal-rel'] = $value;
                    break;

                default:
                    // alles andere direkt übernehmen
                    $mapped[$key] = $value;
                    break;
            }
        }

        return $mapped;
    }



    /**
     * Maps additional table-cell style options to their corresponding ODF attributes.
     * 
     * This mapper covers the supported table-cell options, including
     * 'border', 'padding', and 'text-align'.
     * 
     * @param array $options The input table-cell style options.
     * @return array The mapped style attributes for table cells.
     */
    public static function mapTableCellStyleOptions(array $options): array
    {
        $mapped = [];

        foreach ($options as $key => $value) {
            if (preg_match('/^(fo:|style:)/', $key)) {
                $mapped[$key] = $value;
                continue;
            }

            switch ($key) {
                case 'background-color':
                case 'background':
                    $mapped['fo:background-color'] = $value;
                    break;
                case 'padding':
                    $mapped['fo:padding'] = $value;
                    break;
                case 'padding-left':
                    $mapped['fo:padding-left'] = $value;
                    break;
                case 'padding-right':
                    $mapped['fo:padding-right'] = $value;
                    break;
                case 'padding-top':
                    $mapped['fo:padding-top'] = $value;
                    break;
                case 'padding-bottom':
                    $mapped['fo:padding-bottom'] = $value;
                    break;
                case 'border':
                    $mapped['fo:border'] = $value;
                    break;
                case 'border-left':
                    $mapped['fo:border-left'] = $value;
                    break;
                case 'border-right':
                    $mapped['fo:border-right'] = $value;
                    break;
                case 'border-top':
                    $mapped['fo:border-top'] = $value;
                    break;
                case 'border-bottom':
                    $mapped['fo:border-bottom'] = $value;
                    break;
                case 'align':
                case 'text-align':
                    // Achtung: wird bei Absatzstilen später nochmal extra behandelt!
                    $mapped['fo:text-align'] = $value;
                    break;
                case 'weight':
                    $mapped['fo:font-weight'] = $value;
                    break;
                case 'color':
                    $mapped['fo:color'] = $value;
                    break;
            }
        }

        return $mapped;
    }



    /**
     * Wandelt einfache Image-Options in ODT-kompatible Style-Attribute um.
     * @param array $options
     * @return array
     */
    public static function mapImageStyleOptions(array $options): array
    {
        $mapped = [];

        // Breite und Höhe
        if (!empty($options['width'])) {
            $mapped['svg:width'] = $options['width'];
        }
        if (!empty($options['height'])) {
            $mapped['svg:height'] = $options['height'];
        }

        // Umfluss (wrap)
        if (!empty($options['wrap'])) {
            $validWraps = ['none', 'left', 'right', 'run-through'];
            if (in_array($options['wrap'], $validWraps)) {
                $mapped['style:wrap'] = $options['wrap'];
            } else {
            }
        }

        // Alignment – wichtig für toDomNode, aber nicht als Style in styles.xml!
        if (!empty($options['align'])) {
            $validAligns = ['left', 'right', 'center', 'absolute'];
            if (in_array($options['align'], $validAligns)) {
                $mapped['align'] = $options['align'];
            }
        }

        // Verankerung (anchor)
        if (!empty($options['anchor'])) {
            $validAnchors = ['paragraph', 'page', 'char', 'as-char'];
            if (in_array($options['anchor'], $validAnchors)) {
                $mapped['text:anchor-type'] = $options['anchor'];
            } else {
            }
        }

        // Horizontale Ausrichtung (nur wenn direkt gesetzt)
        if (!empty($options['horizontal-pos'])) {
            $mapped['style:horizontal-pos'] = $options['horizontal-pos'];
        }
        if (!empty($options['horizontal-rel'])) {
            $mapped['style:horizontal-rel'] = $options['horizontal-rel'];
        }

        // Vertikale Ausrichtung
        if (!empty($options['vertical-pos'])) {
            $mapped['style:vertical-pos'] = $options['vertical-pos'];
        }
        if (!empty($options['vertical-rel'])) {
            $mapped['style:vertical-rel'] = $options['vertical-rel'];
        }

        return $mapped;
    }





    /**
     * Generates a unique style name from a given style array by hashing its JSON representation.
     * 
     * @param array $style The style array.
     * @return string The generated unique style name.
     */
    public static function generateStyleName(array $style): string
    {
        // 1. Irrelevante Keys ausschließen (z. B. manuell gesetzte oder intern verwendete)
        $filtered = array_filter(
            $style,
            fn($key) => !in_array($key, ['align', 'style-name'], true),
            ARRAY_FILTER_USE_KEY
        );

        // 2. Keys sortieren für stabile Hashes
        ksort($filtered);

        // 3. Hash berechnen
        return 'auto_' . substr(md5(json_encode($filtered)), 0, 8);
    }


    /**
     * Generates a unique paragraph style name from a given paragraph style array.
     * 
     * @param array $style The paragraph style array.
     * @return string The generated unique paragraph style name.
     */
    public static function generateParagraphStyleName(): string
    {
        // return 'para_' . substr(md5(json_encode($style)), 0, 6);
        return 'para_' . bin2hex(random_bytes(4));
    }



    /**
     * Summary of parseInlineStyle
     * @param string $css
     * @return string[]
     */
    public static function parseInlineStyle(string $css): array
    {
        $styleArray = [];
        $rules = explode(';', $css);

        foreach ($rules as $rule) {
            if (str_contains($rule, ':')) {
                [$key, $value] = explode(':', $rule, 2);
                $key = trim(strtolower($key));
                $value = trim($value);

                // 💡 direkt neutral speichern – Zuweisung zu fo:* macht mapParagraphStyle()
                $styleArray[$key] = $value;
            }
        }

        return $styleArray;
    }




    public static function splitCssProperties(array $rawCss): array
    {
        $textStyle = [];
        $paragraphStyle = [];

        foreach ($rawCss as $key => $value) {
            switch (trim($key)) {
                // Textbezogene Stile
                case 'color':
                case 'background-color':
                case 'font-weight':
                case 'font-style':
                case 'text-decoration':
                case 'font-size':
                case 'font-family':
                    $textStyle[$key] = $value;
                    break;

                // Absatzbezogene Stile
                case 'margin':
                case 'margin-top':
                case 'margin-bottom':
                case 'margin-left':
                case 'margin-right':
                case 'padding':
                case 'padding-top':
                case 'padding-bottom':
                case 'padding-left':
                case 'padding-right':
                case 'align':
                case 'text-align':
                case 'line-height':
                case 'border':
                case 'border-left':
                case 'border-top':
                case 'border-right':
                case 'border-bottom':
                    $paragraphStyle[$key] = $value;
                    break;
            }
        }

        return [$textStyle, $paragraphStyle];
    }

}
