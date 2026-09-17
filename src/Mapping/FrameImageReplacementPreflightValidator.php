<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use DOMDocument;
use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Document\FrameDescriptor;
use OdtTemplateEngine\Template\NativeObjectDescriptor;

/** @internal Checks the bounded named-frame replace-image payload and target. */
final class FrameImageReplacementPreflightValidator
{
    public const APPLICABLE = 'APPLICABLE';
    public const NOT_APPLICABLE = 'NOT_APPLICABLE';

    /**
     * @return array{applicability:string,diagnostics:list<ConcretePreflightDiagnostic>}
     */
    public function validate(
        ApplicationDataResolution $data,
        ?NativeObjectDescriptor $sourceDescriptor,
        DocumentInspection $workingDocument,
        string $identity,
        string $sourcePath
    ): array {
        $diagnostics = $this->payloadDiagnostics($data, $identity, $sourcePath);
        $applicability = self::NOT_APPLICABLE;
        if ($sourceDescriptor === null) {
            $diagnostics[] = $this->diagnostic(
                'ACTION_NOT_APPLICABLE',
                'TemplateContract frame target evidence is unavailable.',
                $identity,
                $sourcePath
            );
            return ['applicability' => $applicability, 'diagnostics' => $diagnostics];
        }

        $matches = array_values(array_filter(
            $workingDocument->frames(),
            static fn (FrameDescriptor $frame): bool => $frame->name() === $sourceDescriptor->name()
                && $frame->documentPart() === $sourceDescriptor->provenance()->sourcePart()
        ));
        if (count($matches) !== 1 || $matches[0]->payloadType() !== 'image') {
            $reason = count($matches) === 0
                ? 'The source-derived frame is absent from the current working document.'
                : (count($matches) > 1
                    ? 'The current working document does not uniquely resolve the source-derived frame.'
                    : 'Frame replace-image requires a direct draw:image child.');
            $diagnostics[] = $this->diagnostic(
                'ACTION_NOT_APPLICABLE',
                $reason,
                $identity,
                $sourcePath,
                ['applicability' => self::NOT_APPLICABLE]
            );
        } else {
            $applicability = self::APPLICABLE;
        }

        return ['applicability' => $applicability, 'diagnostics' => $diagnostics];
    }

    /** @return list<ConcretePreflightDiagnostic> */
    private function payloadDiagnostics(
        ApplicationDataResolution $data,
        string $identity,
        string $sourcePath
    ): array {
        if ($data->status() !== ApplicationDataResolution::PRESENT || $data->items() !== []) {
            return [];
        }
        $payload = $data->value();
        if (!is_array($payload) || !isset($payload['source']) || !is_string($payload['source'])) {
            return [$this->diagnostic(
                'INCOMPATIBLE_NATIVE_ACTION_PAYLOAD',
                'replace-image requires an image payload with a local source path.',
                $identity,
                $sourcePath
            )];
        }

        $diagnostics = [];
        $unknownKeys = array_diff(array_keys($payload), ['source', 'options']);
        if ($unknownKeys !== []) {
            $diagnostics[] = $this->diagnostic(
                'INVALID_REPLACEMENT_OPTION',
                'Image replacement payload contains unsupported fields.',
                $identity,
                $sourcePath,
                ['unsupported_fields' => implode(',', array_map('strval', $unknownKeys))]
            );
        }
        $options = array_key_exists('options', $payload) ? $payload['options'] : [];
        if (!is_array($options)) {
            $diagnostics[] = $this->diagnostic(
                'INVALID_REPLACEMENT_OPTION',
                'Image replacement options must be an associative array.',
                $identity,
                $sourcePath
            );
            $options = [];
        }
        foreach ($options as $key => $value) {
            if (!in_array($key, ['width', 'height'], true)) {
                $diagnostics[] = $this->diagnostic(
                    'INVALID_REPLACEMENT_OPTION',
                    'Only explicit width and height options are supported by the named-frame replacement boundary.',
                    $identity,
                    $sourcePath,
                    ['option' => is_scalar($key) ? $key : get_debug_type($key)]
                );
                continue;
            }
            if (!is_string($value) || !$this->validOdfLength($value)) {
                $diagnostics[] = $this->diagnostic(
                    'INVALID_REPLACEMENT_OPTION',
                    sprintf('Image option "%s" must be a positive ODF length.', $key),
                    $identity,
                    $sourcePath,
                    ['option' => $key]
                );
            }
        }

        $path = $payload['source'];
        if (!is_file($path) || !is_readable($path)) {
            $diagnostics[] = $this->diagnostic(
                'INVALID_IMAGE_SOURCE',
                'Image source must be an existing readable local file.',
                $identity,
                $sourcePath,
                ['image_path' => $path]
            );
            return $diagnostics;
        }
        if (!$this->isSupportedImage($path)) {
            $diagnostics[] = $this->diagnostic(
                'INVALID_IMAGE_SOURCE',
                'Image source is not a recognizably supported image file.',
                $identity,
                $sourcePath,
                ['image_path' => $path]
            );
        }
        return $diagnostics;
    }

    private function validOdfLength(string $value): bool
    {
        if (preg_match('/^((?:\d+(?:\.\d+)?|\.\d+))(cm|mm|in|pt|pc|px)$/', $value, $matches) !== 1) {
            return false;
        }
        return (float) $matches[1] > 0;
    }

    private function isSupportedImage(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'bmp', 'webp'], true)) {
            return false;
        }
        $image = @getimagesize($path);
        if (is_array($image)) {
            $expectedMime = match ($extension) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'webp' => 'image/webp',
                default => null,
            };
            return $expectedMime !== null && ($image['mime'] ?? null) === $expectedMime;
        }
        if ($extension !== 'svg') {
            return false;
        }
        $dom = new DOMDocument();
        return @$dom->load($path, LIBXML_NONET)
            && $dom->documentElement?->localName === 'svg'
            && $dom->documentElement->namespaceURI === 'http://www.w3.org/2000/svg';
    }

    /** @param array<string, scalar|null> $context */
    private function diagnostic(
        string $code,
        string $message,
        string $identity,
        string $sourcePath,
        array $context = []
    ): ConcretePreflightDiagnostic {
        return new ConcretePreflightDiagnostic($code, $message, 'native_action', $identity, $sourcePath, $context);
    }
}
