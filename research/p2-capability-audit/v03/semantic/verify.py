#!/usr/bin/env python3
"""Check semantic heading identity, no duplicate text, real PDF outlines and lifecycle."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import re,json,subprocess,hashlib
BASE=Path(__file__).resolve().parent
NS={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('text','text'),('draw','drawing'),('office','office')]}
def q(tag):
 p,n=tag.split(':');return '{'+NS[p]+'}'+n
expected=list(json.loads((BASE/'labels.json').read_text()).values())
names=['semantic-headings','semantic-headings-repeat','semantic-headings-reloaded']
report={}
for name in names:
 parts={}
 for prefix in ['', 'roundtrip/']:
  with ZipFile(BASE/(prefix+name+'.odt')) as z:
   for part in ['content.xml','styles.xml']:E.fromstring(z.read(part))
   root=E.fromstring(z.read('content.xml'));body=root.find('.//'+q('office:text'))
   hs=body.findall(q('text:h'));texts=[''.join(h.itertext()) for h in hs]
   assert texts==expected,(name,prefix,texts)
   assert [h.get(q('text:outline-level')) for h in hs]==['1']*3
   assert '{{' not in E.tostring(body,encoding='unicode')
   alltext=''.join(body.itertext())
   assert all(alltext.count(text)==1 for text in expected),'duplicate heading'
   assert len(list(body.iter(q('draw:custom-shape'))))==5
   for shape in body.iter(q('draw:custom-shape')):
    assert not ''.join(shape.itertext()).strip(),'decorative shape duplicates text'
   parts[prefix or 'engine']={'headings':texts,'outline_levels':[1,1,1],'decorative_shapes':5}
 for pdfdir in ['pdf','pdf-rt']:
  data=(BASE/pdfdir/(name+'.pdf')).read_bytes()
  assert b'/Outlines ' in data
  titles=[bytes.fromhex(h.decode()).decode('utf-16') for h in re.findall(rb'/Title\s*<([0-9A-Fa-f]+)>\s*/Dest',data)]
  assert titles==expected,(name,pdfdir,titles)
  raw=subprocess.check_output(['pdftotext','-raw',str(BASE/pdfdir/(name+'.pdf')),'-'],text=True)
  for title in expected:assert raw.count(title)==1,(name,pdfdir,title)
  parts[pdfdir]={'outline_titles':titles,'visible_text_once':True}
 pictures=[]
 for p in sorted((BASE/'images').glob(name+'-*.png')):
  if not re.fullmatch(re.escape(name)+r'-[0-9]+\.png',p.name):continue
  pictures.append(p.read_bytes()==(BASE/'images-rt'/p.name).read_bytes())
 assert pictures and all(pictures),(name,'roundtrip visual drift')
 parts['identical_png_pages']=pictures
 report[name]=parts
with ZipFile(BASE/'semantic-headings.odt') as a,ZipFile(BASE/'semantic-headings-repeat.odt') as b:
 for part in ['content.xml','styles.xml']:assert a.read(part)==b.read(part),'repeat-save drift'
with ZipFile(BASE.parent/'p2-v03-fixed.odt') as z:
 control=E.fromstring(z.read('content.xml'))
 assert len(list(control.iter(q('text:h'))))==0
result={'status':'PASS','documents':report,
 'accepted_graphic_control_native_heading_count':0,
 'source_sha256':hashlib.sha256((BASE.parent/'p2-v03-fixed.odt').read_bytes()).hexdigest()}
(BASE/'verification.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'status':'PASS','documents':len(report),'headings_per_document':3,'pdf_outline_entries':3,'png_pages':sum(len(r['identical_png_pages']) for r in report.values())}))
