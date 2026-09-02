<?php

namespace OdtTemplateEngine;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use RuntimeException;
use OdtTemplateEngine\Document\AmbiguousTemplateTargetException;
use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Document\BookmarkTarget;
use OdtTemplateEngine\Document\FrameTarget;
use OdtTemplateEngine\Document\MetadataManager;
use OdtTemplateEngine\Document\SectionTarget;
use OdtTemplateEngine\Document\StructuredElementMaterializer;
use OdtTemplateEngine\Document\StructuredResourceCollector;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Document\TableTarget;
use OdtTemplateEngine\Document\TemplateTargetResolver;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Template\TemplateProcessor;
use OdtTemplateEngine\Template\TemplateStructureInspection;
use OdtTemplateEngine\Template\TemplateStructureInspector;
use OdtTemplateEngine\Template\TemplateStructureNormalizer;
use OdtTemplateEngine\Utils\StyleWriter;
use OdtTemplateEngine\Utils\StyleMapper;


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

class OdtTemplate
{
    private OdtPackage $package;

    /**
     * All placeholder values to be replaced, set via setValues().
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Summary of valueStack
     * @var array
     */
    protected array $valueStack = [];    // Normal values (like name, address, etc.)

    /**
     * Summary of repeatStack
     * @var array
     */
    protected array $repeatStack = [];   // Repeating structures (foreach data)

    /**
     * Whether the legacy assign/render path materialized a structured element.
     * This enables its explicit compatibility finalization without affecting
     * the document-owned setElement() path.
     */
    private bool $legacyStructuredValuesMaterialized = false;

    /** @var list<string> */
    private array $log = [];

