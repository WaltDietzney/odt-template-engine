<?php

namespace OdtTemplateEngine\Utils;

use OdtTemplateEngine\Style\LegacyStyleRegistry;

/**
 * StyleMapper is a utility class responsible for mapping and registering various styles (text, paragraph, and table-cell) 
 * for use in an OpenDocument Text (ODT) document. It allows you to define styles and map them to the required formatting 
 * attributes. Additionally, it handles the generation of unique style names and registers styles to avoid duplication.
 */
class StyleMapper
{
    /**
     * @var array Holds registered text styles.
     */
    protected static array $registeredTextStyles = [];

    private static array $textStyles = [];

    /**
     * @var array Holds registered table cell styles.
     */
    protected static array $registeredTableCellStyles = [];

    /**
     * @var array Holds table cell styles.
     */
    protected static array $tableCellStyles = [];

    /**
     * Summary of registeredImageStyles
     * @var array
     */
    protected static array $registeredImageStyles = [];

    /**
     * Maps fill-image names to their file paths for draw:fill="bitmap".
     * @var array<string, array{name: string, path: string, filename: string}>
     */
    protected static array $registeredFillImages = [];

    /**
     * Summary of registeredFonts
     * @var array
     */
    private static array $registeredFonts = [];

    public static array $frameStyles = [];

