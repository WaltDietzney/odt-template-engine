#!/usr/bin/env python3
"""Pin existing evidence and verify a local pre-consolidation checkpoint."""
from pathlib import Path
from zipfile import ZipFile
import hashlib,json,subprocess,tarfile,datetime,shutil
BASE=Path(__file__).resolve().parent
ROOT=BASE.parents[2]
AUDIT=BASE.parent
EXCLUDE={'docs-build','docs-source','__pycache__','.cache'}
def sha(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def command(*args):return subprocess.check_output(args,cwd=ROOT,text=True).strip()
selected=[]
for directory in [AUDIT/'v01',AUDIT/'v02',AUDIT/'v03',AUDIT/'v04',ROOT/'docs/architecture/cv-handoff']:
 for p in sorted(directory.rglob('*')):
  if p.is_file() and not any(part in EXCLUDE for part in p.parts) and not p.name.startswith('.~lock.'):
   selected.append(p)
entries={str(p.relative_to(ROOT)):{'bytes':p.stat().st_size,'sha256':sha(p)} for p in selected}
stamp=datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
archive=BASE/'checkpoints'/('pre-consolidation-'+stamp+'.tar.gz')
with tarfile.open(archive,'x:gz') as tar:
 for p in selected:tar.add(p,arcname=str(p.relative_to(ROOT)),recursive=False)
with tarfile.open(archive,'r:gz') as tar:
 assert len(tar.getmembers())==len(entries)
 for member in tar:
  assert hashlib.sha256(tar.extractfile(member).read()).hexdigest()==entries[member.name]['sha256'],member.name
original=Path('/tmp/p2-a-audit');comparison={'available':original.is_dir(),'equal':[],'different':[],'not_in_temporary_source':[]}
for relative,entry in entries.items():
 if not relative.startswith('research/p2-capability-audit/'):continue
 rel=Path(relative).relative_to('research/p2-capability-audit');candidate=original/rel
 if candidate.is_file():comparison['equal' if sha(candidate)==entry['sha256'] else 'different'].append(str(rel))
 else:comparison['not_in_temporary_source'].append(str(rel))
ref=Path('/srv/projects/cv-generator-pro/research/visual-template-references/prototypes/E011/e011-tags.odt')
copy=BASE/'inputs/e011-tags.odt'
if not copy.exists():shutil.copy2(ref,copy)
assert sha(copy)==sha(ref)
manual=AUDIT/'v01/p2-v01-padding-writer-reference.odt'
manifest={'baseline':command('git','rev-parse','HEAD'),'branch':command('git','branch','--show-current'),
 'forgejo_develop':command('git','ls-remote','forgejo','refs/heads/develop').split()[0],
 'created_utc':stamp,'files':entries,'excluded_reproducible_directories':sorted(EXCLUDE),
 'checkpoint':{'path':str(archive.relative_to(ROOT)),'sha256':sha(archive),'members_verified':len(entries)},
 'temporary_source_comparison':comparison,
 'inputs':{'e011':{'original_path':str(ref),'local_path':str(copy.relative_to(ROOT)),'sha256':sha(copy)},
 'owner_edited_padding':{'path':str(manual.relative_to(ROOT)),'sha256':sha(manual),'kind':'immutable manual Writer-authored reference, not regenerated'}},
 'provenance_limits':['Checkpoint is a verified second copy on Patil, not an independently verified off-host backup.',
 'Original purchased CV reference image licensing/provenance is not cleared by matching current local evidence.',
 'Later v04 and semantic evidence did not exist in the initial temporary source; absence there is not corruption.']}
(BASE/'evidence-before.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
print(json.dumps({'files':len(entries),'checkpoint_bytes':archive.stat().st_size,'checkpoint_verified':True,
 'temporary_equal':len(comparison['equal']),'temporary_different':comparison['different'],
 'local_e011_input_sha256':sha(copy)},indent=2))
