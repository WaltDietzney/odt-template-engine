<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Resolves existing native ODF objects by their type-specific identity.
 *
 * Resolution is read-only. It does not validate payload capabilities or
 * perform any replacement, resource, style, or package operation.
 */
final class TemplateTargetResolver
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

    public function resolveFrame(DOMDocument $dom, string $name): ?TemplateTarget
    {
        $xpath = $this->xpath($dom);
        $nodes = $xpath->query('//draw:frame[@draw:name=' . $this->xpathLiteral($name) . ']');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        if ($nodes->length > 1) {
            throw new AmbiguousTemplateTargetException(
                sprintf('Multiple frame targets match the name "%s".', $name)
            );
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return null;
        }

        return new TemplateTarget(TemplateTarget::TYPE_FRAME, $name, $node);
    }

    public function resolveTable(DOMDocument $dom, string $name): ?TemplateTarget
    {
        $xpath = $this->xpath($dom);
        $nodes = $xpath->query('//table:table[@table:name=' . $this->xpathLiteral($name) . ']');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        if ($nodes->length > 1) {
            throw new AmbiguousTemplateTargetException(
                sprintf('Multiple table targets match the name "%s".', $name)
            );
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return null;
        }

        return new TemplateTarget(TemplateTarget::TYPE_TABLE, $name, $node);
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', self::DRAWING_NAMESPACE);
        $xpath->registerNamespace('table', self::TABLE_NAMESPACE);

        return $xpath;
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);
        $expressions = [];
        foreach ($parts as $index => $part) {
            if ($part !== '') {
                $expressions[] = "'" . $part . "'";
            }
            if ($index < count($parts) - 1) {
                $expressions[] = '"\'"';
            }
        }

        return 'concat(' . implode(', ', $expressions) . ')';
    }
}
