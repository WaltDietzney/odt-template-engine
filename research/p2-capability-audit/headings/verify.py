#!/usr/bin/env python3
"""Verify programmatic native headings against accepted H09 semantics and visuals."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import re,json,hashlib,subprocess
B=Path(__file__).resolve().parent
N={'text':'urn:oasis:names:tc:opendocument:xmlns:text:1.0','draw':'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0','office':'urn:oasis:names:tc:opendocument:xmlns:office:1.0'}
def q(s):p,n=s.split(':');return '{'+N[p]+'}'+n
def tree(node):return [node.tag,sorted(node.attrib.items()),node.text or '',[tree(c) for c in node]]
with ZipFile(B.parent/'v03/semantic/semantic-headings.odt') as z:
 reference=E.fromstring(z.read('content.xml'))
reference_shapes=[tree(n) for n in reference.iter(q('draw:custom-shape'))]
with ZipFile(B.parent/'v03/semantic/roundtrip/semantic-headings.odt') as z:
 reference_roundtrip=E.fromstring(z.read('content.xml'))
reference_roundtrip_shapes=[tree(n) for n in reference_roundtrip.iter(q('draw:custom-shape'))]
labels=json.loads((B/'labels.json').read_text());titles=json.loads((B/'level-titles.json').read_text())
report={}
for name in ['decorated','decorated-repeat','decorated-reloaded','levels','levels-repeat']:
 decorated=name.startswith('decorated');expected=labels if decorated else titles;levels=['1']*3 if decorated else ['1','2','3','10','1']
 for prefix in ['', 'roundtrip/']:
  with ZipFile(B/(prefix+name+'.odt')) as z:
   for part in ['content.xml','styles.xml']:E.fromstring(z.read(part))
   root=E.fromstring(z.read('content.xml'));body=root.find('.//'+q('office:text'));hs=body.findall(q('text:h'))
   assert [h.get(q('text:outline-level')) for h in hs]==levels,(name,prefix,'levels')
   assert len(hs)==len(expected)
   if decorated:
    assert [''.join(h.itertext()) for h in hs]==labels
    assert [tree(n) for n in body.iter(q('draw:custom-shape'))]==(reference_roundtrip_shapes if prefix else reference_shapes),(name,prefix,'accepted decoration differs')
    assert all(''.join(body.itertext()).count(label)==1 for label in labels)
   else:
    refs=list(body.iter(q('text:user-field-get')));assert len(refs)==1
    if prefix:assert refs[0].text=='Native Überschrift'
   assert '{{' not in E.tostring(body,encoding='unicode')
 for folder in ['pdf','pdf-rt']:
  data=(B/folder/(name+'.pdf')).read_bytes();assert b'/Outlines ' in data
  actual=[bytes.fromhex(h.decode()).decode('utf-16') for h in re.findall(rb'/Title\s*<([0-9A-Fa-f]+)>\s*/Dest',data)]
  assert actual==expected,(name,folder,actual)
  text=subprocess.check_output(['pdftotext','-raw',str(B/folder/(name+'.pdf')),'-'],text=True)
  for label in expected:assert [line.strip() for line in text.splitlines()].count(label)==1,(name,folder,'duplicate or missing title',label)
 pages=sorted((B/'images').glob(name+'-[0-9]*.png'));assert pages
 assert all(p.read_bytes()==(B/'images-rt'/p.name).read_bytes() for p in pages)
 same_accepted=None
 if decorated:
  same_accepted=pages[0].read_bytes()==(B.parent/'v03/semantic/images/semantic-headings-1.png').read_bytes();assert same_accepted
 report[name]={'headings':len(expected),'levels':levels,'pdf_outlines':expected,'roundtrip_pages_identical':len(pages),'accepted_reference_pixels_identical':same_accepted}
for a,b in [('decorated','decorated-repeat'),('levels','levels-repeat')]:
 with ZipFile(B/(a+'.odt')) as x,ZipFile(B/(b+'.odt')) as y:
  for part in ['content.xml','styles.xml']:assert x.read(part)==y.read(part),(a,'repeat-save drift')
result={'status':'PASS','documents':report,'source_baseline':'473ea17705288ad431c3960835fafa05ec2d5340','reference_sha256':hashlib.sha256((B.parent/'v03/semantic/semantic-headings.odt').read_bytes()).hexdigest()}
(B/'verification.json').write_text(json.dumps(result,indent=2,ensure_ascii=False)+'\n')
print(json.dumps({'status':'PASS','documents':5,'writer_roundtrip_pages':sum(v['roundtrip_pages_identical'] for v in report.values()),'accepted_reference_pages':3,'decorative_shapes':len(reference_shapes)},indent=2))
