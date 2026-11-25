#!/usr/bin/env python3
"""Verify rounded native tags, text sizing/styles, lifecycle and negative controls."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import json,re,subprocess,hashlib
B=Path(__file__).resolve().parent
N={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('draw','drawing'),('text','text'),('style','style'),('office','office'),('svg','svg-compatible'),('fo','xsl-fo-compatible')]}
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def compact(s):return re.sub(r'\s+','',s).replace('\u00ad','')
def read(name,folder=''):
 with ZipFile(B/folder/(name+'.odt')) as z:return E.fromstring(z.read('content.xml'))
def pages(name,folder):return int(re.search(r'Pages:\s*(\d+)',subprocess.check_output(['pdfinfo',str(B/folder/(name+'.pdf'))],text=True)).group(1))
labels=json.loads((B/'showcase-labels.json').read_text());many=json.loads((B/'multipage-labels.json').read_text());report={}
for name in ['showcase','showcase-repeat','showcase-reloaded','multipage']:
 expected=many if name=='multipage' else labels;count=90 if name=='multipage' else 18
 source=read(name);rt=read(name,'roundtrip');shapes=list(rt.iter(q('draw:custom-shape')))
 assert len(shapes)==count
 assert not list(rt.iter(q('draw:image')))
 assert len({s.get(q('draw:name')) for s in shapes})==count
 for s in shapes:
  g=s.find(q('draw:enhanced-geometry'));assert g.get(q('draw:type'))=='round-rectangle'
  assert g.get(q('draw:path-stretchpoint-x'))=='10800'
  assert 0.5<float(s.get(q('svg:width'))[:-2])<9
  assert 0.4<float(s.get(q('svg:height'))[:-2])<2
 if name!='multipage':
  allstyles={s.get(q('style:name')):s for s in rt.iter(q('style:style'))}
  for i,font,size,color in [(0,'Liberation Sans','9pt','#24384b'),(6,'Liberation Serif','10pt','#6e3d2c'),(12,'Liberation Sans','9pt','#ffffff')]:
   span=shapes[i].find('.//'+q('text:span'));style=allstyles[span.get(q('text:style-name'))];p=style.find(q('style:text-properties'))
   assert p.get(q('style:font-name'))==font and p.get(q('fo:font-size'))==size and p.get(q('fo:color'))==color
 for folder in ['pdf','pdf-rt']:
  raw=subprocess.check_output(['pdftotext','-raw',str(B/folder/(name+'.pdf')),'-'],text=True)
  for label in expected:assert compact(raw).count(compact(label))==(1 if name=='multipage' else 3),(name,folder,label)
 expected_pages=pages(name,'pdf');assert pages(name,'pdf-rt')==expected_pages
 pngs=sorted((B/'images').glob(name+'-[0-9]*.png'));assert len(pngs)==expected_pages
 assert all(p.read_bytes()==(B/'images-rt'/p.name).read_bytes() for p in pngs)
 bbox=E.fromstring(subprocess.check_output(['pdftotext','-bbox',str(B/'pdf'/(name+'.pdf')),'-'],text=True))
 for page in [p for p in bbox.iter() if p.tag.endswith('page')]:
  words=[w for w in page.iter() if w.tag.endswith('word')]
  for i,a in enumerate(words):
   assert float(a.get('xMin'))>=0 and float(a.get('yMin'))>=0
   assert float(a.get('xMax'))<=float(page.get('width'))+0.1 and float(a.get('yMax'))<=float(page.get('height'))+0.1
   for c in words[i+1:]:
    dx=min(float(a.get('xMax')),float(c.get('xMax')))-max(float(a.get('xMin')),float(c.get('xMin')))
    dy=min(float(a.get('yMax')),float(c.get('yMax')))-max(float(a.get('yMin')),float(c.get('yMin')))
    assert dx<0.5 or dy<0.5,(name,'text overlaps',a.text,c.text)
 if name=='multipage':assert expected_pages>=2
 report[name]={'tags':count,'roundtrip_pages_identical':expected_pages,'pdf_complete':True,'fonts_colors_verified':name!='multipage','off_page_or_overlapping_words':False}
for name in ['showcase','multipage']:
 with ZipFile(B/(name+'.odt')) as a,ZipFile(B/(name+'-repeat.odt')) as c:
  for part in ['content.xml','styles.xml']:assert a.read(part)==c.read(part),(name,'repeat drift')
# Prove the direct Writer-frame rounding path is ignored, rather than calling it supported.
source=read('text-box-radius');rt=read('text-box-radius','roundtrip')
assert len([n for n in source.iter(q('draw:text-box')) if n.get(q('draw:corner-radius'))])==5
assert not [n for n in rt.iter(q('draw:text-box')) if n.get(q('draw:corner-radius'))]
assert (B/'images/control-1.png').read_bytes()==(B/'images/text-box-radius-1.png').read_bytes()
rects=list(read('native-rectangle','roundtrip').iter(q('draw:rect')))
rect_widths=[float(r.get(q('svg:width'))[:-2]) for r in rects]
# The plain native rectangle retains fixed widths; it does not fit long labels.
assert len(rects)==5 and max(rect_widths)-min(rect_widths)<0.01
result={'status':'PASS','documents':report,'direct_frame_radius':'ignored/dropped by Writer; pixel-identical to rectangular control','native_rect_auto_grow':'negative control: all widths remain approximately 1cm','positive_carrier':'existing ShapeGeometry round-rectangle preset, native custom-shape text','owner_visual_acceptance':'ACCEPTED 2026-11-24; recorded owner decision, not an automated test result','production_api':'not changed in this probe'}
(B/'verification.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps({'status':'PASS','positive_documents':4,'rounded_tags_on_multipage':90,'roundtrip_pages':sum(v['roundtrip_pages_identical'] for v in report.values()),'owner_visual_acceptance':'ACCEPTED 2026-11-24; recorded owner decision, not an automated test result'},indent=2))
