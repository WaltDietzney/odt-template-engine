<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L04_rich_content.odt');

$projectBrief = new RichText();
$projectBrief->addParagraph(
    (new Paragraph(null, ['margin-bottom' => '0.14cm']))
        ->addText('Project: ', ['bold' => true, 'color' => '#27616d'])
        ->addText('Aurora')
);
$projectBrief->addParagraph(
    (new Paragraph(null, ['margin-bottom' => '0.14cm']))
        ->addText('Status: ', ['bold' => true, 'color' => '#27616d'])
        ->addText('On track')
);
$summary = (new Paragraph(null, ['margin-bottom' => '0.14cm']))
    ->addText('Summary: ', ['bold' => true, 'color' => '#27616d'])
    ->addText('The team is preparing a clear, editable project brief.')
    ->addLineBreak()
    ->addText('Each paragraph and text run remains native Writer content.');
$projectBrief->addParagraph($summary);
$projectBrief->addParagraph(
    (new Paragraph(null, ['margin-bottom' => '0.14cm']))
        ->addText('More information: ')
        ->addHyperlink('Project notes', 'https://example.com/aurora', [
            'color' => '#1a5fb4',
            'underline' => true,
        ])
);

$template->setElement('project_brief', $projectBrief);
$template->save(__DIR__ . '/output/output_L04_rich_content.odt');
