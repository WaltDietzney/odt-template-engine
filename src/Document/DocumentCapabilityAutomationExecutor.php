<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use LogicException;
use OdtTemplateEngine\Mapping\Applicability;
use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Mapping\ConcretePreflightOperation;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\DocumentCapabilityResolution;
use OdtTemplateEngine\Mapping\EngineCapabilityCatalog;

/** Executes READY metadata capabilities through the existing MetadataManager. */
final class DocumentCapabilityAutomationExecutor
{
    public function execute(MetadataManager $metadataManager, ConcretePreflightResult $preflight): void
    {
        if (!$preflight->ready()) {
            throw new \InvalidArgumentException('Document capability automation requires a READY concrete preflight.');
        }

        $expected = [];
        foreach ($preflight->mappingResolution()->documentCapabilities() as $resolution) {
            $identity = spl_object_id($resolution);
            $expected[$identity] = ($expected[$identity] ?? 0) + 1;
        }

        $catalog = EngineCapabilityCatalog::phaseE1();
        $metadata = [];
        $targets = [];

        foreach ($preflight->operations() as $operation) {
            if ($operation->targetFamily() !== 'document_capability') {
                continue;
            }
            if ($operation->status() !== ConcretePreflightOperation::READY
                || !$operation->resolution() instanceof DocumentCapabilityResolution
            ) {
                throw new LogicException('READY preflight contains an invalid document-capability operation.');
            }

            $resolution = $operation->resolution();
            $resolutionId = spl_object_id($resolution);
            if (!isset($expected[$resolutionId])) {
                throw new LogicException('Document-capability operation is not part of the supplied mapping resolution.');
            }
            --$expected[$resolutionId];
            if ($expected[$resolutionId] === 0) {
                unset($expected[$resolutionId]);
            }

            $mapping = $resolution->mapping();
            $identity = $mapping->group() . '.' . $mapping->target();
            $payloadKind = $catalog->metadataPayloadKind($mapping->target(), $mapping->group());
            if ($resolution->status() !== DocumentCapabilityResolution::RESOLVED
                || $resolution->provenance() !== DocumentCapabilityResolution::EXPLICIT
                || $mapping->group() !== 'metadata'
                || $payloadKind === null
                || $operation->targetIdentity() !== $identity
                || $operation->capabilityId() !== $identity
                || $operation->payloadKind() !== $payloadKind
                || $operation->applicability() !== Applicability::APPLICABLE->value
            ) {
                throw new LogicException('Document-capability operation disagrees with its resolved READY evidence.');
            }

            $data = $resolution->dataResolution();
            if ($data->status() !== ApplicationDataResolution::PRESENT
                || $data->itemIndex() !== null
                || $data->items() !== []
            ) {
                throw new LogicException('READY metadata evidence must contain one PRESENT non-item value.');
            }

            $targetKey = $mapping->group() . "\0" . $mapping->target();
            if (isset($targets[$targetKey])) {
                throw new LogicException(sprintf('Multiple READY operations target %s.', $identity));
            }

            $targets[$targetKey] = true;
            $metadata[$mapping->target()] = $data->value();
        }

        if ($expected !== []) {
            throw new LogicException('READY preflight is missing one or more document-capability operations.');
        }

        if ($metadata !== []) {
            $metadataManager->set($metadata);
        }
    }
}
