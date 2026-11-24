from zipfile import ZipFile
import xml.etree.ElementTree as E
import io
with ZipFile('../v02/p2-v02-centered.odt') as z:d={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(d['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s):p,k=s.split(':');return '{'+ns[p]+'}'+k
def el(s,a={},t=None):
 n=E.Element(q(s),{q(k):str(v) for k,v in a.items()});n.text=t;return n
r=E.fromstring(d['content.xml']);a=r.find(q('office:automatic-styles'));b=r.find('.//'+q('office:text'))
for n in list(b):
 if n.tag!=q('text:sequence-decls'):b.remove(n)
def style(name,family,kind,props):
 s=el('style:style',{'style:name':name,'style:family':family});s.append(el(kind,props));a.append(s);return s
for name,color in [('BandBlue','#375e80'),('BandDark','#203a52'),('BandLight','#87accb')]:
 style(name,'graphic','style:graphic-properties',{'draw:fill':'solid','draw:fill-color':color,'draw:stroke':'none','style:wrap':'run-through','style:run-through':'background','style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph','style:vertical-pos':'from-top','style:vertical-rel':'paragraph'})
s=style('BandHeading','paragraph','style:paragraph-properties',{'fo:margin-top':'0.4cm','fo:margin-bottom':'0.4cm','fo:line-height':'0.9cm','fo:padding-left':'0.25cm','fo:keep-with-next':'always'})
s.append(el('style:text-properties',{'fo:color':'#ffffff','fo:font-size':'15pt','fo:font-weight':'bold'}))
def shape(p,name,st,x,y,w,h,path,z):
 n=el('draw:custom-shape',{'draw:name':name,'draw:style-name':st,'text:anchor-type':'paragraph','svg:x':x+'cm','svg:y':y+'cm','svg:width':w+'cm','svg:height':h+'cm','draw:z-index':z})
 n.append(el('text:p'));n.append(el('draw:enhanced-geometry',{'svg:viewBox':'0 0 21600 21600','draw:type':'non-primitive','draw:enhanced-path':path}));p.append(n)
rect='M 0 0 L 21600 0 21600 21600 0 21600 Z N'
for i,(label,heading) in enumerate([('A | Plain band','Berufserfahrung'),('B | Folded ribbon','Ausbildung und berufliche Weiterbildung'),('C | Angled accent','Qualifikationen')]):
 b.append(el('text:p',t=label))
 p=el('text:h',{'text:style-name':'BandHeading','text:outline-level':'2'});b.append(p)
 if i==1:shape(p,'Fold','BandDark','0','0.85','0.55','0.45','M 0 0 L 21600 0 21600 21600 Z N',0)
 shape(p,'Band'+str(i),'BandBlue','0','0','12','0.9',rect if i==0 else ('M 0 0 L 21600 0 20400 10800 21600 21600 0 21600 Z N' if i==1 else 'M 0 0 L 19800 0 21600 21600 0 21600 Z N'),1)
 if i==2:shape(p,'Accent','BandLight','12','0','0.8','0.9','M 0 0 L 7000 0 21600 21600 14600 21600 Z N',2)
 p[-1].tail=heading
 b.append(el('text:p',t='Editable body text follows the semantic heading.'))
 b.append(el('text:p',t=' '))
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v03-headings.odt','w') as z:
 for n,v in d.items():z.writestr(n,v)
