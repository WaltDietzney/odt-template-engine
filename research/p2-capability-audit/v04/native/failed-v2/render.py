#!/usr/bin/env python3
"""Render only this research set with isolated Writer profiles and bounded calls."""
from pathlib import Path
import subprocess, json, tempfile, hashlib
BASE=Path(__file__).resolve().parent
log=[]
def run(cmd):
 p=subprocess.run(cmd,capture_output=True,text=True,timeout=90)
 log.append({'command':cmd,'code':p.returncode,'stdout':p.stdout,'stderr':p.stderr})
 (BASE/'render-log.json').write_text(json.dumps(log,indent=2))
 if p.returncode:raise RuntimeError(p.stderr)
def lo(source,dest,fmt):
 dest.mkdir(exist_ok=True)
 with tempfile.TemporaryDirectory(prefix='h07-writer-') as profile:
  run(['libreoffice','-env:UserInstallation='+Path(profile).as_uri(),'--headless','--convert-to',fmt,'--outdir',str(dest),str(source)])
 target=dest/(source.stem+('.odt' if fmt.startswith('odt') else '.pdf'))
 if not target.exists():raise RuntimeError('Missing output: '+str(target))
 return target
names=json.loads((BASE/'outputs.json').read_text())
for name in names:
 source=BASE/(name+'.odt')
 pdf=lo(source,BASE/'pdf','pdf:writer_pdf_Export')
 rt=lo(source,BASE/'roundtrip','odt:writer8')
 pdf_rt=lo(rt,BASE/'pdf-rt','pdf:writer_pdf_Export')
 for f,d in [(pdf,'images'),(pdf_rt,'images-rt')]:
  out=BASE/d;out.mkdir(exist_ok=True)
  run(['pdftoppm','-png','-scale-to','1400',str(f),str(out/name)])
  run(['pdftotext','-layout',str(f),str(f.with_suffix('.txt'))])
 print(name,flush=True)
print('All native candidates rendered and roundtripped.',flush=True)
