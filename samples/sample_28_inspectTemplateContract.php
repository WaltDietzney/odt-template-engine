<?php

declare(strict_types=1);

/**
 * Sample 28: Unified Template Inspection
 *
 * Demonstrates TEMPLATE-AUTHORING-01B by loading a LibreOffice-authored ODT
 * template and printing the semantic TemplateContract discovered by
 * inspectTemplate().
 *
 * This sample is inspection-only. It does not render or mutate the template.
 */

use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$templatePath = dirname(__DIR__)
    . '/tests/fixtures/libreoffice-reference/odt/'
    . 'TEMPLATE-AUTHORING-01B-inspection-contract.odt';

$template = new OdtTemplate($templatePath);
$contract = $template->inspectTemplate();

echo "TEMPLATE-AUTHORING-01B — Unified Template Inspection\n";
echo str_repeat('=', 52) . "\n\n";

echo 'Contract version: ' . $contract->toArray()['contract_version'] . "\n\n";

echo "Coverage\n";
echo "--------\n";
foreach ($contract->coverage()->toArray() as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } elseif (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_SLASHES);
    }

    echo sprintf("%-28s %s\n", $key . ':', (string) $value);
}
echo "\n";

$dependencyPaths = [];
foreach ($contract->dependencies() as $dependency) {
    $dependencyPaths[$dependency->id()] = $dependency->path();
}

echo "Bindings\n";
echo "--------\n";
foreach ($contract->bindings() as $binding) {
    $provenance = $binding->provenance();
    $path = $binding->dependencyId() !== null
        ? ($dependencyPaths[$binding->dependencyId()] ?? $binding->variableName())
        : $binding->variableName();

    $source = $provenance->sourcePart();
    if ($provenance->regionOwner() !== null) {
        $source .= ' / ' . $provenance->regionOwner();
    }
    if ($provenance->carrierKind() !== null) {
        $source .= ' / ' . $provenance->carrierKind();
    }

    echo sprintf(
        "%-36s %-11s %s\n",
        $path,
        $binding->supportState(),
        $source
    );
}
echo "\n";

echo "Controls\n";
echo "--------\n";
foreach ($contract->controls() as $control) {
    $dependencies = array_map(
        static fn (string $id): string => $dependencyPaths[$id] ?? $id,
        $control->dependencyIds()
    );

    echo sprintf(
        "%-9s %-28s %-11s %s\n",
        $control->kind(),
        implode(', ', $dependencies),
        $control->supportState(),
        $control->representation()
    );
}
echo "\n";

echo "Native objects\n";
echo "--------------\n";
foreach ($contract->nativeObjects() as $object) {
    $ownerSuffix = $object->ownerIds() === []
        ? ''
        : ' owners=' . implode(' -> ', $object->ownerIds());

    echo sprintf(
        "%-10s %-28s %s%s\n",
        $object->kind(),
        $object->name() ?? '(unnamed)',
        $object->id(),
        $ownerSuffix
    );
}
echo "\n";

echo "Dependencies\n";
echo "------------\n";
foreach ($contract->dependencies() as $dependency) {
    echo sprintf(
        "%-40s %s\n",
        $dependency->path(),
        $dependency->kind()
    );
}
echo "\n";

echo "Capabilities\n";
echo "------------\n";
foreach ($contract->capabilities()->toArray() as $capability => $readiness) {
    echo sprintf("%-24s %s\n", $capability, $readiness);
}
echo "\n";

echo "Diagnostics\n";
echo "-----------\n";
if ($contract->diagnostics() === []) {
    echo "none\n";
} else {
    foreach ($contract->diagnostics() as $diagnostic) {
        echo sprintf(
            "[%s] %s: %s\n",
            strtoupper($diagnostic->severity()),
            $diagnostic->code(),
            $diagnostic->message()
        );
    }
}

echo "\nInspection complete. No rendering or document mutation performed.\n";
