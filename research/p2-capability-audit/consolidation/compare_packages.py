#!/usr/bin/env python3
"""Comparison-only normalization of proven legacy random paragraph style names."""
from zipfile import ZipFile
import xml.etree.ElementTree as E
import hashlib,json,re
STYLE='urn:oasis:names:tc:opendocument:xmlns:style:1.0'
TEXT='urn:oasis:names:tc:opendocument:xmlns:text:1.0'
DRAW='urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
PATTERN=re.compile(r'^para_[0-9a-f]{8}$')
REFERENCE_ATTRIBUTES={f'{{{TEXT}}}style-name',f'{{{DRAW}}}text-style-name',f'{{{STYLE}}}parent-style-name',f'{{{STYLE}}}next-style-name'}
def tree(e,mapping=None,definition=False):
 mapping=mapping or {}
 attributes=[]
 for key,value in e.attrib.items():
  if key in REFERENCE_ATTRIBUTES:value=mapping.get(value,value)
  if key==f'{{{STYLE}}}name' and e.tag==f'{{{STYLE}}}style' and e.get(f'{{{STYLE}}}family')=='paragraph':
   if definition:continue
   value=mapping.get(value,value)
  attributes.append((key,value))
 return [e.tag,sorted(attributes),e.text or '',[tree(c,mapping) for c in e],e.tail or '']
def digest(value):return hashlib.sha256(json.dumps(value,ensure_ascii=False,separators=(',',':')).encode()).hexdigest()
def fingerprint(path,normalize=False):
 with ZipFile(path) as z:
  parts={p:E.fromstring(z.read(p)) for p in ['content.xml','styles.xml','META-INF/manifest.xml']}
  definitions={}
  for part in ['content.xml','styles.xml']:
   root=parts[part];parents={child:parent for parent in root.iter() for child in parent}
   for e in root.iter(f'{{{STYLE}}}style'):
    name=e.get(f'{{{STYLE}}}name','')
    if e.get(f'{{{STYLE}}}family')=='paragraph' and PATTERN.fullmatch(name):
     definitions.setdefault(name,[]).append((part,parents[e].tag,e))
  mapping={};active=set()
  def identity(name):
   if name in mapping:return mapping[name]
   if name in active:raise ValueError('Cyclic generated paragraph parent identity')
   active.add(name)
   for _,_,e in definitions[name]:
    for attribute in REFERENCE_ATTRIBUTES:
     parent=e.get(attribute)
     if parent in definitions:identity(parent)
   value=sorted([[part,scope,tree(e,mapping,definition=True)] for part,scope,e in definitions[name]],key=lambda x:json.dumps(x,ensure_ascii=False))
   mapping[name]='comparison_para_'+digest(value)
   active.remove(name)
   return mapping[name]
  if normalize:
   for name in definitions:identity(name)
  result={p:digest(tree(e,mapping)) for p,e in parts.items()}
  result['referenced_package_images']={n:hashlib.sha256(z.read(n)).hexdigest() for n in sorted(z.namelist()) if n.startswith('Pictures/') and not n.endswith('/')}
  return result,mapping
