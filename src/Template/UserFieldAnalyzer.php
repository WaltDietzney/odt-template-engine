<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMElement;
use DOMXPath;

/**
 * Analyzes bounded Writer User Field evidence without mutating source DOMs.
 *
 * @internal Phase-C semantic service. Public callers consume TemplateContract.
 */
final class UserFieldAnalyzer
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    /**
     * @param list<array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * }> $regions
     * @return array{
     *     evidence:list<array<string,mixed>>,
     *     fields:list<array<string,mixed>>
     * }
     */
    public function analyze(array $regions): array
    {
        $evidence = [];
        $groups = [];

        foreach ($regions as $region) {
            $xpath = new DOMXPath($region['carrier']->ownerDocument);
            $xpath->registerNamespace('text', self::TEXT_NAMESPACE);

            $sourceOrder = 0;
            foreach ($xpath->query(
                './/text:user-field-decl | .//text:user-field-get',
                $region['carrier']
            ) ?: [] as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $isDeclaration = $node->localName === 'user-field-decl';
                $name = $node->getAttributeNS(self::TEXT_NAMESPACE, 'name');

                if ($isDeclaration && $name === '') {
                    // Writer may author empty, unreferenced declarations.
                    // Phase C v1 deliberately ignores those artifacts.
                    ++$sourceOrder;
                    continue;
                }

                $entry = [
                    'kind' => $isDeclaration ? 'DECLARATION' : 'REFERENCE',
                    'node' => $node,
                    'name' => $name,
                    'value_type' => $isDeclaration
                        ? $node->getAttributeNS(self::OFFICE_NAMESPACE, 'value-type')
                        : null,
                    'value' => $isDeclaration
                        ? $this->declarationValue($node)
                        : null,
                    'display_text' => $isDeclaration ? null : $node->textContent,
                    'region' => $region,
                    'source_order' => $sourceOrder,
                ];
                ++$sourceOrder;

                $index = count($evidence);
                $evidence[] = $entry;
                $groups[$name][] = $index;
            }
        }

        $fields = [];
        foreach ($groups as $name => $indexes) {
            $declarations = [];
            $references = [];

            foreach ($indexes as $index) {
                if ($evidence[$index]['kind'] === 'DECLARATION') {
                    $declarations[] = $index;
                } else {
                    $references[] = $index;
                }
            }

            $state = $this->fieldState($name, $declarations, $references, $evidence);
            $state['name'] = $name;
            $state['evidence_indexes'] = $indexes;
            $state['declaration_indexes'] = $declarations;
            $state['reference_indexes'] = $references;
            $fields[] = $state;
        }

        return [
            'evidence' => $evidence,
            'fields' => $fields,
        ];
    }

    /**
     * @param list<int> $declarations
     * @param list<int> $references
     * @param list<array<string,mixed>> $evidence
     * @return array<string,mixed>
     */
    private function fieldState(
        string $name,
        array $declarations,
        array $references,
        array $evidence
    ): array {
        if ($name === '' || $declarations === []) {
            return [
                'support_state' => 'MALFORMED',
                'diagnostic_code' => 'orphan_user_field_reference',
                'diagnostic_message' => $name === ''
                    ? 'User Field reference has no non-empty field name.'
                    : sprintf('User Field reference "%s" has no matching declaration.', $name),
            ];
        }

        $sameRegionConflict = $this->hasSameRegionConflict($declarations, $evidence);
        if ($sameRegionConflict) {
            return [
                'support_state' => 'AMBIGUOUS',
                'diagnostic_code' => 'ambiguous_user_field_declaration',
                'diagnostic_message' => sprintf(
                    'User Field "%s" has conflicting duplicate declarations in one source region.',
                    $name
                ),
            ];
        }

        $types = array_values(array_unique(array_map(
            static fn (int $index): string => (string) $evidence[$index]['value_type'],
            $declarations
        )));

        if (count($types) > 1) {
            return [
                'support_state' => 'AMBIGUOUS',
                'diagnostic_code' => 'conflicting_user_field_type',
                'diagnostic_message' => sprintf(
                    'User Field "%s" has conflicting declaration value types across source regions.',
                    $name
                ),
            ];
        }

        if (($types[0] ?? '') !== 'string') {
            return [
                'support_state' => 'UNSUPPORTED',
                'diagnostic_code' => 'unsupported_user_field_type',
                'diagnostic_message' => sprintf(
                    'User Field "%s" uses unsupported value type "%s".',
                    $name,
                    $types[0] ?? ''
                ),
            ];
        }

        $values = array_values(array_unique(array_map(
            static fn (int $index): string => (string) $evidence[$index]['value'],
            $declarations
        )));

        if (count($values) > 1) {
            return [
                'support_state' => 'AMBIGUOUS',
                'diagnostic_code' => 'conflicting_user_field_value',
                'diagnostic_message' => sprintf(
                    'User Field "%s" has conflicting declaration values across source regions.',
                    $name
                ),
            ];
        }

        return [
            'support_state' => 'SUPPORTED',
            'diagnostic_code' => null,
            'diagnostic_message' => null,
        ];
    }

    /**
     * @param list<int> $declarations
     * @param list<array<string,mixed>> $evidence
     */
    private function hasSameRegionConflict(array $declarations, array $evidence): bool
    {
        $states = [];

        foreach ($declarations as $index) {
            $entry = $evidence[$index];
            $region = $entry['region'];
            $key = implode('|', [
                $region['source_part'],
                (string) $region['region_index'],
            ]);
            $state = implode('|', [
                (string) $entry['value_type'],
                (string) $entry['value'],
            ]);
            $states[$key][$state] = true;
        }

        foreach ($states as $regionStates) {
            if (count($regionStates) > 1) {
                return true;
            }
        }

        return false;
    }

    private function declarationValue(DOMElement $declaration): string
    {
        $type = $declaration->getAttributeNS(self::OFFICE_NAMESPACE, 'value-type');

        return match ($type) {
            'string' => $declaration->getAttributeNS(
                self::OFFICE_NAMESPACE,
                'string-value'
            ),
            default => $declaration->getAttributeNS(
                self::OFFICE_NAMESPACE,
                'value'
            ),
        };
    }
}
