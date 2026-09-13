<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Builds the bounded Slice-1 source contract from original template DOMs.
 *
 * Advanced dependency/control semantics are intentionally deferred to later
 * Phase-B slices.
 */
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
        $repetitionScopedEvidenceIds = [];
        $nativeObjects = [];
        $coverageRegions = [];

        foreach ($regions as $region) {
            $coverageRegions[] = [
                'source_part' => $region['source_part'],
                'region_kind' => $region['region_kind'],
                'region_owner' => $region['region_owner'],
                'carrier_kind' => $region['carrier']->nodeName,
            ];

            [$regionBindings, $regionRepetitionScopedEvidenceIds] = $this->bindingEvidence($region);
            foreach ($regionBindings as $binding) {
                $bindings[] = $binding;
            }
            foreach ($regionRepetitionScopedEvidenceIds as $evidenceId) {
                $repetitionScopedEvidenceIds[$evidenceId] = true;
            }

            foreach ($this->nativeObjectEvidence($region) as $nativeObject) {
                $nativeObjects[] = $nativeObject;
            }
        }

        [$bindings, $dependencies] = $this->projectRootDependencies(
            $bindings,
            $repetitionScopedEvidenceIds
        );

        return new TemplateContract(
            $bindings,
            [],
            $nativeObjects,
            $dependencies,
            [],
            new TemplateContractCoverage(
                $coverageRegions,
                ['meta.xml', 'settings.xml', 'META-INF/manifest.xml', 'embedded_objects']
            ),
            new TemplateContractCapabilities([
                'inspection' => TemplateContractCapabilities::READY,
                'dependency_mapping' => $repetitionScopedEvidenceIds === []
                    ? TemplateContractCapabilities::READY
                    : TemplateContractCapabilities::LIMITED,
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
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if ($child->namespaceURI !== self::STYLE_NAMESPACE) {
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
     * @return array{0:list<BindingDescriptor>,1:list<string>}
     */
    private function bindingEvidence(array $region): array
    {
        $inspection = (new TemplateStructureInspector())->inspect(
            $this->regionDocument($region['carrier'])
        );

        $bindings = [];
        $repetitionScopedEvidenceIds = [];
        $sourceOrder = 0;
        $foreachDepth = 0;

        foreach ($inspection->expressions() as $expression) {
            if ($expression->kind() === 'FOREACH_OPEN') {
                ++$foreachDepth;
                continue;
            }

            if ($expression->kind() === 'FOREACH_END') {
                $foreachDepth = max(0, $foreachDepth - 1);
                continue;
            }

            if (!in_array(
                $expression->kind(),
                ['SCALAR', 'FILTERED_SCALAR', 'SPECIAL'],
                true
            )) {
                continue;
            }

            $provenance = $this->provenance(
                $region,
                'visible_expression',
                $sourceOrder,
                $expression->rawText(),
                $expression->scope()
            );

            $bindings[] = new BindingDescriptor(
                $expression->kind(),
                $expression->rawText(),
                $expression->variableName(),
                $expression->filterName(),
                $expression->filterOption(),
                'SUPPORTED',
                $provenance
            );

            if ($foreachDepth > 0) {
                $repetitionScopedEvidenceIds[] = $provenance->evidenceId();
            }

            ++$sourceOrder;
        }

        return [$bindings, $repetitionScopedEvidenceIds];
    }

    /**
     * @param list<BindingDescriptor> $bindings
     * @param array<string, true> $repetitionScopedEvidenceIds
     * @return array{0:list<BindingDescriptor>,1:list<DependencyDescriptor>}
     */
    private function projectRootDependencies(
        array $bindings,
        array $repetitionScopedEvidenceIds
    ): array
    {
        $root = DataScopeDescriptor::root();
        $evidenceByName = [];

        foreach ($bindings as $binding) {
            $name = $binding->variableName();
            $evidenceId = $binding->provenance()->evidenceId();
            if ($name === null || $name === '' || isset($repetitionScopedEvidenceIds[$evidenceId])) {
                continue;
            }

            $evidenceByName[$name][] = $evidenceId;
        }

        $dependenciesByName = [];
        foreach ($evidenceByName as $name => $evidenceIds) {
            $dependenciesByName[$name] = new DependencyDescriptor(
                $this->dependencyId($root, 'VALUE', $name),
                'VALUE',
                $name,
                $root,
                $name,
                $evidenceIds
            );
        }

        $linkedBindings = [];
        foreach ($bindings as $binding) {
            $name = $binding->variableName();
            $evidenceId = $binding->provenance()->evidenceId();
            $dependency = $name !== null && !isset($repetitionScopedEvidenceIds[$evidenceId])
                ? ($dependenciesByName[$name] ?? null)
                : null;

            $linkedBindings[] = new BindingDescriptor(
                $binding->kind(),
                $binding->rawText(),
                $binding->variableName(),
                $binding->filterName(),
                $binding->filterOption(),
                $binding->supportState(),
                $binding->provenance(),
                $dependency?->id()
            );
        }

        return [$linkedBindings, array_values($dependenciesByName)];
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
