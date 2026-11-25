#!/usr/bin/env python3
"""Validate API integration and compare immutable owner research references."""
from pathlib import Path
import json, subprocess, hashlib
B=Path(__file__).resolve().parent
A=B.parent
for path in ['rounded/verify.py','contacts/composition/verify.py','stories/verify.py','regressions/verify.py']:
 subprocess.run(['python3',str(B/path)],check=True)
result={}
for target,reference,expected in [('rounded','rounded-tags',12),('contacts/composition','flow/contacts/composition',20)]:
 rows=[]
 for folder in ['images','images-rt']:
  for p in sorted((B/target/folder).glob('*.png')):
   ref=A/reference/folder/p.name
   rows.append({'file':str(p.relative_to(B/target)),'reference':str(ref.relative_to(A)),'identical':p.read_bytes()==ref.read_bytes()})
 assert len(rows)==expected and all(x['identical'] for x in rows),(target,rows)
 result[target]=rows
(B/'pixel-comparison.json').write_text(json.dumps(result,indent=2)+'\n')
protected=json.loads((A/'consolidation/evidence-before.json').read_text())['files']
research={p:m for p,m in protected.items() if p.startswith('research/')}
root=A.parents[1]
bad=[p for p,m in research.items() if not (root/p).exists() or hashlib.sha256((root/p).read_bytes()).hexdigest()!=m['sha256']]
assert not bad,bad
(B/'preserved-evidence.json').write_text(json.dumps({'research_artifacts_checked':len(research),'mismatches':bad,'note':'Documentation updates are outside the protected artifact comparison.'},indent=2)+'\n')
print('PASS: migrated references, lifecycle, visible paint order, stories, authored regressions and preserved research artifacts.')
