<?php

namespace OdtTemplateEngine\Utils;

use DOMDocument;
use DOMElement;

/**
 * Utility class for writing and managing styles in an ODT (OpenDocument Text) document.
 * 
 * This class provides static methods for writing text styles, paragraph styles, and table cell styles
 * to the ODT document's styles and automatic-styles sections.
 */
class StyleWriter
{
    /**
     * Writes all styles (text, paragraph, and table-cell styles) to the ODT document.
     * 
     * This method checks if the styles already exist in the document, and if not, it creates and appends
     * them to the appropriate XML elements. Text styles are added to the 'office:styles' section, and
     * table-cell styles are added to the 'office:automatic-styles' section.
     * 
     * @param DOMDocument $dom The ODT DOM document to which styles will be written.
     * @return void
     */
    public static function writeAllStyles(DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

        // Get the target nodes for office styles and automatic styles
        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles) {
            $officeStyles = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'office:styles');
            $dom->documentElement->appendChild($officeStyles);
        }

        $autoStyles = $xpath->query('//office:automatic-styles')->item(0);
        if (!$autoStyles) {
            $autoStyles = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'office:automatic-styles');
            $dom->documentElement->appendChild($autoStyles);
        }

        // Write text styles
        foreach (StyleMapper::getTextStyles() as $name => $props) {
            if (self::styleAlreadyExists($dom, $name, 'text'))
                continue;

            $style = $dom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'text');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $textProps = $dom->createElement('style:text-properties');
            foreach ($props as $key => $value) {
                $textProps->setAttribute($key, $value);
            }

            $style->appendChild($textProps);
            $officeStyles->appendChild($style);
        }

        // Write paragraph styles
        foreach (StyleMapper::getParagraphStyles() as $name => $props) {
            if (self::styleAlreadyExists($dom, $name, 'paragraph'))
                continue;


            $style = $dom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'paragraph');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $paraProps = $dom->createElement('style:paragraph-properties');
            foreach ($props as $key => $value) {
                $paraProps->setAttribute($key, $value);
            }

            $style->appendChild($paraProps);
            $officeStyles->appendChild($style);
        }

        // Write table-cell styles to automatic-styles
        foreach (StyleMapper::getRegisteredTableCellStyles() as $name => $props) {
            if (self::styleAlreadyExists($dom, $name, 'table-cell'))
                continue;

            $style = $dom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'table-cell');
            $style->setAttribute('style:parent-style-name', 'Default');

            // 📦 Table Cell Properties
            $cellProps = $dom->createElement('style:table-cell-properties');
            $paraProps = $dom->createElement('style:paragraph-properties');
            $textProps = $dom->createElement('style:text-properties');

            foreach ($props as $key => $value) {
                if (str_starts_with($key, 'fo:background') || str_starts_with($key, 'fo:border') || str_starts_with($key, 'fo:padding')) {
                    $cellProps->setAttribute($key, $value);
                } elseif (str_starts_with($key, 'fo:text-align')) {
                    $paraProps->setAttribute($key, $value);
                } elseif (str_starts_with($key, 'fo:font-') || $key === 'fo:color') {
                    $textProps->setAttribute($key, $value);
                }
            }

            if ($cellProps->hasAttributes())
                $style->appendChild($cellProps);
            if ($paraProps->hasAttributes())
                $style->appendChild($paraProps);
            if ($textProps->hasAttributes())
                $style->appendChild($textProps);

            $autoStyles->appendChild($style);
        }

        // Write image styles (graphic)
foreach (StyleMapper::getRegisteredImageStyles() as $name => $props) {
    if (self::styleAlreadyExists($dom, $name, 'graphic')) {
        continue;
    }

    $style = $dom->createElement('style:style');
    $style->setAttribute('style:name', $name);
    $style->setAttribute('style:family', 'graphic');
    $style->setAttribute('style:parent-style-name', 'Graphics');

    $graphicProps = $dom->createElement('style:graphic-properties');

    // 🔒 Nur gültige graphic properties erlauben
    $allowedGraphicAttributes = [
        'svg:width',
        'svg:height',
        'style:wrap',
        'style:horizontal-pos',
        'style:horizontal-rel',
        'style:vertical-pos',
        'style:vertical-rel',
        'fo:margin-left',
        'fo:margin-right',
        'fo:margin-top',
        'fo:margin-bottom',
        'draw:mirror',
        'draw:opacity',
        'draw:fill-color',
        'draw:fill-image',
        'draw:fill-style',
        'draw:fill-hatch-name',
        'draw:fill-gradient-name',
        'draw:fill',
        'draw:stroke',
        'draw:stroke-dash',
        'draw:stroke-width',
        'draw:stroke-color',
        'text:anchor-type', // ← Beachte Schreibfehler: in deinem Style war es "pararaph"
    ];

    foreach ($props as $key => $value) {
        if (in_array($key, $allowedGraphicAttributes)) {
            $graphicProps->setAttribute($key, $value);
            error_log("⚠️  Style $name mit Attribut in Image-Style gesetzt '$key' mit: $value");
        } else {
            error_log("⚠️ Unzulässiges Attribut in Image-Style '$name' ignoriert: $key");
        }
    }

    $style->appendChild($graphicProps);
    $autoStyles->appendChild($style);
}



    }

    /**
     * Checks whether a style with the given name already exists in the ODT document.
     * 
     * This method uses XPath to query the ODT document and check if a style with the specified name
     * already exists in the document's styles.
     * 
     * @param DOMDocument $dom The ODT DOM document to check for the style.
     * @param string $name The name of the style to check.
     * @return bool True if the style already exists, false otherwise.
     */
    public static function styleAlreadyExists(DOMDocument $dom, string $name, string $family): bool
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        $query = "//style:style[@style:name='$name' and @style:family='$family']";
        return $xpath->query($query)->length > 0;
    }


    /**
     * Appends a style to the 'office:styles' section of the ODT document.
     * 
     * This method ensures that the style is appended to the correct section of the document,
     * and if the 'office:styles' element is missing, it is created and added to the document.
     * 
     * @param DOMDocument $dom The ODT DOM document to append the style to.
     * @param DOMElement $style The style element to append.
     * @return void
     */
    public static function appendStyleToStylesXml(DOMDocument $dom, DOMElement $style): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles) {
            // Fallback if <office:styles> is missing
            $officeStyles = $dom->createElementNS(
                'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                'office:styles'
            );
            $dom->documentElement->appendChild($officeStyles);
        }

        $officeStyles->appendChild($style);
    }

    public function getRequiredTableCellStyleNodes(DOMDocument $dom): array
    {
        $nodes = [];

        foreach (StyleMapper::getRegisteredTableCellStyles() as $name => $props) {
            if (self::styleAlreadyExists($dom, $name, 'table-cell'))
                continue;

            $style = $dom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'table-cell');
            $style->setAttribute('style:parent-style-name', 'Default');

            $cellProps = $dom->createElement('style:table-cell-properties');
            foreach ($props as $key => $value) {
                $cellProps->setAttribute($key, $value);
            }

            $style->appendChild($cellProps);
            $nodes[] = $style;
        }

        return $nodes;
    }


}
