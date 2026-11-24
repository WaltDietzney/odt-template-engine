from zipfile import ZipFile
import xml.etree.ElementTree as E
import io,copy
with ZipFile('p2-v02-native-section.odt') as z:d={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(d['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s):p,k=s.split(':');return '{'+ns[p]+'}'+k
r=E.fromstring(d['content.xml'])
a=r.find(q('office:automatic-styles'))
# Writer normalizes relative column widths to integers; use explicit 65535 total.
cols=r.find('.//'+q('style:columns'))
for c,w in zip(cols.findall(q('style:column')),['16384*','49151*']):c.set(q('style:rel-width'),w)
# Anchor to right edge of left column, centered across its 0.6cm gutter.
g=next(s for s in a if s.get(q('style:name'))=='P2Marker').find(q('style:graphic-properties'))
g.set(q('style:horizontal-pos'),'from-left')
g.set(q('style:horizontal-rel'),'paragraph')
g.set(q('style:vertical-pos'),'middle')
g.set(q('style:vertical-rel'),'paragraph')
# Derive actual available page width from the active page layout.
s=E.fromstring(d['styles.xml'])
for p in s.iter(q('style:page-layout-properties')):print('page',p.attrib)
for i,n in enumerate(r.iter(q('draw:ellipse'))):
 n.set(q('svg:x'),'8.30cm');n.set(q('svg:y'),'0cm');n.set(q('draw:z-index'),str(20+i))
 if i==1:n.tag=q('draw:rect')
 if i==2:
  n.tag=q('draw:custom-shape')
  E.SubElement(n,q('draw:enhanced-geometry'),{q('svg:viewBox'):'0 0 21600 21600',q('draw:type'):'diamond',q('draw:enhanced-path'):'M 10800 0 L 21600 10800 10800 21600 0 10800 Z N'})
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v02-centered.odt','w') as z:
 for n,v in d.items():z.writestr(n,v)
