<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\RichText;


$template = new OdtTemplate('templates/template_html.odt');
$template->load();

echo "hello";


// 🧪 HTML-Demo mit Bildern in verschiedenen Layouts
$html = <<<HTML
<h1>🧪 Bild-Positionierungs-Demo mit Lorem Ipsum</h1>
<h2>1. Inline-Bild (as-char)</h2>
<p>
    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vel placerat lorem.
    <img src="../assets/Logo.png" width="3cm" height="2cm">
    Phasellus in feugiat lorem. Mauris placerat sapien at quam tincidunt, ut laoreet est laoreet.
</p>
<h2>2. float: right</h2>
<p>
    Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="float: right;">
    Integer sit amet fermentum lacus. Nullam vitae justo ac sapien ultricies dignissim.
    Etiam nec enim nec lacus suscipit rutrum.
</p>
<h2>3. float: left</h2>
<p>
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="float: left;">
    Donec fermentum velit vitae ante bibendum, at efficitur magna cursus.
    Suspendisse euismod, velit in volutpat bibendum, turpis lorem porta velit, at porttitor quam sem nec nulla.
</p>
<h2>4. display: block</h2>
<p>
    Lorem ipsum dolor sit amet.
</p>
<p>
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="display: block;">
</p>
<p>
    Curabitur vel vehicula libero. Quisque nec lobortis libero, in pharetra est.
</p>
<h2>5. display: inline</h2>
<p>
    Lorem ipsum <img src="../assets/Logo.png" width="2cm" height="1.5cm" style="display: inline;"> dolor sit amet, consectetur adipiscing elit.
</p>
<h2>6. position: absolute</h2>
<p>
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="position: absolute; left: 4cm; top: 2cm;">
    Vivamus bibendum orci nec diam tincidunt, sed finibus elit vulputate.
</p>
<h2>7. float: none</h2>
<p>
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="float: none;">
    Nulla facilisi. Aliquam erat volutpat. Vestibulum ante ipsum primis in faucibus.
</p>
<h2>8. display: none (nicht sichtbar)</h2>
<p>
    <img src="../assets/Logo.png" width="3cm" height="2cm" style="display: none;">
    Dieser Text hat eigentlich ein Bild davor, aber es ist dank display: none nicht sichtbar.
</p>
<h2>✅ Fertig!</h2>
<p>
    Das war ein umfangreicher Test der Bildpositionierungen in ODT – sowohl inline, block, float als auch absolut positioniert.
</p>
HTML;

// 🔀 Importieren & setzen
$rich = HtmlImporter::fromHtml($html);
$template->setElement('html', $rich);

$template->save('output/output_html_images.odt');