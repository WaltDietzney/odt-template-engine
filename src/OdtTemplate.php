<?php

namespace OdtTemplateEngine;

use ZipArchive;
use DOMDocument;
use DOMXPath;
use Exception;
use OdtTemplateEngine\Utils\StyleWriter;
use OdtTemplateEngine\Elements\RichText;


/**
 * Class for processing ODT text documents (.odt).
 *
 * Features include:
 * - Placeholder replacement (including optional filters)
 * - Repetitions using foreach loops
 * - Conditional logic (if, ifnot, elseif, else)
 * - Image placeholders
 * - Output as a valid LibreOffice-compatible ODT document
 */

class OdtTemplate extends \OdtTemplateEngine\AbstractOdtTemplate
{
    /**
     * Path to the original ODT template file.
     *
     * @var string
     */
    protected string $templatePath;

    /**
     * Temporary working directory for unpacking and editing the ODT content.
     *
     * @var string
     */
    protected string $tempDir;

    /**
     * Contents of content.xml as a DOMDocument.
     *
     * @var DOMDocument
     */
    protected DOMDocument $domContent;

    /**
     * Contents of styles.xml (e.g., for headers/footers) as a DOMDocument.
     *
     * @var DOMDocument
     */
    protected DOMDocument $domStyles;

    /**
     * All placeholder values to be replaced, set via setValues().
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * DOM representation of meta.xml (for document metadata).
     *
     * @var DOMDocument
     */
    protected DOMDocument $domMeta;

    /**
     * Constructor – prepares the temporary working directory and loads the ODT template.
     *
     * @param string $templatePath Path to the ODT template file.
     *
     * @throws \Exception If the file does not exist or the temporary directory cannot be created.
     */
    /**
     * Constructor – initializes the ODT template processor.
     *
     * - Checks whether the given template file exists.
     * - Creates a unique temporary working directory.
     * - Loads the ODT file contents into memory.
     * - Registers a shutdown function to clean up temporary files.
     *
     * @param string $templatePath Path to the ODT template file.
     *
     * @throws \Exception If the template file does not exist or the temporary directory cannot be created.
     */
    public function __construct(string $templatePath)
    {
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: $templatePath");
        }

        $tmpDir = sys_get_temp_dir() . '/odt_' . uniqid();
        if (!mkdir($tmpDir) && !is_dir($tmpDir)) {
            throw new Exception("Failed to create temporary directory.");
        }

