<?php

declare(strict_types=1);

const ROUNDED_LIBRARY_ONLY = true;
require __DIR__ . '/generate.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;

$labels=['PHP','PostgreSQL','Projektmanagement','Kommunikation','Organisation & Teamarbeit','Änderungsmanagement'];
$t = new PageLayoutOdtTemplate(__DIR__ . '/../flow/seed.odt');
$t->setPageMargins('1.5cm','1.5cm','1.5cm','1.5cm');
$t->appendBody((new Paragraph())->addText('H08 · Abgerundete native Tags', ['font-family'=>'Liberation Sans','font-size'=>'16pt','bold'=>true,'color'=>'#375E80']));
foreach ([
    ['label'=>'Kompakt · Sans Serif · automatische Breite', 'font'=>'Liberation Sans','size'=>'9pt','fill'=>'#DCE8F2','color'=>'#24384B'],
    ['label'=>'Serifenschrift · eigene Farben', 'font'=>'Liberation Serif','size'=>'10pt','fill'=>'#F3E4DA','color'=>'#6E3D2C'],
    ['label'=>'Dunkler Hintergrund · helle Schrift', 'font'=>'Liberation Sans','size'=>'9pt','fill'=>'#203A52','color'=>'#FFFFFF'],
] as $row => $design) {
    $t->appendBody((new Paragraph())->addText($design['label'], ['font-family'=>'Liberation Sans','font-size'=>'10pt','bold'=>true]));
    $shapes=[];
    foreach ($labels as $i=>$label) $shapes[]=new GrowingRectangle('show-'.$row.'-'.$i,$label,true,$design);
    $t->appendBody(new ShapeRow($shapes));
    $t->appendBody((new Paragraph())->addText(' '));
}
$t->appendBody((new Paragraph())->addText('Rechteck-Preset der Engine · nativer Text · kein Bild · kein handgebauter Rundungspfad', ['font-family'=>'Liberation Sans','font-size'=>'8pt','color'=>'#5D7890']));
$t->save(__DIR__.'/showcase.odt');$t->save(__DIR__.'/showcase-repeat.odt');$t->cleanup();
$t = new PageLayoutOdtTemplate(__DIR__.'/showcase.odt');$t->save(__DIR__.'/showcase-reloaded.odt');$t->cleanup();
$t=new PageLayoutOdtTemplate(__DIR__.'/../flow/seed.odt');
$t->definePageLayout('RoundedLayout',['page-width'=>'12cm','page-height'=>'15cm','margin-top'=>'1cm','margin-bottom'=>'1cm','margin-left'=>'1cm','margin-right'=>'1cm']);
$t->definePageStyle('RoundedPage','RoundedLayout',['next-style-name'=>'RoundedPage']);$t->assignPageStyle('RoundedStart','RoundedPage');
$t->appendBody((new Paragraph('RoundedStart'))->addText('Abgerundete Tags · nativer Seitenfluss',['font-family'=>'Liberation Sans','font-size'=>'12pt','bold'=>true]));
$many=[];$expected=[];
for($i=0;$i<90;++$i){$label='Kompetenz '.str_pad((string)$i,3,'0',STR_PAD_LEFT);$expected[]=$label;$many[]=new GrowingRectangle('multi-'.$i,$label,true);}
$t->appendBody(new ShapeRow($many));$t->appendBody((new Paragraph())->addText('ENDE'));
$t->save(__DIR__.'/multipage.odt');$t->save(__DIR__.'/multipage-repeat.odt');$t->cleanup();
file_put_contents(__DIR__.'/showcase-labels.json',json_encode($labels,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n");
file_put_contents(__DIR__.'/multipage-labels.json',json_encode($expected,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n");
