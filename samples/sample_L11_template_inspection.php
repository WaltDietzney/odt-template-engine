<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L11_template_inspection.odt');
$contract = $template->inspectTemplate();

$summary = [
    'bindings' => array_map(static fn ($binding): array => [
        'kind' => $binding->kind(),
        'name' => $binding->variableName(),
        'source' => $binding->provenance()->sourcePart(),
    ], $contract->bindings()),
    'controls' => array_map(static fn ($control): array => [
        'kind' => $control->kind(),
        'representation' => $control->representation(),
        'support' => $control->supportState(),
    ], $contract->controls()),
    'native_objects' => array_map(static fn ($object): array => [
        'kind' => $object->kind(),
        'name' => $object->name(),
        'source' => $object->provenance()->sourcePart(),
    ], $contract->nativeObjects()),
    'dependencies' => array_map(static fn ($dependency): array => [
        'path' => $dependency->path(),
        'kind' => $dependency->kind(),
        'scope' => $dependency->scope()->kind(),
    ], $contract->dependencies()),
    'capabilities' => $contract->capabilities()->toArray(),
    'diagnostics' => array_map(static fn ($diagnostic): array => [
        'code' => $diagnostic->code(),
        'severity' => $diagnostic->severity(),
    ], $contract->diagnostics()),
];

// Use $contract->toArray() when the complete machine-readable contract is needed.
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
