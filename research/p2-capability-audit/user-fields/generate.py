#!/usr/bin/env python3
"""Create a bounded same-name metadata/User Field control using a repository fixture."""
from pathlib import Path
from zipfile import ZipFile, ZIP_STORED, ZIP_DEFLATED
import xml.etree.ElementTree as E
import re
B=Path(__file__).resolve().parent
R=B.parents[2]
N={'office':'urn:oasis:names:tc:opendocument:xmlns:office:1.0','text':'urn:oasis:names:tc:opendocument:xmlns:text:1.0','meta':'urn:oasis:names:tc:opendocument:xmlns:meta:1.0'}
for k,v in N.items():E.register_namespace(k,v)
def q(s):a,b=s.split(':');return '{'+N[a]+'}'+b
def el(p,t,attrs={},text=None):
 n=E.SubElement(p,q(t),{q(k):v for k,v in attrs.items()});n.text=text;return n
with ZipFile(R/'tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') as z:files={n:z.read(n) for n in z.namelist()}
original_namespaces=dict(re.findall(r'xmlns:([\w-]+)="([^"]+)"',files['content.xml'].decode()))
d=E.fromstring(files['content.xml']);body=d.find('.//'+q('office:text'))
for n in list(body):body.remove(n)
decls=el(body,'text:user-field-decls');el(decls,'text:user-field-decl',{'text:name':'contact.email','office:value-type':'string','office:string-value':'field-initial@example.invalid'})
p=el(body,'text:p',text='Writer User Field: ');el(p,'text:user-field-get',{'text:name':'contact.email'},'field-initial@example.invalid')
p=el(body,'text:p',text='Document property: ');el(p,'text:user-defined',{'text:name':'contact.email','text:fixed':'false'},'metadata-initial@example.invalid')
m=E.Element(q('office:document-meta'),{q('office:version'):'1.2'});mr=el(m,'office:meta');el(mr,'meta:user-defined',{'meta:name':'contact.email','meta:value-type':'string'},'metadata-initial@example.invalid')
serialized=E.tostring(d,encoding='utf-8',xml_declaration=True).decode()
root_start=serialized.index('<office:document-content')
root_end=serialized.index('>',root_start)
root=serialized[root_start:root_end]
extra=''.join(' xmlns:'+k+'="'+v+'"' for k,v in original_namespaces.items() if ('xmlns:'+k+'=') not in root)
files['content.xml']=(serialized[:root_end]+extra+serialized[root_end:]).encode();files['meta.xml']=E.tostring(m,encoding='utf-8',xml_declaration=True)
with ZipFile(B/'control.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',files.pop('mimetype'),compress_type=ZIP_STORED)
 for n,v in files.items():z.writestr(n,v)
