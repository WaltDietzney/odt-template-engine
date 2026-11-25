#!/usr/bin/env python3
"""Reopen existing accepted ODTs without regenerating or altering their sources."""
from pathlib import Path
from zipfile import ZipFile
import json, subprocess, xml.etree.ElementTree as E
R=Path(__file__).resolve().parent
B=R.parents[1]
subprocess.run(['pdftoppm','-png','-r','150','-singlefile',str(R/'pdf/graphic-headings.pdf'),str(R/'graphic-headings-150dpi')],check=True)
refs={
 'graphic-headings':B/'v03/historical-render/images/p2-v03-grouped-1.png',
 'semantic-headings':B/'v03/semantic/images/semantic-headings-1.png',
 'rectangular-tags':B/'v01/reproduced-render/run-20261124T182959734312Z/images/p2-v01-padding-spacing-1.png'}
report={};inputs=json.loads((R/'inputs.json').read_text())
for name,ref in refs.items():
 p=R/'images'/(name+'-1.png');rt=R/'images-rt'/p.name
 candidate=R/'graphic-headings-150dpi.png' if name=='graphic-headings' else p
 report[name]={'accepted_pixels_identical':candidate.read_bytes()==ref.read_bytes(),'writer_pixels_identical':p.read_bytes()==rt.read_bytes()}
 with ZipFile(R/inputs[name]) as a,ZipFile(R/(name+'.odt')) as c:
  report[name]['authored_content_canonical_xml_preserved']=E.canonicalize(a.read('content.xml'))==E.canonicalize(c.read('content.xml'))
assert all(all(v.values()) for v in report.values()),report
(R/'verification.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
