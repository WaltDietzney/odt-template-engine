<?php
/**
 * Sample 19: HTML Table Import
 *
 * Demonstrates how to convert an HTML table into rich ODT content using HtmlImporter
 * and inject it into an ODT template.
 */

use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;

// Load the ODT template.
$template = new OdtTemplate('samples/templates/template_19_htmlTable.odt');
$template->load();

// Define HTML containing a styled table and supporting text.
$html = <<<HTML
<h2 style="font-family: Georgia, serif; color: #2c3e50;">Team Overview Table</h2>
<p style="font-size: 11pt; font-family: Georgia, serif;">
    This styled table provides a quick overview of team members, their roles, and current status. Colors and font styles are used
    to enhance readability and highlight important information at a glance.
</p>
<p><p>
<table>
    <thead>
        <tr>
            <th style="background-color:#2c3e50; color:#ffffff; text-align:left; font-size:12pt; font-family:'Arial';">Name</th>
            <th style="background-color:#34495e; color:#ffffff; text-align:center; font-size:12pt; font-family:'Courier New';">Role</th>
            <th style="background-color:#34495e; color:#ffffff; text-align:right; font-size:12pt; font-family:'Verdana';">Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="background-color:#ecf0f1; text-align:left; font-size:11pt; font-family:'Arial';">Alice</td>
            <td style="background-color:#d6eaf8; text-align:center; font-size:11pt; font-family:'Courier New';">Developer</td>
            <td style="background-color:#d5f5e3; color:#008000; text-align:right; font-weight:bold; font-size:11pt;">Active</td>
        </tr>
        <tr>
            <td style="background-color:#ecf0f1; text-align:left; font-size:11pt; font-family:'Arial';">Bob</td>
            <td style="background-color:#d6eaf8; text-align:center; font-size:11pt; font-family:'Courier New';">Designer</td>
            <td style="background-color:#fcf3cf; color:#FFA500; text-align:right; font-weight:bold; font-size:11pt;">Pending</td>
        </tr>
    </tbody>
</table>
<p><p>
<p style="font-size:10pt; font-family:Georgia, serif;">
    <strong>Legend:</strong><br>
    <span style="color:#008000; font-weight:bold;">Active</span> – The team member is currently active.<br>
    <span style="color:#FFA500; font-weight:bold;">Pending</span> – The team member is not yet active or under review.<br>
    Background colors are used to visually separate different columns and enhance focus.
</p>
HTML;

// Convert the HTML fragment into rich ODT content.
$richText = HtmlImporter::fromHtml($html);
$template->setElement('tableblock', $richText);

// Save the generated ODT document.
$template->save('samples/output/output_19_htmlTable.odt');

echo "✅ HTML table sample generated successfully.\n";
