#!/usr/bin/env python3
"""Prepare native field declarations only; all drawing composition is PHP-owned."""
from pathlib import Path
from zipfile import ZipFile, ZIP_STORED, ZIP_DEFLATED
import sys, xml.etree.ElementTree as E, json
BASE=Path(__file__).resolve().parent
sys.path.insert(0,str(BASE.parent/'native'))
from generate import Document, FULL, LONG, q, node, sub, xml
data=dict(LONG)
data.update(phone='+49 5221 123456',email='ada.beispiel@example.invalid')
d=Document('H07',data)
for p in list(d.body):
 if p.tag==q('text:p'):d.body.remove(p)
p=sub(d.body,'text:p')
for k in data:d.field(p,k)
d.body.remove(p)
meta=node('office:document-meta',{'office:version':'1.2'});sub(meta,'office:meta')
files={'content.xml':xml(d.content),'styles.xml':xml(d.styles),'meta.xml':xml(meta)}
for fam in ['lucide','tabler']:files['Licenses/'+fam+'.txt']=(BASE.parent/'native/assets'/(fam+'-LICENSE')).read_bytes()
m=node('manifest:manifest',{'manifest:version':'1.2'})
sub(m,'manifest:file-entry',{'manifest:full-path':'/','manifest:media-type':'application/vnd.oasis.opendocument.text'})
for p in files:sub(m,'manifest:file-entry',{'manifest:full-path':p,'manifest:media-type':'text/plain' if p.endswith('.txt') else 'text/xml'})
files['META-INF/manifest.xml']=xml(m)
with ZipFile(BASE/'seed.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype','application/vnd.oasis.opendocument.text',compress_type=ZIP_STORED)
 for p,v in files.items():z.writestr(p,v)
(BASE/'data.json').write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n')
