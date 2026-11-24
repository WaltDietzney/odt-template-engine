#!/usr/bin/env python3
"""Verify public authoring in body, header and footer across save and Writer."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E,json
B=Path(__file__).resolve().parent
NS={'text':'urn:oasis:names:tc:opendocument:xmlns:text:1.0'}
checks={}
for name in ['constructed','constructed-repeat','constructed-reloaded']:
 for folder in ['pdf','pdf-rt']:
  text=(B/folder/(name+'.txt')).read_text()
  assert ('Änne & Co' if name.endswith('reloaded') else 'Ada Beispiel') in text
  assert text.count('aenne@example.invalid')==3
  assert '+49 5221 123456' in text
 with ZipFile(B/(name+'.odt')) as z,ZipFile(B/'roundtrip'/(name+'.odt')) as rt:
  for part,expected in [('content.xml',3),('styles.xml',2)]:
   source=E.fromstring(z.read(part));final=E.fromstring(rt.read(part))
   assert len(source.findall('.//text:user-field-get',NS))==len(final.findall('.//text:user-field-get',NS))==expected
  source=E.fromstring(z.read('content.xml'));final=E.fromstring(rt.read('content.xml'))
  assert len(source.findall('.//text:user-field-decl',NS))==len(final.findall('.//text:user-field-decl',NS))==3
 pages=list((B/'images').glob(name+'-[0-9]*.png'));assert pages and all(p.read_bytes()==(B/'images-rt'/p.name).read_bytes() for p in pages)
 checks[name]={'body_references':3,'header_footer_references':2,'declarations':3,'pdf_complete':True,'roundtrip_pages_identical':len(pages)}
with ZipFile(B/'constructed.odt') as a,ZipFile(B/'constructed-repeat.odt') as c:
 for part in ['content.xml','styles.xml','meta.xml']:assert a.read(part)==c.read(part)
(B/'construction-verification.json').write_text(json.dumps({'status':'PASS','documents':checks},indent=2)+'\n')
print('PASS: public field construction, 3 lifecycle documents')
