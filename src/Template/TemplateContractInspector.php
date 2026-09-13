<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/** Builds the bounded source-oriented template contract from original template DOMs. */
final class TemplateContractInspector
{
    private const DRAW_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function inspect(DOMDocument $contentDom, DOMDocument $stylesDom): TemplateContract
    {
        $regions = $this->sourceRegions($contentDom, $stylesDom);
        $bindings = [];
        $controlStates = [];
        $nativeObjects = [];
        $diagnostics = [];
        $coverageRegions = [];
        $dependencyStates = [];

        foreach ($regions as $region) {
            $coverageRegions[] = [
                'source_part' => $region['source_part'],
                'region_kind' => $region['region_kind'],
                'region_owner' => $region['region_owner'],
                'carrier_kind' => $region['carrier']->nodeName,
            ];

            $this->projectRegionSemantics(
                $region,
                $bindings,
                $controlStates,
                $dependencyStates,
                $diagnostics
            );

            [$regionNativeObjects, $nativeNodeIds] = $this->nativeObjectEvidence($region);
            foreach ($regionNativeObjects as $nativeObject) {
                $nativeObjects[] = $nativeObject;
            }

            $this->projectDeclarativeSectionCandidates(
                $region,
                $nativeNodeIds,
                $controlStates,
                $dependencyStates,
                $diagnostics
            );
        }

        $this->appendDuplicateNativeNameDiagnostics($nativeObjects, $diagnostics);

        $dependencies = $this->materializeDependencies($dependencyStates);
        $controls = $this->materializeControls($controlStates);

        return new TemplateContract(
            $bindings,
            $controls,
            $nativeObjects,
            $dependencies,
            $diagnostics,
            new TemplateContractCoverage(
                $coverageRegions,
                ['meta.xml', 'settings.xml', 'META-INF/manifest.xml', 'embedded_objects']
            ),
            new TemplateContractCapabilities([
                'inspection' => TemplateContractCapabilities::READY,
                'dependency_mapping' => TemplateContractCapabilities::READY,
            ])
        );
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
    private function sourceRegions(DOMDocument $contentDom, DOMDocument $stylesDom): array
    {
        $regions = [];
        $index = 0;

        $contentXpath = $this->xpath($contentDom);
        $body = $contentXpath->query('/office:document-content/office:body/office:text')->item(0);
        if ($body instanceof DOMElement) {
            $regions[] = [
                'source_part' => 'content.xml',
                'region_kind' => 'BODY',
                'region_owner' => null,
                'carrier' => $body,
                'region_index' => $index++,
            ];
        }

        $stylesXpath = $this->xpath($stylesDom);
        foreach ($stylesXpath->query('//style:master-page') ?: [] as $masterPage) {
            if (!$masterPage instanceof DOMElement) {
                continue;
            }

            $owner = $masterPage->getAttribute('style:name') ?: null;
            foreach ($masterPage->childNodes as $child) {
                if (!$child instanceof DOMElement || $child->namespaceURI !== self::STYLE_NAMESPACE) {
                    continue;
                }

                $localName = $child->localName;
                if (!str_starts_with($localName, 'header')
                    && !str_starts_with($localName, 'footer')
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

    /**
     * @param array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * } $region
     * @param list<BindingDescriptor> $bindings
     * @param list<array<string, mixed>> $controlStates
     * @param array<string, array<string, mixed>> $dependencyStates
     * @param list<TemplateContractDiagnostic> $diagnostics
     */
    private function projectRegionSemantics(
        array $region,
        array &$bindings,
        array &$controlStates,
        array &$dependencyStates,
        array &$diagnostics
    ): void {
        $inspection = (new TemplateStructureInspector())->inspect(
            $this->regionDocument($region['carrier'])
        );

        $root = DataScopeDescriptor::root();
        $scopeStack = [$root];
        $controlStack = [];

        foreach ($inspection->expressions() as $sourceOrder => $expression) {
            $marker = $this->classicControlMarker($expression);

            if ($marker !== null) {
                $provenance = $this->provenance(
                    $region,
                    'classic_control_marker',
                    $sourceOrder,
                    $expression->rawText(),
                    $expression->scope()
                );

                if ($marker['kind'] === 'FOREACH_OPEN') {
                    $scope = $scopeStack[array_key_last($scopeStack)];
                    $dependencyId = $this->ensureDependency(
                        $dependencyStates,
                        $scope,
                        'COLLECTION',
                        $marker['name'],
                        $provenance->evidenceId()
                    );
                    $collectionPath = $scope->dependencyPath($marker['name'], true);
                    $itemScope = DataScopeDescriptor::collectionItem(
                        $scope,
                        $dependencyId,
                        $collectionPath
                    );
                    $controlId = $this->controlId($provenance, 'FOREACH');

                    $controlStates[] = [
                        'id' => $controlId,
                        'kind' => 'FOREACH',
                        'scope' => $scope,
                        'marker_evidence' => [$provenance],
                        'dependency_ids' => [$dependencyId],
                        'created_scope' => $itemScope,
                    ];
                    $controlIndex = array_key_last($controlStates);

                    if ($controlStack !== []) {
                        $diagnostics[] = $this->nestedControlCompatibilityFinding(
                            $controlId,
                            $provenance
                        );
                    }

                    $controlStack[] = ['type' => 'FOREACH', 'index' => $controlIndex];
                    $scopeStack[] = $itemScope;
                    continue;
                }

                if ($marker['kind'] === 'FOREACH_END') {
                    $index = $this->topControlIndex($controlStack, 'FOREACH');
                    if ($index !== null) {
                        $controlStates[$index]['marker_evidence'][] = $provenance;
                        array_pop($controlStack);
                        if (count($scopeStack) > 1) {
                            array_pop($scopeStack);
                        }
                    }
                    continue;
                }

                if (in_array($marker['kind'], ['IF_OPEN', 'IFNOT_OPEN'], true)) {
                    $scope = $scopeStack[array_key_last($scopeStack)];
                    $condition = ConditionExpression::parse($marker['expression']);
                    $dependencyId = $this->ensureDependency(
                        $dependencyStates,
                        $scope,
                        'VALUE',
                        $condition->referenceName(),
                        $provenance->evidenceId()
                    );
                    $kind = $marker['kind'] === 'IFNOT_OPEN' ? 'IFNOT' : 'IF';
                    $controlId = $this->controlId($provenance, $kind);

                    $controlStates[] = [
                        'id' => $controlId,
                        'kind' => $kind,
                        'scope' => $scope,
                        'marker_evidence' => [$provenance],
                        'dependency_ids' => [$dependencyId],
                        'created_scope' => null,
                    ];
                    $controlIndex = array_key_last($controlStates);

                    if ($controlStack !== []) {
                        $diagnostics[] = $this->nestedControlCompatibilityFinding(
                            $controlId,
                            $provenance
                        );
                    }

                    $controlStack[] = ['type' => 'CONDITION', 'index' => $controlIndex];
                    continue;
                }

                if ($marker['kind'] === 'ELSEIF') {
                    $index = $this->topControlIndex($controlStack, 'CONDITION');
                    if ($index !== null) {
                        $controlStates[$index]['marker_evidence'][] = $provenance;
                        $condition = ConditionExpression::parse($marker['expression']);
                        $dependencyId = $this->ensureDependency(
                            $dependencyStates,
                            $scopeStack[array_key_last($scopeStack)],
                            'VALUE',
                            $condition->referenceName(),
                            $provenance->evidenceId()
                        );
                        if (!in_array($dependencyId, $controlStates[$index]['dependency_ids'], true)) {
                            $controlStates[$index]['dependency_ids'][] = $dependencyId;
                        }
                    }
                    continue;
                }

                if ($marker['kind'] === 'ELSE') {
                    $index = $this->topControlIndex($controlStack, 'CONDITION');
                    if ($index !== null) {
                        $controlStates[$index]['marker_evidence'][] = $provenance;
                    }
                    continue;
                }

                if ($marker['kind'] === 'ENDIF') {
                    $index = $this->topControlIndex($controlStack, 'CONDITION');
                    if ($index !== null) {
                        $controlStates[$index]['marker_evidence'][] = $provenance;
                        array_pop($controlStack);
                    }
                    continue;
                }
            }

            if (!in_array(
                $expression->kind(),
                ['SCALAR', 'FILTERED_SCALAR', 'SPECIAL'],
                true
            )) {
                continue;
            }

            $scope = $scopeStack[array_key_last($scopeStack)];
            $provenance = $this->provenance(
                $region,
                'visible_expression',
                $sourceOrder,
                $expression->rawText(),
                $expression->scope()
            );
            $dependencyId = $this->ensureDependency(
                $dependencyStates,
                $scope,
                'VALUE',
                (string) $expression->variableName(),
                $provenance->evidenceId()
            );

            $bindings[] = new BindingDescriptor(
                $expression->kind(),
                $expression->rawText(),
                $expression->variableName(),
                $expression->filterName(),
                $expression->filterOption(),
                'SUPPORTED',
                $provenance,
                $dependencyId
            );
        }
    }

    /**
     * @return array{kind:string,name?:string,expression?:string}|null
     */
    private function classicControlMarker(TemplateExpressionDescriptor $expression): ?array
    {
        if ($expression->kind() === 'FOREACH_OPEN' && $expression->variableName() !== null) {
            return ['kind' => 'FOREACH_OPEN', 'name' => $expression->variableName()];
        }

        if ($expression->kind() === 'FOREACH_END') {
            return ['kind' => 'FOREACH_END'];
        }

        $body = substr($expression->rawText(), 2, -2);
        if (preg_match('/^#(if|ifnot|elseif):(.+)$/', $body, $match) === 1) {
            return [
                'kind' => match ($match[1]) {
                    'if' => 'IF_OPEN',
                    'ifnot' => 'IFNOT_OPEN',
                    'elseif' => 'ELSEIF',
                },
                'expression' => trim($match[2]),
            ];
        }

        return match ($body) {
            '#else' => ['kind' => 'ELSE'],
            '#endif' => ['kind' => 'ENDIF'],
            default => null,
        };
    }

    /**
     * @param array<string, array<string, mixed>> $states
     */
    private function ensureDependency(
        array &$states,
        DataScopeDescriptor $scope,
        string $kind,
        string $name,
        string $evidenceId
    ): string {
        $key = implode('|', [$scope->id(), $kind, $name]);

        if (!isset($states[$key])) {
            $states[$key] = [
                'id' => $this->dependencyId($scope, $kind, $name),
                'kind' => $kind,
                'name' => $name,
                'scope' => $scope,
                'path' => $scope->dependencyPath($name, $kind === 'COLLECTION'),
                'evidence_ids' => [],
            ];
        }

        if (!in_array($evidenceId, $states[$key]['evidence_ids'], true)) {
            $states[$key]['evidence_ids'][] = $evidenceId;
        }

        return $states[$key]['id'];
    }

    /**
     * @param array<string, array<string, mixed>> $states
     * @return list<DependencyDescriptor>
     */
    private function materializeDependencies(array $states): array
    {
        $dependencies = [];
        foreach ($states as $state) {
            $dependencies[] = new DependencyDescriptor(
                $state['id'],
                $state['kind'],
                $state['name'],
                $state['scope'],
                $state['path'],
                $state['evidence_ids']
            );
        }

        return $dependencies;
    }

    /**
     * @param list<array<string, mixed>> $states
     * @return list<ControlDescriptor>
     */
    private function materializeControls(array $states): array
    {
        return array_map(
            static fn (array $state): ControlDescriptor => new ControlDescriptor(
                $state['id'],
                $state['kind'],
                $state['representation'] ?? 'CLASSIC',
                $state['support_state'] ?? 'SUPPORTED',
                $state['scope'],
                $state['marker_evidence'],
                $state['dependency_ids'],
                $state['created_scope'],
                $state['carrier_native_object_id'] ?? null
            ),
            $states
        );
    }

    /**
     * @param list<array{type:string,index:int}> $stack
     */
    private function topControlIndex(array $stack, string $type): ?int
    {
        $top = $stack[array_key_last($stack)] ?? null;
        if ($top === null || $top['type'] !== $type) {
            return null;
        }

        return $top['index'];
    }

    private function nestedControlCompatibilityFinding(
        string $controlId,
        SourceProvenance $provenance
    ): TemplateContractDiagnostic {
        return new TemplateContractDiagnostic(
            'classic_nested_control_runtime_limitation',
            'warning',
            'Intended nested control scope is inspectable, but current classic runtime has known nested-control limitations.',
            $controlId,
            $provenance
        );
    }

    private function controlId(SourceProvenance $provenance, string $kind): string
    {
        return 'c_' . substr(
            hash('sha256', $kind . '|' . $provenance->evidenceId()),
            0,
            16
        );
    }

    private function dependencyId(
        DataScopeDescriptor $scope,
        string $kind,
        string $name
    ): string {
        return 'd_' . substr(hash('sha256', implode('|', [
            $scope->id(),
            $kind,
            $name,
        ])), 0, 16);
    }

    /**
     * @param array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * } $region
     * @return array{0:list<NativeObjectDescriptor>,1:array<int,string>}
     */
    private function nativeObjectEvidence(array $region): array
    {
        $xpath = $this->xpath($region['carrier']->ownerDocument);
        $query = './/text:section'
            . ' | .//text:bookmark'
            . ' | .//text:bookmark-start'
            . ' | .//table:table'
            . ' | .//draw:frame';

        $nodes = [];
        foreach ($xpath->query($query, $region['carrier']) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        $nodeIds = [];
        foreach ($nodes as $sourceOrder => $node) {
            [$kind, $attribute] = $this->nativeObjectKindAndNameAttribute($node);
            if ($kind === null || $attribute === null) {
                continue;
            }

            $name = $node->getAttribute($attribute);
            $name = $name !== '' ? $name : null;
            $provenance = $this->provenance(
                $region,
                'native_object_name',
                $sourceOrder,
                $name ?? $kind,
                $node->nodeName,
                $this->nativeOwnerChain($node, $region['carrier'])
            );
            $nodeIds[spl_object_id($node)] = 'n_' . substr(
                hash('sha256', $kind . '|' . $provenance->evidenceId()),
                0,
                16
            );
        }

        $objects = [];
        foreach ($nodes as $sourceOrder => $node) {
            [$kind, $attribute] = $this->nativeObjectKindAndNameAttribute($node);
            if ($kind === null || $attribute === null) {
                continue;
            }

            $name = $node->getAttribute($attribute);
            $name = $name !== '' ? $name : null;
            $provenance = $this->provenance(
                $region,
                'native_object_name',
                $sourceOrder,
                $name ?? $kind,
                $node->nodeName,
                $this->nativeOwnerChain($node, $region['carrier'])
            );

            $ownerIds = [];
            for ($owner = $node->parentNode;
                $owner !== null && $owner !== $region['carrier'];
                $owner = $owner->parentNode
            ) {
                if ($owner instanceof DOMElement
                    && isset($nodeIds[spl_object_id($owner)])
                ) {
                    array_unshift($ownerIds, $nodeIds[spl_object_id($owner)]);
                }
            }

            $objects[] = new NativeObjectDescriptor(
                $kind,
                $name,
                $provenance,
                $nodeIds[spl_object_id($node)],
                $ownerIds
            );
        }

        return [$objects, $nodeIds];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function nativeObjectKindAndNameAttribute(DOMElement $node): array
    {
        return match ($node->nodeName) {
            'text:section' => ['section', 'text:name'],
            'text:bookmark', 'text:bookmark-start' => ['bookmark', 'text:name'],
            'table:table' => ['table', 'table:name'],
            'draw:frame' => ['frame', 'draw:name'],
            default => [null, null],
        };
    }

    /**
     * @param array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * } $region
     * @param array<int,string> $nativeNodeIds
     * @param list<array<string,mixed>> $controlStates
     * @param array<string,array<string,mixed>> $dependencyStates
     * @param list<TemplateContractDiagnostic> $diagnostics
     */
    private function projectDeclarativeSectionCandidates(
        array $region,
        array $nativeNodeIds,
        array &$controlStates,
        array &$dependencyStates,
        array &$diagnostics
    ): void {
        $xpath = $this->xpath($region['carrier']->ownerDocument);
        $sourceOrder = 0;
        $sectionCreatedScopes = [];

        foreach ($xpath->query('.//text:section', $region['carrier']) ?: [] as $section) {
            if (!$section instanceof DOMElement) {
                continue;
            }

            $name = $section->getAttribute('text:name');
            if ($name === '' || !str_starts_with($name, '#')) {
                ++$sourceOrder;
                continue;
            }

            $provenance = $this->provenance(
                $region,
                'native_section_name',
                $sourceOrder,
                $name,
                'text:section',
                $this->nativeOwnerChain($section, $region['carrier'])
            );
            ++$sourceOrder;

            $parsed = $this->declarativeSectionCandidate($name);
            if ($parsed === null) {
                if ($this->resemblesDeclarativeSectionCandidate($name)) {
                    $diagnostics[] = new TemplateContractDiagnostic(
                        'malformed_native_section_declaration',
                        'warning',
                        'Section name resembles a declarative control but does not match the recognized Phase-B candidate grammar.',
                        $nativeNodeIds[spl_object_id($section)] ?? null,
                        $provenance
                    );
                }
                continue;
            }

            $scope = DataScopeDescriptor::root();
            for ($owner = $section->parentNode;
                $owner !== null && $owner !== $region['carrier'];
                $owner = $owner->parentNode
            ) {
                if ($owner instanceof DOMElement
                    && isset($sectionCreatedScopes[spl_object_id($owner)])
                ) {
                    $scope = $sectionCreatedScopes[spl_object_id($owner)];
                    break;
                }
            }
            $dependencyKind = $parsed['kind'] === 'FOREACH' ? 'COLLECTION' : 'VALUE';
            $referenceName = $parsed['kind'] === 'FOREACH'
                ? $parsed['reference']
                : ConditionExpression::parse($parsed['reference'])->referenceName();
            $dependencyId = $this->ensureDependency(
                $dependencyStates,
                $scope,
                $dependencyKind,
                $referenceName,
                $provenance->evidenceId()
            );

            $createdScope = null;
            if ($parsed['kind'] === 'FOREACH') {
                $createdScope = DataScopeDescriptor::collectionItem(
                    $scope,
                    $dependencyId,
                    $scope->dependencyPath($referenceName, true)
                );
                $sectionCreatedScopes[spl_object_id($section)] = $createdScope;
            }

            $controlStates[] = [
                'id' => $this->controlId($provenance, $parsed['kind']),
                'kind' => $parsed['kind'],
                'representation' => 'NATIVE_SECTION_DECLARATION',
                'support_state' => 'RECOGNIZED',
                'scope' => $scope,
                'marker_evidence' => [$provenance],
                'dependency_ids' => [$dependencyId],
                'created_scope' => $createdScope,
                'carrier_native_object_id' => $nativeNodeIds[spl_object_id($section)] ?? null,
            ];
        }
    }

    private function resemblesDeclarativeSectionCandidate(string $name): bool
    {
        return str_starts_with($name, '#foreach')
            || str_starts_with($name, '#if:')
            || str_starts_with($name, '#ifnot:');
    }

    /**
     * @return array{kind:string,reference:string}|null
     */
    private function declarativeSectionCandidate(string $name): ?array
    {
        if (preg_match('/^#foreach:([A-Za-z_][A-Za-z0-9_]*)$/', $name, $match) === 1) {
            return ['kind' => 'FOREACH', 'reference' => $match[1]];
        }

        if (preg_match('/^#(if|ifnot):(.+)$/', $name, $match) === 1) {
            $expression = trim($match[2]);
            if ($expression === '') {
                return null;
            }

            $condition = ConditionExpression::parse($expression);
            if ($condition->referenceName() === '') {
                return null;
            }

            return [
                'kind' => $match[1] === 'ifnot' ? 'IFNOT' : 'IF',
                'reference' => $expression,
            ];
        }

        return null;
    }

    /**
     * @param list<NativeObjectDescriptor> $nativeObjects
     * @param list<TemplateContractDiagnostic> $diagnostics
     */
    private function appendDuplicateNativeNameDiagnostics(
        array $nativeObjects,
        array &$diagnostics
    ): void {
        $groups = [];
        foreach ($nativeObjects as $object) {
            if ($object->name() === null) {
                continue;
            }

            $groups[$object->kind() . '|' . $object->name()][] = $object;
        }

        foreach ($groups as $objects) {
            if (count($objects) < 2) {
                continue;
            }

            foreach ($objects as $object) {
                $diagnostics[] = new TemplateContractDiagnostic(
                    'duplicate_native_name',
                    'warning',
                    'Multiple native objects of the same kind use the same authored name.',
                    $object->id(),
                    $object->provenance()
                );
            }
        }
    }

    private function regionDocument(DOMElement $carrier): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $imported = $dom->importNode($carrier, true);
        $dom->appendChild($imported);

        return $dom;
    }

    /**
     * @param array{
     *     source_part:string,
     *     region_kind:string,
     *     region_owner:?string,
     *     carrier:DOMElement,
     *     region_index:int
     * } $region
     * @param list<string> $nativeOwnerChain
     */
    private function provenance(
        array $region,
        string $representationKind,
        int $sourceOrder,
        string $sourceValue,
        ?string $physicalScope = null,
        array $nativeOwnerChain = []
    ): SourceProvenance {
        $seed = implode('|', [
            $region['source_part'],
            $region['region_kind'],
            $region['region_owner'] ?? '',
            $region['carrier']->nodeName,
            (string) $region['region_index'],
            $representationKind,
            (string) $sourceOrder,
            $sourceValue,
        ]);

        return new SourceProvenance(
            'e_' . substr(hash('sha256', $seed), 0, 16),
            $region['source_part'],
            $region['region_kind'],
            $region['region_owner'],
            $region['carrier']->nodeName,
            $representationKind,
            $sourceOrder,
            $physicalScope,
            $nativeOwnerChain
        );
    }

    /** @return list<string> */
    private function nativeOwnerChain(DOMNode $node, DOMElement $regionRoot): array
    {
        $owners = [];
        for ($current = $node->parentNode;
            $current !== null && $current !== $regionRoot;
            $current = $current->parentNode
        ) {
            if (!$current instanceof DOMElement) {
                continue;
            }

            if ($current->nodeName === 'text:section') {
                $name = $current->getAttribute('text:name');
                $owners[] = 'section:' . ($name !== '' ? $name : '<unnamed>');
            } elseif ($current->nodeName === 'table:table') {
                $name = $current->getAttribute('table:name');
                $owners[] = 'table:' . ($name !== '' ? $name : '<unnamed>');
            } elseif ($current->nodeName === 'draw:frame') {
                $name = $current->getAttribute('draw:name');
                $owners[] = 'frame:' . ($name !== '' ? $name : '<unnamed>');
            }
        }

        return array_reverse($owners);
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', self::DRAW_NAMESPACE);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        $xpath->registerNamespace('style', self::STYLE_NAMESPACE);
        $xpath->registerNamespace('table', self::TABLE_NAMESPACE);
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);

        return $xpath;
    }
}
