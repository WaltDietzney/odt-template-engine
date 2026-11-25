<?php

declare(strict_types=1);

require __DIR__ . '/../../v04/native/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$inputs = [
    'graphic-headings' => '../../v03/p2-v03-grouped.odt',
    'semantic-headings' => '../../v03/semantic/semantic-headings.odt',
    'rectangular-tags' => '../../v01/p2-v01-padding-spacing.odt',
];
foreach ($inputs as $name => $input) {
    $template = new OdtTemplate(__DIR__ . '/' . $input);
    $template->render();
    $template->save(__DIR__ . '/' . $name . '.odt');
    $template->cleanup();
}
file_put_contents(__DIR__ . '/inputs.json', json_encode($inputs, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/outputs.json', json_encode(array_keys($inputs)));
