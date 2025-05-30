<?php

namespace OdtTemplateEngine\Utils;

use DOMDocument;
use DOMElement;
use DOMXPath;

class StyleWriter
{
    /**
     * Stores generated styles to avoid duplicates.
     */
    private static array $generatedTextStyles = [];

    private static array $fontsUsed = [];

    /**
     * Writes all necessary styles and font declarations.
     */
    public static function writeAllStyles(DOMDocument $domStyles): void
    {
        $xpath = new DOMXPath($domStyles);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        $officeStyles = $xpath->query('//office:styles')->item(0)
            ?? $domStyles->createElementNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'office:styles');
        if (!$officeStyles->parentNode) {
            $domStyles->documentElement->appendChild($officeStyles);
        }

        // === 1) TEXT Styles ===
        foreach (StyleMapper::getTextStyles() as $name => $props) {
            if (self::styleAlreadyExists($domStyles, $name, 'text')) {
                continue;
            }
            $style = $domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'text');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $textProps = $domStyles->createElement('style:text-properties');
            foreach ($props as $key => $value) {
                $textProps->setAttribute($key, $value);
                if ($key === 'style:font-name') {
                    $textProps->setAttribute('fo:font-family', $value);
                    self::$fontsUsed[$value] = true;
                }
            }
            $style->appendChild($textProps);
            $officeStyles->appendChild($style);
        }

        // === 2) GRAPHIC Styles (neu) ===
// Nach den Text-Styles ➔ Frame-Styles schreiben
        foreach (StyleMapper::getFrameStyles() as $name => $props) {
            if (self::styleAlreadyExists($domStyles, $name, 'graphic')) {
                continue;
            }

            $style = $domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'graphic');
            $style->setAttribute('style:parent-style-name', 'Frame'); // 🔥 Wichtig: parent "Frame"

            $graphicProps = $domStyles->createElement('style:graphic-properties');

            // 🛠 Automatische Ergänzung, falls nur fo:background-color gesetzt wurde
            if (
                isset($props['fo:background-color']) &&
                !isset($props['draw:fill']) &&
                !isset($props['draw:fill-color'])
            ) {
                $props['draw:fill'] = 'solid';
                $props['draw:fill-color'] = $props['fo:background-color'];
            }

            foreach ($props as $key => $value) {
                $graphicProps->setAttribute($key, $value);
            }

            $style->appendChild($graphicProps);
            $officeStyles->appendChild($style);
        }


        // === 3) TABLE-CELL Styles ===
        foreach (StyleMapper::getRegisteredTableCellStyles() as $name => $props) {
            if (self::styleAlreadyExists($domStyles, $name, 'table-cell')) {
                continue;
            }

            $style = $domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'table-cell');
            $style->setAttribute('style:parent-style-name', 'Default');

            $cellProps = $domStyles->createElement('style:table-cell-properties');
            foreach ($props as $key => $value) {
                $cellProps->setAttribute($key, $value);
            }

            $style->appendChild($cellProps);
            $officeStyles->appendChild($style);
        }



        // === 4) Fonts (wie bisher) ===
        $decls = $domStyles->createElement('office:font-face-decls');
        foreach (array_keys(self::$fontsUsed) as $fontName) {
            $fontName = trim($fontName, "'\" ");
            if ($fontName === '' || $fontName === '0') {
                continue;
            }
            $fontFace = $domStyles->createElement('style:font-face');
            $fontFace->setAttribute('style:name', $fontName);
            $fontFace->setAttribute('svg:font-family', $fontName);
            $fontFace->setAttribute('style:font-pitch', 'variable');
            $decls->appendChild($fontFace);
        }
        $officeStyles->appendChild($decls);
    }



    /**
     * Writes text styles (with fonts) to office:styles.
     */
    public static function writeTextStyles(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $officeStyles = $xpath->query('//office:styles')->item(0);

        if (!$officeStyles) {
            $officeStyles = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'office:styles');
            $dom->documentElement->appendChild($officeStyles);
        }

        foreach (StyleMapper::getTextStyles() as $styleName => $props) {
            if (isset(self::$generatedTextStyles[$styleName])) {
                continue;
            }

            $style = $dom->createElement('style:style');
            $style->setAttribute('style:name', $styleName);
            $style->setAttribute('style:family', 'text');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $textProps = $dom->createElement('style:text-properties');

            foreach ($props as $key => $value) {
                $textProps->setAttribute($key, $value);

                if ($key === 'style:font-name') {
                    $textProps->setAttribute('fo:font-family', $value);
                    self::$fontsUsed[$value] = true;
                }
            }

            $style->appendChild($textProps);
            $officeStyles->appendChild($style);
            self::$generatedTextStyles[$styleName] = true;
        }
    }

    /**
     * Writes all needed font faces based on used fonts.
     */
    public static function writeFontFaces(DOMDocument $dom): void
    {
        if (empty(self::$fontsUsed)) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

        // Remove wrong font-face entries like "0"
        foreach ($xpath->query('//style:font-face[@style:name="0"]') as $badFontFace) {
            $badFontFace->parentNode->removeChild($badFontFace);
        }

        // Debug-Ausgabe: Logs alle gesammelten Fonts
        error_log('=== StyleWriter: fontsUsed === ' . implode(', ', array_keys(self::$fontsUsed)));

        $fontFaceDecls = $dom->createElement('office:font-face-decls');

        foreach (array_keys(self::$fontsUsed) as $fontName) {
            $fontName = trim((string) $fontName, "'\" ");
            if ($fontName === '' || $fontName === '0') {
                continue;
            }

            $fontFace = $dom->createElement('style:font-face');
            $fontFace->setAttribute('style:name', $fontName);
            $fontFace->setAttribute('svg:font-family', $fontName);
            $fontFace->setAttribute('style:font-pitch', 'variable');

            // Classification
            $lowerFont = strtolower($fontName);
            if (str_contains($lowerFont, 'sans') || str_contains($lowerFont, 'arial') || str_contains($lowerFont, 'ubuntu')) {
                $fontFace->setAttribute('style:font-family-generic', 'swiss');
            } elseif (str_contains($lowerFont, 'serif') || str_contains($lowerFont, 'times') || str_contains($lowerFont, 'georgia')) {
                $fontFace->setAttribute('style:font-family-generic', 'roman');
            } else {
                $fontFace->setAttribute('style:font-family-generic', 'system');
            }

            $fontFaceDecls->appendChild($fontFace);
        }

        $autoStyles = $xpath->query('//office:automatic-styles')->item(0);
        if ($autoStyles && $autoStyles->parentNode) {
            $autoStyles->parentNode->insertBefore($fontFaceDecls, $autoStyles);
        } else {
            $dom->documentElement->appendChild($fontFaceDecls);
        }
    }

    /**
     * Prüft, ob ein Stil mit dem gegebenen Namen und Stilfamilie bereits existiert.
     *
     * @param DOMDocument $domStyles
     * @param string $styleName
     * @param string $family 'text', 'paragraph', 'graphic' usw.
     * @return bool
     */
    private static function styleAlreadyExists(DOMDocument $domStyles, string $styleName, string $family): bool
    {
        $xpath = new \DOMXPath($domStyles);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        $query = sprintf('//style:style[@style:name="%s" and @style:family="%s"]', $styleName, $family);
        return $xpath->query($query)->length > 0;
    }

}
