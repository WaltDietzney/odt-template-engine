<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Reads and updates the bounded metadata set supported by one document context.
 */
final class MetadataManager
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const DC_NS = 'http://purl.org/dc/elements/1.1/';
    private const META_NS = 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0';

    /** @var array<string, string> */
    private const FIELD_MAP = [
        'title' => 'dc:title',
        'subject' => 'dc:subject',
        'description' => 'dc:description',
        'coverage' => 'dc:coverage',
        'keywords' => 'meta:keyword',
        'initial_creator' => 'meta:initial-creator',
        'creator' => 'dc:creator',
        'language' => 'dc:language',
        'creation_date' => 'meta:creation-date',
        'date' => 'dc:date',
        'editing_cycles' => 'meta:editing-cycles',
        'editing_duration' => 'meta:editing-duration',
        'generator' => 'meta:generator',
    ];

    /** @var array<string, string> */
    private const INPUT_ALIASES = [
        'author' => 'creator',
        'initial_author' => 'initial_creator',
    ];

    public function __construct(private readonly OdtDocumentContext $context)
    {
    }

    /**
     * Update supported metadata fields. Unknown keys are ignored for compatibility.
     *
     * @param array<string, mixed> $metadata
     */
    public function set(array $metadata): void
    {
        $dom = $this->context->metaDom();
        $xpath = $this->createXPath();

        foreach ($metadata as $inputKey => $value) {
            $key = self::INPUT_ALIASES[$inputKey] ?? $inputKey;
            $qualifiedName = self::FIELD_MAP[$key] ?? null;
            if ($qualifiedName === null) {
                continue;
            }

            if ($key === 'keywords') {
                $this->replaceKeywords($dom, $xpath, $value);
                continue;
            }

            $this->setSingularField($dom, $xpath, $qualifiedName, (string) $value);
        }
    }

    /**
     * Return supported metadata currently present in meta.xml.
     *
     * Creator fields include both canonical keys and their established read aliases.
     *
     * @return array<string, string|list<string>>
     */
    public function get(): array
    {
        $xpath = $this->createXPath();
        $result = [];

        foreach (self::FIELD_MAP as $key => $qualifiedName) {
            if ($key === 'keywords') {
                $nodes = $xpath->query('//meta:keyword');
                if ($nodes === false || $nodes->length === 0) {
                    continue;
                }

                $result[$key] = array_map(
                    static fn (DOMNode $node): string => $node->textContent,
                    iterator_to_array($nodes)
                );
                continue;
            }

            $node = $xpath->query('//' . $qualifiedName)->item(0);
            if ($node === null) {
                continue;
            }

            $result[$key] = $node->textContent;
            if ($key === 'creator') {
                $result['author'] = $node->textContent;
            } elseif ($key === 'initial_creator') {
                $result['initial_author'] = $node->textContent;
            }
        }

        return $result;
    }

    private function setSingularField(DOMDocument $dom, DOMXPath $xpath, string $qualifiedName, string $value): void
    {
        $node = $xpath->query('//' . $qualifiedName)->item(0);
        if ($node !== null) {
            $node->nodeValue = $value;
            return;
        }

        $metaRoot = $xpath->query('//office:document-meta/office:meta')->item(0);
        if (!$metaRoot instanceof DOMElement) {
            return;
        }

        $element = $this->createMetadataElement($dom, $qualifiedName, $value);
        $metaRoot->appendChild($element);
    }

    private function replaceKeywords(DOMDocument $dom, DOMXPath $xpath, mixed $value): void
    {
        if (is_string($value)) {
            $keywords = [$value];
        } elseif (is_array($value) && array_is_list($value)) {
            foreach ($value as $keyword) {
                if (!is_string($keyword)) {
                    throw new InvalidArgumentException('Metadata keywords must be a string or a list of strings.');
                }
            }
            $keywords = $value;
        } else {
            throw new InvalidArgumentException('Metadata keywords must be a string or a list of strings.');
        }

        $nodes = $xpath->query('//meta:keyword');
        if ($nodes !== false) {
            foreach (iterator_to_array($nodes) as $node) {
                $node?->parentNode?->removeChild($node);
            }
        }

        $metaRoot = $xpath->query('//office:document-meta/office:meta')->item(0);
        if (!$metaRoot instanceof DOMElement) {
            return;
        }

        foreach ($keywords as $keyword) {
            $metaRoot->appendChild($this->createMetadataElement($dom, 'meta:keyword', $keyword));
        }
    }

    private function createMetadataElement(DOMDocument $dom, string $qualifiedName, string $value): DOMElement
    {
        [$prefix] = explode(':', $qualifiedName, 2);
        $namespace = match ($prefix) {
            'dc' => self::DC_NS,
            'meta' => self::META_NS,
            default => throw new InvalidArgumentException('Unsupported metadata namespace.'),
        };

        return $dom->createElementNS($namespace, $qualifiedName, $value);
    }

    private function createXPath(): DOMXPath
    {
        $xpath = new DOMXPath($this->context->metaDom());
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $xpath->registerNamespace('dc', self::DC_NS);
        $xpath->registerNamespace('meta', self::META_NS);

        return $xpath;
    }
}