        $this->tempDir = $tmpDir;
        $this->templatePath = $templatePath;
        $this->load();
        // Automatische Aufräumaktion beim Scriptende
        register_shutdown_function([$this, 'cleanup']);
    }

    /**
     * Loads and prepares the ODT template for processing.
     *
     * - Extracts the .odt file into the working directory.
     * - Loads content.xml, styles.xml, and meta.xml as DOMDocument instances.
     * - Normalizes placeholder structure to fix split nodes (via normalizeTemplateDom()).
     * - Ensures default paragraph and list styles are present.
     *
     * @return void
     *
     * @throws \Exception If the ODT file cannot be opened or extracted.
     */

    public function load(): void
    {
        $zip = new ZipArchive;
        if ($zip->open($this->templatePath) !== true) {
            throw new Exception("Could not open ODT file.");
        }

        $zip->extractTo($this->tempDir);
        $zip->close();

        $this->domContent = $this->loadXmlFile('content.xml');
        $this->domStyles = $this->loadXmlFile('styles.xml');
        $this->domMeta = $this->loadXmlFile('meta.xml');

        $this->normalizeTemplateDom($this->domContent);
        $this->normalizeTemplateDom($this->domStyles);

        $this->ensureDefaultParagraphStyles();
        $this->ensureDefaultListStyles();

    }


    /**
     * Loads an XML file from the temporary working directory as a DOMDocument.
     *
     * @param string $filename The name of the XML file (e.g. 'content.xml').
     * @return DOMDocument The loaded XML document.
     *
     * @throws \Exception If the file is missing or cannot be loaded.
     */
    // Note: LIBXML_NOENT and LIBXML_NOCDATA are used to expand entities and convert CDATA sections.
    // Ensure the source XML is trusted to avoid potential security issues.
    protected function loadXmlFile(string $filename): DOMDocument
    {
        $path = $this->tempDir . '/' . $filename;
        if (!file_exists($path)) {
            throw new Exception("Missing $filename in template.");
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->load($path, LIBXML_NOENT | LIBXML_NOCDATA);

        return $dom;
    }


    /**
     * Sets placeholder values for the template.
     *
     * Merges the given values into the internal value store and applies them to the document.
     * 
     * - Replaces placeholders (with optional filters) in content.xml and styles.xml
     * - Converts line breaks in values to <text:line-break/> tags (newline to break)
     * - Processes conditional logic blocks (if, ifnot, elseif, else)
     *
     * @param array<string, mixed> $values An associative array of placeholder names and their values.
     *
     * @return void
     */
    public function setValues(array $values): void
    {
        $this->values = array_merge($this->values, $values);

        $this->fixBrokenVariables($this->domContent);
        $this->fixBrokenVariables($this->domStyles);

        // Platzhalter & Filter
        $this->setValuesInDom($this->domContent, $this->values);
        $this->setValuesInDom($this->domStyles, $this->values);

        // Sonderfall: Zeilenumbrüche
        $this->replaceNl2brInDom($this->domContent, $this->values);
        $this->replaceNl2brInDom($this->domStyles, $this->values);

        // Logikverarbeitung
        $this->applyConditionalsInDom($this->domContent, $this->values);
        $this->applyConditionalsInDom($this->domStyles, $this->values);
    }


    /**
     * Replaces `{{nl2br:placeholder}}` tags with text content and <text:line-break/> elements.
     *
     * Searches all text nodes in the given XML DOM and replaces matching placeholders with the
     * corresponding value from the `$values` array, splitting the text into multiple lines
     * wherever a newline character is found.
     *
     * Example:
     *   '{{nl2br:comment}}' with value "Line 1\nLine 2"
     *   becomes: "Line 1", <text:line-break/>, "Line 2"
     *
     * @param DOMDocument $dom    The XML document to modify (content.xml or styles.xml).
     * @param array<string, string> $values Associative array of placeholder values.
     *
     * @return void
     */
    protected function replaceNl2brInDom(DOMDocument $dom, array $values): void
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//text()');

        foreach ($nodes as $textNode) {
            $text = $textNode->nodeValue;
            if (preg_match('/{{nl2br:(\w+)}}/', $text, $match)) {
                $key = $match[1];
                $original = $values[$key] ?? '';
                $parts = preg_split('/\r\n|\n|\r/', $original);

                $parent = $textNode->parentNode;
                foreach ($parts as $i => $part) {
                    if ($i > 0) {
                        $lineBreak = $dom->createElement('text:line-break');
                        $parent->appendChild($lineBreak);
                    }
                    $parent->appendChild($dom->createTextNode($part));
                }

                $parent->removeChild($textNode);
            }
        }
    }


    /**
     * Evaluates logical conditions like {{#if:...}}, {{#ifnot:...}}, {{#elseif:...}}, and {{#else}}.
     *
     * This method processes conditional logic in text paragraphs (`<text:p>`) within the given DOM document.
     * It identifies conditional blocks and evaluates them against the provided placeholder values,
     * keeping only the matching branch and removing the others from the DOM.
     *
     * Supported syntax:
     * - {{#if:placeholder}} ... {{#endif}}
     * - {{#ifnot:placeholder}} ... {{#endif}}
     * - {{#if:...}} ... {{#elseif:...}} ... {{#else}} ... {{#endif}}
     *
     * The expression after `if` or `elseif` can be any key in the `$values` array. If the value is truthy, the block is kept.
     * The `ifnot` block is kept only if the value is falsy or undefined.
     *
     * @param DOMDocument $dom    The XML document to modify (typically content.xml or styles.xml).
     * @param array<string, mixed> $values Associative array of placeholder values used for condition evaluation.
     *
     * @return void
     */

    protected function applyConditionalsInDom(DOMDocument $dom, array $values): void
    {
        $xpath = new DOMXPath($dom);
        $paragraphs = iterator_to_array($xpath->query('//text:p'));
        $i = 0;

        while ($i < count($paragraphs)) {
            $node = $paragraphs[$i];
            $text = trim($node->textContent);

            if (preg_match('/{{#(ifnot|if):(.+?)}}/', $text, $match)) {
                $type = $match[1]; // "if" oder "ifnot"
                $expr = trim($match[2]);

                $conditions = [
                    ['start' => $i, 'expr' => $expr, 'type' => $type]
                ];

                $else = null;
                $end = null;
                $j = $i + 1;

                while ($j < count($paragraphs)) {
                    $inner = trim($paragraphs[$j]->textContent);
                    if (preg_match('/{{#elseif:(.+?)}}/', $inner, $m)) {
                        $conditions[] = ['start' => $j, 'expr' => trim($m[1]), 'type' => 'if'];
                    } elseif ($inner === '{{#else}}') {
                        $else = $j;
                    } elseif ($inner === '{{#endif}}') {
                        $end = $j;
                        break;
                    }
                    $j++;
                }

                if ($end === null) {
                    $i++;
                    continue;
                }

                $keepStart = null;
                $keepEnd = null;

                for ($c = 0; $c < count($conditions); $c++) {
                    $cond = $conditions[$c];
                    $result = $this->evaluateCondition($cond['expr'], $values);
                    if ($cond['type'] === 'ifnot') {
                        $result = !$result;
                    }

                    if ($result) {
                        $keepStart = $cond['start'] + 1;
                        $keepEnd = isset($conditions[$c + 1])
                            ? $conditions[$c + 1]['start'] - 1
                            : ($else ?? $end) - 1;
                        break;
                    }
                }


                if ($keepStart === null && $else !== null) {
                    $keepStart = $else + 1;
                    $keepEnd = $end - 1;
                }

                for ($k = $end; $k >= $i; $k--) {
                    if ($k >= $keepStart && $k <= $keepEnd)
                        continue;
                    $n = $paragraphs[$k];
                    if ($n->parentNode) {
                        $n->parentNode->removeChild($n);
                    }
                }

                $paragraphs = iterator_to_array($xpath->query('//text:p'));
                $i = $i;
            } else {
                $i++;
            }
        }
    }


    /**
     * Evaluates a simple conditional expression based on placeholder values.
     *
     * Supports basic comparison expressions such as:
     * - `price > 100`
     * - `name == "Anna"`
     * - `count != 0`
     *
     * If no comparison operator is found, the expression is treated as a truthy check:
     * - `active` → true if $values['active'] is truthy
     *
     * Supported comparison operators:
     * - ==, !=, >, <, >=, <=
     *
     * Numeric values are automatically cast to float for comparison.
     * String values can be quoted using either single or double quotes.
     *
     * @param string $expr   The expression to evaluate (e.g. "price > 100").
     * @param array<string, mixed> $values Associative array of placeholder data.
     *
     * @return bool True if the condition evaluates to true; otherwise, false.
     */

    protected function evaluateCondition(string $expr, array $values): bool
    {
        if (preg_match('/^(\w+)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', $expr, $m)) {
            $var = $m[1];
            $op = $m[2];
            $val = trim($m[3], '"\'');

            $left = $values[$var] ?? null;
            if (is_numeric($left) && is_numeric($val)) {
                $left = (float) $left;
                $val = (float) $val;
            }

            return match ($op) {
                '==' => $left == $val,
                '!=' => $left != $val,
                '>' => $left > $val,
                '<' => $left < $val,
                '>=' => $left >= $val,
                '<=' => $left <= $val,
            };
        }

        // Wahrheitswert prüfen
        $val = $values[$expr] ?? false;
        return filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }


    /**
     * Applies repeating blocks in the template using {{#foreach:key}} ... {{#endforeach}} syntax.
     *
     * This method replaces the specified placeholder block with repeated content,
     * using the given data rows. Each row is treated as an associative array of placeholder values
     * and inserted between the foreach markers.
     *
     * The replacement is applied to both content.xml and styles.xml.
     *
     * Example usage:
     *   setRepeating('items', [
     *     ['name' => 'Apple', 'price' => '1.20'],
     *     ['name' => 'Banana', 'price' => '0.90'],
     *   ]);
     *
     * Template snippet:
     *   {{#foreach:items}}
     *     {{name}} – {{price}} €
     *   {{#endforeach}}
     *
     * @param string $key   The name of the repeating block (used after #foreach:).
     * @param array<int, array<string, mixed>> $rows An array of associative arrays representing each row's data.
     *
     * @return void
     */
    public function setRepeating(string $key, array $rows): void
    {
        $this->fixBrokenVariables($this->domContent);
        $this->fixBrokenVariables($this->domStyles);
        $this->applyRepeatingInDom($this->domContent, $key, $rows);
        $this->applyRepeatingInDom($this->domStyles, $key, $rows);
    }

    /**
     * Joins all repeating blocks.
     *
     * @param array<string, array<int, array<string, mixed>>> $data
     */
    public function setRepeatingData(array $data): void
    {
        $this->applyAllRepeatingBlocksInDom($this->domContent, $data);
        $this->applyAllRepeatingBlocksInDom($this->domStyles, $data);
    }


    protected function applyRepeatingInDom(DOMDocument $dom, string $key, array $rows): void
    {
        $xpath = new DOMXPath($dom);

        while (true) {
            // Suche nach einem Start- und End-Block für die Schleife
            $startNodeList = $xpath->query("//text:p[contains(text(), '{{#foreach:$key}}')]");
            if ($startNodeList->length === 0) {
                break; // Keine weiteren foreach-Blöcke vorhanden
            }

            $startNode = $startNodeList->item(0);

            // Suche den dazugehörigen End-Block
            $endNode = null;
            $current = $startNode->nextSibling;
            while ($current) {
                if ($current->nodeType === XML_ELEMENT_NODE && strpos($current->textContent, '{{#endforeach}}') !== false) {
                    $endNode = $current;
                    break;
                }
                $current = $current->nextSibling;
            }

            if (!$endNode) {
                // Fehler: Kein passendes #endforeach gefunden, Abbruch
                break;
            }

            $parent = $startNode->parentNode;
            $referenceNode = $endNode->nextSibling;

            // Sammle alle Knoten zwischen start und end
            $templateNodes = [];
            $current = $startNode->nextSibling;
            while ($current && $current !== $endNode) {
                $templateNodes[] = $current;
                $next = $current->nextSibling;
                $parent->removeChild($current);
                $current = $next;
            }

            // Entferne Start- und End-Marker
            $parent->removeChild($startNode);
            $parent->removeChild($endNode);

            // Jetzt für jede Zeile neue Knoten einfügen
            foreach ($rows as $rowData) {
                foreach ($templateNodes as $template) {
                    $clone = $template->cloneNode(true); // Deep Clone
                    $this->replacePlaceholdersInNode($clone, $rowData);
                    $parent->insertBefore($clone, $referenceNode); // An der richtigen Stelle einfügen
                }
            }
        }
    }


    protected function applyAllRepeatingBlocksInDom(DOMDocument $dom, array $repeatingData): void
    {
        $xpath = new DOMXPath($dom);

        foreach ($repeatingData as $key => $rows) {
            while (true) {
                $startNodeList = $xpath->query("//text:p[contains(text(), '{{#foreach:$key}}')]");
                if ($startNodeList->length === 0)
                    break;

                $startNode = $startNodeList->item(0);

                // Finde zugehöriges {{#endforeach}}
                $endNode = null;
                $current = $startNode->nextSibling;
                while ($current) {
                    if ($current->nodeType === XML_ELEMENT_NODE && strpos($current->textContent, '{{#endforeach}}') !== false) {
                        $endNode = $current;
                        break;
                    }
                    $current = $current->nextSibling;
                }

                if (!$endNode)
                    break; // Fehlerhafte Struktur

                $parent = $startNode->parentNode;
                $referenceNode = $endNode->nextSibling;

                // Inhalte zwischen Start- und Endknoten sammeln
                $templateNodes = [];
                $current = $startNode->nextSibling;
                while ($current && $current !== $endNode) {
                    $templateNodes[] = $current;
                    $next = $current->nextSibling;
                    $parent->removeChild($current);
                    $current = $next;
                }

                // Entferne Start/Ende
                $parent->removeChild($startNode);
                $parent->removeChild($endNode);

                // Füge neue Knoten ein
                foreach ($rows as $rowData) {
                    foreach ($templateNodes as $template) {
                        $clone = $template->cloneNode(true);
                        $this->replacePlaceholdersInNode($clone, $rowData);
                        $parent->insertBefore($clone, $referenceNode);
                    }
                }

                // XPath aktualisieren
                $xpath = new DOMXPath($dom);
            }
        }
    }

    /**
     * Sets metadata fields for the ODT document (e.g. title, author, description).
     *
     * Updates or creates metadata elements in `meta.xml` using standard ODF/DC/meta tags.
     * This includes common document information like title, author, subject, and creation date.
     *
     * Supported keys:
     * - 'title'            => dc:title
     * - 'subject'          => dc:subject
     * - 'description'      => dc:description
     * - 'keywords'         => meta:keyword
     * - 'initial_author'   => meta:initial-creator
     * - 'author'           => dc:creator
     * - 'language'         => dc:language
     * - 'creation_date'    => meta:creation-date
     * - 'date'             => dc:date
     * - 'editing_cycles'   => meta:editing-cycles
     * - 'editing_duration' => meta:editing-duration
     * - 'generator'        => meta:generator
     *
     * Missing XML nodes are automatically created under the <office:meta> element.
     *
     * @param array<string, string> $meta Associative array of metadata fields and values.
     *
     * @return void
     */
    public function setMeta(array $meta): void
    {
        $xpath = new DOMXPath($this->domMeta);
        $xpath->registerNamespace("office", "urn:oasis:names:tc:opendocument:xmlns:office:1.0");
        $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $xpath->registerNamespace("meta", "urn:oasis:names:tc:opendocument:xmlns:meta:1.0");

        $map = [
            'title' => ['dc:title'],
            'subject' => ['dc:subject'],
            'description' => ['dc:description'],
            'keywords' => ['meta:keyword'],
            'initial_author' => ['meta:initial-creator'],
            'author' => ['dc:creator'],
            'language' => ['dc:language'],
            'creation_date' => ['meta:creation-date'],
            'date' => ['dc:date'],
            'editing_cycles' => ['meta:editing-cycles'],
            'editing_duration' => ['meta:editing-duration'],
            'generator' => ['meta:generator'],
        ];


        foreach ($meta as $key => $value) {
            if (!isset($map[$key]))
                continue;

            foreach ($map[$key] as $xpathExpr) {
                $nodes = $xpath->query("//$xpathExpr");
                if ($nodes->length > 0) {
                    $nodes->item(0)->nodeValue = $value;
                } else {
                    // Füge Knoten hinzu, falls nicht vorhanden
                    $metaRoot = $xpath->query('//office:document-meta/office:meta')->item(0);
                    if ($metaRoot) {
                        [$prefix, $tag] = explode(':', $xpathExpr);
                        $newNode = $this->domMeta->createElement("$prefix:$tag", $value);
                        $metaRoot->appendChild($newNode);
                    }
                }
            }
        }
    }


    /**
     * Returns a list of known document metadata fields extracted from meta.xml.
     *
     * Scans the ODT document's meta.xml using standard ODF namespaces and collects values
     * for supported metadata fields such as title, author, and creation date.
     *
     * Supported keys:
     * - 'title'            => dc:title
     * - 'subject'          => dc:subject
     * - 'description'      => dc:description
     * - 'keywords'         => meta:keyword
     * - 'initial_author'   => meta:initial-creator
     * - 'author'           => dc:creator
     * - 'language'         => dc:language
     * - 'creation_date'    => meta:creation-date
     * - 'date'             => dc:date
     * - 'editing_cycles'   => meta:editing-cycles
     * - 'editing_duration' => meta:editing-duration
     * - 'generator'        => meta:generator
     *
     * @return array<string, string> Associative array of metadata fields and their current values.
     */
    public function getMeta(): array
    {
        $xpath = new DOMXPath($this->domMeta);
        $xpath->registerNamespace("office", "urn:oasis:names:tc:opendocument:xmlns:office:1.0");
        $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $xpath->registerNamespace("meta", "urn:oasis:names:tc:opendocument:xmlns:meta:1.0");

        $map = [
            'title' => 'dc:title',
            'subject' => 'dc:subject',
            'description' => 'dc:description',
            'keywords' => 'meta:keyword',
            'initial_author' => 'meta:initial-creator',
            'author' => 'dc:creator',
            'language' => 'dc:language',
            'creation_date' => 'meta:creation-date',
            'date' => 'dc:date',
            'editing_cycles' => 'meta:editing-cycles',
            'editing_duration' => 'meta:editing-duration',
            'generator' => 'meta:generator',
        ];

        $result = [];

        foreach ($map as $key => $xpathExpr) {
            $node = $xpath->query("//$xpathExpr")->item(0);
            if ($node) {
                $result[$key] = $node->textContent;
            }
        }

        return $result;
    }


    /**
     * Replaces an image placeholder like {{image}} with an actual image inside the ODT template.
     *
     * The method copies the provided image file into the template's `Pictures/` directory,
     * calculates the appropriate width and height (preserving aspect ratio if only one dimension is given),
     * and injects the image into the DOM where the placeholder is found.
     *
     * The image placeholder must be present as text content within the template (e.g., {{bild}}).
     *
     * Supported options:
     * - width  (string, e.g. "5cm")     — Target image width
     * - height (string, e.g. "3cm")     — Target image height
     * - anchor (string, default: "paragraph") — How the image is anchored (e.g., "paragraph", "as-char")
     * - wrap   (string, default: "none")      — Text wrap mode (e.g., "none", "run-through")
     *
     * If neither width nor height is provided, a default of 5cm x 3cm is used.
     * If only one dimension is set, the other is automatically calculated to preserve aspect ratio.
     *
     * @param string $key Placeholder name (e.g. 'bild' for {{bild}})
     * @param string $imagePath Absolute path to the image file
     * @param array<string, string> $options Image options: width, height, anchor, wrap
     * @throws Exception If the image file cannot be found or read
     */
    public function setImage(string $key, string $imagePath, array $options = []): void
    {
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: $imagePath");
        }

        $filename = basename($imagePath);
        $picturesDir = $this->tempDir . '/Pictures';
        if (!is_dir($picturesDir)) {
            mkdir($picturesDir);
        }

        $targetPath = $picturesDir . '/' . $filename;
        copy($imagePath, $targetPath);

        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $targetWidth = $options['width'] ?? null;
        $targetHeight = $options['height'] ?? null;

        if ($targetWidth && !$targetHeight) {
            $cm = (float) rtrim($targetWidth, 'cm');
            $ratio = $imgHeight / $imgWidth;
            $targetHeight = round($cm * $ratio, 3) . 'cm';
        } elseif (!$targetWidth && $targetHeight) {
            $cm = (float) rtrim($targetHeight, 'cm');
            $ratio = $imgWidth / $imgHeight;
            $targetWidth = round($cm * $ratio, 3) . 'cm';
        } elseif (!$targetWidth && !$targetHeight) {
            $targetWidth = '5cm';
            $targetHeight = '3cm';
        }

        $anchor = $options['anchor'] ?? 'paragraph';
        $wrap = $options['wrap'] ?? 'none';

        $this->replaceImageInDom($this->domContent, $key, $filename, $targetWidth, $targetHeight, $anchor, $wrap);
        $this->replaceImageInDom($this->domStyles, $key, $filename, $targetWidth, $targetHeight, $anchor, $wrap);
    }


    /**
     * Replaces an image placeholder (e.g. {{image}}) with a <draw:image> inside a <draw:frame>.
     *
     * This method locates the text paragraph containing the image placeholder and replaces
     * it with a properly structured OpenDocument image block, using the given dimensions,
     * anchoring, and text wrap configuration.
     *
     * Parameters:
     * - $dom: The DOMDocument object in which the replacement should occur.
     * - $key: The placeholder name (e.g. "image" for {{image}}).
     * - $filename: The name of the image file (should be located inside the "Pictures" folder).
     * - $width: The image width as a string with unit (e.g. "5cm").
     * - $height: The image height as a string with unit (e.g. "3cm").
     * - $anchor: The anchor type, typically "paragraph", "page", or "as-char".
     * - $wrap: Text wrapping mode around the image. Supported: "none", "left", "right", "parallel".
     *
     * Behavior:
     * - Wraps the image inside a <draw:frame> with proper sizing and positioning.
     * - Optionally adds a <style:wrap> element if wrap is "left", "right", or "parallel".
     * - Replaces the entire paragraph node containing the placeholder with the generated image block.
     *
     * Notes:
     * - This function assumes the image has already been copied to the "Pictures" folder.
     * - Namespace prefixes (e.g. draw:, text:, xlink:) must be valid for ODT.
     *
     * @param DOMDocument $dom The document (content or styles) where the replacement is applied
     * @param string $key Placeholder key (without brackets)
     * @param string $filename Image filename (e.g. "logo.png")
     * @param string $width Width in OpenDocument format (e.g. "5cm")
     * @param string $height Height in OpenDocument format (e.g. "3cm")
     * @param string $anchor Anchor type (e.g. "paragraph", "page", "as-char")
     * @param string $wrap Wrap mode ("none", "left", "right", "parallel")
     */
    protected function replaceImageInDom(
        DOMDocument $dom,
        string $key,
        string $filename,
        string $width,
        string $height,
        string $anchor,
        string $wrap
    ): void {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//text:p[contains(text(), '{{{$key}}}')]");

        foreach ($nodes as $node) {
            $parent = $node->parentNode;

            $draw = $dom->createElement('draw:frame');
            $draw->setAttribute('draw:name', $key);
            $draw->setAttribute('text:anchor-type', $anchor);
            $draw->setAttribute('svg:width', $width);
            $draw->setAttribute('svg:height', $height);
            $draw->setAttribute('draw:z-index', '0');

            if (in_array($wrap, ['left', 'right', 'parallel'])) {
                $wrapTag = $dom->createElement('style:wrap');
                $wrapTag->setAttribute('style:wrap', $wrap);
                $draw->appendChild($wrapTag);
            }

            $image = $dom->createElement('draw:image');
            $image->setAttribute('xlink:href', 'Pictures/' . $filename);
            $image->setAttribute('xlink:type', 'simple');
            $image->setAttribute('xlink:show', 'embed');
            $image->setAttribute('xlink:actuate', 'onLoad');
            $draw->appendChild($image);

            $framePara = $dom->createElement('text:p');
            $framePara->appendChild($draw);

            $parent->replaceChild($framePara, $node);
        }
    }


    /**
     * Replaces an existing image in the document by targeting a specific <draw:frame> using its draw:name attribute.
     *
     * This is useful when the image to be replaced is already embedded and referenced by name
     * (e.g., <draw:frame draw:name="logo">...</draw:frame>). The method copies the new image into the
     * "Pictures" directory and updates the <draw:image> reference within the targeted <draw:frame>.
     *
     * Parameters:
     * - $name: The name of the draw frame to target (value of the draw:name attribute).
     * - $imagePath: The path to the new image file to insert.
     * - $options: An optional array of sizing options:
     *     - 'width': Desired width (e.g. "6cm")
     *     - 'height': Desired height (e.g. "3cm")
     *
     * Behavior:
     * - Copies the image to the "Pictures" folder inside the ODT temp directory.
     * - Calculates missing width or height proportionally based on original image dimensions.
     * - Updates the xlink:href attribute of the targeted <draw:image> node.
     *
     * Throws:
     * - Exception if the specified image file does not exist.
     *
     * @param string $name The draw:name attribute of the target <draw:frame>
     * @param string $imagePath Path to the replacement image
     * @param array $options Optional dimensions: 'width' and/or 'height'
     * @throws Exception If the image file does not exist
     */
    public function replaceImageByName(string $name, string $imagePath, array $options = []): void
    {
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: $imagePath");
        }

        $filename = basename($imagePath);
        $picturesDir = $this->tempDir . '/Pictures';
        if (!is_dir($picturesDir)) {
            mkdir($picturesDir);
        }

        $targetPath = $picturesDir . '/' . $filename;
        copy($imagePath, $targetPath);

        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $width = $options['width'] ?? '5cm';
        $height = $options['height'] ?? '3cm';

        if ($width && !$options['height']) {
            $cm = (float) rtrim($width, 'cm');
            $height = round($cm * $imgHeight / $imgWidth, 3) . 'cm';
        } elseif (!$options['width'] && $height) {
            $cm = (float) rtrim($height, 'cm');
            $width = round($cm * $imgWidth / $imgHeight, 3) . 'cm';
        }

        $this->replaceImageInNamedDom($this->domContent, $name, $filename, $width, $height);
        $this->replaceImageInNamedDom($this->domStyles, $name, $filename, $width, $height);
    }


    /**
     * Replaces the image reference within a <draw:frame> identified by draw:name in the given DOM document.
     *
     * This method is called internally by replaceImageByName() and handles the actual update of:
     * - Image dimensions (svg:width, svg:height)
     * - Image path (xlink:href of the <draw:image> node inside the frame)
     *
     * Parameters:
     * - $dom: The DOMDocument representing either content.xml or styles.xml.
     * - $name: The value of the draw:name attribute used to locate the <draw:frame>.
     * - $filename: The name of the image file, relative to the Pictures/ folder in the ODT archive.
     * - $width: The new width to set on the frame (e.g., "6cm").
     * - $height: The new height to set on the frame (e.g., "4cm").
     *
     * Behavior:
     * - Locates all <draw:frame> elements with the specified draw:name.
     * - Updates the svg:width and svg:height attributes.
     * - Replaces the xlink:href of the nested <draw:image> with the new image path.
     *
     * Note:
     * - This method does not modify the actual image file or filesystem; it assumes
     *   the image has already been copied into the correct location.
     *
     * @param DOMDocument $dom The XML DOM to process (usually content.xml or styles.xml)
     * @param string $name The draw:name identifying the image frame to replace
     * @param string $filename The new image filename to link
     * @param string $width Desired width (e.g., "6cm")
     * @param string $height Desired height (e.g., "4cm")
     */
    protected function replaceImageInNamedDom(
        DOMDocument $dom,
        string $name,
        string $filename,
        string $width,
        string $height
    ): void {
        $xpath = new DOMXPath($dom);
        $frames = $xpath->query("//draw:frame[@draw:name='$name']");

        foreach ($frames as $frame) {
            $frame->setAttribute('svg:width', $width);
            $frame->setAttribute('svg:height', $height);

            foreach ($frame->childNodes as $child) {
                if ($child->nodeName === 'draw:image') {
                    $child->setAttribute('xlink:href', 'Pictures/' . $filename);
                }
            }
        }
    }


    /**
     * Applies a transformation filter to a placeholder value (e.g., {{upper:name}}).
     *
     * This method supports various formatting filters that can be applied inline within placeholders
     * in the ODT template. It is typically used during placeholder replacement to modify the output value.
     *
     * Supported filters:
     * - `upper`: Converts the string to uppercase.
     * - `lower`: Converts the string to lowercase.
     * - `trim`: Removes surrounding whitespace.
     * - `nl2br`: No-op here, handled separately via replaceNl2brInDom().
     * - `date`: Formats the string as a date using the provided format (default: 'd.m.Y').
     * - `number`: Formats the string as a number (e.g., "1.234,56").
     * - `currency`: Formats the number as currency with two decimal places and ' €'.
     * - `checkbox`: Outputs a checkmark ☑ if the value is truthy, otherwise ☐.
     *
     * Parameters:
     * - $filter: The name of the filter (e.g., "upper", "date").
     * - $value: The original string value to transform.
     * - $option: Optional parameter passed to the filter (e.g., date format or number precision).
     *
     * Returns:
     * - The transformed string after applying the filter.
     *
     * Example usage:
     * - `{{upper:name}}` turns "anna" into "ANNA"
     * - `{{date:created_at|Y-m-d}}` converts a datetime string to "2025-04-20"
     *
     * @param string $filter Name of the filter to apply (e.g., 'upper', 'date')
     * @param string $value The original placeholder value
     * @param string|null $option Optional parameter (e.g., date format or precision)
     * @return string The filtered/transformed value
     */
    protected function applyFilter(string $filter, string $value, ?string $option = null): string
    {
        return match ($filter) {
            'upper' => mb_strtoupper($value),
            'lower' => mb_strtolower($value),
            'trim' => trim($value),
            'nl2br' => $value, // von replaceNl2brInDom separat behandelt
            'date' => date($option ?: 'd.m.Y', strtotime($value)),
            'number' => number_format((float) str_replace(',', '.', $value), (int) ($option ?? 2), ',', '.'),
            default => $value,
            'checkbox' => ($value) ? '☑' : '☐',
            'currency' => number_format((float) str_replace(',', '.', $value), (int) 2, ',', '.') . ' €'
        };
    }


    /**
     * Normalizes broken placeholders within ODT paragraphs (e.g., fragmented across text:span elements).
     *
     * OpenDocument files (especially created by editors like LibreOffice or OpenOffice) may split
     * placeholders such as `{{name}}` into multiple text nodes or nested spans like:
     * `<text:span>{{na</text:span><text:span>me}}</text:span>`.
     *
     * This method iterates over all `<text:p>` elements in the given DOM and reconstructs the full
     * placeholder content into a single text node. This simplifies further placeholder processing.
     *
     * Parameters:
     * - $dom: The DOMDocument instance (typically content.xml or styles.xml)
     *
     * Effects:
     * - Merges all text nodes and span elements inside each `<text:p>` into a single text node
     * - Removes child nodes that previously split the placeholder
     * - Preserves the raw text content but discards inline formatting
     *
     * Example:
     * Before:
     *   <text:p><text:span>{{na</text:span><text:span>me}}</text:span></text:p>
     * After:
     *   <text:p>{{name}}</text:p>
     *
     * @param DOMDocument $dom The ODT XML DOM to normalize (usually content.xml or styles.xml)
     */
    protected function normalizeTemplateDom(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $paragraphs = $xpath->query('//text:p');

        foreach ($paragraphs as $p) {
            $merged = '';
            foreach ($p->childNodes as $node) {
                if ($node->nodeType === XML_TEXT_NODE || $node->nodeName === 'text:span') {
                    $merged .= $node->textContent;
                }
            }

            if ($merged !== '') {
                // alten Inhalt löschen und durch neuen Text ersetzen
                while ($p->firstChild) {
                    $p->removeChild($p->firstChild);
                }
                $p->appendChild($dom->createTextNode($merged));
            }
        }
    }


    /**
     * Saves the processed ODT template to a new .odt file.
     *
     * This method finalizes the document creation by:
     * - Writing styles via an external StyleWriter
     * - Minifying and saving the updated XML files (content.xml, styles.xml, meta.xml)
     * - Creating a ZIP archive following ODT structure
     *
     * The `mimetype` file is added first without compression, as required by the ODT specification.
     * All other files from the working directory are added recursively, excluding `mimetype` and
     * temporary template copies (e.g., `template.odt`).
     *
     * Parameters:
     * - $outputPath: Path to the final output file (e.g., "/path/to/document.odt")
     *
     * Throws:
     * - Exception if the mimetype file is missing or the output file cannot be created
     *
     * @param string $outputPath Absolute or relative path where the ODT will be saved
     * @throws Exception If the mimetype file is missing or the ZIP cannot be created
     */
    public function save(string $outputPath): void
    {
        // ✅ StyleWriter einbinden und Styles eintragen
        StyleWriter::writeAllStyles($this->domStyles);


        // 💾 Minifizierte XML-Dateien speichern
        $this->saveMinifiedXml($this->domContent, $this->tempDir . '/content.xml');
        $this->saveMinifiedXml($this->domStyles, $this->tempDir . '/styles.xml');
        $this->saveMinifiedXml($this->domMeta, $this->tempDir . '/meta.xml');

        // 📦 Archiv erzeugen
        $zip = new ZipArchive;
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Could not create output file: $outputPath");
        }

        // 📄 mimetype-Datei (uncompressed zuerst)
        $mimetypePath = $this->tempDir . '/mimetype';
        if (!file_exists($mimetypePath)) {
            throw new Exception("Missing mimetype file in template.");
        }

        $zip->addFromString('mimetype', file_get_contents($mimetypePath));
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

        // 📂 Restliche Dateien hinzufügen
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($rii as $file) {
            if ($file->isDir())
                continue;

            $filePath = $file->getPathname();
            $localPath = substr($filePath, strlen($this->tempDir) + 1);

            // ❌ Skip mimetype & evtl. temporäre template-Datei
            if (in_array($localPath, ['mimetype', 'template.odt']))
                continue;

            $zip->addFile($filePath, $localPath);
        }

        $zip->close();
    }


    /**
     * Saves a minified version of an XML DOMDocument to a file.
     *
     * This method removes unnecessary whitespace from the XML output to reduce file size:
     * - Removes tabs, line breaks, and multiple spaces
     * - Collapses spaces between tags
     *
     * This is useful for optimizing the final ODT file without affecting functionality.
     *
     * Parameters:
     * - $dom: The DOMDocument instance to save
     * - $path: Target file path where the XML should be written
     *
     * @param DOMDocument $dom The XML DOMDocument to be saved
     * @param string $path Destination file path for the minified XML
     */
    protected function saveMinifiedXml(DOMDocument $dom, string $path): void
    {
        $xml = $dom->saveXML();
        $xml = preg_replace('/>\s+</', '><', $xml);
        $xml = preg_replace('/[\r\n\t]+/', '', $xml);
        $xml = preg_replace('/ {2,}/', ' ', $xml);
        file_put_contents($path, $xml);
    }


    /**
     * Removes the temporary working directory and all its contents.
     * 
     * This method is also called automatically at the end of the script to ensure that temporary files
     * and directories are properly cleaned up after processing.
     *
     * It recursively deletes all files and subdirectories inside the temporary directory, 
     * and then removes the directory itself.
     * 
     * @throws Exception If there are any issues during the cleanup process (e.g. permission errors)
     */
    public function cleanup(): void
    {
        if (!is_dir($this->tempDir))
            return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }

        rmdir($this->tempDir);
    }



}
