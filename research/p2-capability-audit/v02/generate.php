<?php
require '/tmp/odt-h04-analysis/vendor/autoload.php';
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\{Paragraph,RichText,RichTable,RichTableCell};
function p($s,$opts=[]){return (new Paragraph())->addText($s,$opts);}
foreach(['linear','alternating','paragraph'] as $mode){
$t=new class('/tmp/odt-h04-analysis/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') extends OdtTemplate {public function dom(){return $this->documentContext()->contentDom();}};
$b=$t->dom()->getElementsByTagName('text')->item(0);foreach(iterator_to_array($b->childNodes) as $n)if($n->nodeName==='text:p')$b->removeChild($n);
foreach([0,1,3,12] as $count){
$t->appendBody(p("$mode | $count stations",['font-weight'=>'bold','font-size'=>'14pt']));
if(!$count)continue;
$table=(new RichTable())->setTableName($mode.$count)->setTableStyle(['width'=>'15cm','alignment'=>'left']);
$table->setColumnWidths($mode==='alternating'?['7cm','1cm','7cm']:['3cm','0.7cm','11.3cm']);
for($i=0;$i<$count;$i++){
$label='Station '.($i+1).' | '.(2026-$i);
$details=new RichText();$details->addParagraph(p($label,['font-weight'=>'bold','color'=>'#153d61']));
for($j=0;$j<($count===12&&$i===5?32:($i%3+1));$j++)$details->addParagraph(p('Detail '.($j+1).': Coordination of projects, documentation and team responsibilities.',['font-size'=>'10pt']));
if($mode==='paragraph'){
$t->appendBody(p('● '.$label,['font-weight'=>'bold','color'=>'#153d61'])->setParagraphStyleOptions(['border-left'=>'1pt solid #87accb','padding-left'=>'0.2cm']));
for($j=0;$j<($count===12&&$i===5?32:($i%3+1));$j++)$t->appendBody(p('Detail '.($j+1).': Coordination of projects, documentation and team responsibilities.',['font-size'=>'10pt'])->setParagraphStyleOptions(['border-left'=>'1pt solid #87accb','padding-left'=>'0.2cm']));
continue;}
$marker=(new RichTableCell(p('●',['color'=>'#1e5a91'])))->setStyle(['border-left'=>'1pt solid #87accb','padding-top'=>'0.1cm']);
$c=(new RichTableCell($details))->setStyle(['padding'=>'0.15cm']);
$date=new RichTableCell($mode==='linear'?(string)(2026-$i):'');
$table->addRow($mode==='alternating'&&$i%2===0?[$c,$marker,$date]:[$date,$marker,$c]);
}
if($mode!=='paragraph')$t->appendBody($table);
}
$t->save('/tmp/p2-a-audit/v02/p2-v02-'.$mode.'.odt');$t->cleanup();
}
