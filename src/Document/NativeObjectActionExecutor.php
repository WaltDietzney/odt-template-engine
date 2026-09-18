<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Mapping\ConcretePreflightOperation;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\ImageReplacementPreflightPayload;
use OdtTemplateEngine\Mapping\NativeObjectActionResolution;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\SourceProvenance;
use OdtTemplateEngine\Template\TemplateContract;

/** Executes only explicit native-object actions selected by READY Phase-E evidence. */
final class NativeObjectActionExecutor
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function execute(
        OdtDocumentContext $context,
        OdtPackage $package,
        TemplateContract $contract,
        ConcretePreflightResult $preflight
    ): void {
        if (!$preflight->ready()) {
            throw new \InvalidArgumentException('Native object automation requires a READY concrete preflight.');
        }

        $descriptors = $this->nativeObjectIndex($contract);
        $expectedResolutions = [];
        foreach ($preflight->mappingResolution()->nativeObjectActions() as $resolution) {
            $expectedResolutions[spl_object_id($resolution)] = true;
        }
        $plans = [];
        foreach ($preflight->operations() as $operation) {
            if ($operation->targetFamily() !== 'native_action') {
                continue;
            }
            if ($operation->status() !== ConcretePreflightOperation::READY
                || !$operation->resolution() instanceof NativeObjectActionResolution
            ) {
                throw new \LogicException('READY preflight contains an invalid native-action operation.');
            }

            $resolution = $operation->resolution();
            $resolutionId = spl_object_id($resolution);
            if (!isset($expectedResolutions[$resolutionId])) {
                throw new \LogicException('Native action operation is not part of the supplied mapping resolution.');
            }
            unset($expectedResolutions[$resolutionId]);
            $mapping = $resolution->mapping();
            $identity = $mapping->targetKind() . ':' . $mapping->targetName();
            if ($operation->targetIdentity() !== $identity
                || $operation->capabilityId() !== $mapping->actionId()
                || $resolution->provenance() !== NativeObjectActionResolution::EXPLICIT
            ) {
                throw new \LogicException('Native action operation disagrees with its resolved explicit mapping.');
            }

            $key = $mapping->targetKind() . "\0" . $mapping->targetName();
            $descriptorMatches = $descriptors[$key] ?? [];
            if (count($descriptorMatches) !== 1) {
                throw new \LogicException('Resolved native action does not identify exactly one TemplateContract object.');
            }
            $descriptor = $descriptorMatches[0];
            $value = $this->presentValue($resolution->dataResolution(), $identity);
            $plan = $this->prepareAction($context, $package, $descriptor, $mapping->actionId(), $value);
            $plans[] = $plan;
        }

        if ($expectedResolutions !== []) {
            throw new \LogicException('READY preflight is missing one or more resolved native action operations.');
        }
        $this->validateImageResourcePaths($package, $plans);
        $this->assertNoDestructiveInterference($plans);

        foreach ($plans as $plan) {
            switch ($plan['action']) {
                case 'replace-content':
                    if ($plan['target'] instanceof SectionTarget) {
                        $plan['target']->replaceContent($plan['value']);
                    } else {
                        (new SectionMutationService())->replaceContentInWorkingTarget(
                            $plan['target'],
                            $plan['value'],
                            $package
                        );
                    }
                    break;
                case 'replace-text':
                    if ($plan['target'] instanceof BookmarkTarget) {
                        $plan['target']->replaceText($plan['value']);
                    } else {
                        (new BookmarkMutationService())->replaceTextInWorkingRegion(
                            $plan['document'],
                            $plan['target'],
                            $plan['descriptor']->name(),
                            $plan['value']
                        );
                    }
                    break;
                case 'replace-image':
                    $this->replaceImage($package, $plan['frame'], $plan['image']);
                    break;
                default:
                    throw new \LogicException('Unsupported native object action reached E4 execution.');
            }
        }
    }

    /** @return array<string,list<NativeObjectDescriptor>> */
    private function nativeObjectIndex(TemplateContract $contract): array
    {
        $index = [];
        foreach ($contract->nativeObjects() as $descriptor) {
            if ($descriptor->name() !== null) {
                $index[$descriptor->kind() . "\0" . $descriptor->name()][] = $descriptor;
            }
        }
        return $index;
    }

    private function presentValue(ApplicationDataResolution $data, string $identity): mixed
    {
        if ($data->status() !== ApplicationDataResolution::PRESENT || $data->items() !== []) {
            throw new \LogicException(sprintf('READY native action %s has no concrete PRESENT payload.', $identity));
        }

        return $data->value();
    }

    /** @return array<string,mixed> */
    private function prepareAction(
        OdtDocumentContext $context,
        OdtPackage $package,
        NativeObjectDescriptor $descriptor,
        string $action,
        mixed $value
    ): array {
        $provenance = $descriptor->provenance();
        if ($action === 'replace-content') {
            if (!$value instanceof OdtElement) {
                throw new \LogicException('READY Section replace-content payload is not an OdtElement.');
            }
            $target = (new SectionWorkingTargetResolver())->resolve($context, $descriptor);
            if ($provenance->sourcePart() === 'content.xml' && $provenance->regionKind() === 'BODY') {
                $target = (new TypedTargetResolver())->resolveSection($context, $descriptor->name(), $package);
            }
            return ['action' => $action, 'descriptor' => $descriptor, 'value' => $value, 'target' => $target];
        }
        if ($action === 'replace-text') {
            if (!is_string($value)) {
                throw new \LogicException('READY Bookmark replace-text payload is not a string.');
            }
            [$document, $regionRoot] = $this->workingRegion($context, $provenance);
            $target = $regionRoot;
            if ($provenance->sourcePart() === 'content.xml' && $provenance->regionKind() === 'BODY') {
                $target = (new TypedTargetResolver())->resolveBookmark($context, $descriptor->name());
            }
            return [
                'action' => $action,
                'descriptor' => $descriptor,
                'value' => $value,
                'target' => $target,
                'document' => $document,
            ];
        }
        if ($action !== 'replace-image' || $descriptor->kind() !== 'frame') {
            throw new \LogicException('Unsupported or contradictory native object action evidence.');
        }

        $payload = ImageReplacementPreflightPayload::fromApplicationValue($value);
        if ($payload === null || $payload->unsupportedFields() !== [] || !$payload->optionsWereArray()) {
            throw new \LogicException('READY Frame replace-image payload is not a valid bounded image payload.');
        }
        $image = $this->prepareImage($payload);
        $frame = $this->locateFrame($context, $descriptor);

        return [
            'action' => $action,
            'descriptor' => $descriptor,
            'value' => $value,
            'frame' => $frame,
            'image' => $image,
        ];
    }

    /** @return array{path:string,width:?string,height:?string} */
    private function prepareImage(ImageReplacementPreflightPayload $payload): array
    {
        $path = $payload->sourcePath();
        if (!is_file($path) || !is_readable($path)) {
            throw new \LogicException('READY frame image source is no longer available.');
        }
        $options = $payload->options();
        foreach ($options as $name => $value) {
            if (!in_array($name, ['width', 'height'], true)
                || !is_string($value)
                || preg_match('/^((?:\d+(?:\.\d+)?|\.\d+))(cm|mm|in|pt|pc|px)$/', $value, $matches) !== 1
                || (float) $matches[1] <= 0
            ) {
                throw new \LogicException('READY frame image payload contains an invalid dimensional option.');
            }
        }

        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        if (($width === null) xor ($height === null)) {
            $ratio = $this->intrinsicRatio($path);
            if ($ratio === null || $ratio <= 0 || !is_finite($ratio)) {
                throw new \LogicException('One-dimensional Frame replacement requires a determinable intrinsic image ratio.');
            }
            if ($width !== null) {
                [$number, $unit] = $this->lengthParts($width);
                $derived = $this->formatLength($number / $ratio);
                if ((float) $derived <= 0) {
                    throw new \LogicException('Intrinsic-ratio calculation produced an unrepresentable positive height.');
                }
                $height = $derived . $unit;
            } else {
                [$number, $unit] = $this->lengthParts($height);
                $derived = $this->formatLength($number * $ratio);
                if ((float) $derived <= 0) {
                    throw new \LogicException('Intrinsic-ratio calculation produced an unrepresentable positive width.');
                }
                $width = $derived . $unit;
            }
        }

        return ['path' => $path, 'width' => $width, 'height' => $height];
    }

    private function intrinsicRatio(string $path): ?float
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension !== 'svg') {
            $size = @getimagesize($path);
            if (!is_array($size) || ($size[0] ?? 0) <= 0 || ($size[1] ?? 0) <= 0) {
                return null;
            }
            return $size[0] / $size[1];
        }

        $dom = new DOMDocument();
        if (!@$dom->load($path, LIBXML_NONET)
            || $dom->documentElement?->localName !== 'svg'
            || $dom->documentElement->namespaceURI !== 'http://www.w3.org/2000/svg'
        ) {
            return null;
        }
        $viewBox = trim($dom->documentElement->getAttribute('viewBox'));
        if ($viewBox !== ''
            && preg_match('/^\s*[-+]?(?:\d+(?:\.\d*)?|\.\d+)[,\s]+[-+]?(?:\d+(?:\.\d*)?|\.\d+)[,\s]+(\d+(?:\.\d*)?|\.\d+)[,\s]+(\d+(?:\.\d*)?|\.\d+)\s*$/', $viewBox, $matches) === 1
            && (float) $matches[1] > 0
            && (float) $matches[2] > 0
        ) {
            return (float) $matches[1] / (float) $matches[2];
        }

        $dimensions = [];
        foreach (['width', 'height'] as $attribute) {
            $raw = trim($dom->documentElement->getAttribute($attribute));
            if (preg_match('/^(\d+(?:\.\d+)?|\.\d+)([A-Za-z%]*)$/', $raw, $matches) !== 1
                || (float) $matches[1] <= 0
                || $matches[2] === '%'
            ) {
                return null;
            }
            $dimensions[] = [(float) $matches[1], strtolower($matches[2])];
        }
        if ($dimensions[0][1] !== $dimensions[1][1]) {
            return null;
        }

        return $dimensions[0][0] / $dimensions[1][0];
    }

    /** @return array{float,string} */
    private function lengthParts(string $length): array
    {
        preg_match('/^((?:\d+(?:\.\d+)?|\.\d+))(cm|mm|in|pt|pc|px)$/', $length, $matches);
        return [(float) $matches[1], $matches[2]];
    }

    private function formatLength(float $value): string
    {
        return rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
    }

    private function replaceImage(OdtPackage $package, DOMElement $frame, array $image): void
    {
        $created = $package->copyImageResourcesAtomically([['path' => $image['path']]]);
        try {
            (new FrameImageReplacementService())->updateFrame(
                $frame,
                'Pictures/' . basename($image['path']),
                $image['width'],
                $image['height']
            );
        } catch (\Throwable $exception) {
            $package->removePreparedPackageFiles($created);
            throw $exception;
        }
    }

    /** @param list<array<string,mixed>> $plans */
    private function validateImageResourcePaths(OdtPackage $package, array $plans): void
    {
        $destinations = [];
        foreach ($plans as $plan) {
            if ($plan['action'] !== 'replace-image') {
                continue;
            }
            $path = $plan['image']['path'];
            $destination = 'Pictures/' . basename($path);
            $hash = hash_file('sha256', $path);
            if ($hash === false) {
                throw new \LogicException('READY frame image source cannot be hashed.');
            }
            if (isset($destinations[$destination]) && !hash_equals($destinations[$destination], $hash)) {
                throw new \LogicException(sprintf('Selected Frame actions conflict at package resource path %s.', $destination));
            }
            $destinations[$destination] = $hash;
            $existing = $package->path($destination);
            if (is_file($existing)) {
                $existingHash = hash_file('sha256', $existing);
                if ($existingHash === false || !hash_equals($existingHash, $hash)) {
                    throw new \LogicException(sprintf('Package resource path %s already contains different content.', $destination));
                }
            }
        }
    }

    private function locateFrame(OdtDocumentContext $context, NativeObjectDescriptor $descriptor): DOMElement
    {
        [$document, $regionRoot] = $this->workingRegion($context, $descriptor->provenance());
        $xpath = new DOMXPath($document);
        foreach ([
            'draw' => self::DRAWING_NAMESPACE,
            'office' => self::OFFICE_NAMESPACE,
            'style' => self::STYLE_NAMESPACE,
            'table' => self::TABLE_NAMESPACE,
            'text' => self::TEXT_NAMESPACE,
        ] as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }
        $nodes = $xpath->query('.//text:section | .//text:bookmark | .//text:bookmark-start | .//table:table | .//draw:frame', $regionRoot);
        $node = $nodes?->item($descriptor->provenance()->sourceOrder());
        $sameNameFrames = $xpath->query('.//draw:frame[@draw:name=' . $this->xpathLiteral($descriptor->name() ?? '') . ']', $regionRoot);
        if (!$node instanceof DOMElement
            || $node->nodeName !== 'draw:frame'
            || $node->getAttribute('draw:name') !== $descriptor->name()
            || $sameNameFrames === false
            || $sameNameFrames->length !== 1
            || $this->ownerChain($node, $regionRoot) !== $descriptor->provenance()->nativeOwnerChain()
        ) {
            throw new \LogicException('Resolved Frame cannot be deterministically localized in its source-derived working region.');
        }
        $images = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'draw:image') {
                $images[] = $child;
            }
        }
        if (count($images) !== 1) {
            throw new \LogicException('Resolved Frame no longer has exactly one direct draw:image child.');
        }

        return $node;
    }

    /** @return array{0:DOMDocument,1:DOMElement} */
    private function workingRegion(OdtDocumentContext $context, SourceProvenance $provenance): array
    {
        if ($provenance->sourcePart() === 'content.xml'
            && $provenance->regionKind() === 'BODY'
            && $provenance->regionOwner() === null
            && $provenance->carrierKind() === 'office:text'
        ) {
            $document = $context->contentDom();
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
            $root = $xpath->query('/office:document-content/office:body/office:text')->item(0);
            if ($root instanceof DOMElement) {
                return [$document, $root];
            }
        }
        if ($provenance->sourcePart() === 'styles.xml'
            && $provenance->regionKind() === 'MASTER_PAGE_CONTENT'
            && $provenance->regionOwner() !== null
            && in_array($provenance->carrierKind(), ['style:header', 'style:footer'], true)
        ) {
            $document = $context->stylesDom();
            foreach ($document->getElementsByTagNameNS(self::STYLE_NAMESPACE, 'master-page') as $master) {
                if (!$master instanceof DOMElement || $master->getAttribute('style:name') !== $provenance->regionOwner()) {
                    continue;
                }
                foreach ($master->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->nodeName === $provenance->carrierKind()) {
                        return [$document, $child];
                    }
                }
            }
        }

        throw new \LogicException('Resolved native-target provenance is unsupported or contradictory.');
    }

    /** @return list<string> */
    private function ownerChain(DOMNode $node, DOMElement $regionRoot): array
    {
        $owners = [];
        for ($current = $node->parentNode; $current !== null && $current !== $regionRoot; $current = $current->parentNode) {
            if (!$current instanceof DOMElement) {
                continue;
            }
            $identity = match ($current->nodeName) {
                'text:section' => ['section', $current->getAttribute('text:name')],
                'table:table' => ['table', $current->getAttribute('table:name')],
                'draw:frame' => ['frame', $current->getAttribute('draw:name')],
                default => null,
            };
            if ($identity !== null) {
                $owners[] = $identity[0] . ':' . ($identity[1] !== '' ? $identity[1] : '<unnamed>');
            }
        }
        return array_reverse($owners);
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }
        return "concat('" . str_replace("'", "',\"'\",'", $value) . "')";
    }

    /** @param list<array<string,mixed>> $plans */
    private function assertNoDestructiveInterference(array $plans): void
    {
        foreach ($plans as $sectionPlan) {
            if ($sectionPlan['action'] !== 'replace-content') {
                continue;
            }
            $sectionId = $sectionPlan['descriptor']->id();
            foreach ($plans as $otherPlan) {
                if ($otherPlan === $sectionPlan) {
                    continue;
                }
                if (in_array($sectionId, $otherPlan['descriptor']->ownerIds(), true)) {
                    throw new \LogicException(sprintf(
                        'Section replace-content target %s would destroy selected %s target %s.',
                        $sectionPlan['descriptor']->name(),
                        $otherPlan['descriptor']->kind(),
                        $otherPlan['descriptor']->name() ?? '<unnamed>'
                    ));
                }
            }
        }
    }
}
