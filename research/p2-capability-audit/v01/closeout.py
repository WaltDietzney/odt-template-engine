from zipfile import ZipFile, ZIP_DEFLATED, ZIP_STORED
import xml.etree.ElementTree as E
import io, copy
from pathlib import Path
base=Path(__file__).resolve().parent
with ZipFile(base/'p2-v01-padding.odt') as z: data={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(data['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s): p,k=s.split(':');return '{'+ns[p]+'}'+k
def el(s,a={},t=None):
 n=E.Element(q(s),{q(k):v for k,v in a.items()});n.text=t;return n
r=E.fromstring(data['content.xml']); a=r.find(q('office:automatic-styles')); b=r.find('.//'+q('office:text'))
st=copy.deepcopy(next(s for s in a if s.get(q('style:name'))=='P2PadSmall'))
st.set(q('style:name'),'P2RoundedAuto');a.append(st)
p=el('text:p',t='Rounded custom shape + auto-grow candidate (not accepted)');b.append(p)
p=el('text:p');b.append(p)
for i,label in enumerate(['PHP','PostgreSQL','Projektmanagement','Longer editable label']):
 n=el('draw:custom-shape',{'draw:name':'RoundedTag'+str(i),'draw:style-name':'P2RoundedAuto','text:anchor-type':'as-char','svg:width':'1cm','svg:height':'0.5cm'})
 n.append(el('text:p',t=label))
 n.append(el('draw:enhanced-geometry',{'svg:viewBox':'0 0 21600 21600','draw:type':'round-rectangle','draw:modifiers':'3600','draw:enhanced-path':'M 3600 0 L 18000 0 Q 21600 0 21600 3600 L 21600 18000 Q 21600 21600 18000 21600 L 3600 21600 Q 0 21600 0 18000 L 0 3600 Q 0 0 3600 0 Z N','draw:text-areas':'1800 1800 19800 19800'}))
 p.append(n);n.tail=' '
data['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile(base/'p2-v01-closeout.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',data.pop('mimetype'),compress_type=ZIP_STORED)
 for n,v in data.items():z.writestr(n,v)
