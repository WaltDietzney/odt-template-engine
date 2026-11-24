#!/usr/bin/env python3
"""Inspect native structure, authoritative fields, resources and rendered completeness."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as E
import json, hashlib, re, unicodedata, subprocess
from generate import NS, q, FULL, LONG
BASE=Path(__file__).resolve().parent
def compact(s):return re.sub(r'\s+','',unicodedata.normalize('NFKC',s)).replace('\u00ad','')
def inspect(path):
 with ZipFile(path) as z:
  parts={p:E.fromstring(z.read(p)) for p in ['content.xml','styles.xml']}
  manifest=E.fromstring(z.read('META-INF/manifest.xml'))
  entries={e.get(q('manifest:full-path')):e.get(q('manifest:media-type')) for e in manifest}
  refs=[];decls={};fields=[]
  for part,r in parts.items():
   assert '{{' not in E.tostring(r,encoding='unicode'),path
   for e in r.iter(q('text:user-field-decl')):decls[e.get(q('text:name'))]=e.get(q('office:string-value'))
   fields += [(part,e.get(q('text:name')),e.text or '') for e in r.iter(q('text:user-field-get'))]
   for e in r.iter(q('draw:image')):
    href=e.get(q('xlink:href'))
    if href:refs.append(href);assert href in z.namelist(),(path,href)
  for href in set(refs):
   if href.endswith('.svg'):assert entries[href]=='image/svg+xml'
  return {'fields':fields,'declarations':decls,'image_references':refs,
   'unique_referenced_resources':len(set(refs)),'all_picture_resources':len([p for p in z.namelist() if p.startswith('Pictures/') and not p.endswith('/')]),
   'frames':sum(len(list(r.iter(q('draw:frame')))) for r in parts.values()),
   'hidden_paragraphs':sum(len(list(r.iter(q('text:hidden-paragraph')))) for r in parts.values())}
names=json.loads((BASE/'outputs.json').read_text());report={}
for name in names:
 a=inspect(BASE/(name+'.odt'));b=inspect(BASE/'roundtrip'/(name+'.odt'))
 assert a['declarations']==b['declarations'],(name,'declarations changed')
 assert sorted(x[1] for x in a['fields'])==sorted(x[1] for x in b['fields']),(name,'field references lost')
 text=subprocess.check_output(['pdftotext','-raw',str(BASE/'pdf'/(name+'.pdf')),'-'],text=True)
 (BASE/'pdf'/(name+'.raw.txt')).write_text(text)
 missing=[k for k,v in b['declarations'].items() if v and compact(v) not in compact(text)]
 # Shape/text caching must be re-evaluated by Writer; hidden data is separate.
 pixels=[]
 for p in sorted((BASE/'images').glob(name+'-*.png')):
  if not re.fullmatch(re.escape(name)+r'-[0-9]+\.png',p.name):continue
  rt=BASE/'images-rt'/p.name
  pixels.append({'page':p.name,'same_png':p.read_bytes()==rt.read_bytes()})
 report[name]={'structure':a,'roundtrip':b,'missing_pdf_values':missing,'png_comparison':pixels}
 if name!='contact-overflow-rejected':assert not missing,(name,missing)
 assert all(p['same_png'] for p in pixels),(name,'pixel drift')
# Optional omissions are structural, not hidden paragraphs or cached empty fields.
for case,expected in [('contact-empty',{'name'}),('contact-minimal',{'name','email'}),('contact-one-address',{'name','city'})]:
 a=report[case]['structure'];assert set(a['declarations'])==expected,(case,a['declarations'])
 if case=='contact-empty':assert a['frames']==0 and not a['image_references']
assert report['contact-hidden']['structure']['hidden_paragraphs']==1
assert report['contact-hidden']['structure']['image_references']
a=report['contact-resources']['structure'];assert len(a['image_references'])==6 and a['unique_referenced_resources']==4
orig=a['image_references'];new=report['contact-resources-replaced']['structure']['image_references']
assert orig[0]!=new[0] and orig[1:]==new[1:],('replacement changed shared resource consumers')
assert report['contact-sidebar-blank-bound']['structure']['frames']==report['contact-sidebar']['structure']['frames']
for case in ['contact-classic','contact-sidebar','contact-header-sidebar']:
 assert report[case+'-bound']['structure']==report[case+'-bound-repeat']['structure']
 assert report[case+'-reloaded']['structure']['declarations']['email']=='reloaded.binding@example.invalid'
(BASE/'verification.json').write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n')
print(json.dumps({'documents':len(report),'png_pages':sum(len(r['png_comparison']) for r in report.values()),'missing_pdf_values':{n:r['missing_pdf_values'] for n,r in report.items() if r['missing_pdf_values']},'status':'PASS (overflow candidate explicitly excluded from acceptance)'},indent=2))
