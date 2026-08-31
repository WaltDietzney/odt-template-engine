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
     *
     * The default retains the legacy static paragraph-style compatibility
     * path. Document finalization can opt out and rely on its own
     * document-scoped paragraph requirements.
     */
    public static function writeAllStyles(
        DOMDocument $domStyles,
        bool $includeLegacyParagraphStyles = true,
        bool $includeLegacyTextStyles = true,
        bool $includeLegacyFrameStyles = true
    ): void
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
        if ($includeLegacyTextStyles) {
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
        }

        // === 2) PARAGRAPH Styles ===
        if ($includeLegacyParagraphStyles) {
            foreach (StyleMapper::getParagraphStyles() as $name => $options) {
                if (self::styleAlreadyExists($domStyles, $name, 'paragraph')) {
                    continue;
                }

                $style = $domStyles->createElement('style:style');
                $style->setAttribute('style:name', $name);
                $style->setAttribute('style:family', 'paragraph');
                $style->setAttribute('style:parent-style-name', 'Standard');
                $style->setAttribute('style:class', 'text');

                $paragraphProps = $domStyles->createElement('style:paragraph-properties');
                $mappedOptions = StyleMapper::mapParagraphStyle($options);

                foreach ($mappedOptions as $key => $value) {
                    if ($key === 'style:tab-stops' && is_array($value)) {
                        $tabStops = $domStyles->createElement('style:tab-stops');
                        foreach ($value as $tabStop) {
                            $tabStopElement = $domStyles->createElement('style:tab-stop');
                            foreach ($tabStop as $attribute => $attributeValue) {
                                $tabStopElement->setAttribute($attribute, $attributeValue);
                            }
                            $tabStops->appendChild($tabStopElement);
                        }
                        $paragraphProps->appendChild($tabStops);
                        continue;
                    }

                    $paragraphProps->setAttribute($key, $value);
                }

                $style->appendChild($paragraphProps);
                $officeStyles->appendChild($style);
            }
        }

        // === 3) GRAPHIC Styles ===
        foreach ($includeLegacyFrameStyles ? StyleMapper::getFrameStyles() : [] as $name => $props) {
            if (self::styleAlreadyExists($domStyles, $name, 'graphic')) {
                continue;
            }

            $style = $domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'graphic');
            $style->setAttribute('style:parent-style-name', 'Frame');

            $graphicProps = $domStyles->createElement('style:graphic-properties');

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

        // === 4) TABLE-CELL Styles ===
        $cellStyles = StyleMapper::getRegisteredTableCellStyles();

        foreach ($cellStyles as &$props) {
            foreach (['style:column-width', 'fo:width'] as $forbidden) {
                unset($props[$forbidden]);
            }
        }
        unset($props);

        foreach ($cellStyles as $name => $props) {
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

        // === 5) TABLE Styles ===
        $tableStyles = StyleMapper::getRegisteredTableStyles();

        foreach ($tableStyles as $name => $props) {
            if (self::styleAlreadyExists($domStyles, $name, 'table')) {
                continue;
            }

            $style = $domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'table');

            $tableProps = $domStyles->createElement('style:table-properties');
            foreach ($props as $key => $value) {
                $tableProps->setAttribute($key, $value);
            }

            $style->appendChild($tableProps);
            $officeStyles->appendChild($style);
        }

        // === 6) Fonts ===
        // Text styles may have been written directly by OdtTemplate's
        // compatibility helpers, so collect font references from this document
        // rather than relying only on StyleMapper's process-wide registry.
        $fontNames = [];
        foreach ($xpath->query('//@*[contains(name(), "font-name")]') as $fontAttribute) {
            if (!str_ends_with($fontAttribute->nodeName, 'font-name')) {
                continue;
            }

            $fontName = trim($fontAttribute->nodeValue, "'\" ");
            if ($fontName !== '' && $fontName !== '0') {
                $fontNames[$fontName] = true;
            }
        }

        $fontFaceDecls = $xpath->query('/office:document-styles/office:font-face-decls')->item(0);
        if (!$fontFaceDecls) {
            $fontFaceDecls = $domStyles->createElement('office:font-face-decls');
            $domStyles->documentElement->insertBefore($fontFaceDecls, $officeStyles);
        }

        $existingFonts = [];
        foreach ($xpath->query('style:font-face/@style:name', $fontFaceDecls) as $fontAttribute) {
            $existingFonts[$fontAttribute->nodeValue] = true;
        }

        foreach (array_keys($fontNames) as $fontName) {
            if (isset($existingFonts[$fontName])) {
                continue;
            }

            $fontFace = $domStyles->createElement('style:font-face');
            $fontFace->setAttribute('style:name', $fontName);
            $fontFace->setAttribute('svg:font-family', $fontName);
            $fontFace->setAttribute('style:font-pitch', 'variable');
            $fontFaceDecls->appendChild($fontFace);
        }
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

        foreach ($xpath->query('//style:font-face[@style:name="0"]') as $badFontFace) {
            $badFontFace->parentNode->removeChild($badFontFace);
        }

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
     * Checks whether a style with the given name and family already exists.
     */
    private static function styleAlreadyExists(DOMDocument $domStyles, string $styleName, string $family): bool
    {
        $xpath = new DOMXPath($domStyles);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        $query = sprintf('//style:style[@style:name="%s" and @style:family="%s"]', $styleName, $family);

        return $xpath->query($query)->length > 0;
    }

    public static function writeColumnStyles(DOMDocument $doc, array $columnWidths): array
    {
        $styleNames = [];

        $automaticStyles = $doc->getElementsByTagName('office:automatic-styles')->item(0);
        if (!$automaticStyles) {
            $automaticStyles = $doc->createElement('office:automatic-styles');
            $doc->documentElement->insertBefore($automaticStyles, $doc->documentElement->firstChild);
        }

        foreach ($columnWidths as $i => $width) {
            $styleName = 'co' . $i;

            $styleElement = $doc->createElement('style:style');
            $styleElement->setAttribute('style:name', $styleName);
            $styleElement->setAttribute('style:family', 'table-column');

            $columnProps = $doc->createElement('style:table-column-properties');
            $columnProps->setAttribute('style:column-width', $width);

            $styleElement->appendChild($columnProps);
            $automaticStyles->appendChild($styleElement);

            $styleNames[] = $styleName;
        }

        return $styleNames;
    }
}
