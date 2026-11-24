#!/usr/bin/env python3
"""Verify an isolated replay against sealed source and available rendered references."""
from pathlib import Path
from zipfile import ZipFile
import argparse,json,re,subprocess,hashlib,shutil,tarfile
from compare_packages import fingerprint
BASE=Path(__file__).resolve().parent
ROOT=BASE.parents[2]
AUDIT=BASE.parent
parser=argparse.ArgumentParser();parser.add_argument('--report');args=parser.parse_args()
pointer=json.loads((BASE/'latest-replay.json').read_text())
path=ROOT/(args.report or pointer['report']);report=json.loads(path.read_text())
RUN=ROOT/report['run'];STAGE=RUN/'research/p2-capability-audit'
def sha(p):return hashlib.sha256(p.read_bytes()).hexdigest()
comparisons={}
for key,value in report['package_comparisons'].items():
 original=AUDIT/(key+'.odt');replayed=STAGE/(key+'.odt')
 a,ma=fingerprint(original,True);b,mb=fingerprint(replayed,True)
 value.update(equal=a==b,original_sha256=sha(original),comparison_only_generated_paragraph_style_names={'original':ma,'replay':mb})
 value['normalized_different_keys']=[k for k in a if a[k]!=b[k]]
def pages(folder,name):return sorted(p for p in folder.glob(name+'-*.png') if re.fullmatch(re.escape(name)+r'-[0-9]+\.png',p.name))
render_targets=[('v01','p2-v01-final'),('v01','p2-v01-padding-spacing'),('v02','p2-v02-centered'),('v02','p2-v02-alternating-flow'),('v03','p2-v03-fixed')]
render_targets += [('v04/composition',name) for name in json.loads((AUDIT/'v04/composition/outputs.json').read_text())]
render_targets += [('v03/semantic',name) for name in ['semantic-headings','semantic-headings-repeat','semantic-headings-reloaded']]
roundtrips={}
for folder,name in render_targets:
 rendered=STAGE/folder/'images';rt=STAGE/folder/'images-rt'
 source_pages=pages(rendered,name)
 checks=[{'page':p.name,'equal':(rt/p.name).exists() and p.read_bytes()==(rt/p.name).read_bytes()} for p in source_pages]
 roundtrips[folder+'/'+name]={'pages':checks,'equal':bool(checks) and all(p['equal'] for p in checks)}
 if folder in ['v01','v02','v03']:
  pdf=AUDIT/folder/'historical-render/pdf'/(name+'.pdf')
  if not pdf.exists():
   comparisons[folder+'/'+name]={'available':False,'reason':'Historical acceptance source ODT is preserved; no original PDF was recovered. Fresh replay is not labeled as original evidence.'}
   continue
  dest=RUN/'reference-render'/folder;dest.mkdir(parents=True,exist_ok=True)
  subprocess.run(['pdftoppm','-png','-scale-to','1400',str(pdf),str(dest/name)],check=True,timeout=30,capture_output=True)
 else:dest=AUDIT/folder/'images'
 checks=[{'page':p.name,'equal':(rendered/p.name).exists() and p.read_bytes()==(rendered/p.name).read_bytes()} for p in pages(dest,name)]
 comparisons[folder+'/'+name]={'available':True,'pages':checks,'equal':bool(checks) and all(p['equal'] for p in checks)}
before=json.loads((BASE/'evidence-before.json').read_text())
protected={key:value for key,value in before['files'].items() if key.startswith('research/')}
changed=[key for key,value in protected.items() if not (ROOT/key).is_file() or sha(ROOT/key)!=value['sha256']]
# Preserve fresh padding renders explicitly as regenerated evidence, never historical.
fresh=STAGE/'v01';new=AUDIT/'v01/reproduced-render'/RUN.name;generated=[]
for folder in ['pdf','pdf-rt','images','images-rt','roundtrip']:
 for p in (fresh/folder).glob('p2-v01-padding-spacing*'):
  if not p.is_file():continue
  d=new/folder/p.name;d.parent.mkdir(parents=True,exist_ok=True)
  if not d.exists():shutil.copy2(p,d)
  else:assert sha(d)==sha(p)
  generated.append({'source':str(p.relative_to(ROOT)),'destination':str(d.relative_to(ROOT)),'sha256':sha(d),'kind':'regenerated consolidation evidence'})
(BASE/'regenerated-render-provenance.json').write_text(json.dumps(generated,indent=2)+'\n')
report['reference_render_comparisons']=comparisons
report['source_to_roundtrip_render_comparisons']=roundtrips
report['accepted_evidence_changed']=changed
report['accepted_artifacts_untouched']=not changed
passed=all(v['equal'] for v in report['package_comparisons'].values()) and all(v['equal'] for v in roundtrips.values()) and all(v.get('equal',True) for v in comparisons.values()) and not changed
report['status']='PASS' if passed else 'BLOCKED'
report['summary']={'packages':len(report['package_comparisons']),'render_documents':len(roundtrips),'render_pages':sum(len(v['pages']) for v in roundtrips.values()),'reference_documents':sum(v['available'] for v in comparisons.values()),'reference_pages':sum(len(v.get('pages',[])) for v in comparisons.values()),'unavailable_original_render_references':[k for k,v in comparisons.items() if not v['available']],'accepted_evidence_changed':changed}
path.write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n')
pointer['status']=report['status'];(BASE/'latest-replay.json').write_text(json.dumps(pointer,indent=2)+'\n')
print(json.dumps({'status':report['status'],**report['summary']},indent=2))
if not passed:raise SystemExit(2)
