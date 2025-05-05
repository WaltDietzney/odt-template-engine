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

    // --- 1) Ensure <office:styles> exists ---
    $officeStyles = $xpath->query('//office:styles')->item(0)
                 ?? $domStyles->createElementNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0','office:styles');
    if (!$officeStyles->parentNode) {
        $domStyles->documentElement->appendChild($officeStyles);
    }

    // --- 2) Write text-styles and collect fonts ---
    $fontsUsed = [];
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
                $fontsUsed[$value] = true;
            }
        }
        $style->appendChild($textProps);
        $officeStyles->appendChild($style);
    }

    // --- 3) Write font-face-decls under office:styles ---
    $decls = $domStyles->createElement('office:font-face-decls');
    foreach (array_keys($fontsUsed) as $fontName) {
        $fontName = trim($fontName, "'\" ");
        if ($fontName === '' || $fontName === '0') {
            continue;
        }
        $fontFace = $domStyles->createElement('style:font-face');
        $fontFace->setAttribute('style:name', $fontName);
        $fontFace->setAttribute('svg:font-family', $fontName);
        $fontFace->setAttribute('style:font-pitch', 'variable');

        $lower = strtolower($fontName);
        if (str_contains($lower, 'sans')||str_contains($lower,'arial')||str_contains($lower,'ubuntu')) {
            $fontFace->setAttribute('style:font-family-generic','swiss');
        } elseif (str_contains($lower,'serif')||str_contains($lower,'times')) {
            $fontFace->setAttribute('style:font-family-generic','roman');
        } else {
            $fontFace->setAttribute('style:font-family-generic','system');
        }
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
}
