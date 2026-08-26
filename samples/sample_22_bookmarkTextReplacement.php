<?php

/**
 * Sample 22 - Safe Text Replacement in Native ODF Bookmarks
 *
 * This sample demonstrates typed bookmark addressing without XPath or direct
 * XML manipulation. The template was authored in LibreOffice and contains
 * existing formatting around the Position bookmark.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_22_bookmarkTextReplacement.odt');

// Replace text through native bookmark identities. Existing template
// formatting, including the Position span, remains owned by the ODT template.
$template->bookmark('FullName')->replaceText('Walter Dietz');
$template->bookmark('Position')->replaceText('ODT Template Engine Developer');
$template->bookmark('Location')->replaceText('Herford');

$outputPath = __DIR__ . '/output/output_22_bookmarkTextReplacement.odt';
$template->save($outputPath);

echo "✅ samples/output/output_22_bookmarkTextReplacement.odt generated successfully\n";
