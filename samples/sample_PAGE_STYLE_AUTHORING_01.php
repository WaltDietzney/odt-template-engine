<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;

/**
 * PAGE-STYLE-AUTHORING-01: page styles and page layouts are created in PHP.
 * The input template contains only ordinary Writer content; First Page and
 * Continuation are not authored prerequisites.
 */
$template = new PageLayoutOdtTemplate(__DIR__ . '/../tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');

$template
    ->definePageLayout('PSA01_FirstLayout', [
        'page-width' => '21cm', 'page-height' => '29.7cm',
        'margin-top' => '1cm', 'margin-right' => '1.2cm',
        'margin-bottom' => '1cm', 'margin-left' => '1.2cm',
        'orientation' => 'portrait',
    ])
    ->definePageLayout('PSA01_ContinuationLayout', [
        'page-width' => '21cm', 'page-height' => '29.7cm',
        'margin-top' => '2cm', 'margin-right' => '1.8cm',
        'margin-bottom' => '2cm', 'margin-left' => '1.8cm',
        'orientation' => 'portrait',
    ])
    ->definePageStyle('PSA01_First', 'PSA01_FirstLayout', [
        'display-name' => 'Visual Designer First Page',
        'next-style-name' => 'PSA01_Continuation',
        'header' => (new Paragraph())->addText('CV Generator Pro · First Page'),
        'footer' => (new Paragraph())->addText('Confidential · Page '),
    ])
    ->definePageStyle('PSA01_Continuation', 'PSA01_ContinuationLayout', [
        'display-name' => 'Visual Designer Continuation',
        'header' => (new Paragraph())->addText('CV Generator Pro · Continuation'),
        'footer' => (new Paragraph())->addText('Confidential · Continuation'),
    ])
    ->assignPageStyle('P5', 'PSA01_First');

$rows = [];
for ($index = 1; $index <= 42; ++$index) {
    $rows[] = [
        'produkt' => sprintf('CV content block %02d — editable Writer paragraph and table content', $index),
        'preis' => sprintf('Section %02d', $index),
    ];
}

$template->assign([
    'name' => 'Programmatic Page Style Authoring',
    'date' => '29.10.2026',
]);
$template->assignRepeating('items', $rows);
$template->render();
$outputPath = getenv('PAGE_STYLE_AUTHORING_OUTPUT') ?: (__DIR__ . '/output/output_PAGE_STYLE_AUTHORING_01.odt');
$template->save($outputPath);
