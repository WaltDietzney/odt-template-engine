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

            foreach ($this->nativeObjectEvidence($region) as $nativeObject) {
                $nativeObjects[] = $nativeObject;
            }
        }

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
                'CLASSIC',
                'SUPPORTED',
                $state['scope'],
                $state['marker_evidence'],
                $state['dependency_ids'],
                $state['created_scope']
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
     * @return list<NativeObjectDescriptor>
     */
    private function nativeObjectEvidence(array $region): array
    {
        $xpath = $this->xpath($region['carrier']->ownerDocument);
        $query = './/text:section'
            . ' | .//text:bookmark'
            . ' | .//text:bookmark-start'
            . ' | .//table:table'
            . ' | .//draw:frame';

        $objects = [];
        $sourceOrder = 0;
        foreach ($xpath->query($query, $region['carrier']) ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            [$kind, $attribute] = match ($node->nodeName) {
                'text:section' => ['section', 'text:name'],
                'text:bookmark', 'text:bookmark-start' => ['bookmark', 'text:name'],
                'table:table' => ['table', 'table:name'],
                'draw:frame' => ['frame', 'draw:name'],
                default => [null, null],
            };

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

            $objects[] = new NativeObjectDescriptor($kind, $name, $provenance);
            ++$sourceOrder;
        }

        return $objects;
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
