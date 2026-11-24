from zipfile import ZipFile
import xml.etree.ElementTree as E
import copy, math, struct,zlib
from pathlib import Path
src=Path('p2-v01-engine.odt')
ref='/srv/projects/cv-generator-pro/research/visual-template-references/prototypes/E011/e011-tags.odt'
with ZipFile(src) as z: data={n:z.read(n) for n in z.namelist()}
# Preserve namespace prefixes, including prefixes used in attribute values.
import io
for _,(p,u) in E.iterparse(io.BytesIO(data['content.xml']),events=['start-ns']):
 if p and not p.startswith('ns'): E.register_namespace(p,u)
ns=dict(E.iterparse(io.BytesIO(data['content.xml']),events=['start-ns']).__next__()[1:] ) if False else {}
for _,(p,u) in E.iterparse(io.BytesIO(data['content.xml']),events=['start-ns']): ns[p]=u
def q(s): p,k=s.split(':');return '{'+ns[p]+'}'+k
def el(s,a={},text=None):
 n=E.Element(q(s),{q(k):str(v) for k,v in a.items()});n.text=text;return n
r=E.fromstring(data['content.xml']); auto=r.find(q('office:automatic-styles')); body=r.find('.//'+q('office:text'))
with ZipFile(ref) as z: rr=E.fromstring(z.read('content.xml'))
box=copy.deepcopy(next(s for s in rr.iter(q('style:style')) if s.get(q('style:name'))=='Box'))
box.set(q('style:name'),'P2NativeAuto');auto.append(box)
for f in r.iter(q('draw:frame')):
 if f.get(q('draw:name'),'').startswith('Tag'): f.set(q('draw:style-name'),'P2NativeAuto')
def para(t): n=el('text:p',text=t);body.append(n);return n
def style(name,props):
 s=el('style:style',{'style:name':name,'style:family':'graphic'});s.append(el('style:graphic-properties',props));auto.append(s)
style('P2Blue',{'draw:fill':'solid','draw:fill-color':'#337799','draw:stroke':'none','style:wrap':'run-through','style:run-through':'foreground','style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph','style:vertical-pos':'from-top','style:vertical-rel':'paragraph'})
style('P2Gray',{'draw:fill':'solid','draw:fill-color':'#DDDDDD','draw:stroke':'none','style:wrap':'run-through','style:run-through':'foreground','style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph','style:vertical-pos':'from-top','style:vertical-rel':'paragraph'})
p=para('E03 native experimental ring: 70 percent')
for name,st,angle,z in [('Track','P2Gray',360,20),('Progress','P2Blue',252,21)]:
 pts=[]
 for rad,angles in [(10800,range(0,angle+1,3)),(7000,range(angle,-1,-3))]:
  for a in angles: pts.append((round(10800+rad*math.sin(math.radians(a))),round(10800-rad*math.cos(math.radians(a)))))
 n=el('draw:custom-shape',{'draw:name':name,'draw:style-name':st,'text:anchor-type':'paragraph','svg:x':'0cm','svg:y':'0.6cm','svg:width':'2cm','svg:height':'2cm','draw:z-index':z})
 n.append(el('text:p'));n.append(el('draw:enhanced-geometry',{'svg:viewBox':'0 0 21600 21600','draw:type':'non-primitive','draw:enhanced-path':'M '+' L '.join(f'{x} {y}' for x,y in pts)+' Z N'}));p.append(n)
for i in range(7):para(' ')
# Explicit ordering of original overlap, reverse order relative to baseline.
sh=list(r.iter(q('draw:custom-shape')))
sh[0].set(q('draw:z-index'),'10');sh[1].set(q('draw:z-index'),'9')
# Synthetic image with geometry grid, native frame dimensions for contain/cover comparison.
def png(w,h):
 raw=b''.join(b'\0'+b''.join(bytes((40,120,170) if (x//20+y//20)%2 else (240,200,90)) for x in range(w)) for y in range(h))
 def chunk(t,d):return struct.pack('>I',len(d))+t+d+struct.pack('>I',zlib.crc32(t+d)&0xffffffff)
 return b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('>2I5B',w,h,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(raw))+chunk(b'IEND',b'')
data['Pictures/p2-grid.png']=png(240,120)
style('P2Photo',{'style:vertical-pos':'middle','style:vertical-rel':'char'})
style('P2Crop',{'style:vertical-pos':'middle','style:vertical-rel':'char','fo:clip':'rect(0cm, 1cm, 0cm, 1cm)'})
p=para('E01 native image: proportional / stretched / crop candidate')
for i,(w,h,st) in enumerate([('4cm','2cm','P2Photo'),('2cm','2cm','P2Photo'),('2cm','2cm','P2Crop')]):
 f=el('draw:frame',{'draw:name':'Photo'+str(i),'draw:style-name':st,'text:anchor-type':'as-char','svg:width':w,'svg:height':h});f.append(el('draw:image',{'xlink:href':'Pictures/p2-grid.png','xlink:type':'simple','xlink:show':'embed','xlink:actuate':'onLoad'}));p.append(f);f.tail=' '
m=E.fromstring(data['META-INF/manifest.xml']); uri=m.tag.split('}')[0]+'}';E.register_namespace('manifest',uri[1:-1]);E.SubElement(m,uri+'file-entry',{uri+'full-path':'Pictures/p2-grid.png',uri+'media-type':'image/png'})
data['META-INF/manifest.xml']=E.tostring(m,encoding='utf-8',xml_declaration=True)
data['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v01-native.odt','w') as z:
 for n,d in data.items():z.writestr(n,d)
