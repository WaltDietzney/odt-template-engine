<?php

namespace OdtTemplateEngine\Utils;

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

    /**
     * @var array Holds registered paragraph styles.
     */
    protected static array $registeredParagraphStyles = [];

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
                case 'padding':
                    $mapped['fo:padding'] = $value;
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

        if (!empty($options['bold'])) {
            $mapped['fo:font-weight'] = 'bold';
        }

        if (!empty($options['italic'])) {
            $mapped['fo:font-style'] = 'italic';
        }

        if (!empty($options['underline'])) {
            $mapped['style:text-underline-style'] = 'solid';
            $mapped['style:text-underline-type'] = 'single';
            $mapped['style:text-underline-width'] = 'auto';
        }

        if (!empty($options['color'])) {
            $mapped['fo:color'] = $options['color'];
        }

        if (!empty($options['background-color'])) {
            $mapped['fo:background-color'] = $options['background-color'];
        }

        if (!empty($options['font-size'])) {
            $mapped['fo:font-size'] = $options['font-size'];
        }

        if (!empty($options['font-family'])) {
            $mapped['style:font-name'] = $options['font-family'];
        }

        return $mapped;
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
            // LibreOffice-konforme Keys direkt übernehmen
            if (preg_match('/^(fo:|style:)/', $key)) {
                $mapped[$key] = $value;
                continue;
            }

            // Alias-Mapping für menschenfreundliche Namen
            switch ($key) {
                case 'background':
                    $mapped['fo:background-color'] = $value;
                    break;
                case 'padding':
                    $mapped['fo:padding'] = $value;
                    break;
                case 'text-align':
                case 'align':
                    $mapped['fo:text-align'] = $value;
                    break;
                case 'weight':
                    $mapped['fo:font-weight'] = $value;
                    break;
                case 'color':
                    $mapped['fo:color'] = $value;
                    break;
                case 'border':
                    $mapped['fo:border'] = $value;
                    break;
                // Weitere Aliase hier ergänzen
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
                error_log("⚠️ Ungültiger wrap-Wert: {$options['wrap']}");
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
                error_log("🚨 Ungültiger anchor-Wert: {$options['anchor']}");
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
    public static function generateParagraphStyleName(array $style): string
    {
        //return 'para_' . substr(md5(json_encode($style)), 0, 6);
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
        }
        error_log("TextStyle '{$styleName}' registered.");
        return $styleName;
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
        if (!isset(self::$registeredParagraphStyles[$styleName])) {
            self::$registeredParagraphStyles[$styleName] = $style;
            error_log("ParagraphStyle '{$styleName}' registered.");
        }
    }


    /**
     * Retrieves all registered styles (text and paragraph).
     * 
     * @return array The merged array of all registered text and paragraph styles.
     */
    public static function getRegisteredStyles(): array
    {
        return array_merge(self::$registeredTextStyles, self::$registeredParagraphStyles);
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
        return self::$registeredParagraphStyles;
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
            'paragraph' => self::$registeredParagraphStyles,
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
}
