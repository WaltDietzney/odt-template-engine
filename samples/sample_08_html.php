<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ImageElement;

/**
 * Sample 8 - HTML Import with Rich Structures
 *
 * Demonstrates importing an HTML block that includes:
 * - Headers (h1, h2) with inline styles
 * - Paragraphs with spans and strong formatting
 * - External and internal links
 * - Local images (only local paths supported)
 * - Inline style attributes (color, text-decoration, font-style)
 */

$template = new OdtTemplate('samples/templates/template_08_html.odt');
$template->load();

// 1️⃣ Define an HTML document as a string
$html = <<<HTML
<h1 style="color: #000080;">Welcome to OdtTemplateEngine 🌟</h1>

<p>This is a <span style="color: #008000;">dynamic document</span> generated using <strong>HTML structures</strong>.</p>

<h2 id="features" style="color: #2F4F4F;">Features</h2>

<p>Our engine supports:</p>

<ul>
    <li><strong>Paragraphs</strong> with different <span style="color: #FF0000;">colors</span> and <span style="font-style: italic;">styles</span></li>
    <li><strong>Links</strong> to <a href="https://example.com" style="color: #0000FF; text-decoration: underline;">external websites</a></li>
    <li><strong>Internal anchors</strong> like <a href="#contact" style="text-decoration: none; color: #800080;">Contact Section</a></li>
    <li><strong>Local images</strong> embedded directly from the project</li>
</ul>

<h2 id="gallery" style="color: #2F4F4F;">Gallery</h2>


<p>Here is an embedded image:</p>
<p><img src="assets/banner.png" width="6cm" height="3cm" style="display: block; margin: auto;"></p>

<h2 id="contact" style="color: #2F4F4F;">Contact</h2>

<p>You can reach us at: <a href="mailto:contact@example.com" style="color: #006400;">contact@example.com</a></p>

<p style="font-size: small;">© 2025 OdtTemplateEngine - All rights reserved.</p>
HTML;

// 2️⃣ Import the HTML into a RichText object
$rich = HtmlImporter::fromHtml($html);

// 3️⃣ Insert the RichText into the template at the {{html}} placeholder
$template->setElement('html', $rich);

// 4️⃣ Save the final document
$template->save('samples/output/output_08_html.odt');

echo "✅ Document successfully created: output_8_html.odt\n";
