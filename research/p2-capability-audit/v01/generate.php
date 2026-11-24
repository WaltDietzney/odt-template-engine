<?php
require '/tmp/odt-h04-analysis/vendor/autoload.php';
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\{Paragraph,ShapeElement,ShapeGeometry,ShapeAppearance,DrawingLayout,DrawTextBox,ImageElement};
$t=new class('/tmp/odt-h04-analysis/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') extends OdtTemplate {public function dom(){return $this->documentContext()->contentDom();}};
$d=$t->dom(); $body=$d->getElementsByTagName('text')->item(0);
foreach(iterator_to_array($body->childNodes) as $n) if($n->nodeName==='text:p') $body->removeChild($n);
function p($s){return (new Paragraph())->addText($s);}
function shape($type,$color,$x,$y,$w,$h){return new ShapeElement(ShapeGeometry::preset($type),ShapeAppearance::solid($color),DrawingLayout::fromArray(['anchor'=>'paragraph','width'=>$w.'cm','height'=>$h.'cm','horizontal'=>['offset'=>$x.'cm','relative-to'=>'paragraph'],'vertical'=>['offset'=>$y.'cm','relative-to'=>'paragraph'],'wrap'=>'run-through']));}
$t->appendBody(p('P2-V01 | Engine capability baseline'));
$t->appendBody(p('E02: overlapping native shapes (insertion order)')->addElement(shape('rectangle','#BBDDEE',0,0.6,4,1.5))->addElement(shape('ellipse','#337799',2,0.8,2,1.5)));
for($i=0;$i<5;$i++)$t->appendBody(p(' '));
$q=p('E03: stars, segments and full ring');
for($i=0;$i<5;$i++){$q->addElement(shape('star5',$i<3?'#337799':'#DDDDDD',$i*0.8,0.7,0.7,0.7));$q->addElement(shape('round-rectangle',$i<4?'#337799':'#DDDDDD',$i*0.8,1.6,0.7,0.3));}
$q->addElement(shape('ring','#337799',5,0.7,1.5,1.5));$t->appendBody($q);
for($i=0;$i<6;$i++)$t->appendBody(p(' '));
$t->appendBody(p('E05: legacy auto-grow inline frames'));
$q=p('');
foreach(['PHP','PostgreSQL','Docker','Projektmanagement','Longer editable label'] as $i=>$s){$q->addElement((new DrawTextBox('Tag'.$i,['anchor'=>'as-char','width'=>'1cm','height'=>'0.5cm','draw:auto-grow-width'=>'true','draw:auto-grow-height'=>'true','padding'=>'0.08cm','background-color'=>'#DDEEFF']))->addElement(p($s)));}
$t->appendBody($q);
$t->save('/tmp/p2-a-audit/v01/p2-v01-engine.odt');$t->cleanup();
