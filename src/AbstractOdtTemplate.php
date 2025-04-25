<?php

namespace OdtTemplateEngine;

use OdtTemplateEngine\Elements\OdtElement;
use DOMDocument;
use DOMNode;
use DOMXPath;
use DOMElement;
use Exception;
use OdtTemplateEngine\Utils\StyleWriter;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Abstract class for handling OpenDocument Template operations.
 * 
 * This abstract class provides functionality to manipulate OpenDocument files (e.g., `.odt` files).
 * It defines the necessary methods for inserting styles, replacing placeholders, and manipulating content within an ODT document.
 * Subclasses must implement the actual template logic and provide specific functionality.
 */
abstract class AbstractOdtTemplate
{
    /**
     * Path to the ODT template file.
     * 
     * This property stores the file path of the ODT template that is being processed.
     * The path is used to load the template into memory for manipulation.
     * 
     * @var string
     */
    protected string $templatePath;

    /**
     * Directory used for temporary storage.
     * 
     * This property stores the directory path where temporary files, such as generated ODT files, are stored.
     * This is used for operations like saving the processed document before final export.
     * 
     * @var string
     */
    protected string $tempDir;

    /**
     * DOMDocument instance representing the content.xml of the ODT file.
     * 
     * This property represents the content of the ODT file as a DOMDocument object, which allows for easy manipulation of the document's content.
     * It is used to replace placeholders and manipulate other parts of the document.
     * 
     * @var DOMDocument
     */
    protected DOMDocument $domContent;

    /**
     * DOMDocument instance representing the styles.xml of the ODT file.
     * 
     * This property represents the styles of the ODT file as a DOMDocument object. It is used to manage the various styles applied to content elements,
     * including text styles, paragraph styles, and table styles.
     * 
     * @var DOMDocument
     */
    protected DOMDocument $domStyles;



    /**
     * Registers the required XML namespaces for the DOMXPath object.
     * These namespaces are necessary to correctly apply XPath expressions to ODT-specific XML elements.
     *
     * @param DOMXPath $xpath The DOMXPath object where the namespaces will be registered.
     * 
     * @return void
     * 
     * @see https://www.oasis-open.org/committees/tc_home.php?wg_abbrev=office for details on the ODT XML specification.
     */
    protected function prepareNamespaces(DOMXPath $xpath): void
    {
        $xpath->registerNamespace("office", "urn:oasis:names:tc:opendocument:xmlns:office:1.0");
        $xpath->registerNamespace("style", "urn:oasis:names:tc:opendocument:xmlns:style:1.0");
        $xpath->registerNamespace("fo", "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0");
    }


