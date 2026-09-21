<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L09_native_objects.odt');

// Resolve and use only the bounded mutation supported by each native target.
$template->bookmark('ClientName')->replaceText('Aurora Studio');
$template->section('ProjectSummary')->replaceContent(
    (new RichText())->addParagraph(
        (new Paragraph())->addText('Project summary supplied by PHP.', ['bold' => true])
    )
);

// Tables and frames are addressable and inspectable, but remain read-only.
$table = $template->table('ProjectMilestones')->descriptor();
$frame = $template->frame('ProjectNote')->descriptor();

echo sprintf(
    "Table %s: %d rows, %s columns\nFrame %s: %s (%s × %s)\n",
    $table->name(),
    $table->rowCount(),
    $table->columnCount() ?? 'unknown',
    $frame->name(),
    $frame->payloadType(),
    $frame->width() ?? 'unspecified',
    $frame->height() ?? 'unspecified'
);

$template->save(__DIR__ . '/output/output_L09_native_objects.odt');
