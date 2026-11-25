#!/usr/bin/env python3
"""Check ordered text flow, native frame growth, wrapping, spacing and stable Writer export."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import subprocess,json,re
B=Path(__file__).resolve().parent
N={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('text','text'),('draw','drawing'),('office','office'),('svg','svg-compatible'),('style','style'),('fo','xsl-fo-compatible')]}
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def compact(s):return re.sub(r'\s+','',s).replace('\u00ad','')
def words(name):
 xml=subprocess.check_output(['pdftotext','-bbox',str(B/'pdf'/(name+'.pdf')),'-'],text=True)
 root=E.fromstring(xml)
 return [[w for w in page.iter() if w.tag.endswith('word')] for page in root.iter() if page.tag.endswith('page')]
def marker(name,value):return next(float(w.get('yMin')) for page in words(name) for w in page if w.text==value)
data=json.loads((B/'data.json').read_text());labels=json.loads((B/'tag-labels.json').read_text());report={}
for name in json.loads((B/'outputs.json').read_text()):
 with ZipFile(B/(name+'.odt')) as z,ZipFile(B/'roundtrip'/(name+'.odt')) as rt,ZipFile(B/(name+'-repeat.odt')) as repeated:
  for part in ['content.xml','styles.xml','META-INF/manifest.xml']:assert z.read(part)==repeated.read(part),(name,'repeat drift',part)
  source=E.fromstring(z.read('content.xml'));final=E.fromstring(rt.read('content.xml'))
  assert '{{' not in z.read('content.xml').decode()
  if name=='ordered':
   h=source.find('.//'+q('text:h'));assert h is not None
   assert [n.tag for n in h]==[q('draw:frame'),q('draw:frame'),q('text:user-field-get')]
   assert h.text=='A ' and list(h)[0].tail==' B ' and list(h)[1].tail==' C ' and list(h)[2].tail==' D'
   assert len(list(final.iter(q('text:h'))))==1
   assert len(list(final.iter(q('text:user-field-get'))))==1
   styles=E.fromstring(rt.read('styles.xml'));assert len(list(styles.iter(q('draw:frame'))))==2
  if name=='tags-multipage':
   frames=list(final.iter(q('draw:frame')));assert len(frames)==90
   for f in frames:assert len(list(f.iter(q('text:p'))))==1
   allstyles=[]
   for part in ['content.xml','styles.xml']:
    allstyles.extend(E.fromstring(rt.read(part)).iter(q('style:style')))
   byname={n.get(q('style:name')):n for n in allstyles}
   for f in frames:
    style=byname[f.get(q('draw:style-name'))];props=style.find(q('style:graphic-properties'))
    for side in ['top','bottom','left','right']:
     assert abs(float(props.get(q('fo:padding-'+side),props.get(q('fo:padding')))[:-2])-0.152)<0.003
    for side in ['left','right']:assert abs(float(props.get(q('fo:margin-'+side))[:-2])-0.08)<0.003
    for side in ['top','bottom']:assert abs(float(props.get(q('fo:margin-'+side))[:-2])-0.06)<0.003
   assert len(words(name))>=2
  if name=='wide-fallback':
   frame=next(final.iter(q('draw:frame')));assert abs(float(frame.get(q('svg:width'))[:-2])-4)<0.003
 for folder in ['pdf','pdf-rt']:
  text=subprocess.check_output(['pdftotext','-raw',str(B/folder/(name+'.pdf')),'-'],text=True)
  if name=='ordered':assert re.search(r'A\s+B\s+TAG\s+C\s+FIELD\s+D',text)
  if name.startswith('flow-'):assert compact(data['addresses'][name[5:]]) in compact(text)
  if name=='tags-multipage':
   for label in labels:assert compact(text).count(compact(label))==1,(name,label)
  if name=='wide-fallback':assert compact(data['wide']) in compact(text)
 pages=sorted((B/'images').glob(name+'-[0-9]*.png'));assert pages
 matches=[p.read_bytes()==(B/'images-rt'/p.name).read_bytes() for p in pages];assert all(matches),(name,'roundtrip pixels',matches)
 # Render feedback rejects off-page or overlapping visible words; no silent text shortening.
 bbox=E.fromstring(subprocess.check_output(['pdftotext','-bbox',str(B/'pdf'/(name+'.pdf')),'-'],text=True))
 for page_node in [node for node in bbox.iter() if node.tag.endswith('page')]:
  page=[w for w in page_node.iter() if w.tag.endswith('word')]
  for word in page:
   assert float(word.get('xMax'))<=float(page_node.get('width'))+0.1 and float(word.get('yMax'))<=float(page_node.get('height'))+0.1,(name,'off-page text',word.text)
  for i,a in enumerate(page):
   assert float(a.get('xMin'))>=0 and float(a.get('yMin'))>=0
   for other in page[i+1:]:
    dx=min(float(a.get('xMax')),float(other.get('xMax')))-max(float(a.get('xMin')),float(other.get('xMin')))
    dy=min(float(a.get('yMax')),float(other.get('yMax')))-max(float(a.get('yMin')),float(other.get('yMin')))
    assert dx<0.5 or dy<0.5,(name,'overlapping words',a.text,other.text)
 report[name]={'roundtrip_pages':len(pages),'same_pixels':matches,'complete_pdf':True,'no_visible_word_overlap':True}
growth=marker('flow-long','FOLLOWING')-marker('flow-short','FOLLOWING');assert growth>20,growth
assert marker('wide-fallback','AFTERWIDE')>40
result={'status':'PASS','documents':report,'long_content_growth_pt':growth,'tags':90,'public_maximum_bounds':'rejected: Writer ignores maxima; negative fixture preserved','contact_migration':str(B/'contacts/composition/verification.json')}
(B/'verification.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps({'status':'PASS','documents':len(report),'pages':sum(v['roundtrip_pages'] for v in report.values()),'tags':90,'long_growth_pt':growth},indent=2))