    /**
     * Ensures that the necessary XML namespace attributes are present in the root element of the styles document.
     * If the 'xmlns:fo' or 'xmlns:style' attributes are missing, they will be added to the root element.
     * 
     * This method ensures that the styles XML is properly set up for the OpenDocument format.
     * 
     * @return void
     */
    protected function ensureXmlnsAttributes(): void
    {
        $root = $this->domStyles->documentElement;

        if (!$root->hasAttribute('xmlns:fo')) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');
        }
        if (!$root->hasAttribute('xmlns:style')) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        }
    }


    /**
     * Ensures that the necessary text styles are defined in the styles.xml document.
     * For each style in the provided map, if the style does not already exist, it will be created and added to the styles section.
     * 
     * The method supports a variety of text properties such as bold, italic, underline, color, background-color, font-size, and font-family.
     * It creates new `style:style` elements with appropriate `style:text-properties` and appends them to the `<office:styles>` node in the styles.xml document.
     * 
     * @param array $styleMap Associative array where keys are style names and values are arrays of properties for each style.
     * 
     * @return void
     * @throws Exception If the `<office:styles>` section is not found in the styles.xml document.
     */
    protected function ensureTextStylesExist(array $styleMap): void
    {
        $this->ensureXmlnsAttributes();
        $xpath = new DOMXPath($this->domStyles);
        $this->prepareNamespaces($xpath);

        $officeStylesNode = $xpath->query('//office:styles')->item(0);
        if (!$officeStylesNode) {
            throw new Exception("❌ <office:styles> section not found in styles.xml");
        }

        foreach ($styleMap as $name => $options) {
            if ($xpath->query("//style:style[@style:name='$name']")->length > 0)
                continue;

            $style = $this->domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'text');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $props = $this->domStyles->createElement('style:text-properties');

            $RawOptions = StyleMapper::mapTextStyleOptions($options);
            foreach ($RawOptions as $key => $value) {
                $props->setAttribute($key, $value);
            }

            $style->appendChild($props);
            $officeStylesNode->appendChild($style);
        }
    }


    /**
     * Ensures that the necessary paragraph styles are defined in the styles.xml document.
     * For each style in the provided map, if the style does not already exist, it will be created and added to the styles section.
     * 
     * The method supports a variety of paragraph properties such as margin-top, margin-bottom, text-align, line-height,
     * background-color, keep-with-next, break-before, and break-after. It creates new `style:style` elements with appropriate 
     * `style:paragraph-properties` and appends them to the `<office:styles>` node in the styles.xml document.
     * 
     * @param array $styleMap Associative array where keys are style names and values are arrays of properties for each style.
     * 
     * @return void
     * @throws Exception If the `<office:styles>` section is not found in the styles.xml document.
     */
    public function ensureParagraphStylesExist(array $styleMap): void
    {
        $this->ensureXmlnsAttributes();
        $xpath = new DOMXPath($this->domStyles);
        $this->prepareNamespaces($xpath);

        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles)
            throw new Exception("❌ <office:styles> not found");

        foreach ($styleMap as $name => $rawOptions) {
            if ($xpath->query("//style:style[@style:name='$name']")->length > 0)
                continue;

            $style = $this->domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'paragraph');
            $style->setAttribute('style:parent-style-name', 'Standard');
            $style->setAttribute('style:class', 'text');

            $options = StyleMapper::mapParagraphStyle($rawOptions);
            $paraProps = $this->domStyles->createElement('style:paragraph-properties');

            foreach ($options as $key => $value) {
                if ($key === 'style:tab-stops' && is_array($value)) {
                    $tabStopsElement = $this->domStyles->createElement('style:tab-stops');

                    foreach ($value as $tabStop) {
                        $tabElement = $this->domStyles->createElement('style:tab-stop');

                        foreach ($tabStop as $attrName => $attrValue) {
                            $tabElement->setAttribute($attrName, $attrValue);
                        }

                        $tabStopsElement->appendChild($tabElement);
                    }

                    $paraProps->appendChild($tabStopsElement);
                } else {
                    $paraProps->setAttribute($key, $value);
                }
            }


            $style->appendChild($paraProps);
            $officeStyles->appendChild($style);
        }
    }


    /**
     * Inserts an automatic style into the `<office:automatic-styles>` section of the provided DOM document.
     * If the `<office:automatic-styles>` section does not exist, it will be created.
     * 
     * @param DOMDocument $dom The DOM document in which the style will be inserted.
     * @param DOMElement $style The style element to insert into the document.
     * 
     * @return void
     */
    protected function insertAutomaticStyle(DOMDocument $dom, DOMElement $style): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

        $automaticStylesNode = $xpath->query('//office:automatic-styles')->item(0);

        if (!$automaticStylesNode) {
            // If <office:automatic-styles> does not exist, create it
            $automaticStylesNode = $dom->createElement('office:automatic-styles');
            $firstChild = $dom->documentElement->firstChild;
            $dom->documentElement->insertBefore($automaticStylesNode, $firstChild);
        }

        // Insert the style into <office:automatic-styles>
        $automaticStylesNode->appendChild($style);
    }


    /**
     * Ensures that the table cell style nodes exist in the `<office:automatic-styles>` section of the DOM content.
     * For each style node in the provided list, if the style doesn't already exist, it is imported and appended to the 
     * `<office:automatic-styles>` section.
     * 
     * @param array $styleNodes An array of DOMElement nodes representing table cell styles to be ensured.
     * 
     * @return void
     */
    protected function ensureTableCellStyleNodesExist(array $styleNodes): void
    {
        $xpath = new DOMXPath($this->domContent);
        $xpath->registerNamespace("style", "urn:oasis:names:tc:opendocument:xmlns:style:1.0");
        $xpath->registerNamespace("office", "urn:oasis:names:tc:opendocument:xmlns:office:1.0");

        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);

        if (!$automaticStyles) {
            $automaticStyles = $this->domContent->createElement('office:automatic-styles');
            $this->domContent->documentElement->insertBefore(
                $automaticStyles,
                $this->domContent->documentElement->firstChild
            );
        }

        foreach ($styleNodes as $styleNode) {
            if (!$styleNode instanceof DOMElement) {
                continue;
            }

            $styleName = $styleNode->getAttribute('style:name');
            $exists = $xpath->query("//style:style[@style:name='$styleName']")->length > 0;

            if (!$exists) {
                $imported = $this->domContent->importNode($styleNode, true);
                $automaticStyles->appendChild($imported);
            }
        }
    }


    /**
     * Ensures that the default list styles (bullet and numbered lists) exist in the document's style section.
     * If the styles are not present, they will be created and added to the document's styles.
     * 
     * - Bullet list style with a bullet character (•) and custom spacing.
     * - Numbered list style with the number format "1." and custom spacing.
     * 
     * @return void
     */
    protected function ensureDefaultListStyles(): void
    {
        $xpath = new DOMXPath($this->domStyles);
        $xpath->registerNamespace("text", "urn:oasis:names:tc:opendocument:xmlns:text:1.0");
        $xpath->registerNamespace("style", "urn:oasis:names:tc:opendocument:xmlns:style:1.0");

        // 🔹 Bullet list
        if ($xpath->query("//text:list-style[@style:name='Bullet_20_Symbol']")->length === 0) {
            $listStyle = $this->domStyles->createElement('text:list-style');
            $listStyle->setAttribute('style:name', 'Bullet_20_Symbol');

            $level = $this->domStyles->createElement('text:list-level-style-bullet');
            $level->setAttribute('text:level', '1');
            $level->setAttribute('text:bullet-char', '•');

            $props = $this->domStyles->createElement('style:list-level-properties');
            $props->setAttribute('text:space-before', '0.5cm');
            $props->setAttribute('text:min-label-width', '0.5cm');

            $level->appendChild($props);
            $listStyle->appendChild($level);
            $this->domStyles->documentElement->appendChild($listStyle);
        }

        // 🔸 Numbered list
        if ($xpath->query("//text:list-style[@style:name='Numbering_20_Symbol']")->length === 0) {
            $listStyle = $this->domStyles->createElement('text:list-style');
            $listStyle->setAttribute('style:name', 'Numbering_20_Symbol');

            $level = $this->domStyles->createElement('text:list-level-style-number');
            $level->setAttribute('text:level', '1');
            $level->setAttribute('style:num-format', '1');
            $level->setAttribute('style:num-suffix', '.');
            $level->setAttribute('style:num-prefix', '');

            $props = $this->domStyles->createElement('style:list-level-properties');
            $props->setAttribute('text:space-before', '0.5cm');
            $props->setAttribute('text:min-label-width', '0.5cm');

            $level->appendChild($props);
            $listStyle->appendChild($level);
            $this->domStyles->documentElement->appendChild($listStyle);
        }
    }


    /**
     * Ensures that the default paragraph styles (Heading 1 to Heading 6) exist in the document's style section.
     * If the styles are not present, they will be created and added to the document's styles.
     * 
     * The default styles include:
     * - Heading 1 to Heading 6 with bold font weight and specific top and bottom margins.
     * 
     * @return void
     * @throws Exception If the <office:styles> section is not found in the document.
     */
    protected function ensureDefaultParagraphStyles(): void
    {
        $xpath = new DOMXPath($this->domStyles);
        $xpath->registerNamespace("style", "urn:oasis:names:tc:opendocument:xmlns:style:1.0");
        $xpath->registerNamespace("fo", "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0");
        $xpath->registerNamespace("office", "urn:oasis:names:tc:opendocument:xmlns:office:1.0");

        $officeStylesNode = $xpath->query('//office:styles')->item(0);
        if (!$officeStylesNode) {
            throw new Exception("❌ <office:styles> section not found.");
        }

        for ($i = 1; $i <= 6; $i++) {
            $styleName = "Heading $i";

            $exists = $xpath->query("//style:style[@style:name='$styleName']")->length > 0;
            if ($exists)
                continue;

            $style = $this->domStyles->createElement('style:style');
            $style->setAttribute('style:name', $styleName);
            $style->setAttribute('style:family', 'paragraph');
            $style->setAttribute('style:parent-style-name', 'Standard');

            $textProps = $this->domStyles->createElement('style:text-properties');
            $textProps->setAttribute('fo:font-weight', 'bold');

            $paraProps = $this->domStyles->createElement('style:paragraph-properties');
            $paraProps->setAttribute('fo:margin-top', '0.5cm');
            $paraProps->setAttribute('fo:margin-bottom', '0.3cm');

            $style->appendChild($textProps);
            $style->appendChild($paraProps);

            $officeStylesNode->appendChild($style);
        }
    }


    /**
     * Replaces placeholders in the format {{name}} or {{upper:name}} with corresponding values.
     * Supports both regular placeholders and filtered placeholders (e.g., {{filter:name|option}}).
     * 
     * @param DOMDocument $dom The XML document (content.xml or styles.xml)
     * @param array $values The values to replace in the placeholders
     * 
     * @return void
     */
    protected function setValuesInDom(DOMDocument $dom, array $values): void
    {
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//text()') as $textNode) {
            $text = $textNode->nodeValue;

            // Standard placeholders: {{key}}
            foreach ($values as $key => $value) {
                if ($value instanceof OdtElement) {
                    $this->replacePlaceholderWithDom($dom, $key, $value->toDomNode($dom));
                } else {
                    $text = str_replace('{{' . $key . '}}', $value, $text);
                }
            }
            // Filtered placeholders: {{filter:key}} or {{filter:key|option}}
            $text = preg_replace_callback('/{{(\w+):(\w+)(?:\|([^}]+))?}}/', function ($matches) use ($values) {
                $filter = $matches[1];
                $key = $matches[2];
                $option = $matches[3] ?? null;
                $val = $values[$key] ?? '';
                return $this->applyFilter($filter, $val, $option);
            }, $text);

            $textNode->nodeValue = $text;
        }
    }


    /**
     * Sets an OdtElement to the document by processing its styles, image assets, and placeholder replacements.
     * This method will:
     * 1. Extract and register the required text, paragraph, and table cell styles.
     * 2. Insert the styles into the document.
     * 3. Replace placeholders in the content.xml with the element's DOM representation.
     * 
     * @param string $placeholder The placeholder name in the content.xml to replace
     * @param OdtElement $element The element whose values, styles, and images are used to populate the placeholder
     * 
     * @return void
     */
    public function setElement(string $placeholder, OdtElement $element): void
    {

        // 1. 🔍 Extract required styles
        $textStyles = $element->getRequiredStyles() ?? [];
        $paragraphStyles = method_exists($element, 'getRequiredParagraphStyles')
            ? $element->getRequiredParagraphStyles()
            : [];
        $tableCellStyles = method_exists($element, 'getRequiredTableCellStyleNodes')
            ? $element->getRequiredTableCellStyleNodes()
            : [];

        if ($element instanceof HasStyles) {
            $this->registerStyles($element->getStyleDefinitions());
        }

        // 2. 🧩 Insert styles into the registerSty
        if (!empty($textStyles)) {
            $this->ensureTextStylesExist($textStyles);
        }

        if (!empty($paragraphStyles)) {
            $this->ensureParagraphStylesExist($paragraphStyles);
        }

        if (!empty($tableCellStyles)) {
            $this->ensureTableCellStyleNodesExist($tableCellStyles);
        }

        // 3. 🖼️ Replace image assets from the element
        if (method_exists($element, 'getImageAssets')) {
            foreach ($element->getImageAssets() as $img) {
                $this->replaceImageByName($img['id'], $img['path']);
            }
        }

        // 5. 🎨 Insert style node, if available
        if (method_exists($element, 'toStyleDomNode')) {
            $styleNode = $element->toStyleDomNode($this->domStyles);
            if ($styleNode instanceof DOMElement) {
                $xpath = new DOMXPath($this->domStyles);
                $stylesRoot = $xpath->query('//office:automatic-styles')->item(0);
                if ($stylesRoot) {
                    $stylesRoot->appendChild($styleNode);
                }
            }
        }

        // 6. 🪄 Replace placeholder with DOM node in content.xml
        $this->replacePlaceholderWithDom(
            $this->domContent,
            $placeholder,
            $element->toDomNode($this->domContent)
        );
        // 6. 🪄 Replace placeholder with DOM node in styles.xml
        $this->replacePlaceholderWithDom(
            $this->domStyles,
            $placeholder,
            $element->toDomNode($this->domStyles)
        );
    }


    /**
     * Replaces a placeholder in the DOM with a given DOM node.
     * This method searches for the placeholder in the form {{key}} inside <text:p> elements and replaces it with the provided DOM node.
     * 
     * @param DOMDocument $dom The XML document (content.xml or styles.xml)
     * @param string $key The placeholder key (e.g., "name")
     * @param DOMNode $replacement The DOM node to replace the placeholder with
     * 
     * @return void
     */
    protected function replacePlaceholderWithDom(DOMDocument $dom, string $key, DOMNode $replacement): void
    {
        $xpath = new DOMXPath($dom);
        $query = "//text:p[contains(text(), '{{{$key}}}')]";

        foreach ($xpath->query($query) as $pNode) {
            $parent = $pNode->parentNode;
            if ($parent) {
                $parent->replaceChild($replacement, $pNode);
            }
        }
    }


    /**
     * Recursively replaces placeholders within the entire node tree.
     * This method performs a recursive replacement of placeholders (e.g., {{key}}) in the provided DOM node and its child nodes.
     * 
     * @param DOMNode $node The DOM node to process
     * @param array $data The placeholder data for replacement
     * 
     * @return void
     */
    protected function replacePlaceholdersInNode(DOMNode $node, array $data): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            foreach ($data as $key => $value) {
                $node->nodeValue = str_replace('{{' . $key . '}}', $value, $node->nodeValue);
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->replacePlaceholdersInNode($child, $data);
            }
        }
    }


    /**
     * Registers a set of styles in the styles.xml document.
     * This method checks if the style already exists in the styles document, and if not, it creates a new style element and appends it.
     * 
     * @param array $styleDefinitions An array of style definitions where the key is the style name and the value is an array containing 'family' and 'properties'
     * 
     * @return void
     */
    protected function registerStyles(array $styleDefinitions): void
    {
        foreach ($styleDefinitions as $name => $def) {
            $family = $def['family'];
            if (StyleWriter::styleAlreadyExists($this->domStyles, $name, $family)) {
                continue;
            }
            $family = $def['family'];
            $properties = $def['properties'];
            $style = $this->domStyles->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', $family);
            $style->setAttribute('style:parent-style-name', 'Standard');

            $propsElement = match ($family) {
                'text' => 'style:text-properties',
                'paragraph' => 'style:paragraph-properties',
                'table-cell' => 'style:table-cell-properties',
                'graphic' => 'style:graphic-properties',
                default => null,
            };

            if ($propsElement) {
                $props = $this->domStyles->createElement($propsElement);
                foreach ($properties as $key => $val) {
                    $props->setAttribute($key, $val);
                }
                $style->appendChild($props);
            }

            StyleWriter::appendStyleToStylesXml($this->domStyles, $style);
        }
    }

    /**
     * Searches for variables, loops, conditions and filter in the template
     *
     * @return array{
     *     variables: string[],
     *     loops: string[],
     *     conditions: string[],
     *     negated_conditions: string[],
     *     filters: string[],
     *     filter_options: array<string, string[]>
     * }
     */
    public function extractTemplateVariables(): array
    {
        $xmlContents = [
            $this->getContentXml(),
            $this->getStylesXml(),
            $this->getHeaderXml(),     // falls vorhanden
            $this->getFooterXml(),     // falls vorhanden
        ];

        $result = [
            'variables' => [],
            'loops' => [],
            'conditions' => [],
            'negated_conditions' => [],
            'filters' => [],
            'filter_options' => [],
        ];

        foreach ($xmlContents as $content) {
            if (empty($content)) {
                continue;
            }

            $parsed = $this->parseTemplateContent($content);

            foreach ($parsed as $key => $values) {
                if (is_array($values)) {
                    if ($key === 'filter_options') {
                        foreach ($values as $var => $opts) {
                            $result['filter_options'][$var] = array_unique(array_merge($result['filter_options'][$var] ?? [], $opts));
                        }
                    } else {
                        $result[$key] = array_unique(array_merge($result[$key], $values));
                    }
                }
            }
        }

        return $result;
    }

    
    /**
     * Summary of parseTemplateContent
     * @param string $content
     * @return array{conditions: array, filter_options: array, filters: array, loops: array, negated_conditions: array, variables: array}
     */
    protected function parseTemplateContent(string $content): array
    {
        $variables = [];
        $loops = [];
        $conditions = [];
        $negatedConditions = [];
        $filters = [];
        $filterOptions = [];

        // Match: {{filter:name|option}} oder {{name}}
        preg_match_all('/\{\{(?:(\w+):)?(\w+)(?:\|(\w+))?\}\}/', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $filters[] = $match[1];
            }

            $variables[] = $match[2];

            if (!empty($match[3])) {
                $filterOptions[$match[2]][] = $match[3];
            }
        }

        // Match: {{#foreach:items}}
        preg_match_all('/\{\{#foreach:(\w+)\}\}/', $content, $matches);
        $loops = $matches[1];

        // Match: {{#if:...}}, {{#elseif:...}}
        preg_match_all('/\{\{#(?:if|elseif):([^\}]+)\}\}/', $content, $matches);
        $conditions = $matches[1];

        // Match: {{#ifnot:...}}
        preg_match_all('/\{\{#ifnot:(\w+)\}\}/', $content, $matches);
        $negatedConditions = $matches[1];

        return [
            'variables' => array_unique($variables),
            'loops' => array_unique($loops),
            'conditions' => array_unique($conditions),
            'negated_conditions' => array_unique($negatedConditions),
            'filters' => array_unique($filters),
            'filter_options' => $filterOptions,
        ];
    }


}
