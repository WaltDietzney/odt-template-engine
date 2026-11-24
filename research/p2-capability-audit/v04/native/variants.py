#!/usr/bin/env python3
"""Independent custom SVG, label/separator and orientation probes."""
from generate import Document, FULL, BASE, sub, q
import json
# Original project-owned artwork, not copied from a third-party library.
(BASE/'assets'/'custom-mail.svg').write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 22 7 22 17 12 22 2 17 2 7Z"/><path d="M6 8h12v8H6Z M6 8l6 5 6-5"/></svg>')
d=Document('H07 · austauschbare Ressourcen und Anordnung',FULL)
for family in ['lucide','tabler','custom']:
 p=d.p(d.body,family+' · ');d.image(p,'mail',family,color='#b94336',stroke='2.5')
 p=d.p(d.body);d.field(p,'email')
# Same contact data, right-hand icon, bottom and middle native cell alignment.
for alignment in ['top','middle','bottom']:
 d.p(d.body,'Symbol rechts · Zellenausrichtung '+alignment)
 before=len(d.body);d.compose('editorial',family='tabler')
 frame=d.body[-1]
 for row in frame.iter(q('table:table-row')):
  cells=list(row);row.remove(cells[0]);row.append(cells[0])
 for col in frame.iter(q('table:table-column')):
  key=col.get(q('table:style-name'));col.set(q('table:style-name'),'TextCol' if key=='IconCol' else 'IconCol')
 style='Aligned'+alignment
 d.style(style,'table-cell',{'fo:padding':'0.08cm','fo:border-bottom':'0.02cm solid #87accb','style:vertical-align':alignment},group='table-cell')
 rows=list(frame.iter(q('table:table-row')))
 for row in rows[:-1]:
  for cell in row:cell.set(q('table:style-name'),style)
# Repeated field name in content and styles; existing binding must update both displays.
p=d.p(d.body,'Bewerber: ');d.field(p,'name')
name=d.save('contact-design-options')
names=json.loads((BASE/'outputs.json').read_text())
if name not in names:names.append(name)
(BASE/'outputs.json').write_text(json.dumps(names,indent=2)+'\n')
print(name)
