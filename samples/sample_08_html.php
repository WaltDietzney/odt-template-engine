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
<h1 style="color: #000080;">Welcome to the Project Report 📘</h1>
<p style="margin-bottom: 0.5cm; text-align: justify;font-family: Calibri">This document was automatically generated from 
<strong>HTML content</strong> using the <em>OdtTemplateEngine</em>. 
It demonstrates key features such as <u>styling</u>, <mark>highlighting</mark>, <del>deletions</del>, 
<sub>subscripts</sub>, and <sup>superscripts</sup>.</p>
<h2 style="color: #2F4F4F; margin-top: 1cm;">Core Features</h2>
<p style="text-align: left;">Supported elements include:</p>
<!-- Test: DIV with paragraph AND text styles -->
<div style="color: #990000; background-color: #fdf2f2; padding: 0.3cm; border: 0.03cm solid #990000; 
            margin-top: 0.5cm; margin-bottom: 0.5cm; text-align: center;">
  This is a <strong>styled</strong> div block with padding, border and alignment.
</div>
<ul>
  <li><strong>Styled text</strong> with 
    <span style="color: #FF0000;">colors</span>, 
    <span style="font-style: italic;">italics</span>, 
    <span style="text-decoration: underline;">underlining</span>, and 
    <mark>highlighting</mark>
  </li>
  <li>Text can be <del>removed</del> or annotated with 
    <sub>subscripts</sub> and <sup>superscripts</sup>
  </li>
  <li>Semantically highlighted code such as 
    <code>let x = 10;</code> or inline <tt>typewriter</tt> style
  </li>
  <li>Keyboard input: <kbd>Ctrl</kbd> + <kbd>S</kbd></li>
  <li>Sample output: <samp>Hello, user!</samp></li> 
  <li>Preformatted block:</li>
  <pre style="font-family: monospace; background-color: #f0f0f0; padding: 0.2cm;">
function helloWorld() {
    console.log("Hello World!");
}
  </pre>
  <li>Clickable <a href="https://example.com" style="color: #0000FF;">hyperlinks</a> to external resources</li>
  <li>Local image embedding from your project files</li>
  <li>Remote image loading from the web</li>
  <li>Embedded base64 images (if enabled)</li>
  <li>Structured lists with nested items</li>
</ul>
<h2 style="margin-top: 1cm;">Project Structure</h2>
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
<p>An example of an embedded image:</p>
<p><img src="assets/banner.png" width="6cm" height="3cm" style="display: block; margin: auto;"></p>
<h2 id="contact" style="color: #2F4F4F;">Contact</h2>
<p style="font-size: small; text-align: center;">For questions, reach us at: 
<a href="mailto:contact@example.com" style="color: #006400;">contact@example.com</a></p>
<p style="font-size: small; text-align: center;">© 2025 OdtTemplateEngine — Automatically generated with ❤️.</p>
HTML;

// 2️⃣ Import the HTML into a RichText object
$rich = HtmlImporter::fromHtml($html);

// 3️⃣ Insert the RichText into the template at the {{html}} placeholder
$template->setElement('html', $rich);

// 4️⃣ Save the final document
$template->save('samples/output/output_08_html.odt');

echo "✅ Document successfully created: output_8_html.odt\n";
