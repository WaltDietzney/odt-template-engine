#!/usr/bin/env python3
"""Replay accepted P2 generators in a new tree; never overwrite owner evidence."""
from pathlib import Path
from zipfile import ZipFile
import argparse,datetime,subprocess,json,hashlib,shutil,os,sys,time
import xml.etree.ElementTree as E

BASE=Path(__file__).resolve().parent
ROOT=BASE.parents[2]
AUDIT=BASE.parent
args=argparse.ArgumentParser()
args.add_argument('--render',action='store_true',help='Include Writer/PDF/PNG technical replay, not new visual acceptance.')
options=args.parse_args()
stamp=datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
RUN=BASE/'replay'/('run-'+stamp)
TREE=RUN/'research/p2-capability-audit'
TREE.mkdir(parents=True)
report={'run':str(RUN.relative_to(ROOT)),'source_head':subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip(),
 'render_requested':options.render,'comparison_policy':'Definition-based normalization of legacy random paragraph style names; no source/package mutation','commands':[],'transport_patches':[],'package_comparisons':{},'status':'RUNNING'}
def save():
 (RUN/'replay-report.json').write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n')
 (BASE/'latest-replay.json').write_text(json.dumps({'report':str((RUN/'replay-report.json').relative_to(ROOT)),'status':report['status']},indent=2)+'\n')
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def copy(source,destination):
 destination.parent.mkdir(parents=True,exist_ok=True)
 shutil.copy2(source,destination)
 return destination
def patch(path,replacements):
 source=path.read_text();original=source
 for old,new in replacements:
  if old not in source:raise RuntimeError('Expected transport dependency missing in '+str(path)+': '+old)
  source=source.replace(old,new)
 path.write_text(source)
 report['transport_patches'].append({'path':str(path.relative_to(RUN)),'before_sha256':hashlib.sha256(original.encode()).hexdigest(),'after_sha256':digest(path),'replacements':replacements})
def run(command,cwd,timeout=90):
 start=time.monotonic()
 p=subprocess.run(command,cwd=cwd,capture_output=True,text=True,timeout=timeout)
 report['commands'].append({'command':command,'cwd':str(cwd.relative_to(RUN)),'exit_code':p.returncode,
 'seconds':round(time.monotonic()-start,3),'stdout':p.stdout[-12000:],'stderr':p.stderr[-12000:]})
 save()
 if p.returncode:raise RuntimeError('Replay failed: '+' '.join(command)+'\n'+p.stderr)
 print('PASS',str(cwd.relative_to(RUN)),Path(command[-1]).name,flush=True)
def php_quote(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
from compare_packages import fingerprint as package_fingerprint
def fingerprint(path):return package_fingerprint(path,normalize=True)[0]
try:
 # Historical PHP entry points are transported to current source and repository fixture.
 bootstrap=AUDIT/'v04/native/bootstrap.php'
 for version in ['v01','v02','v03']:
  for source in (AUDIT/version).iterdir():
   if source.suffix in ['.py','.php']:copy(source,TREE/version/source.name)
 for version in ['v01','v02']:
  p=TREE/version/'generate.php'
  patch(p,[
   ("require '/tmp/odt-h04-analysis/vendor/autoload.php';",'require '+php_quote(bootstrap)+';'),
   ('/tmp/odt-h04-analysis/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt',str(ROOT/'tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt')),
   ('/tmp/p2-a-audit/'+version+'/',str(TREE/version)+'/'),
  ])
 reference=copy(BASE/'inputs/e011-tags.odt',RUN/'inputs/e011-tags.odt')
 patch(TREE/'v01/extend.py',[
  ('/srv/projects/cv-generator-pro/research/visual-template-references/prototypes/E011/e011-tags.odt',str(reference)),
 ])
 for command in [['php','generate.php'],['python3','finish.py'],['python3','padding-spacing.py']]:
  run(command,TREE/'v01')
 for command in [['php','generate.php'],['python3','native.py'],['python3','centered.py'],['python3','alternating.py']]:
  run(command,TREE/'v02')
 run(['python3','fixed.py'],TREE/'v03')
 # Current class-based native contact composition; only its local authoring helper/assets are needed.
 native=TREE/'v04/native';native.mkdir(parents=True)
 for name in ['generate.py','bootstrap.php']:copy(AUDIT/'v04/native'/name,native/name)
 patch(native/'bootstrap.php',[('$root = dirname(__DIR__, 4);','$root = '+php_quote(ROOT)+';')])
 shutil.copytree(AUDIT/'v04/native/assets',native/'assets')
 composition=TREE/'v04/composition'
 for source in (AUDIT/'v04/composition').iterdir():
  if source.suffix in ['.py','.php']:copy(source,composition/source.name)
 for command in [['python3','seed.py'],['php','carrier.php'],['php','generate.php'],['php','lifecycle.php']]:
  run(command,composition)
 semantic=TREE/'v03/semantic'
 for source in (AUDIT/'v03/semantic').iterdir():
  if source.suffix in ['.py','.php']:copy(source,semantic/source.name)
 for command in [['python3','generate.py'],['php','bind.php']]:run(command,semantic)
 # Compare current accepted source packages structurally; ZIP timestamps/metadata are not byte reproducibility.
 targets=[
 ('v01','p2-v01-engine'),('v01','p2-v01-final'),('v01','p2-v01-padding-spacing'),
 ('v02','p2-v02-centered'),('v02','p2-v02-alternating-flow'),('v03','p2-v03-fixed'),
 ('v03/semantic','semantic-headings'),('v03/semantic','semantic-headings-repeat'),('v03/semantic','semantic-headings-reloaded'),
 ]
 for name in json.loads((composition/'outputs.json').read_text()):targets.append(('v04/composition',name))
 for folder,name in targets:
  original=AUDIT/folder/(name+'.odt');replayed=TREE/folder/(name+'.odt')
  a,b=fingerprint(original),fingerprint(replayed)
  report['package_comparisons'][folder+'/'+name]={'equal':a==b,'different_keys':[k for k in a if a[k]!=b[k]],'original':a,'replay':b,'original_sha256':digest(original)}
 if options.render:
  render=AUDIT/'v04/native/render.py'
  for folder,names in [
   ('v01',['p2-v01-final','p2-v01-padding-spacing']),
   ('v02',['p2-v02-centered','p2-v02-alternating-flow']),
   ('v03',['p2-v03-fixed']),
   ('v04/composition',[]),
   ('v03/semantic',['semantic-headings','semantic-headings-repeat','semantic-headings-reloaded']),
  ]:
   copy(render,TREE/folder/'replay-render.py')
   run(['python3','replay-render.py',*names],TREE/folder,timeout=240)
  run(['python3','replay-render.py','carrier'],composition,timeout=90)
  run(['python3','verify.py'],composition)
  run(['python3','verify.py'],semantic)
 report['status']='PASS' if all(x['equal'] for x in report['package_comparisons'].values()) else 'STRUCTURAL_DIFFERENCES'
 report['accepted_artifacts_untouched']=all(digest(AUDIT/f/(n+'.odt'))==report['package_comparisons'][f+'/'+n]['original_sha256'] for f,n in targets)
 save()
 print(json.dumps({'status':report['status'],'packages':len(targets),'structural_differences':[n for n,v in report['package_comparisons'].items() if not v['equal']],'report':str(RUN/'replay-report.json')},indent=2),flush=True)
 if report['status']!='PASS':sys.exit(2)
except Exception as error:
 report['status']='BLOCKED';report['error']=str(error);save();raise
