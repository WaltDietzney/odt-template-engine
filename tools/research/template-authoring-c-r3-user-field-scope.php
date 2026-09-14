<?php

declare(strict_types=1);

use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$input = $argv[1] ?? dirname(__DIR__, 2) . '/research/08-foreach-section-user-field.odt';
$output = $argv[2] ?? sys_get_temp_dir() . '/odt-template-authoring-c-r3-result.odt';

if (!is_file($input)) {
    fwrite(STDERR, "Input fixture not found: {$input}\n");
    exit(1);
}

$template = new OdtTemplate($input);

$before = $template->inspectTemplate()->toArray();

$template
    ->section('#foreach:experience')
    ->instantiateMany([
        [
            'position' => 'Projektleiter',
            'company' => 'Firma A',
        ],
        [
            'position' => 'Entwickler',
            'company' => 'Firma B',
        ],
    ]);

$template->save($output);

$reopened = new OdtTemplate($output);
$live = $reopened->inspect()->toArray();

echo "TEMPLATE-AUTHORING-01C C-R3 — User Field collection-scope research\n";
echo str_repeat('=', 70) . "\n\n";
echo "Input:  {$input}\n";
echo "Output: {$output}\n\n";

echo "Original authored contract remains source-oriented:\n";
echo json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Saved/reopened live native inspection:\n";
echo json_encode($live, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Next manual/XML check:\n";
echo "- inspect cloned #foreach:experience_* Sections\n";
echo "- verify each clone contains the User Field reference\n";
echo "- verify cloned references keep the same text:name\n";
echo "- verify no item-local User Field declaration was synthesized\n";
echo "- compare {{company}} item-local values against the native User Field value\n";
