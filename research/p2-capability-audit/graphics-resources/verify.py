#!/usr/bin/env python3
"""Verify native SVG packaging, copy-on-write isolation and actual Writer rendering."""
from pathlib import Path
from zipfile import ZipFile
from xml.dom.minidom import parseString
import xml.etree.ElementTree as E
import subprocess,json,hashlib
B=Path(__file__).resolve().parent
N={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('draw','drawing'),('office','office'),('style','style'),('svg','svg-compatible'),('manifest','manifest'),('text','text')]}
N['xlink']='http://www.w3.org/1999/xlink'
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def inspect(path):
 with ZipFile(path) as z:
  roots={p:E.fromstring(z.read(p)) for p in ['content.xml','styles.xml','META-INF/manifest.xml']}
  entries={n.get(q('manifest:full-path')):n.get(q('manifest:media-type')) for n in roots['META-INF/manifest.xml']}
  for part in ['content.xml','styles.xml']:
   assert '{{' not in z.read(part).decode()
   for element in roots[part].iter():
    href=element.get(q('xlink:href'))
    if href and element.tag in [q('draw:image'),q('draw:fill-image'),q('style:background-image')]:
     assert href in z.namelist(),(path,href)
     if href.endswith('.svg'):
      assert entries[href]=='image/svg+xml'
      assert E.fromstring(z.read(href)).tag=='{http://www.w3.org/2000/svg}svg'
      hint=element.get(q('draw:mime-type'));assert hint in [None,'image/svg+xml'],(path,hint)
  objects={n.get(q('draw:name')):(part,n) for part in ['content.xml','styles.xml'] for n in roots[part].iter(q('draw:frame'))}
  return roots,objects,{p:z.read(p) for p in z.namelist() if p.startswith('Pictures/') and p.endswith('.svg')}
def pixel(name,x,y,folder='pdf'):
 data=subprocess.check_output(['pdftoppm','-r','72','-x',str(x),'-y',str(y),'-W','1','-H','1','-singlefile',str(B/folder/(name+'.pdf'))])
 return list(data[-3:])
names=json.loads((B/'outputs.json').read_text())+['writer-rebound']
report={}
for name in names:
 source,objects,assets=inspect(B/(name+'.odt'));inspect(B/'roundtrip'/(name+'.odt'))
 p=B/'images'/(name+'-1.png');assert p.exists()
 assert p.read_bytes()==(B/'images-rt'/p.name).read_bytes(),(name,'Writer pixel drift')
 report[name]={'source_svg_resources':len(assets),'writer_roundtrip_pixels_identical':True}
for name in ['svg-gallery','isolated-after']:
 with ZipFile(B/(name+'.odt')) as a,ZipFile(B/(name+'-repeat.odt')) as b:
  for part in ['content.xml','styles.xml','META-INF/manifest.xml']:assert a.read(part)==b.read(part),(name,part)
with ZipFile(B/'isolated-after.odt') as a,ZipFile(B/'isolated-after-reloaded.odt') as b:
 for part in ['content.xml','styles.xml']:assert a.read(part)==b.read(part)
oldroots,oldobjects,oldassets=inspect(B/'shared-before.odt')
newroots,newobjects,newassets=inspect(B/'isolated-after.odt')
for name in ['FillB','DirectB']:
 assert E.canonicalize(E.tostring(oldobjects[name][1]))==E.canonicalize(E.tostring(newobjects[name][1])),name
for href,data in oldassets.items():assert newassets[href]==data,href
for name in ['HeaderFill','FillA']:
 old=oldobjects[name][1];new=newobjects[name][1]
 assert old.get(q('draw:style-name'))!=new.get(q('draw:style-name'))
 for attr in ['svg:width','svg:height','text:anchor-type']:assert old.get(q(attr))==new.get(q(attr))
a=newobjects['DirectA'][1].find(q('draw:image')).get(q('xlink:href'))
b=newobjects['DirectB'][1].find(q('draw:image')).get(q('xlink:href'))
assert a!=b and newassets[a]==(B/'assets/b/panel.svg').read_bytes() and newassets[b]==(B/'assets/a/panel.svg').read_bytes()
# Rendered colors verify isolation, including the header and Writer-finalized input.
positions={'header':(70,85),'fillA':(70,190),'fillB':(200,190),'directA':(70,270),'directB':(70,330)}
blue=[55,94,128];orange=[198,93,50]
for name in ['shared-before','isolated-after','writer-rebound']:
 colors={key:pixel(name,*pos) for key,pos in positions.items()}
 expected={key:(blue if name=='shared-before' or key in ['fillB','directB'] else orange) for key in positions}
 assert colors==expected,(name,colors)
 assert {key:pixel(name,*pos,'pdf-rt') for key,pos in positions.items()}==expected
 report[name]['visible_resource_isolation']=colors
roots,_,assets=inspect(B/'svg-gallery.odt');assert len(assets)==4
frames=list(roots['content.xml'].iter(q('draw:frame')))
assert any(n.get(q('svg:width'))=='40mm' and n.get(q('svg:height'))=='20mm' for n in frames)
assert any(n.get(q('svg:width'))=='3cm' and n.get(q('svg:height'))=='1.5cm' for n in frames)
text=subprocess.check_output(['pdftotext',str(B/'pdf/svg-gallery.pdf'),'-'],text=True)
for label in ['Lucide','Tabler','Custom','Non-square','Native shape']:assert label in text
# Extract actual XML; no package/output surgery is used by the implementation.
for name in ['svg-gallery','isolated-after','writer-rebound']:
 for stage,folder in [('source',''),('writer','roundtrip')]:
  dest=B/'xml'/name/stage;dest.mkdir(parents=True,exist_ok=True)
  with ZipFile(B/folder/(name+'.odt')) as z:
   for part in ['content.xml','styles.xml','META-INF/manifest.xml']:
    (dest/Path(part).name).write_bytes(parseString(z.read(part)).toprettyxml(encoding='utf-8'))
start=json.loads((B/'start-state.json').read_text())['files'];root=B.parents[2]
protected={p:h for p,h in start.items() if p.startswith('research/')}
changed=[p for p,h in protected.items() if not (root/p).exists() or hashlib.sha256((root/p).read_bytes()).hexdigest()!=h]
assert not changed,changed
report['preserved_research']={'files_checked':len(protected),'changed':changed}
(B/'verification.json').write_text(json.dumps(report,indent=2)+'\n')
print('PASS: seven native documents, SVG dimensions/MIME/reuse, isolation, Writer roundtrip and preserved research.')
