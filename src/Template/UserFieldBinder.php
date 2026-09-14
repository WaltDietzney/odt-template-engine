<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Validates and atomically mutates one logical Writer User Field.
 *
 * @internal Public callers use OdtTemplate::setUserField().
 */
final class UserFieldBinder
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    public function bind(
        DOMDocument $contentDom,
        DOMDocument $stylesDom,
        string $name,
        string $value
    ): void {
        if ($name === '') {
            throw new UserFieldBindingException(
                $name,
                UserFieldBindingException::MALFORMED,
                'User Field name must not be empty.'
            );
        }

        $regions = $this->sourceRegions($contentDom, $stylesDom);
        $analysis = (new UserFieldAnalyzer())->analyze($regions);

        $field = null;
        foreach ($analysis['fields'] as $candidate) {
            if ($candidate['name'] === $name) {
                $field = $candidate;
                break;
            }
        }

        if ($field === null) {
            throw new UserFieldBindingException(
                $name,
                UserFieldBindingException::NOT_FOUND,
                sprintf('User Field "%s" was not found.', $name)
            );
        }

        $reason = match ($field['support_state']) {
            'SUPPORTED' => null,
            'UNSUPPORTED' => UserFieldBindingException::UNSUPPORTED_TYPE,
            'MALFORMED' => UserFieldBindingException::MALFORMED,
            'AMBIGUOUS' => UserFieldBindingException::AMBIGUOUS,
            default => UserFieldBindingException::MALFORMED,
        };

        if ($reason !== null) {
            throw new UserFieldBindingException(
                $name,
                $reason,
                sprintf(
                    'User Field "%s" cannot be bound: %s.',
                    $name,
                    $field['diagnostic_message'] ?? $field['support_state']
                )
            );
        }

        $declarations = [];
        foreach ($field['declaration_indexes'] as $index) {
            $node = $analysis['evidence'][$index]['node'] ?? null;
            if ($node instanceof DOMElement) {
                $declarations[] = $node;
            }
        }

        if ($declarations === []) {
            throw new UserFieldBindingException(
                $name,
                UserFieldBindingException::MALFORMED,
                sprintf('User Field "%s" has no authoritative declaration.', $name)
            );
        }

        // Validation is complete before the first mutation. All declaration
        // nodes belong to the current working DOMs and are known string fields.
        foreach ($declarations as $declaration) {
            $declaration->setAttributeNS(
                self::OFFICE_NAMESPACE,
                'office:string-value',
                $value
            );
        }
    }

    /**
     * @return list<array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * }>
     */
    private function sourceRegions(
        DOMDocument $contentDom,
        DOMDocument $stylesDom
    ): array {
        $regions = [];
        $index = 0;

        $contentXpath = new DOMXPath($contentDom);
        $contentXpath->registerNamespace(
            'office',
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0'
        );
        $body = $contentXpath->query(
            '/office:document-content/office:body/office:text'
        )->item(0);

        if ($body instanceof DOMElement) {
            $regions[] = [
                'source_part' => 'content.xml',
                'region_kind' => 'BODY',
                'region_owner' => null,
                'carrier' => $body,
                'region_index' => $index++,
            ];
        }

        $stylesXpath = new DOMXPath($stylesDom);
        $stylesXpath->registerNamespace('style', self::STYLE_NAMESPACE);

        foreach ($stylesXpath->query('//style:master-page') ?: [] as $masterPage) {
            if (!$masterPage instanceof DOMElement) {
                continue;
            }

            $owner = $masterPage->getAttributeNS(self::STYLE_NAMESPACE, 'name')
                ?: $masterPage->getAttribute('style:name')
                ?: null;

            foreach ($masterPage->childNodes as $child) {
                if (!$child instanceof DOMElement
                    || $child->namespaceURI !== self::STYLE_NAMESPACE
                ) {
                    continue;
                }

                if (!str_starts_with($child->localName, 'header')
                    && !str_starts_with($child->localName, 'footer')
                ) {
                    continue;
                }

                $regions[] = [
                    'source_part' => 'styles.xml',
                    'region_kind' => 'MASTER_PAGE_CONTENT',
                    'region_owner' => $owner,
                    'carrier' => $child,
                    'region_index' => $index++,
                ];
            }
        }

        return $regions;
    }
}
