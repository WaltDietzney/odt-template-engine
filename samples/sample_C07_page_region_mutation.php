<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;

$template = new PageLayoutOdtTemplate(__DIR__ . '/templates/template_C01_page_flow_layout.odt');
$template->definePageLayout('C07Layout', [
    'header' => ['min-height' => '1cm', 'background-color' => '#ddeeff'],
    'footer' => ['min-height' => '1cm'],
]);
$template->definePageStyle('C07Master', 'C07Layout', [
    'header' => (new Paragraph())->addText('Original header'),
]);
$template->updatePageStyle('C07Master', [
    'header' => (new Paragraph())->addText('Updated header'),
    'footer' => (new Paragraph())->addText('New footer'),
]);
$template->assignPageStyle('Standard', 'C07Master');
$template->appendBody((new Paragraph('Standard'))->addText('Native page region mutation'));
$template->save(getenv('C07_OUTPUT') ?: __DIR__ . '/output/output_C07_page_region_mutation.odt');
