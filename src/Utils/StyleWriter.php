<?php

namespace OdtTemplateEngine\Utils;

use DOMDocument;

/**
 * Serializes explicit document data into ODF.
 *
 * Style ownership and requirement discovery belong to the document context
 * and semantic materializers. The remaining helper writes explicit table
 * column widths directly to the supplied content DOM.
 */
final class StyleWriter
{
    /**
     * Write explicit table-column styles to the target document.
     *
     * @param list<string> $columnWidths
     * @return list<string>
     */
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
