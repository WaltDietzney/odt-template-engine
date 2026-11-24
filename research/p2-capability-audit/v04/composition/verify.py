#!/usr/bin/env python3
"""Verify actual native composition results, not a mirrored implementation."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import subprocess,json,re,hashlib
BASE=Path(__file__).resolve().parent
NS={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [
 ('text','text'),('draw','drawing'),('office','office'),('svg','svg-compatible'),('manifest','manifest'),('table','table')]}
NS['xlink']='http://www.w3.org/1999/xlink'
def q(n):
 k,v=n.split(':');return '{'+NS[k]+'}'+v
def compact(s):return re.sub(r'\s+','',s).replace('\u00ad','')
def inspect(path):
 with ZipFile(path) as z:
  parts={n:E.fromstring(z.read(n)) for n in ['content.xml','styles.xml','meta.xml','META-INF/manifest.xml']}
  refs=[];decl={};images=[]
  for part in ['content.xml','styles.xml']:
   r=parts[part]
   assert '{{' not in z.read(part).decode(),path
   assert not list(r.iter(q('table:table'))),path
   for e in r.iter(q('text:user-field-decl')):decl[e.get(q('text:name'))]=e.get(q('office:string-value'))
   refs.extend(e.get(q('text:name')) for e in r.iter(q('text:user-field-get')))
   for f in r.iter(q('draw:frame')):
    direct=[e for e in f if e.tag==q('draw:image')]
    if direct:
     width=f.get(q('svg:width'));height=f.get(q('svg:height'))
     assert width==height,(path,'distorted icon',width,height)
     for image in direct:
      href=image.get(q('xlink:href'))
      if href:assert href in z.namelist(),(path,href)
     images.append((width,height))
  manifest={e.get(q('manifest:full-path')):e.get(q('manifest:media-type')) for e in parts['META-INF/manifest.xml']}
  for name in z.namelist():
   if name.startswith('Pictures/') and name.endswith('.svg'):assert manifest[name]=='image/svg+xml'
  return {'declarations':decl,'references':sorted(refs),'image_sizes':images,
   'frames':len(list(parts['content.xml'].iter(q('draw:frame'))))}
specs=json.loads((BASE/'specs.json').read_text());report={}
for name,spec in specs.items():
 src=inspect(BASE/(name+'.odt'));rt=inspect(BASE/'roundtrip'/(name+'.odt'))
 assert src['declarations']==spec['data'],(name,'authoritative declarations')
 assert src['references']==rt['references'],(name,'native fields lost')
 assert rt['declarations']==src['declarations'],(name,'binding changed')
 text=subprocess.check_output(['pdftotext','-raw',str(BASE/'pdf'/(name+'.pdf')),'-'],text=True)
 (BASE/'pdf'/(name+'.raw.txt')).write_text(text)
 for key,value in spec['data'].items():assert compact(value) in compact(text),(name,'PDF field missing',key)
 with ZipFile(BASE/(name+'.odt')) as a,ZipFile(BASE/'repeat'/(name+'.odt')) as b:
  for part in ['content.xml','styles.xml','META-INF/manifest.xml']:
   assert a.read(part)==b.read(part),(name,'repeat-save drift',part)
 comparisons=[]
 for p in sorted((BASE/'images').glob(name+'-*.png')):
  if not re.fullmatch(re.escape(name)+r'-[0-9]+\.png',p.name):continue
  same=p.read_bytes()==(BASE/'images-rt'/p.name).read_bytes()
  comparisons.append(same)
 assert comparisons and all(comparisons),(name,'Writer rendering drift')
 report[name]={'source':src,'roundtrip':rt,'same_png_pages':comparisons,'complete_pdf':True}
assert report['empty']['source']['frames']==0
assert set(report['minimal']['source']['declarations'])=={'name','email'}
# The targeted control contains 4 field references; Writer drawing text loses 3.
with ZipFile(BASE/'carrier.odt') as a,ZipFile(BASE/'roundtrip/carrier.odt') as b:
 before=E.fromstring(a.read('content.xml'));after=E.fromstring(b.read('content.xml'))
 assert len(list(before.iter(q('text:user-field-get'))))==4
 assert len(list(after.iter(q('text:user-field-get'))))==1
def final_marker_y(name):
 raw=subprocess.check_output(['pdftotext','-bbox',str(BASE/'pdf'/(name+'.pdf')),'-'],text=True)
 r=E.fromstring(raw)
 return next(float(e.get('yMin')) for e in r.iter() if e.tag.endswith('word') and e.text=='ENDE')
growth=final_marker_y('long')-final_marker_y('compact')
assert growth>20,('long text did not move subsequent content',growth)
result={'documents':report,'long_content_vertical_growth_pt':growth,
 'carrier_boundary':{'before':4,'after':1,'accepted_carrier':'Writer text frame; draw:g rejected for User Fields'},
 'status':'PASS'}
(BASE/'verification.json').write_text(json.dumps(result,indent=2,ensure_ascii=False)+'\n')
print(json.dumps({'documents':len(report),'pages':sum(len(r['same_png_pages']) for r in report.values()),'long_growth_pt':growth,'status':'PASS'},indent=2))
