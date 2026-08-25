<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Reads and updates standard ODT metadata for one document context.
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
        'initial_author' => 'meta:initial-creator',
        'author' => 'dc:creator',
        'language' => 'dc:language',
        'creation_date' => 'meta:creation-date',
        'date' => 'dc:date',
        'editing_cycles' => 'meta:editing-cycles',
        'editing_duration' => 'meta:editing-duration',
        'generator' => 'meta:generator',
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

        foreach ($metadata as $key => $value) {
            $qualifiedName = self::FIELD_MAP[$key] ?? null;
            if ($qualifiedName === null) {
                continue;
            }

            $node = $xpath->query('//' . $qualifiedName)->item(0);
            if ($node !== null) {
                $node->nodeValue = (string) $value;
                continue;
            }

            $metaRoot = $xpath->query('//office:document-meta/office:meta')->item(0);
            if (!$metaRoot instanceof DOMElement) {
                continue;
            }

            [$prefix, $localName] = explode(':', $qualifiedName, 2);
            $namespace = match ($prefix) {
                'dc' => self::DC_NS,
                'meta' => self::META_NS,
                default => null,
            };

            if ($namespace === null) {
                continue;
            }

            $element = $dom->createElementNS($namespace, $qualifiedName, (string) $value);
            $metaRoot->appendChild($element);
        }
    }

    /**
     * Return all supported metadata fields currently present in meta.xml.
     *
     * @return array<string, string>
     */
    public function get(): array
    {
        $xpath = $this->createXPath();
        $result = [];

        foreach (self::FIELD_MAP as $key => $qualifiedName) {
            $node = $xpath->query('//' . $qualifiedName)->item(0);
            if ($node !== null) {
                $result[$key] = $node->textContent;
            }
        }

        return $result;
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
