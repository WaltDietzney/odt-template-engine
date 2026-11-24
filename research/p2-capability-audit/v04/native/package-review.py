#!/usr/bin/env python3
"""Package actual native ODT candidates and Writer exports for owner review."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import subprocess, hashlib, json
BASE=Path(__file__).resolve().parent
names=['contact-classic','contact-sidebar','contact-horizontal','contact-editorial',
 'contact-sidebar-long','contact-horizontal-long','contact-minimal','contact-empty',
 'contact-header-sidebar','contact-design-options']
subprocess.run(['pdfunite',*[str(BASE/'pdf'/(n+'.pdf')) for n in names],
 str(BASE/'H07-Kontaktvarianten.pdf')],check=True,timeout=30)
with ZipFile(BASE/'H07-Native-ODT-Prototypen.zip','w',ZIP_DEFLATED) as z:
 for n in names:
  z.write(BASE/(n+'.odt'),n+'.odt')
  z.write(BASE/'roundtrip'/(n+'.odt'),'Writer-Roundtrip/'+n+'.odt')
 for p in [BASE/'README.md',BASE/'verification.json',BASE/'engine-probe.json',
           BASE/'assets/lucide-LICENSE',BASE/'assets/tabler-LICENSE',BASE/'assets/provenance.json']:
  z.write(p,p.relative_to(BASE))
report={}
for p in [BASE/'H07-Kontaktvarianten.pdf',BASE/'H07-Native-ODT-Prototypen.zip']:
 report[p.name]={'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()}
(BASE/'review-checksums.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))

review=BASE/'review';review.mkdir(exist_ok=True)
subprocess.run(['pdfunite',*[str(BASE/'pdf'/(n+'.pdf')) for n in names[:4]],str(review/'H07-Kontaktvergleich.pdf')],check=True,timeout=30)
with ZipFile(review/'H07-ODT-Prototypen.zip','w',ZIP_DEFLATED) as z:
 for n in names:z.write(BASE/(n+'.odt'),n+'.odt')
 for p in [BASE/'README.md',BASE/'assets/lucide-LICENSE',BASE/'assets/tabler-LICENSE',BASE/'assets/provenance.json']:z.write(p,p.relative_to(BASE))
for p in review.iterdir():print(p.name,p.stat().st_size,hashlib.sha256(p.read_bytes()).hexdigest())
