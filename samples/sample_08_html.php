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

//require_once '../vendor/autoload.php';

$template = new OdtTemplate('samples/templates/template_08_html.odt');
$template->load();

// 1️⃣ Define an HTML document as a string
$html = <<<HTML
<h1 style="color: #000080;">Welcome to the Project Report 📘</h1>

<p>This document was automatically generated from <strong>HTML content</strong> using the <em>OdtTemplateEngine</em>. It demonstrates key features such as styling, structure, and media embedding.</p>

<h2 style="color: #2F4F4F;">Core Features</h2>

<p>Supported elements include:</p>

<ul>
    <li><strong>Styled text</strong> with <span style="color: #FF0000;">colors</span>, <span style="font-style: italic;">italics</span>, <span style="text-decoration: underline;">underlining</span>, and <mark>highlighting</mark></li>
    <li>Text can be <del>removed</del> or annotated with <sub>subscripts</sub> and <sup>superscripts</sup></li>
    <li>Clickable <a href="https://example.com" style="color: #0000FF;">hyperlinks</a> to external resources</li>
    <li>Local image embedding from your project files</li>
    <li>Remote image loading from the web</li>
    <li>Embedded base64 images (if enabled)</li>
    <li>Structured lists with nested items</li>
</ul>

<h2>Project Structure</h2>

<ol>
  <li>Introduction
    <ul>
      <li><strong>Goals</strong> of the project</li>
      <li>Initial <em>approach</em> and <u>planning</u></li>
    </ul>
  </li>
  <li>Analysis
    <ol>
      <li>Data Collection</li>
      <li>Evaluation
        <ul>
          <li><span style="color: #228B22;">Strengths</span></li>
          <li><span style="color: #B22222;">Weaknesses</span></li>
        </ul>
      </li>
    </ol>
  </li>
  <li>Conclusion</li>
</ol>

<h2>Image Showcase</h2>
<p>Embedded local image:</p>
<p><img src="assets/banner.png" width="6cm" height="3cm" style="display: block; margin: auto;"></p>

<p>Remote image (web-hosted):</p>
<p><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/PHP_Logo.svg/320px-PHP_Logo.svg.png" width="4cm"></p>

<p>Base64-encoded image (small placeholder):</p>
<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAMAAABhvppWAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAJUExURQAAAFVVVZqamgAAAJUjkLIAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAHdElNRQflDBgSKQfQ9Zn1AAAAMElEQVQI12NgQAX/GQgYGBgZ+f///3DwxGRkYHiEkhRCR8RQzMeQBNVCHDADqF8IAAzXWAgWYI0RQAAAAASUVORK5CYII=" width="1.2cm" height="1.2cm" /></p>

<h2 id="contact" style="color: #2F4F4F;">Contact</h2>

<p>For questions, reach us at: <a href="mailto:contact@example.com" style="color: #006400;">contact@example.com</a></p>

<p style="font-size: small;">© 2025 OdtTemplateEngine — Automatically generated with ❤️.</p>
HTML;



// 2️⃣ Import the HTML into a RichText object
$rich = HtmlImporter::fromHtml($html);

// 3️⃣ Insert the RichText into the template at the {{html}} placeholder
$template->setElement('html', $rich);

// 4️⃣ Save the final document
$template->save('samples/output/output_08_html.odt');

echo "✅ Document successfully created: output_8_html.odt\n";
