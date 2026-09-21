<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L08_html_import.odt');
$localImage = __DIR__ . '/../assets/banner.png';
$embeddedImage = 'data:image/png;base64,' . base64_encode((string) file_get_contents(__DIR__ . '/../assets/Logo.png'));

$html = <<<HTML
<h1 style="color: #27616d;">Project Report: Aurora</h1>
<p style="margin-bottom: 0.3cm; text-align: justify;">Aurora is a compact example of a <strong>substantial HTML fragment</strong> translated into <em>editable Writer content</em>. The importer creates native ODT structures rather than storing a screenshot or opaque HTML.</p>
<h2>Visual references</h2>
<p>The first image is loaded from a local readable project asset:</p>
<p><img src="{$localImage}" width="5.2cm" height="3.5cm"></p>
<p>The second image is embedded as a data URL:</p>
<p><img src="{$embeddedImage}" width="2.4cm" height="2.4cm"></p>
<h2>Executive summary</h2>
<p>The team is <strong>on track</strong>, with <u>reviewable milestones</u> and a <mark>clear delivery plan</mark>. One early assumption was <del>to preserve HTML as markup</del>; the document now contains native paragraphs and tables. Water is H<sub>2</sub>O and the release is scheduled for Q<sup>3</sup>.</p>
<p>Implementation notes:<br><code>HtmlImporter::fromHtml(\$html)</code> turns controlled markup into ODT elements.</p>
<div style="background-color: #e9f2f3; border: 0.03cm solid #91b7bd; padding: 0.25cm; margin-top: 0.2cm; margin-bottom: 0.2cm;">
  <strong>Project principle</strong><br>Keep content semantic, editable, and easy to review.
</div>
<blockquote>“A useful document is structured content first, and presentation second.”</blockquote>
<h2>Work plan</h2>
<ul>
  <li><strong>Prepare</strong> — confirm requirements and gather source material</li>
  <li><strong>Build</strong> — create the Writer shell and import the report</li>
  <li><strong>Review</strong> — approve the editable result</li>
</ul>
<p>Approval sequence:</p>
<ol><li>Approve the content</li><li>Deliver the editable ODT</li></ol>
<pre>composer require waltdietzney/odt-template-engine</pre>
<h2>Team overview</h2>
<table style="border: 0.03cm solid #cbd7dc;">
  <thead>
    <tr>
      <th style="background-color: #27616d; color: #ffffff; padding: 0.16cm; text-align: left;">Contributor</th>
      <th style="background-color: #27616d; color: #ffffff; padding: 0.16cm; text-align: left;">Focus</th>
      <th style="background-color: #27616d; color: #ffffff; padding: 0.16cm; text-align: left;">Status</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="background-color: #f1f6f7; padding: 0.14cm;"><strong>Anna</strong></td>
      <td style="padding: 0.14cm;">Content and review</td>
      <td style="padding: 0.14cm;"><span style="color: #245d3c;">Ready</span></td>
    </tr>
    <tr>
      <td style="background-color: #f1f6f7; padding: 0.14cm;">Ben</td>
      <td style="padding: 0.14cm;">Template and import</td>
      <td style="padding: 0.14cm;">In progress</td>
    </tr>
  </tbody>
</table>
<h2>Delivery notes</h2>
<p>The table is an editable Writer table, while paragraphs and list items remain native document content. This supports ordinary review and follow-up editing after generation.</p>
<ul><li>Keep the source fragment under application control</li><li>Validate image sources before enabling remote access</li></ul>
<h2>Further information</h2>
<p>Read the <a href="https://example.com/aurora">project notes</a> or contact the delivery team.</p>
HTML;

// Remote images are disabled by default. Trusted callers may opt in explicitly:
// HtmlImporter::fromHtml($html, ['allow_remote_images' => true]);
// This public sample intentionally performs no network request.
$content = HtmlImporter::fromHtml($html);
$template->setElement('imported_report', $content);
$template->save(__DIR__ . '/output/output_L08_html_import.odt');
