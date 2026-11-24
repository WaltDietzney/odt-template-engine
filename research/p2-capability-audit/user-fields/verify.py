#!/usr/bin/env python3
"""Assert independent native field stores, effective Writer values and repeat-save stability."""
from pathlib import Path
from zipfile import ZipFile
import json,xml.etree.ElementTree as E,hashlib
B=Path(__file__).resolve().parent
N={'text':'urn:oasis:names:tc:opendocument:xmlns:text:1.0','office':'urn:oasis:names:tc:opendocument:xmlns:office:1.0','meta':'urn:oasis:names:tc:opendocument:xmlns:meta:1.0'}
def part(file,name):
 with ZipFile(file) as z:return z.read(name)
def values(file):
 c=E.fromstring(part(file,'content.xml'));m=E.fromstring(part(file,'meta.xml'))
 d=c.find('.//text:user-field-decl',N);g=c.find('.//text:user-field-get',N);p=c.find('.//text:user-defined',N);v=m.find('.//meta:user-defined',N)
 return {'declaration':None if d is None else d.get('{'+N['office']+'}string-value'),'reference_cache':None if g is None else g.text,'metadata':v.text,'property_cache':p.text,'reference_count':len(c.findall('.//text:user-field-get',N)),'property_count':len(c.findall('.//text:user-defined',N))}
checks={}
for name in ['control','metadata','field','both','metadata-only']:
 field=('field-updated' if name in ['field','both'] else 'field-initial')+'@example.invalid'
 meta=('metadata-updated' if name in ['metadata','both'] else 'metadata-initial')+'@example.invalid'
 before=values(B/(name+'.odt'));after=values(B/'roundtrip'/(name+'.odt'))
 assert before['metadata']==after['metadata']==meta
 assert after['property_cache']==meta and after['property_count']==1
 assert after['reference_count']==(0 if name=='metadata-only' else 1)
 if name!='metadata-only':assert before['declaration']==after['declaration']==after['reference_cache']==field
 for folder in ['pdf','pdf-rt']:
  text=(B/folder/(name+'.txt')).read_text()
  assert meta in text
  if name!='metadata-only':assert field in text
 for folder in ['images']:
  a=sorted((B/folder).glob(name+'-[0-9]*.png'));other=B/'images-rt'
  assert a and all(p.read_bytes()==(other/p.name).read_bytes() for p in a)
 if name in ['metadata','field','both']:
  for p in ['content.xml','styles.xml','meta.xml']:assert part(B/(name+'.odt'),p)==part(B/(name+'-repeat.odt'),p)
 checks[name]={'source':before,'writer_roundtrip':after,'pdf_values':'PASS','roundtrip_pixels':'PASS'}
life=json.loads((B/'lifecycle.json').read_text());assert life['metadata_only_binding_reason']=='NOT_FOUND'
result={'status':'PASS','cases':checks,'metadata_only_binding_reason':life['metadata_only_binding_reason'],'files':{str(p.relative_to(B)):hashlib.sha256(p.read_bytes()).hexdigest() for p in B.rglob('*') if p.is_file() and p.suffix in ['.odt','.pdf','.png'] and 'failed-initial' not in p.parts}}
(B/'verification.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps({'status':'PASS','cases':len(checks),'repeat_save_cases':3,'writer_roundtrip_pages':5},indent=2))
