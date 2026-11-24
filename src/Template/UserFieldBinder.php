<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

/**
 * Validates, declares and binds document-global native string User Fields.
 *
 * @internal Public callers use OdtTemplate::declareUserField() and setUserField().
 */
final class UserFieldBinder
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    /** Register a string declaration without changing any existing logical field. */
    public function declare(
        DOMDocument $contentDom,
        DOMDocument $stylesDom,
        string $name,
        string $value
    ): void {
        if (trim($name) === '' || !$this->isXmlString($name) || !$this->isXmlString($value)) {
            throw new InvalidArgumentException(
                'User Field name and value must be valid XML strings; name must not be blank.'
            );
        }

        $analysis = (new UserFieldAnalyzer())->analyze($this->sourceRegions($contentDom, $stylesDom));
        foreach ($analysis['fields'] as $field) {
            if ($field['name'] !== $name) {
                continue;
            }
            if ($field['support_state'] !== 'SUPPORTED') {
                $reason = match ($field['support_state']) {
                    'UNSUPPORTED' => UserFieldBindingException::UNSUPPORTED_TYPE,
                    'AMBIGUOUS' => UserFieldBindingException::AMBIGUOUS,
                    default => UserFieldBindingException::MALFORMED,
                };
                throw new UserFieldBindingException($name, $reason, 'Existing User Field cannot be reused safely.');
            }
            foreach ($field['declaration_indexes'] as $index) {
                if ($analysis['evidence'][$index]['value'] !== $value) {
                    throw new UserFieldBindingException(
                        $name,
                        UserFieldBindingException::AMBIGUOUS,
                        'Existing User Field has a different value; use setUserField() to change it.'
                    );
                }
            }
            return;
        }

        $xpath = new DOMXPath($contentDom);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $body = $xpath->query('/office:document-content/office:body/office:text')->item(0);
        if (!$body instanceof DOMElement) {
            throw new InvalidArgumentException('Document does not contain office:body/office:text.');
        }
        $textNamespace = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
        $containers = $xpath->query('./text:user-field-decls', $body);
        if ($containers->length > 1) {
            throw new UserFieldBindingException(
                $name,
                UserFieldBindingException::AMBIGUOUS,
                'Multiple declaration containers.'
            );
        }
        $container = $containers->item(0);
        $declaration = $contentDom->createElementNS($textNamespace, 'text:user-field-decl');
        $declaration->setAttributeNS($textNamespace, 'text:name', $name);
        $declaration->setAttributeNS(self::OFFICE_NAMESPACE, 'office:value-type', 'string');
        $declaration->setAttributeNS(self::OFFICE_NAMESPACE, 'office:string-value', $value);
        if ($container instanceof DOMElement) {
            $container->appendChild($declaration);
            return;
        }
        // Construct detached, then insert after variable declarations in ODF body order.
        $container = $contentDom->createElementNS($textNamespace, 'text:user-field-decls');
        $container->appendChild($declaration);
        $variables = $xpath->query('./text:variable-decls', $body)->item(0);
        $body->insertBefore($container, $variables instanceof DOMElement ? $variables->nextSibling : $body->firstChild);
    }

    private function isXmlString(string $value): bool
    {
        return preg_match(
            '/^[\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]*$/uD',
            $value
        ) === 1;
    }

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
