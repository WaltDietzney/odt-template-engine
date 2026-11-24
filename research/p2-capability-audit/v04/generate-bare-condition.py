from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED, ZIP_STORED
import xml.etree.ElementTree as E
import io
base=Path(__file__).resolve().parent
root=base.parents[2]
fixture=root/'tests/fixtures/libreoffice-reference/odt/TEMPLATE-AUTHORING-01C-user-field-header.odt'
with ZipFile(fixture) as z:d={n:z.read(n) for n in z.namelist()}
ns={}
for _,(p,u) in E.iterparse(io.BytesIO(d['content.xml']),events=['start-ns']):
 ns[p]=u
 if p:E.register_namespace(p,u)
def q(s):p,k=s.split(':');return '{'+ns[p]+'}'+k
def el(s,a={},t=None):
 n=E.Element(q(s),{q(k):v for k,v in a.items()});n.text=t;return n
r=E.fromstring(d['content.xml']); b=r.find('.//'+q('office:text'))
for n in list(b):
 if n.tag not in [q('text:user-field-decls'),q('text:sequence-decls')]:b.remove(n)
b.append(el('text:p',t='Contact'))
p=el('text:p');p.append(el('text:user-field-get',{'text:name':'customer'},'Walter'));b.append(p)
for key,label in [('address','Address'),('phone','Phone'),('email','Email'),('website','Website')]:
 b.append(el('text:p',t='{{#if:'+key+'}}'))
 b.append(el('text:p',t=label+': {{'+key+'}}'))
 b.append(el('text:p',t='{{#endif}}'))
b.append(el('text:p',t='END CONTACT'))
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile(base/'contact-template.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',d.pop('mimetype'),compress_type=ZIP_STORED)
 for n,v in d.items():z.writestr(n,v)
