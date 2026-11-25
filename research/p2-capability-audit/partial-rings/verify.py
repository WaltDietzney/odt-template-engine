#!/usr/bin/env python3
"""Verify native contours, real paint coverage, lifecycle and immutable reference evidence."""
from pathlib import Path
from zipfile import ZipFile
from xml.dom.minidom import parseString
import xml.etree.ElementTree as E
import json,subprocess,math,hashlib
B=Path(__file__).resolve().parent
N={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('draw','drawing'),('text','text'),('style','style'),('office','office')]}
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def read(name,folder=''):
 with ZipFile(B/folder/(name+'.odt')) as z:return {p:E.fromstring(z.read(p)) for p in ['content.xml','styles.xml']}
names=json.loads((B/'outputs.json').read_text());report={}
for name in names:
 source=read(name);rt=read(name,'roundtrip')
 counts=[]
 for roots in [source,rt]:
  groups=[n for r in roots.values() for n in r.iter(q('draw:g'))]
  shapes=[n for r in roots.values() for n in r.iter(q('draw:custom-shape'))]
  assert not [n for r in roots.values() for n in r.iter(q('draw:image'))]
  if name=='probe-0':assert len(shapes)==1
  if name.startswith('probe-') and name!='probe-0':assert len(shapes)==2
  if name.startswith('showcase'):assert len(shapes)==21 and len(groups)==11
  if name.startswith('stories'):assert len(shapes)==6 and len(groups)==3
  counts.append(len(shapes))
  for g in groups:
   for child in g.findall(q('draw:custom-shape')):assert q('text:anchor-type') not in child.attrib
  for s in shapes:
   geometry=s.find(q('draw:enhanced-geometry'));assert geometry is not None
   assert geometry.get(q('draw:type')) in ['ring','non-primitive']
 assert counts[0]==counts[1]
 pngs=list((B/'images').glob(name+'-[0-9]*.png'));assert len(pngs)==1
 identical=pngs[0].read_bytes()==(B/'images-rt'/pngs[0].name).read_bytes()
 comparison={'pixel_identical':identical,'changed_pixels':0,'maximum_channel_delta':0}
 if not identical:
  assert name.startswith('probe-'),(name,'flow fixture pixel drift')
  def raster(folder):
   raw=subprocess.check_output(['pdftoppm','-scale-to','1400','-singlefile',str(B/folder/(name+'.pdf'))])
   lines=raw.split(b'\n',3);return tuple(map(int,lines[1].split())),lines[3]
  size,a=raster('pdf');size2,c=raster('pdf-rt');assert size==size2
  changed=[i for i in range(0,len(a),3) if a[i:i+3]!=c[i:i+3]]
  maximum=max(abs(x-y) for x,y in zip(a,c))
  assert len(changed)<=9 and maximum<=27,(name,len(changed),maximum)
  center=4*1400/29.7;inner=(7000/10800)*2*1400/29.7
  assert all(abs(math.hypot((i//3)%size[0]-center,(i//3)//size[0]-center)-inner)<1.5 for i in changed)
  comparison={'pixel_identical':False,'changed_pixels':len(changed),'maximum_channel_delta':maximum,'all_differences_at_inner_edge':True}
 report[name]={'native_shapes':counts[0],'writer_roundtrip_raster':comparison,'pages':1}
for name in ['showcase','stories']:
 with ZipFile(B/(name+'.odt')) as a,ZipFile(B/(name+'-repeat.odt')) as c:
  for part in ['content.xml','styles.xml']:assert a.read(part)==c.read(part),(name,'repeat drift')
with ZipFile(B/'showcase.odt') as a,ZipFile(B/'showcase-reloaded.odt') as c:
 for part in ['content.xml','styles.xml']:assert a.read(part)==c.read(part),'reopen drift'
# At 1800 dpi even the 1% segment and 99% gap have many pixels at mid-band radius.
def sample(name,angle=None,folder='pdf',outside=False):
 center=4/2.54*72;radius=(2/2.54*72)*(1.1 if outside else (1+7000/10800)/2)
 if angle is None:x=y=center
 else:x=center+radius*math.sin(math.radians(angle));y=center-radius*math.cos(math.radians(angle))
 dpi=1800;x=round(x*dpi/72);y=round(y*dpi/72)
 data=subprocess.check_output(['pdftoppm','-r',str(dpi),'-x',str(x),'-y',str(y),'-W','1','-H','1','-singlefile',str(B/folder/(name+'.pdf'))])
 return list(data[-3:])
blue=[51,119,153];gray=[221,221,221];white=[255,255,255]
probes={'probe-0':[(45,gray),(180,gray)],'probe-1':[(1.8,blue),(3.3,blue),(5,gray)],
 'probe-70':[(126,blue),(306,gray),(252.18,gray)],'probe-99':[(180,blue),(358.2,gray)],
 'probe-100':[(45,blue),(270,blue)],'probe-70.1':[(252.18,blue),(253,gray)]}
for name,checks in probes.items():
 samples=[]
 for folder in ['pdf','pdf-rt']:
  assert sample(name,None,folder)==white,(name,'hole filled')
  assert sample(name,90,folder,True)==white,(name,'outside contour painted')
  for angle,expected in checks:
   color=sample(name,angle,folder);assert color==expected,(name,folder,angle,color,expected)
   samples.append({'stage':folder,'angle':angle,'rgb':color})
 report[name]['visible_radial_coverage']=samples
for name in ['showcase','stories']:
 text=subprocess.check_output(['pdftotext',str(B/'pdf'/(name+'.pdf')),'-'],text=True)
 expected=['0%','1%','70%','99%','100%','33.37%','70.1%','Kontur'] if name=='showcase' else ['Body','Header','Footer','70%','99%']
 for label in expected:assert label in text,(name,label)
 if name=='stories':
  for part in ['content.xml','styles.xml']:
   for shape in read(name)[part].iter(q('draw:custom-shape')):
    assert shape.get(q('draw:style-name')).startswith('drawing_c_' if part=='content.xml' else 'drawing_s_')
# Save selected actual XML without modifying any ODT.
for name in ['showcase','stories','probe-1','probe-70.1','probe-100']:
 for stage,folder in [('source',''),('writer','roundtrip')]:
  dest=B/'xml'/name/stage;dest.mkdir(parents=True,exist_ok=True)
  with ZipFile(B/folder/(name+'.odt')) as z:
   for part in ['content.xml','styles.xml']:(dest/part).write_bytes(parseString(z.read(part)).toprettyxml(encoding='utf-8'))
root=B.parents[2];start=json.loads((B/'start-state.json').read_text())['files']
protected={p:h for p,h in start.items() if p.startswith('research/')}
changed=[p for p,h in protected.items() if not (root/p).exists() or hashlib.sha256((root/p).read_bytes()).hexdigest()!=h]
assert not changed,changed
report['preserved_research']={'files_checked':len(protected),'changed':changed}
(B/'verification.json').write_text(json.dumps(report,indent=2)+'\n')
print('PASS: 11 native documents/pages, endpoint-sensitive paint probes, open holes, lifecycle and preserved research.')
