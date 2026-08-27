<?php

/**
 * Sample 23 - Replace a native ODF section with structured CV content.
 *
 * The section itself remains authored in LibreOffice. PHP replaces only its
 * children through the typed SectionTarget API.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_23_sectionContentReplacement.odt');

$content = new RichText();
$content->addParagraph(
    (new Paragraph())->addText('Marketing-Spezialist | Unternehmen, Ort', [
        'bold' => true,
        'font-size' => '10.5pt',
    ])
);
$content->addParagraph(
    (new Paragraph())->addText('Datum - datum', [
        'bold' => true,
        'font-size' => '8.5pt',
    ])
);

$tasks = new ListElement('bullet');
foreach ([
    'Erstellung von digitalen Marketingkampagnen (Social Media, E-Mail, SEO), die zu einer Umsatzsteigerung von 15 % führten.',
    'Zusammenarbeit mit internen Teams und externen Agenturen zur erfolgreichen Durchführung von Veranstaltungen und Kampagnen.',
    'Analyse von Markttrends und Durchführung von Wettbewerbsanalysen.',
] as $task) {
    $tasks->addItem((new Paragraph())->addText($task, [
        'font-size' => '8.5pt',
    ]));
}
$content->addElement($tasks);

$template->section('ProfileSection')->replaceContent($content);

$outputPath = __DIR__ . '/output/output_23_sectionContentReplacement.odt';
$template->save($outputPath);

echo "✅ samples/output/output_23_sectionContentReplacement.odt generated successfully\n";
