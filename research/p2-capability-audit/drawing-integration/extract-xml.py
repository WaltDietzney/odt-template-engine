#!/usr/bin/env python3
"""Extract selected actual package parts without changing source packages."""
from pathlib import Path
from zipfile import ZipFile
from xml.dom.minidom import parseString
B=Path(__file__).resolve().parent
for name,path in [('rounded','rounded/showcase.odt'),('sidebar','contacts/composition/sidebar-rounded.odt'),('stories','stories/background.odt')]:
 for stage in ['source','roundtrip']:
  p=B/path
  if stage=='roundtrip':p=p.parent/'roundtrip'/p.name
  dest=B/'xml'/name/stage;dest.mkdir(parents=True,exist_ok=True)
  with ZipFile(p) as z:
   for part in ['content.xml','styles.xml','META-INF/manifest.xml']:
    (dest/Path(part).name).write_bytes(parseString(z.read(part)).toprettyxml(encoding='utf-8'))
print('Extracted actual source/roundtrip XML for rounded, sidebar and stories.')
