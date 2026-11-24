from zipfile import ZipFile
import xml.etree.ElementTree as E
import io
with ZipFile('p2-v02-paragraph.odt') as z: data={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(data['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s):p,k=s.split(':');return '{'+ns[p]+'}'+k
def el(s,a={},t=None):
 n=E.Element(q(s),{q(k):str(v) for k,v in a.items()});n.text=t;return n
for mode in ['drawing','section']:
 r=E.fromstring(data['content.xml']);a=r.find(q('office:automatic-styles'));b=r.find('.//'+q('office:text'))
 for n in list(b):
  if n.tag!=q('text:sequence-decls'):b.remove(n)
 def style(name,family,props,kind):
  s=el('style:style',{'style:name':name,'style:family':family});s.append(el(kind,props));a.append(s);return s
 style('P2Marker','graphic',{'draw:fill':'solid','draw:fill-color':'#375e80','draw:stroke':'none','style:wrap':'run-through','style:run-through':'foreground','style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph','style:vertical-pos':'from-top','style:vertical-rel':'paragraph'},'style:graphic-properties')
 style('P2Line','graphic',{'draw:stroke':'solid','svg:stroke-color':'#87accb','svg:stroke-width':'0.04cm','style:wrap':'run-through'},'style:graphic-properties')
 style('P2Heading','paragraph',{'fo:margin-top':'0.2cm','fo:margin-bottom':'0.1cm'},'style:paragraph-properties').append(el('style:text-properties',{'fo:font-weight':'bold','fo:color':'#375e80','fo:font-size':'14pt'}))
 b.append(el('text:p',{'text:style-name':'P2Heading'},'P2-V02 | '+mode))
 if mode=='drawing':
  p=el('text:p');b.append(p)
  for i,y in enumerate([1,4,8]):
   n=el('draw:ellipse',{'draw:name':'Marker'+str(i),'draw:id':'marker'+str(i),'draw:style-name':'P2Marker','text:anchor-type':'paragraph','svg:x':'2cm','svg:y':str(y)+'cm','svg:width':'0.4cm','svg:height':'0.4cm','draw:z-index':10+i});p.append(n)
   f=el('draw:frame',{'draw:name':'Station'+str(i),'text:anchor-type':'paragraph','svg:x':'3cm','svg:y':str(y)+'cm','svg:width':'10cm','svg:height':'2cm'});box=el('draw:text-box');box.append(el('text:p',{'text:style-name':'P2Heading'},'Station '+str(i+1)))
   for j in range(i+1):box.append(el('text:p',t='Dynamic content '+str(j+1)))
   f.append(box);p.append(f)
  for i,(y1,y2) in enumerate([(1.2,4.2),(4.2,8.2)]):
   p.append(el('draw:connector',{'draw:style-name':'P2Line','text:anchor-type':'paragraph','draw:type':'line','draw:start-shape':'marker'+str(i),'draw:end-shape':'marker'+str(i+1),'svg:x1':'2.2cm','svg:y1':str(y1)+'cm','svg:x2':'2.2cm','svg:y2':str(y2)+'cm','draw:z-index':i}))
 else:
  s=style('P2Section','section',{},'style:section-properties');g=s[0];cols=el('style:columns',{'fo:column-count':'2','fo:column-gap':'0.6cm'});cols.append(el('style:column-sep',{'style:width':'0.04cm','style:height':'100%','style:color':'#87accb','style:vertical-align':'middle'}))
  for w in ['1*','4*']:cols.append(el('style:column',{'style:rel-width':w,'fo:start-indent':'0.3cm','fo:end-indent':'0.3cm'}))
  g.append(cols)
  style('P2Break','paragraph',{'fo:break-before':'column'},'style:paragraph-properties')
  for i in range(3):
   sec=el('text:section',{'text:name':'Station'+str(i),'text:style-name':'P2Section'});b.append(sec)
   p=el('text:p',t=str(2026-i));sec.append(p)
   p.append(el('draw:ellipse',{'draw:name':'Marker'+str(i),'draw:style-name':'P2Marker','text:anchor-type':'paragraph','svg:x':'2.5cm','svg:y':'0cm','svg:width':'0.4cm','svg:height':'0.4cm'}))
   sec.append(el('text:p',{'text:style-name':'P2Break'},'Station '+str(i+1)))
   for j in range(2+i*3):sec.append(el('text:p',t='Dynamic content '+str(j+1)+' — project coordination and documentation.'))
 out=dict(data);out['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
 with ZipFile('p2-v02-native-'+mode+'.odt','w') as z:
  for n,v in out.items():z.writestr(n,v)
