#!/usr/bin/env python3
"""Check visible paint order and story text after real Writer processing."""
from pathlib import Path
from zipfile import ZipFile
import subprocess, json, xml.etree.ElementTree as E
B=Path(__file__).resolve().parent
N={k:'urn:oasis:names:tc:opendocument:xmlns:'+v+':1.0' for k,v in [('draw','drawing'),('text','text'),('style','style'),('fo','xsl-fo-compatible')]}
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def pixel(name,x,y):
 # Poppler's lossless RGB raster is inspected at calibrated page coordinates.
 data=subprocess.check_output(['pdftoppm','-r','72','-x',str(x),'-y',str(y),'-W','1','-H','1','-singlefile',str(B/'pdf'/(name+'.pdf'))])
 return list(data[-3:])
report={}
for name in ['foreground','background']:
 for folder in ['','roundtrip']:
  with ZipFile(B/folder/(name+'.odt')) as z:
   for part,prefix in [('content.xml','drawing_c_'),('styles.xml','drawing_s_')]:
    r=E.fromstring(z.read(part));shapes=list(r.iter(q('draw:custom-shape')))
    if part=='styles.xml':
     images=list(r.iter(q('draw:image')));assert images
     for image in images:
      href=image.get('{http://www.w3.org/1999/xlink}href');assert href in z.namelist()
     groups=list(r.iter(q('draw:g')));assert len(groups)==1
     assert len(list(groups[0].iter(q('draw:custom-shape'))))==1
     assert len(list(groups[0].iter(q('draw:image'))))>=1
    assert shapes
    definitions={e.get(q('style:name')):e for e in r.iter(q('style:style'))}
    for shape in shapes:
     for span in shape.iter(q('text:span')):
      ref=span.get(q('text:style-name'));assert ref in definitions
      props=definitions[ref].find(q('style:text-properties'))
      assert props.get(q('fo:font-size'))=='11pt' and props.get(q('fo:color')).lower()=='#ffffff'
      if folder=='':assert ref.startswith(prefix)
 for folder in ['pdf','pdf-rt']:
  text=subprocess.check_output(['pdftotext',str(B/folder/(name+'.pdf')),'-'],text=True)
  for label in ['Header · Serif 11','Footer · Serif 11','Body · Serif 11']:assert text.count(label)==1
 p=B/'images'/(name+'-1.png');assert p.read_bytes()==(B/'images-rt'/p.name).read_bytes()
 with ZipFile(B/(name+'.odt')) as a,ZipFile(B/(name+'-repeat.odt')) as b:
  for part in ['content.xml','styles.xml']:assert a.read(part)==b.read(part)
 # At x=110pt the two 3cm shapes overlap. Child order and explicit object z-index differ.
 group=pixel(name,110,245);objects=pixel(name,110,365)
 assert group==[0,0,255],('group paint order',group)
 assert objects==[255,0,0],('object z-index',objects)
 report[name]={'roundtrip_pixel_identical':True,'header_body_footer_fonts_verified':True,
  'group_overlap_rgb':group,'independent_overlap_rgb':objects}
# M glyph in the overlapping run-through object: hidden in foreground, visible in background.
def dark_pixels(name):
 data=subprocess.check_output(['pdftoppm','-r','72','-x','57','-y','130','-W','82','-H','38','-singlefile',str(B/'pdf'/(name+'.pdf'))])[-82*38*3:]
 return sum(max(data[i:i+3])<30 for i in range(0,len(data),3))
a=dark_pixels('foreground');b=dark_pixels('background')
assert a==0 and b>100,('foreground/background visible glyph area',a,b)
report['layer_probe']={'foreground_dark_pixels':a,'background_dark_pixels':b,'status':'PASS'}
(B/'verification.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