    private bool $debugMode = false;




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
        $this->package = new OdtPackage($templatePath);
        $this->prepareLoadedTemplate();

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
        $this->package->resetFromTemplate();
        $this->legacyStructuredValuesMaterialized = false;
        $this->prepareLoadedTemplate();
    }

    /**
     * Access the document context owned by the package.
     */
    protected function documentContext(): OdtDocumentContext
    {
        return $this->package->context();
    }

    /**
     * Inspect native named structures in the current document state.
     *
     * Each call creates a read-only snapshot. No DOM or package state is
     * changed, and the result does not expose internal DOM nodes.
     */
    public function inspect(): DocumentInspection
    {
        $context = $this->documentContext();

        return (new DocumentInspector())->inspect($context->contentDom(), $context->stylesDom());
    }

    /** Inspect original template structure without exposing or mutating DOM nodes. */
    public function inspectTemplateStructure(): TemplateStructureInspection
    {
        return (new TemplateStructureInspector())->inspect($this->package->sourceDom('content.xml'));
    }

    /**
     * Resolve a named bookmark or range in the current document state.
     */
    public function bookmark(string $name): BookmarkTarget
    {
        return (new TypedTargetResolver())->resolveBookmark($this->documentContext(), $name);
    }

    /**
     * Resolve a named native section in the current document state.
     */
    public function section(string $name): SectionTarget
    {
        return (new TypedTargetResolver())->resolveSection($this->documentContext(), $name, $this->package);
    }

    /**
     * Resolve a named native table in the current document state.
     */
    public function table(string $name): TableTarget
    {
        return (new TypedTargetResolver())->resolveTable($this->documentContext(), $name);
    }

    /**
     * Resolve a named native drawing frame in the current document state.
     */
    public function frame(string $name): FrameTarget
    {
        return (new TypedTargetResolver())->resolveFrame($this->documentContext(), $name);
    }

    /**
     * Prepare a constructed element's image resource without resolving a
     * named template target.
     */
    protected function copyImageResource(string $imagePath): void
    {
        $this->package->copyImageResource($imagePath);
    }

    private function prepareLoadedTemplate(): void
    {
        $context = $this->documentContext();
        (new TemplateStructureNormalizer())->normalize($context->contentDom());
        (new TemplateStructureNormalizer())->normalize($context->stylesDom());
        $this->ensureDefaultParagraphStyles();
        $this->ensureDefaultListStyles();
        $this->ensureDefaultListStylesForContentXml($context->contentDom());
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
        $path = $this->package->path($filename);
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
    /**
     * Assigns values to be replaced later in the template.
     *
     * @param array<string, mixed> $values
     * @return void
     */
    public function setValues(array $values): void
    {
        $this->valueStack = array_merge($this->valueStack, $values);
    }

    /**
     * Insert constructed structured content through the public template facade.
     *
     * Style and resource preparation remain delegated to the existing
     * compatibility/document collaborators. The materializer owns the ODF
     * subtree replacement rules, while protected callbacks continue to
     * dispatch through the facade.
     */
    public function setElement(string $placeholder, OdtElement $element): void
    {
        $collector = new StyleRequirementCollector();
        foreach ($collector->collectSemantic($element) as $requirement) {
            $this->documentContext()->styleContext()->registerRequirement($requirement);
        }

        foreach ($collector->collect($element) as $requirement) {
            if ($requirement['family'] === 'paragraph') {
                $this->documentContext()->styleContext()->registerParagraphStyle(
                    $requirement['name'],
                    $requirement['definition']
                );
                $this->ensureParagraphStylesExist([
                    $requirement['name'] => $requirement['definition'],
                ]);
            } elseif ($requirement['family'] === 'text') {
                $this->documentContext()->styleContext()->registerTextStyle(
                    $requirement['name'],
                    $requirement['definition']
                );
                $this->ensureTextStylesExist([
                    $requirement['name'] => $requirement['definition'],
                ]);
            }
        }
        if ($element instanceof HasStyles) {
            $this->registerStyles($element->getStyleDefinitions());
        }

        $resources = iterator_to_array((new StructuredResourceCollector())->collect($element), false);
        if ($resources !== []) {
            $this->package->copyImageResourcesAtomically($resources);
        }

        $materializer = new StructuredElementMaterializer();
        $materializer->insert(
            $this->documentContext()->contentDom(),
            $this->documentContext()->stylesDom(),
            $placeholder,
            $element,
            function (DOMDocument $dom) use ($placeholder): void {
                $this->normalizeStructuredPlaceholder($dom, $placeholder);
            },
            function (DOMDocument $dom, string $key, DOMNode $replacement): void {
                $this->replacePlaceholderWithDom($dom, $key, $replacement);
            },
            fn (DOMDocument $dom, string $key): bool => $this->hasPlaceholder($dom, $key)
        );

        foreach ($collector->collect($element) as $requirement) {
            $styleContext = $this->documentContext()->styleContext();
            switch ($requirement['family']) {
                case 'paragraph':
                    $styleContext->registerParagraphStyle($requirement['name'], $requirement['definition']);
                    break;
                case 'text':
                    $styleContext->registerTextStyle($requirement['name'], $requirement['definition']);
                    break;
                case 'frame':
                    $styleContext->registerFrameStyle($requirement['name'], $requirement['definition']);
                    break;
                case 'image':
                    $styleContext->registerImageStyle($requirement['name'], $requirement['definition']);
                    break;
                case 'fill-image':
                    $styleContext->registerFillImage($requirement['name'], $requirement['definition']);
                    break;
            }
        }
    }

    /**
     * Apply assigned scalar values and structured elements to one document DOM.
     *
     * Scalar replacement remains delegated to TemplateProcessor. Structured
     * values are routed through the facade callback so materialization and
     * protected compatibility dispatch remain separate concerns.
     */
    protected function setValuesInDom(DOMDocument $dom, array $values): void
    {
        $processor = new TemplateProcessor();
        $scalarValues = [];
        foreach ($values as $key => $value) {
            if ($value instanceof OdtElement) {
                $this->legacyStructuredValuesMaterialized = true;
                $replacement = $value->toDomNode($dom);
                $this->registerLegacyGraphicRequirements($value);
                $this->replacePlaceholderWithDom($dom, (string) $key, $replacement);
            } else {
                $scalarValues[(string) $key] = $value;
            }
        }
        $processor->replaceScalarTextInSubtree(
            $dom,
            $scalarValues,
            fn (string $filter, mixed $value, ?string $option): string => $this->applyFilter($filter, $value, $option)
        );
    }

    /**
     * Repair placeholders split across ODF text nodes.
     */
    protected function fixBrokenVariables(DOMNode $node): void
    {
        (new TemplateProcessor())->fixBrokenVariables($node);
    }

    /** Join only the requested structured placeholder for legacy materialization. */
    private function normalizeStructuredPlaceholder(DOMDocument $dom, string $key): void
    {
        $token = '{{' . $key . '}}';
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//text:p | //text:h') ?: [] as $scope) {
            if (!$scope instanceof DOMElement) continue;
            $run = [];
            $text = '';
            foreach ([...iterator_to_array($scope->childNodes), null] as $node) {
                $isText = $node instanceof DOMNode
                    && ($node->nodeType === XML_TEXT_NODE || ($node instanceof DOMElement && $node->nodeName === 'text:span'));
                if ($isText) {
                    $run[] = $node;
                    $text .= $node->textContent;
                    continue;
                }
                if ($run !== [] && $text === $token) {
                    $first = $run[0];
                    $scope->insertBefore($dom->createTextNode($text), $first);
                    foreach ($run as $remove) $scope->removeChild($remove);
                }
                $run = [];
                $text = '';
            }
        }
    }

    /**
     * Route structured placeholder replacement through the materializer.
     */
    protected function replacePlaceholderWithDom(
        DOMDocument $dom,
        string $key,
        DOMNode $replacement
    ): void {
        (new StructuredElementMaterializer())->replacePlaceholder($dom, $key, $replacement);
    }

    /**
     * Check whether a structured placeholder remains in a document DOM.
     */
    protected function hasPlaceholder(DOMDocument $dom, string $key): bool
    {
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//text()') as $textNode) {
            if (strpos($textNode->nodeValue, '{{' . $key . '}}') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace placeholders recursively in a cloned foreach row subtree.
     */
    protected function replacePlaceholdersInNode(DOMNode $node, array $data): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $replaced = $this->replaceInText($node->nodeValue, $data);
            if ($replaced !== $node->nodeValue) {
                $node->nodeValue = $replaced;
            }
        }

        if ($node->hasChildNodes()) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                $this->replacePlaceholdersInNode($child, $data);
            }
        }
    }

    /**
     * Apply legacy row-local placeholder substitution semantics.
     */
    protected function replaceInText(string $text, array $data): string
    {
        return preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($data) {
            $key = trim($matches[1]);

            if (!array_key_exists($key, $data)) {
                return '';
            }

            return (string) $data[$key];
        }, $text);
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
        (new TemplateProcessor())->replaceNl2brInDom($dom, $values);
    }

    /**
     * Replaces placeholders like {{ul:fieldname}} or {{ol:fieldname}} in an ODT DOMDocument
     * with properly formatted bullet or numbered lists. The method scans text nodes in the document
     * and replaces the entire <text:p> node containing the placeholder with a <text:list> structure
     * containing the corresponding list items.
     *
     * Supported placeholders:
     *   - {{ul:fieldname}} for unordered (bulleted) lists
     *   - {{ol:fieldname}} for ordered (numbered) lists
     *
     * The values must be provided in the $values array under the specified 'fieldname' key
     * and should be separated by newlines (\n or \r\n).
     *
     * @param DOMDocument $dom The ODT document as a DOM structure
     * @param array<string, string> $values Associative array mapping field names to the replacement text
     */
    protected function replaceListsInDom(DOMDocument $dom, array $values): void
    {
        (new TemplateProcessor())->replaceListsInDom($dom, $values);
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
        (new TemplateProcessor())->applyConditionalsInDom(
            $dom,
            $values,
            fn (string $expression, array $conditionValues): bool =>
                $this->evaluateCondition($expression, $conditionValues)
        );
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
        return (new TemplateProcessor())->evaluateCondition($expr, $values);
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
    /**
     * Old-style direct assignment for repeating data.
     *
     * @deprecated Use assignRepeating() and render() instead.
     */
    /**
     * Assigns a repeating block (e.g., a table) to be processed later.
     *
     * @param string $key
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function setRepeating(string $key, array $rows): void
    {
        $this->repeatStack[$key] = $rows;
    }


    /**
     * Joins all repeating blocks.
     *
     * @param array<string, array<int, array<string, mixed>>> $data
     */
    public function setRepeatingData(array $data): void
    {
        $context = $this->documentContext();
        $this->fixBrokenVariables($context->contentDom());
        $this->fixBrokenVariables($context->stylesDom());
        $this->applyAllRepeatingBlocksInDom($context->contentDom(), $data);
        $this->applyAllRepeatingBlocksInDom($context->stylesDom(), $data);
    }


    protected function applyRepeatingInDom(DOMDocument $dom, string $key, array $rows): void
    {
        (new TemplateProcessor())->applyRepeatingInDom(
            $dom,
            $key,
            $rows,
            function (DOMNode $node, array $rowData): void {
                $this->replacePlaceholdersInNode($node, $rowData);
            }
        );
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
        (new MetadataManager($this->documentContext()))->set($meta);
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
        return (new MetadataManager($this->documentContext()))->get();
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
        $picturesDir = $this->package->path('Pictures');
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

        $context = $this->documentContext();
        $this->replaceImageInDom($context->contentDom(), $key, $filename, $targetWidth, $targetHeight, $anchor, $wrap);
        $this->replaceImageInDom($context->stylesDom(), $key, $filename, $targetWidth, $targetHeight, $anchor, $wrap);
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
     * - Uses the legacy default dimensions of 5cm x 3cm unless explicit options
     *   replace them. A single explicit dimension does not trigger proportional
     *   recalculation because the other dimension already has a default.
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
        $picturesDir = $this->package->path('Pictures');
        if (!is_dir($picturesDir)) {
            mkdir($picturesDir);
        }

        $targetPath = $picturesDir . '/' . $filename;
        copy($imagePath, $targetPath);

        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $width = $options['width'] ?? '5cm';
        $height = $options['height'] ?? '3cm';

        if ($width && !$height) {
            $cm = (float) rtrim($width, 'cm');
            $height = round($cm * $imgHeight / $imgWidth, 3) . 'cm';
        } elseif (!$width && $height) {
            $cm = (float) rtrim($height, 'cm');
            $width = round($cm * $imgWidth / $imgHeight, 3) . 'cm';
        }

        $context = $this->documentContext();
        $this->replaceImageInNamedDom($context->contentDom(), $name, $filename, $width, $height);
        $this->replaceImageInNamedDom($context->stylesDom(), $name, $filename, $width, $height);
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
        $resolver = new TemplateTargetResolver();

        try {
            $target = $resolver->resolveFrame($dom, $name);
        } catch (AmbiguousTemplateTargetException) {
            // Preserve the legacy public behavior: every matching frame was
            // updated when duplicate names existed in one document.
            $xpath = new DOMXPath($dom);
            $frames = $xpath->query("//draw:frame[@draw:name='$name']");

            foreach ($frames as $frame) {
                $this->replaceImageInFrame($frame, $filename, $width, $height);
            }

            return;
        }

        if ($target === null) {
            return;
        }

        $this->replaceImageInFrame($target->node(), $filename, $width, $height);
    }

    private function replaceImageInFrame(
        DOMElement $frame,
        string $filename,
        string $width,
        string $height
    ): void {
        $frame->setAttribute('svg:width', $width);
        $frame->setAttribute('svg:height', $height);

        foreach ($frame->childNodes as $child) {
            if ($child->nodeName === 'draw:image') {
                $child->setAttribute('xlink:href', 'Pictures/' . $filename);
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
        return (new TemplateProcessor())->applyFilter($filter, $value, $option);
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
        (new TemplateProcessor())->normalizeTemplateDom($dom);
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
        $this->injectImageStyles();
        $this->injectDocumentGraphicStyles();
        StyleWriter::writeAllStyles(
            $this->documentContext()->stylesDom(),
            false,
            false,
            $this->legacyStructuredValuesMaterialized
        );
        $this->adjustBulletIndentation();
        $this->package->saveAs($outputPath);
    }

    public function refresh()
    {
        $this->injectDocumentGraphicStyles();
        StyleWriter::writeAllStyles($this->documentContext()->stylesDom(), false, false, false);
        $this->package->persistCoreDocuments();
        $this->load();
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
     * Fügt fehlende Einträge für Bilder im Pictures/-Verzeichnis zum Manifest hinzu.
     * ODF erfordert, dass jede Datei im Archiv im META-INF/manifest.xml deklariert ist.
     */
    protected function addImagesToManifest(): void
    {
        $this->package->synchronizeImageManifest();
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
        $this->package->cleanup();
    }


    /**
     * Applies all assigned values, repeaters, and conditional logic into the DOM structure.
     *
     * - Repairs broken placeholders.
     * - Applies nl2br (newline-to-linebreak) transformations.
     * - Replaces normal {{key}} placeholders.
     * - Executes repeating blocks ({{#foreach}} ... {{#endforeach}}).
     * - Evaluates conditional blocks ({{#if}}, {{#ifnot}}, {{#else}}, {{#endif}}).
     * - Ensures full text and format preservation (tabs, styling, spans, etc.).
     *
     * @return void
     */
    public function render(): void
    {
        $context = $this->documentContext();
        $contentDom = $context->contentDom();
        $stylesDom = $context->stylesDom();

        foreach ($this->valueStack as $key => $value) {
            if ($value instanceof OdtElement) {
                $this->normalizeStructuredPlaceholder($contentDom, (string) $key);
                $this->normalizeStructuredPlaceholder($stylesDom, (string) $key);
            }
        }

        $this->fixBrokenVariables($contentDom);
        $this->fixBrokenVariables($stylesDom);

        // Sonderbehandlungen zuerst
        $this->replaceNl2brInDom($contentDom, $this->valueStack);
        $this->replaceNl2brInDom($stylesDom, $this->valueStack);

        $this->replaceListsInDom($contentDom, $this->valueStack);
        $this->replaceListsInDom($stylesDom, $this->valueStack);

        // Normale Werte ersetzen
        $this->setValuesInDom($contentDom, $this->valueStack);
        $this->setValuesInDom($stylesDom, $this->valueStack);

        // Textboxen separat behandeln
        $this->renderTextBoxes($contentDom, $this->valueStack);
        $this->renderTextBoxes($stylesDom, $this->valueStack);

        foreach ($this->repeatStack as $key => $rows) {
            $this->applyRepeatingInDom($contentDom, $key, $rows);
            $this->applyRepeatingInDom($stylesDom, $key, $rows);
        }

        $this->applyConditionalsInDom($contentDom, $this->valueStack);
        $this->applyConditionalsInDom($stylesDom, $this->valueStack);
    }


    protected function renderTextBoxes(DOMDocument $dom, array $valueStack): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        $textBoxes = $xpath->query('//draw:text-box//text:p');

        foreach ($textBoxes as $pNode) {
            foreach ($valueStack as $key => $value) {
                if (strpos($pNode->textContent, '{{' . $key . '}}') !== false) {
                    $pNode->nodeValue = str_replace('{{' . $key . '}}', $value, $pNode->nodeValue);
                }
            }
        }
    }




    /**
     * Assigns one or more values to the internal value stack.
     *
     * @param array<string, mixed> $values
     * @return void
     */
    public function assign(array $values): void
    {
        $this->valueStack = array_merge($this->valueStack, $values);
    }

    /**
     * Assigns repeating data for foreach templates.
     *
     * @param string $key
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function assignRepeating(string $key, array $rows): void
    {
        $this->repeatStack[$key] = $rows;
    }

    /**
     * Processes conditionals (if/ifnot/else/endif) in the template based on text nodes.
     *
     * @param DOMDocument $dom The XML DOM to modify
     * @param array<string, mixed> $values Key-value pairs used for evaluating conditions
     */
    protected function applyConditionalsInDomTextBased(DOMDocument $dom, array $values): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        // Alle Text-Nodes durchlaufen
        $textNodes = iterator_to_array($xpath->query('//text()'));
        $insideCondition = false;
        $keepBlock = false;

        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;

            if (preg_match('/{{#(if|ifnot):([^}]+)}}/', $text, $match)) {
                $insideCondition = true;
                $conditionType = $match[1];
                $conditionKey = trim($match[2]);
                $keepBlock = $this->evaluateCondition($conditionKey, $values);
                if ($conditionType === 'ifnot') {
                    $keepBlock = !$keepBlock;
                }
                // Marker löschen
                $textNode->nodeValue = str_replace($match[0], '', $text);
                continue;
            }

            if (strpos($text, '{{#else}}') !== false) {
                $keepBlock = !$keepBlock;
                $textNode->nodeValue = str_replace('{{#else}}', '', $text);
                continue;
            }

            if (strpos($text, '{{#endif}}') !== false) {
                $insideCondition = false;
                $keepBlock = true;
                $textNode->nodeValue = str_replace('{{#endif}}', '', $text);
                continue;
            }

            if ($insideCondition && !$keepBlock) {
                $textNode->parentNode->removeChild($textNode);
            }
        }
    }

    /**
     * Processes foreach loops in the template based on text nodes.
     *
     * @param DOMDocument $dom The XML DOM to modify
     * @param string $key The key for the foreach block
     * @param array<int, array<string, mixed>> $rows The data rows to repeat
     */
    protected function applyRepeatingInDomTextBased(DOMDocument $dom, string $key, array $rows): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        $textNodes = iterator_to_array($xpath->query('//text()'));
        $collecting = false;
        $templateNodes = [];
        $parent = null;
        $afterNode = null;

        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;

            if (strpos($text, '{{#foreach:' . $key . '}}') !== false) {
                $collecting = true;
                $parent = $textNode->parentNode;
                $afterNode = $textNode->nextSibling;
                $textNode->parentNode->removeChild($textNode);
                continue;
            }

            if (strpos($text, '{{#endforeach}}') !== false) {
                $collecting = false;
                $textNode->parentNode->removeChild($textNode);

                foreach ($rows as $rowData) {
                    foreach ($templateNodes as $templateNode) {
                        $clone = $templateNode->cloneNode(true);
                        $this->replacePlaceholdersInNode($clone, $rowData);
                        $parent->insertBefore($clone, $afterNode);
                    }
                }

                $templateNodes = [];
                continue;
            }

            if ($collecting) {
                $templateNodes[] = $textNode;
            }
        }
    }
    protected function splitConditionalsInTextNodes(DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//text()') as $textNode) {
            $text = $textNode->nodeValue;

            if (preg_match_all('/({{#(if|ifnot|elseif|else|endif):?.*?}})/', $text, $matches, PREG_OFFSET_CAPTURE)) {
                $parent = $textNode->parentNode;
                $ref = $textNode;

                $parts = [];
                $lastPos = 0;

                foreach ($matches[0] as [$matchText, $pos]) {
                    if ($pos > $lastPos) {
                        $parts[] = substr($text, $lastPos, $pos - $lastPos);
                    }
                    $parts[] = $matchText;
                    $lastPos = $pos + strlen($matchText);
                }
                if ($lastPos < strlen($text)) {
                    $parts[] = substr($text, $lastPos);
                }

                foreach ($parts as $part) {
                    if (trim($part) !== '') {
                        $newTextNode = $dom->createTextNode($part);
                        $parent->insertBefore($newTextNode, $ref);
                    }
                }

                $parent->removeChild($ref);
            }
        }
    }

    /**
     * Register the namespaces used by the ODF style helpers.
     */
    protected function prepareNamespaces(DOMXPath $xpath): void
    {
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $xpath->registerNamespace('fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');
    }

    /**
     * Ensure namespaces required by generated style attributes exist.
     */
    protected function ensureXmlnsAttributes(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $root = $stylesDom->documentElement;

        if (!$root->hasAttribute('xmlns:fo')) {
            $root->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:fo',
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0'
            );
        }
        if (!$root->hasAttribute('xmlns:style')) {
            $root->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:style',
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
            );
        }
    }

    /**
     * Write registered image styles into the authoritative styles DOM.
     */
    /**
     * Retained as a protected compatibility hook; active save finalization is
     * document-owned and is performed by injectDocumentGraphicStyles().
     */
    protected function injectImageStyles(): void
    {
        if ($this->legacyStructuredValuesMaterialized) {
            $this->injectLegacyImageStyles();
        }
    }

    /** Register requirements materialized through the explicit legacy path. */
    private function registerLegacyGraphicRequirements(OdtElement $element): void
    {
        if (method_exists($element, 'getFrameStyleRequirements')) {
            foreach ($element->getFrameStyleRequirements() as $name => $definition) {
                StyleMapper::$frameStyles[$name] = $definition;
            }
        }

        if (method_exists($element, 'getImageStyleRequirements')) {
            foreach ($element->getImageStyleRequirements() as $name => $definition) {
                StyleMapper::registerImageStyle($name, $definition);
            }
        }

        if (method_exists($element, 'getFillImageRequirements')) {
            foreach ($element->getFillImageRequirements() as $name => $definition) {
                $path = $definition['path'] ?? null;
                if (is_string($path)) {
                    StyleMapper::registerFillImage($name, $path);
                }
            }
        }
    }

    /** @deprecated Legacy implementation retained for source reference only. */
    protected function injectLegacyImageStyles(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $officeStyles = $xpath->query('//office:styles')->item(0);
        foreach (StyleMapper::getRegisteredFillImages() as $name => $image) {
            if (!$officeStyles || $xpath->query("//draw:fill-image[@draw:name='$name']")->length > 0) {
                continue;
            }
            $fillImage = $stylesDom->createElement('draw:fill-image');
            $fillImage->setAttribute('draw:name', $image['name']);
            $fillImage->setAttribute('xlink:href', 'Pictures/' . $image['filename']);
            $fillImage->setAttribute('xlink:type', 'simple');
            $fillImage->setAttribute('xlink:show', 'embed');
            $fillImage->setAttribute('xlink:actuate', 'onLoad');
            $officeStyles->insertBefore($fillImage, $officeStyles->firstChild);
        }

        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);
        foreach (StyleMapper::getRegisteredImageStyles() as $styleName => $options) {
            if (!$automaticStyles) {
                continue;
            }
            $existing = $xpath->query("//style:style[@style:name='$styleName']")->item(0);
            if ($existing) {
                $props = $existing->getElementsByTagName('style:graphic-properties')->item(0);
                if ($props instanceof DOMElement && !$props->hasAttributes()) {
                    $this->applyImageStyleProps($props, $options);
                }
                continue;
            }
            $style = $stylesDom->createElement('style:style');
            $style->setAttribute('style:name', $styleName);
            $style->setAttribute('style:family', 'graphic');
            $style->setAttribute('style:parent-style-name', 'Standard');
            $props = $stylesDom->createElement('style:graphic-properties');
            $this->applyImageStyleProps($props, $options);
            $style->appendChild($props);
            $automaticStyles->appendChild($style);
        }
    }

    /** Adopt only the element's own graphic/image requirements after materialization. */
    private function adoptTopLevelGraphicRequirements(OdtElement $element): void
    {
        $styleContext = $this->documentContext()->styleContext();

        if (method_exists($element, 'getFrameStyleRequirements')) {
            foreach ($element->getFrameStyleRequirements() as $name => $definition) {
                $styleContext->registerFrameStyle($name, $definition);
            }
        }

        if (method_exists($element, 'getImageStyleRequirements')) {
            foreach ($element->getImageStyleRequirements() as $name => $definition) {
                $styleContext->registerImageStyle($name, $definition);
            }
        }

        if (method_exists($element, 'getFillImageRequirements')) {
            foreach ($element->getFillImageRequirements() as $name => $definition) {
                $styleContext->registerFillImage($name, $definition);
            }
        }
    }

    /** Write document-owned graphic requirements using the existing ODF placement. */
    private function injectDocumentGraphicStyles(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        $officeStyles = $xpath->query('//office:styles')->item(0);
        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);
        $styleContext = $this->documentContext()->styleContext();

        if ($officeStyles) {
            foreach ($styleContext->frameStyles() as $name => $properties) {
                $this->appendGraphicStyleIfMissing($stylesDom, $xpath, $officeStyles, $name, 'Frame', $properties);
            }
        }

        if ($automaticStyles) {
            foreach ($styleContext->imageStyles() as $name => $properties) {
                $this->appendGraphicStyleIfMissing($stylesDom, $xpath, $automaticStyles, $name, 'Standard', $properties);
            }
        }

        if ($officeStyles) {
            foreach ($styleContext->fillImages() as $name => $definition) {
                if ($xpath->query("//draw:fill-image[@draw:name='$name']")->length > 0) {
                    continue;
                }
                $fillImage = $stylesDom->createElement('draw:fill-image');
                $fillImage->setAttribute('draw:name', $definition['name'] ?? $name);
                $fillImage->setAttribute('xlink:href', 'Pictures/' . ($definition['filename'] ?? basename((string) ($definition['path'] ?? ''))));
                $fillImage->setAttribute('xlink:type', 'simple');
                $fillImage->setAttribute('xlink:show', 'embed');
                $fillImage->setAttribute('xlink:actuate', 'onLoad');
                $officeStyles->insertBefore($fillImage, $officeStyles->firstChild);
            }
        }
    }

    private function appendGraphicStyleIfMissing(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $parent,
        string $name,
        string $parentStyle,
        array $properties
    ): void {
        foreach ($dom->getElementsByTagName('*') as $existingStyle) {
            if (!$existingStyle instanceof DOMElement
                || !in_array($existingStyle->localName, ['style', 'style:style'], true)) {
                continue;
            }
            $existingName = $this->graphicStyleAttribute($existingStyle, 'style:name', 'name');
            $existingFamily = $this->graphicStyleAttribute($existingStyle, 'style:family', 'family');
            if ($existingName === $name && $existingFamily === 'graphic') {
                return;
            }
        }
        $style = $dom->createElement('style:style');
        $style->setAttribute('style:name', $name);
        $style->setAttribute('style:family', 'graphic');
        $style->setAttribute('style:parent-style-name', $parentStyle);
        $graphicProperties = $dom->createElement('style:graphic-properties');
        foreach ($properties as $key => $value) {
            if (is_scalar($value)) {
                $graphicProperties->setAttribute($key, (string) $value);
            }
        }
        $style->appendChild($graphicProperties);
        $parent->appendChild($style);
    }

    private function graphicStyleAttribute(DOMElement $element, string $qualifiedName, string $localName): string
    {
        foreach ($element->attributes as $attribute) {
            if ($attribute->nodeName === $qualifiedName || $attribute->localName === $localName) {
                return $attribute->nodeValue;
            }
        }

        return '';
    }

    protected function adjustBulletIndentation(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        foreach ($xpath->query('//style:list-level-label-alignment') as $node) {
            if ($node instanceof DOMElement) {
                $node->setAttribute('fo:margin-left', '0.35cm');
                $node->setAttribute('fo:text-indent', '-0.25cm');
            }
        }
    }

    private function applyImageStyleProps(DOMElement $props, array $options): void
    {
        $attributes = [
            'style:wrap', 'style:horizontal-pos', 'style:horizontal-rel',
            'style:vertical-pos', 'style:vertical-rel', 'fo:margin-left',
            'fo:margin-right', 'fo:margin-top', 'fo:margin-bottom', 'draw:fill',
            'draw:fill-image-name', 'draw:fill-image-width', 'draw:fill-image-height',
            'style:repeat', 'draw:stroke',
        ];
        foreach ($attributes as $attribute) {
            if (isset($options[$attribute])) {
                $props->setAttribute($attribute, (string) $options[$attribute]);
            }
        }
    }

    protected function ensureTextStylesExist(array $styleMap): void
    {
        $this->ensureXmlnsAttributes();
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles) {
            throw new Exception('❌ <office:styles> section not found in styles.xml');
        }
        foreach ($styleMap as $name => $options) {
            if ($xpath->query("//style:style[@style:name='$name']")->length > 0) {
                continue;
            }
            $style = $stylesDom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'text');
            $style->setAttribute('style:parent-style-name', 'Standard');
            $props = $stylesDom->createElement('style:text-properties');
            foreach (StyleMapper::mapTextStyleOptions($options) as $key => $value) {
                $props->setAttribute($key, $value);
            }
            $style->appendChild($props);
            $officeStyles->appendChild($style);
        }
    }

    public function ensureParagraphStylesExist(array $styleMap): void
    {
        $this->ensureXmlnsAttributes();
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles) {
            throw new Exception('❌ <office:styles> not found');
        }
        foreach ($styleMap as $name => $rawOptions) {
            if ($xpath->query("//style:style[@style:name='$name']")->length > 0) {
                continue;
            }
            $style = $stylesDom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'paragraph');
            $style->setAttribute('style:parent-style-name', 'Standard');
            $style->setAttribute('style:class', 'text');
            $paraProps = $stylesDom->createElement('style:paragraph-properties');
            foreach (StyleMapper::mapParagraphStyle($rawOptions) as $key => $value) {
                if ($key === 'style:tab-stops' && is_array($value)) {
                    $tabStops = $stylesDom->createElement('style:tab-stops');
                    foreach ($value as $tabStop) {
                        $tab = $stylesDom->createElement('style:tab-stop');
                        foreach ($tabStop as $attr => $attrValue) {
                            $tab->setAttribute($attr, $attrValue);
                        }
                        $tabStops->appendChild($tab);
                    }
                    $paraProps->appendChild($tabStops);
                } else {
                    $paraProps->setAttribute($key, $value);
                }
            }
            $style->appendChild($paraProps);
            $officeStyles->appendChild($style);
        }
    }

    protected function insertAutomaticStyle(DOMDocument $dom, DOMElement $style): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);
        if (!$automaticStyles) {
            $automaticStyles = $dom->createElement('office:automatic-styles');
            $dom->documentElement->insertBefore($automaticStyles, $dom->documentElement->firstChild);
        }
        $automaticStyles->appendChild($style);
    }

    protected function ensureDefaultListStyles(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        foreach ([['Bullet_20_Symbol', 'text:list-level-style-bullet', 'text:bullet-char', '•'], ['Numbering_20_Symbol', 'text:list-level-style-number', 'style:num-format', '1']] as $definition) {
            [$name, $levelName, $attribute, $value] = $definition;
            if ($xpath->query("//text:list-style[@style:name='$name']")->length > 0) {
                continue;
            }
            $list = $stylesDom->createElement('text:list-style');
            $list->setAttribute('style:name', $name);
            $level = $stylesDom->createElement($levelName);
            $level->setAttribute('text:level', '1');
            $level->setAttribute($attribute, $value);
            if ($name === 'Numbering_20_Symbol') {
                $level->setAttribute('style:num-suffix', '.');
                $level->setAttribute('style:num-prefix', '');
            }
            $props = $stylesDom->createElement('style:list-level-properties');
            $props->setAttribute('text:space-before', '0.5cm');
            $props->setAttribute('text:min-label-width', '0.5cm');
            $level->appendChild($props);
            $list->appendChild($level);
            $stylesDom->documentElement->appendChild($list);
        }
    }

    public function ensureDefaultListStylesForContentXml(DOMDocument $contentDom): void
    {
        $xpath = new DOMXPath($contentDom);
        foreach ([
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'loext' => 'urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
        ] as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }
        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);
        if (!$automaticStyles) {
            $office = $xpath->query('//office:document-content')->item(0);
            if (!$office) {
                throw new RuntimeException('No <office:document-content> found.');
            }
            $automaticStyles = $contentDom->createElement('office:automatic-styles');
            $office->insertBefore($automaticStyles, $office->firstChild);
        }
        foreach ($automaticStyles->getElementsByTagName('list-style') as $style) {
            if ($style->getAttribute('style:name') === 'Numbering_20_Symbol') {
                return;
            }
        }
        $list = $contentDom->createElement('text:list-style');
        $list->setAttribute('style:name', 'Numbering_20_Symbol');
        $level = $contentDom->createElement('text:list-level-style-number');
        $level->setAttribute('text:level', '1');
        $level->setAttribute('style:num-format', '1');
        $level->setAttribute('style:num-suffix', '.');
        $level->setAttribute('loext:num-list-format', '%1%.');
        $props = $contentDom->createElement('style:list-level-properties');
        $props->setAttribute('text:list-level-position-and-space-mode', 'label-alignment');
        $align = $contentDom->createElement('style:list-level-label-alignment');
        $align->setAttribute('text:label-followed-by', 'listtab');
        $align->setAttribute('text:list-tab-stop-position', '1.27cm');
        $align->setAttribute('fo:text-indent', '-0.635cm');
        $align->setAttribute('fo:margin-left', '1.27cm');
        $props->appendChild($align);
        $level->appendChild($props);
        $list->appendChild($level);
        $automaticStyles->appendChild($list);
    }

    protected function ensureDefaultParagraphStyles(): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        $xpath = new DOMXPath($stylesDom);
        $this->prepareNamespaces($xpath);
        $officeStyles = $xpath->query('//office:styles')->item(0);
        if (!$officeStyles) {
            throw new Exception('❌ <office:styles> section not found.');
        }
        for ($i = 1; $i <= 6; $i++) {
            $name = "Heading $i";
            if ($xpath->query("//style:style[@style:name='$name']")->length > 0) {
                continue;
            }
            $style = $stylesDom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', 'paragraph');
            $style->setAttribute('style:parent-style-name', 'Standard');
            $textProps = $stylesDom->createElement('style:text-properties');
            $textProps->setAttribute('fo:font-weight', 'bold');
            $paraProps = $stylesDom->createElement('style:paragraph-properties');
            $paraProps->setAttribute('fo:margin-top', '0.5cm');
            $paraProps->setAttribute('fo:margin-bottom', '0.3cm');
            $style->appendChild($textProps);
            $style->appendChild($paraProps);
            $officeStyles->appendChild($style);
        }
        $this->ensureParagraphStylesExist([
            'CenterPara' => ['text-align' => 'center'],
            'LeftPara' => ['text-align' => 'left'],
            'RightPara' => ['text-align' => 'right'],
        ]);
    }

    protected function registerStyles(array $styleDefinitions): void
    {
        $stylesDom = $this->documentContext()->stylesDom();
        foreach ($styleDefinitions as $name => $definition) {
            $family = $definition['family'];
            if (StyleWriter::styleAlreadyExists($stylesDom, $name, $family)) {
                continue;
            }
            $style = $stylesDom->createElement('style:style');
            $style->setAttribute('style:name', $name);
            $style->setAttribute('style:family', $family);
            $style->setAttribute('style:parent-style-name', 'Standard');
            $elementName = match ($family) {
                'text' => 'style:text-properties',
                'paragraph' => 'style:paragraph-properties',
                'table-cell' => 'style:table-cell-properties',
                'graphic' => 'style:graphic-properties',
                default => null,
            };
            if ($elementName) {
                $properties = $stylesDom->createElement($elementName);
                foreach ($definition['properties'] as $key => $value) {
                    $properties->setAttribute($key, $value);
                }
                $style->appendChild($properties);
            }
            StyleWriter::appendStyleToStylesXml($stylesDom, $style);
        }
    }

    public function extractTemplateVariables(): array
    {
        $result = [
            'variables' => [], 'loops' => [], 'conditions' => [],
            'negated_conditions' => [], 'filters' => [], 'filter_options' => [],
        ];
        foreach ([$this->documentContext()->contentDom(), $this->documentContext()->stylesDom()] as $dom) {
            foreach ($this->parseTemplateContent($dom->saveXML()) as $key => $values) {
                if ($key === 'filter_options') {
                    foreach ($values as $variable => $options) {
                        $result[$key][$variable] = array_unique(array_merge($result[$key][$variable] ?? [], $options));
                    }
                } else {
                    $result[$key] = array_unique(array_merge($result[$key], $values));
                }
            }
        }
        return $result;
    }

    protected function parseTemplateContent(string $content): array
    {
        $result = ['variables' => [], 'loops' => [], 'conditions' => [], 'negated_conditions' => [], 'filters' => [], 'filter_options' => []];
        preg_match_all('/\{\{(?:(\w+):)?(\w+)(?:\|(\w+))?\}\}/', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!empty($match[1])) $result['filters'][] = $match[1];
            $result['variables'][] = $match[2];
            if (!empty($match[3])) $result['filter_options'][$match[2]][] = $match[3];
        }
        preg_match_all('/\{\{#foreach:(\w+)\}\}/', $content, $matches);
        $result['loops'] = $matches[1];
        preg_match_all('/\{\{#(?:if|elseif):([^\}]+)\}\}/', $content, $matches);
        $result['conditions'] = $matches[1];
        preg_match_all('/\{\{#ifnot:(\w+)\}\}/', $content, $matches);
        $result['negated_conditions'] = $matches[1];
        foreach ($result as $key => $values) {
            if ($key !== 'filter_options') $result[$key] = array_unique($values);
        }
        return $result;
    }

    public function enableDebugMode(): void
    {
        $this->debugMode = true;
    }

    protected function log(string $message): void
    {
        if ($this->debugMode) {
            $this->log[] = $message;
        }
    }

    public function getDebugLog(): array
    {
        return $this->log;
    }


}
