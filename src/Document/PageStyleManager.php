<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;
use RuntimeException;

/**
 * Authors native style:master-page definitions and their page-owned content.
 *
 * Page identity/succession remains separate from page-layout geometry. The
 * latter is delegated to PageLayoutManager.
 */
final class PageStyleManager
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    public function __construct(
        private readonly OdtDocumentContext $context,
        private readonly ?OdtPackage $package = null
    ) {
    }

    /**
     * @param array<string, string> $options
     */
    public function definePageLayout(string $name, array $options): void
    {
        (new PageLayoutManager($this->context))->defineLayout($name, $options);
    }

    /**
     * @param array{display-name?: string, next-style-name?: string, header?: OdtElement, footer?: OdtElement} $options
     */
    public function definePageStyle(string $name, string $pageLayoutName, array $options = []): void
    {
        $snapshot = $this->context->snapshotState();
        $createdResources = [];

        try {
            $name = $this->requiredName($name, 'Page style');
            $pageLayoutName = $this->requiredName($pageLayoutName, 'Page layout');
            $this->assertPageLayoutExists($pageLayoutName);
            $this->assertPageStyleAbsent($name);

            foreach (['header', 'footer'] as $region) {
                if (array_key_exists($region, $options) && !$options[$region] instanceof OdtElement) {
                    throw new InvalidArgumentException(sprintf('Page style %s must be an OdtElement.', $region));
                }
            }

            $masterStyles = $this->container('master-styles');
            $master = $this->context->stylesDom()->createElementNS(self::STYLE_NS, 'style:master-page');
            $master->setAttributeNS(self::STYLE_NS, 'style:name', $name);
            $master->setAttributeNS(self::STYLE_NS, 'style:page-layout-name', $pageLayoutName);

            if (isset($options['display-name'])) {
                $displayName = trim((string) $options['display-name']);
                if ($displayName !== '') {
                    $master->setAttributeNS(self::STYLE_NS, 'style:display-name', $displayName);
                }
            }
            if (isset($options['next-style-name'])) {
                $next = $this->requiredName((string) $options['next-style-name'], 'Next page style');
                $master->setAttributeNS(self::STYLE_NS, 'style:next-style-name', $next);
            }

            foreach (['header', 'footer'] as $region) {
                $element = $options[$region] ?? null;
                if (!$element instanceof OdtElement) {
                    continue;
                }
                $createdResources = array_merge(
                    $createdResources,
                    $this->package?->copyImageResourcesAtomically(
                        iterator_to_array((new StructuredResourceCollector())->collect($element), false)
                    ) ?? []
                );
                $this->materializeElementStyles($element);
                $container = $this->context->stylesDom()->createElementNS(
                    self::STYLE_NS,
                    'style:' . $region
                );
                $this->appendElementChildren($container, $element->toDomNode($this->context->stylesDom()));
                $master->appendChild($container);
            }

            $masterStyles->appendChild($master);
        } catch (\Throwable $exception) {
            $this->context->restoreState($snapshot);
            if ($createdResources !== []) {
                $this->package?->removePreparedPackageFiles($createdResources);
            }
            throw $exception;
        }
    }

    /**
     * Assign a master page to a paragraph style used by document content.
     *
     * Writer resolves paragraph styles used in content through
     * content.xml/office:automatic-styles before common styles in
     * styles.xml. If a named style exists only in styles.xml, create a
     * content-local copy with the same identity and preserved properties so
     * the assignment is active for the existing text:p/text:h references.
     */
    public function assignPageStyle(string $paragraphStyleName, string $masterPageName): void
    {
        $paragraphStyleName = $this->requiredName($paragraphStyleName, 'Paragraph style');
        $masterPageName = $this->requiredName($masterPageName, 'Master page');
        $this->assertPageStyleExists($masterPageName);

        $contentDom = $this->context->contentDom();
        $automaticStyles = $this->containerIn($contentDom, 'automatic-styles');
        $automaticMatches = $this->directStyleMatches($automaticStyles, $paragraphStyleName);

        if (count($automaticMatches) > 1) {
            throw new RuntimeException(sprintf('Paragraph style "%s" is ambiguous.', $paragraphStyleName));
        }
        if ($automaticMatches !== []) {
            $style = $automaticMatches[0];
            $this->assertParagraphStyle($style, $paragraphStyleName);
            $style->setAttributeNS(self::STYLE_NS, 'style:master-page-name', $masterPageName);
            return;
        }

        $commonStyles = $this->containerIn($this->context->stylesDom(), 'styles');
        $commonMatches = $this->directStyleMatches($commonStyles, $paragraphStyleName);
        if (count($commonMatches) > 1) {
            throw new RuntimeException(sprintf('Paragraph style "%s" is ambiguous.', $paragraphStyleName));
        }
        if ($commonMatches !== []) {
            $this->assertParagraphStyle($commonMatches[0], $paragraphStyleName);
            $style = $contentDom->importNode($commonMatches[0], true);
            if (!$style instanceof DOMElement) {
                throw new RuntimeException(sprintf('Paragraph style "%s" could not be copied.', $paragraphStyleName));
            }
            $automaticStyles->appendChild($style);
        } else {
            $style = $contentDom->createElementNS(self::STYLE_NS, 'style:style');
            $style->setAttributeNS(self::STYLE_NS, 'style:name', $paragraphStyleName);
            $style->setAttributeNS(self::STYLE_NS, 'style:family', 'paragraph');
            $style->setAttributeNS(self::STYLE_NS, 'style:parent-style-name', 'Standard');
            $automaticStyles->appendChild($style);
        }
        $style->setAttributeNS(self::STYLE_NS, 'style:master-page-name', $masterPageName);
    }

    public function setNextPageStyle(string $masterPageName, ?string $nextMasterPageName): void
    {
        $masterPageName = $this->requiredName($masterPageName, 'Master page');
        $master = $this->masterPage($masterPageName);
        if ($nextMasterPageName === null || trim($nextMasterPageName) === '') {
            $master->removeAttributeNS(self::STYLE_NS, 'next-style-name');
            return;
        }
        $nextMasterPageName = $this->requiredName($nextMasterPageName, 'Next page style');
        $this->assertPageStyleExists($nextMasterPageName);
        $master->setAttributeNS(self::STYLE_NS, 'style:next-style-name', $nextMasterPageName);
    }

    private function materializeElementStyles(OdtElement $element): void
    {
        $collector = new StyleRequirementCollector();
        $materializer = new StyleRequirementMaterializer();
        foreach ($collector->collectSemantic($element) as $requirement) {
            $this->context->styleContext()->registerRequirement($requirement);
            $materializer->materialize($this->context, $requirement);
        }
    }

    private function appendElementChildren(DOMElement $parent, DOMNode $node): void
    {
        if ($node instanceof \DOMDocumentFragment) {
            while ($node->firstChild !== null) {
                $parent->appendChild($node->firstChild);
            }
            return;
        }
        $parent->appendChild($node);
    }

    private function assertPageLayoutExists(string $name): void
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        if ($xpath->query(sprintf('//style:page-layout[@style:name=%s]', $this->literal($name)))->length === 0) {
            throw new RuntimeException(sprintf('Page layout "%s" was not found.', $name));
        }
    }

    private function assertPageStyleAbsent(string $name): void
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        if ($xpath->query(sprintf('//style:master-page[@style:name=%s]', $this->literal($name)))->length > 0) {
            throw new RuntimeException(sprintf('Page style "%s" already exists.', $name));
        }
    }

    private function assertPageStyleExists(string $name): void
    {
        $this->masterPage($name);
    }

    private function masterPage(string $name): DOMElement
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $node = $xpath->query(sprintf('//style:master-page[@style:name=%s]', $this->literal($name)))->item(0);
        if (!$node instanceof DOMElement) {
            throw new RuntimeException(sprintf('Master page "%s" was not found.', $name));
        }
        return $node;
    }

    private function container(string $localName): DOMElement
    {
        $dom = $this->context->stylesDom();
        foreach ($dom->getElementsByTagNameNS(self::OFFICE_NS, $localName) as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }
        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new RuntimeException('ODF styles.xml has no document element.');
        }
        $container = $dom->createElementNS(self::OFFICE_NS, 'office:' . $localName);
        $root->appendChild($container);
        return $container;
    }

    private function containerIn(DOMDocument $dom, string $localName): DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::OFFICE_NS, $localName) as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new RuntimeException('ODF document has no document element.');
        }
        $container = $dom->createElementNS(self::OFFICE_NS, 'office:' . $localName);
        if ($localName === 'automatic-styles') {
            foreach ($root->childNodes as $child) {
                if ($child instanceof DOMElement
                    && $child->namespaceURI === self::OFFICE_NS
                    && $child->localName === 'body'
                ) {
                    $root->insertBefore($container, $child);
                    return $container;
                }
            }
        }
        $root->appendChild($container);
        return $container;
    }

    /** @return list<DOMElement> */
    private function directStyleMatches(DOMElement $container, string $name): array
    {
        $matches = [];
        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::STYLE_NS
                && $child->localName === 'style'
                && $child->getAttributeNS(self::STYLE_NS, 'name') === $name
            ) {
                $matches[] = $child;
            }
        }
        return $matches;
    }

    private function assertParagraphStyle(DOMElement $style, string $name): void
    {
        if ($style->getAttributeNS(self::STYLE_NS, 'family') !== 'paragraph') {
            throw new RuntimeException(sprintf('Style "%s" is not a paragraph style.', $name));
        }
    }

    private function requiredName(string $name, string $label): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException($label . ' name must not be empty.');
        }
        return $name;
    }

    private function literal(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }
        return 'concat(' . implode(', "\'", ', array_map(
            static fn (string $part): string => "'" . $part . "'",
            explode("'", $value)
        )) . ')';
    }
}