    public static array $tableStyles = [];


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
     * Maps a set of table-cell style options to their corresponding ODF attributes.
     * 
     * This method maps table-cell properties, such as 'background', 'border', 'padding', etc., to the appropriate
     * attributes used for table cells in ODF.
     * 
     * @param array $input The input table-cell style options.
     * @return array The mapped style attributes for table cells.
     */
    public static function mapTableCellStyle(array $input): array
    {
        $map = [];

        if (!empty($input['background'])) {
            $map['fo:background-color'] = $input['background'];
        }

        if (!empty($input['border'])) {
            $map['fo:border'] = $input['border'];
        }

        if (!empty($input['padding'])) {
            $map['fo:padding'] = $input['padding'];
        }

        if (!empty($input['text-align'])) {
            $map['fo:text-align'] = $input['text-align'];
        }

        return $map;
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



    public static function getRegisteredFontsXml(): string
    {
        $xml = '';

        foreach (array_keys(self::$registeredFonts) as $fontName) {
            $xml .= '<style:font-face style:name="' . htmlspecialchars($fontName) . '" svg:font-family="' . htmlspecialchars($fontName) . '"/>' . "\n";
        }

        return $xml;
    }


    /**
     * Maps additional table-cell style options to their corresponding ODF attributes.
     * 
     * This method extends the functionality of `mapTableCellStyle()` to include more options such as
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
     * Registers a new text style.
     * 
     * This method generates a unique name for the style and stores it in the static array of registered text styles.
     * 
     * @param array $style The text style array.
     */
    public static function registerTextStyle(array $style): string
    {
        $styleName = self::generateStyleName($style);
        if (!isset(self::$registeredTextStyles[$styleName])) {
            self::$registeredTextStyles[$styleName] = $style;
            self::$textStyles[$styleName] = $style; // <- wenn getTextStyles() das benutzt
        }
        return $styleName;
    }

    public static function setTextStyle(string $styleName, array $style)
    {
        if (!isset(self::$registeredTextStyles[$styleName])) {
            self::$registeredTextStyles[$styleName] = $style;
        }
    }


    /**
     * Registers a new paragraph style.
     * 
     * This method registers the paragraph style under the provided name in the static array of registered paragraph styles.
     * 
     * @param string $styleName The name of the paragraph style.
     * @param array $style The paragraph style array.
     */
    public static function registerParagraphStyle(string $styleName, array $style): void
    {
        LegacyStyleRegistry::registerParagraphStyle($styleName, $style);
    }


    /**
     * Retrieves all registered styles (text and paragraph).
     * 
     * @return array The merged array of all registered text and paragraph styles.
     */
    public static function getRegisteredStyles(): array
    {
        return array_merge(self::$registeredTextStyles, LegacyStyleRegistry::paragraphStyles());
    }

    /**
     * Retrieves all registered text styles.
     * 
     * @return array The array of registered text styles.
     */
    public static function getTextStyles(): array
    {
        return self::$registeredTextStyles;
    }

    /**
     * Retrieves all registered paragraph styles.
     * 
     * @return array The array of registered paragraph styles.
     */
    public static function getParagraphStyles(): array
    {
        return LegacyStyleRegistry::paragraphStyles();
    }

    /**
     * Retrieves all registered styles (text, paragraph, and table-cell).
     * 
     * @return array The array of all registered styles categorized by type.
     */
    public static function getAllRegisteredStyles(): array
    {
        return [
            'text' => self::$registeredTextStyles,
            'paragraph' => LegacyStyleRegistry::paragraphStyles(),
            'table-cell' => self::$registeredTableCellStyles,
        ];
    }

    /**
     * Registers a new table-cell style.
     * 
     * This method registers the table-cell style with the provided name and style options.
     * 
     * @param string $name The name of the table-cell style.
     * @param array $options The style options for the table-cell.
     */
    public static function registerTableCellStyle(string $name, array $options): void
    {
        self::$tableCellStyles[$name] = self::mapTableCellStyleOptions($options);
    }

    /**
     * Retrieves all registered table-cell styles.
     * 
     * @return array The array of registered table-cell styles.
     */
    public static function getRegisteredTableCellStyles(): array
    {
        return self::$tableCellStyles;
    }

    /**
     * Summary of hasTextStyle
     * @param string $styleName
     * @return bool
     */
    public static function hasTextStyle(string $styleName): bool
    {
        return isset(self::$registeredTextStyles[$styleName]);
    }

    /**
     * Registriert einen Bildstil unter einem stabilen Namen.
     *
     * @param string|null $name Optionaler Stilname. Wenn leer, wird er aus den Optionen generiert.
     * @param array $options Stiloptionen
     * @return void
     */
    public static function registerImageStyle(?string $name, array $options): void
    {
        // Normalisieren: irrelevante Keys raus, sortieren
        $normalized = array_filter(
            $options,
            fn($key) => !in_array($key, ['align', 'style-name'], true),
            ARRAY_FILTER_USE_KEY
        );
        ksort($normalized);

        // Falls kein Name übergeben, Style-Name generieren
        $name ??= self::generateStyleName($normalized);

        // Speichern
        self::$registeredImageStyles[$name] = $normalized;
    }


    /**
     * Summary of getRegisteredImageStyles
     * @return array
     */
    public static function getRegisteredImageStyles(): array
    {
        return self::$registeredImageStyles;
    }

    /**
     * Registers a fill-image for use with draw:fill="bitmap".
     *
     * @param string $name The unique name (referenced by draw:fill-image-name).
     * @param string $imagePath Absolute path to the image file.
     * @return void
     */
    public static function registerFillImage(string $name, string $imagePath): void
    {
        self::$registeredFillImages[$name] = [
            'name' => $name,
            'path' => $imagePath,
            'filename' => basename($imagePath),
        ];
    }

    /**
     * Returns all registered fill-images.
     *
     * @return array<string, array{name: string, path: string, filename: string}>
     */
    public static function getRegisteredFillImages(): array
    {
        return self::$registeredFillImages;
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




    /**
     * Gibt alle Frame-Styles (für draw:frame etc.) zurück.
     */
    public static function getFrameStyles(): array
    {
        return self::$frameStyles;
    }


    /**
     * Registriert einen neuen Frame-Style.
     */
    public static function addFrameStyle(string $name, array $properties): void
    {
        // Doppelte Styles vermeiden
        if (!isset(self::$frameStyles[$name])) {
            self::$frameStyles[$name] = $properties;
        } else {
            // Optional: bestehende Styles zusammenführen (wenn sinnvoll)
            self::$frameStyles[$name] = array_merge(self::$frameStyles[$name], $properties);
        }
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

    public static function registerTableStyle(string $name, array $properties): void
    {
        self::$tableStyles[$name] = $properties;
    }
    public static function getRegisteredTableStyles(): array
    {
        return self::$tableStyles;
    }

}
