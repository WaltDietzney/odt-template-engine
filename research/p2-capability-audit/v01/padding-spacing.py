from zipfile import ZipFile, ZIP_DEFLATED, ZIP_STORED
import xml.etree.ElementTree as E
import io
from pathlib import Path
base=Path(__file__).resolve().parent
with ZipFile(base/'p2-v01-final.odt') as z:d={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(d['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s):p,k=s.split(':');return '{'+ns[p]+'}'+k
r=E.fromstring(d['content.xml'])
g=next(s for s in r.iter(q('style:style')) if s.get(q('style:name'))=='P2NativeAuto').find(q('style:graphic-properties'))
g.attrib.pop(q('fo:padding'),None)
for side in ['top','bottom','left','right']:g.set(q('fo:padding-'+side),'0.152cm')
for side in ['left','right']:g.set(q('fo:margin-'+side),'0.08cm')
for side in ['top','bottom']:g.set(q('fo:margin-'+side),'0.06cm')
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile(base/'p2-v01-padding-spacing.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',d.pop('mimetype'),compress_type=ZIP_STORED)
 for n,v in d.items():z.writestr(n,v)
