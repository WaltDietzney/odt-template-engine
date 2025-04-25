<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ImageElement;


$template = new OdtTemplate('templates/template_html.odt');
$template->load();

// $p = new Paragraph('Text über dem Bild');
// $p ->addLineBreak();
// $p->addElement(new ImageElement('../assets/logo.png', [
//     'width' => '4cm',
//     'anchor' => 'as-char'
// ]));

//$template->setElement('html', $p);
$html = <<<HTML
<h1>🖼️ Bild-Demo mit CSS Varianten</h1>

<p><strong>Bild inline (Standard)</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm"></p>

<p><strong>Bild mit float: right</strong></p>
<p><img src="../assets/lLgo.png" width="3cm" height="2cm" style="float: right;"></p>

<p><strong>Bild mit float: left</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="float: left;"></p>

<p><strong>Bild mit display: block</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="display: block;"></p>

<p><strong>Bild mit display: inline</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="display: inline;"></p>

<p><strong>Bild mit position: absolute</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="position: absolute; left: 3cm;"></p>

<p><strong>Bild mit float: none</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="float: none;"></p>

<p><strong>Bild mit display: none (soll nicht erscheinen)</strong></p>
<p><img src="../assets/Logo.png" width="3cm" height="2cm" style="display: none;"></p>

<p>✅ Test abgeschlossen</p>
HTML;

// ⬇️ Importieren
$rich = HtmlImporter::fromHtml($html);
$template->setElement('html', $rich);
$template->save('output/output_html_images_all.odt');


// $rich = HtmlImporter::fromHtml($html);
// $template->setElement('html', $rich);
// $template->save('output/output_html_images.odt');


//$template->save('output/output_html.odt');

echo "✅ Dokument erfolgreich erstellt: output_html.odt\n";
