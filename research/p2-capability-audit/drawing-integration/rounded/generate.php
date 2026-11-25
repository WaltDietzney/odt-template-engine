<?php

declare(strict_types=1);

require __DIR__ . '/../../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeTextLayout;
use OdtTemplateEngine\Elements\DrawingLayout;

/** Consumer factory using production APIs only; no serializer/style-scope adapters. */
function tag(string $name, string $label, bool $preset = true, array $design = []): ShapeElement
{
    return (new ShapeElement(ShapeGeometry::preset('round-rectangle'),
        ShapeAppearance::solid($design['fill'] ?? '#DCE8F2'),
        DrawingLayout::fromArray(['anchor'=>'as-char','width'=>'1cm','height'=>'0.2cm',
            'margin'=>['left'=>'0.08cm','right'=>'0.08cm','top'=>'0.06cm','bottom'=>'0.06cm']])))
        ->setTextAutoGrow(true,true)
        ->setTextLayout(new ShapeTextLayout('top',['top'=>'0.152cm','right'=>'0.152cm','bottom'=>'0.152cm','left'=>'0.152cm']))
        ->addParagraph((new Paragraph(null,['fo:margin'=>'0cm']))->addText($label,[
            'font-family'=>$design['font']??'Liberation Sans','font-size'=>$design['size']??'9pt','color'=>$design['color']??'#24384B',
        ]));
}
function row(array $shapes): Paragraph
{
    $p=new Paragraph();
    foreach($shapes as $shape)$p->addInlineElement($shape)->addText(' ');
    return $p;
}
$labels=['PHP','PostgreSQL','Projektmanagement','Kommunikation','Organisation & Teamarbeit','Änderungsmanagement'];
$t = new PageLayoutOdtTemplate(__DIR__ . '/../../flow/seed.odt');
$t->setPageMargins('1.5cm','1.5cm','1.5cm','1.5cm');
$t->appendBody((new Paragraph())->addText('H08 · Abgerundete native Tags', ['font-family'=>'Liberation Sans','font-size'=>'16pt','bold'=>true,'color'=>'#375E80']));
foreach ([
    ['label'=>'Kompakt · Sans Serif · automatische Breite', 'font'=>'Liberation Sans','size'=>'9pt','fill'=>'#DCE8F2','color'=>'#24384B'],
    ['label'=>'Serifenschrift · eigene Farben', 'font'=>'Liberation Serif','size'=>'10pt','fill'=>'#F3E4DA','color'=>'#6E3D2C'],
    ['label'=>'Dunkler Hintergrund · helle Schrift', 'font'=>'Liberation Sans','size'=>'9pt','fill'=>'#203A52','color'=>'#FFFFFF'],
] as $row => $design) {
    $t->appendBody((new Paragraph())->addText($design['label'], ['font-family'=>'Liberation Sans','font-size'=>'10pt','bold'=>true]));
    $shapes=[];
    foreach ($labels as $i=>$label) $shapes[]=tag('show-'.$row.'-'.$i,$label,true,$design);
    $t->appendBody(row($shapes));
    $t->appendBody((new Paragraph())->addText(' '));
}
$t->appendBody((new Paragraph())->addText('Rechteck-Preset der Engine · nativer Text · kein Bild · kein handgebauter Rundungspfad', ['font-family'=>'Liberation Sans','font-size'=>'8pt','color'=>'#5D7890']));
$t->save(__DIR__.'/showcase.odt');$t->save(__DIR__.'/showcase-repeat.odt');$t->cleanup();
$t = new PageLayoutOdtTemplate(__DIR__.'/showcase.odt');$t->save(__DIR__.'/showcase-reloaded.odt');$t->cleanup();
$t=new PageLayoutOdtTemplate(__DIR__.'/../../flow/seed.odt');
$t->definePageLayout('RoundedLayout',['page-width'=>'12cm','page-height'=>'15cm','margin-top'=>'1cm','margin-bottom'=>'1cm','margin-left'=>'1cm','margin-right'=>'1cm']);
$t->definePageStyle('RoundedPage','RoundedLayout',['next-style-name'=>'RoundedPage']);$t->assignPageStyle('RoundedStart','RoundedPage');
$t->appendBody((new Paragraph('RoundedStart'))->addText('Abgerundete Tags · nativer Seitenfluss',['font-family'=>'Liberation Sans','font-size'=>'12pt','bold'=>true]));
$many=[];$expected=[];
for($i=0;$i<90;++$i){$label='Kompetenz '.str_pad((string)$i,3,'0',STR_PAD_LEFT);$expected[]=$label;$many[]=tag('multi-'.$i,$label,true);}
$t->appendBody(row($many));$t->appendBody((new Paragraph())->addText('ENDE'));
$t->save(__DIR__.'/multipage.odt');$t->save(__DIR__.'/multipage-repeat.odt');$t->cleanup();
file_put_contents(__DIR__.'/showcase-labels.json',json_encode($labels,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n");
file_put_contents(__DIR__.'/multipage-labels.json',json_encode($expected,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n");
