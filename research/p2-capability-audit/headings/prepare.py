#!/usr/bin/env python3
"""Extract immutable accepted decoration and prepare field-free authoring seeds."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED,ZIP_STORED
from xml.dom import minidom as M
import json
B=Path(__file__).resolve().parent
R=B.parents[2]
source=R/'research/p2-capability-audit/v03/semantic/semantic-headings.odt'
with ZipFile(source) as z:files={n:z.read(n) for n in z.namelist()}
d=M.parseString(files['content.xml']);text='urn:oasis:names:tc:opendocument:xmlns:text:1.0';draw='urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';office='urn:oasis:names:tc:opendocument:xmlns:office:1.0'
body=d.getElementsByTagNameNS(office,'text')[0];headings=list(body.getElementsByTagNameNS(text,'h'));labels=[]
assets=B/'decoration-input';assets.mkdir(exist_ok=True)
for i,h in enumerate(headings):
 group=h.getElementsByTagNameNS(draw,'g')[0]
 label=''.join(n.data for n in h.childNodes if n.nodeType==n.TEXT_NODE).strip();labels.append(label)
 doc=M.Document();root=group.cloneNode(True)
 for name,value in d.documentElement.attributes.items():
  if name.startswith('xmlns:'):root.setAttribute(name,value)
 doc.appendChild(root);(assets/(str(i)+'.xml')).write_bytes(doc.toxml(encoding='utf-8'))
 # Heading construction and content now belong to PHP. Keep accepted styles/other body content.
 placeholder=d.createElementNS(text,'text:p');placeholder.appendChild(d.createTextNode('{{heading_'+str(i)+'}}'));body.replaceChild(placeholder,h)
files['content.xml']=d.toxml(encoding='utf-8')
with ZipFile(B/'decorated-seed.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',files.pop('mimetype'),compress_type=ZIP_STORED)
 for name,data in files.items():z.writestr(name,data)
(B/'labels.json').write_text(json.dumps(labels,ensure_ascii=False,indent=2)+'\n')
with ZipFile(R/'tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') as z:files={n:z.read(n) for n in z.namelist()}
d=M.parseString(files['content.xml']);body=d.getElementsByTagNameNS(office,'text')[0]
for n in list(body.childNodes):body.removeChild(n)
files['content.xml']=d.toxml(encoding='utf-8')
with ZipFile(B/'plain-seed.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',files.pop('mimetype'),compress_type=ZIP_STORED)
 for name,data in files.items():z.writestr(name,data)
