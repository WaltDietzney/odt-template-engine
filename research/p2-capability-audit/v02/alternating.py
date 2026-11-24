exec(open('centered.py').read().split("d['content.xml']=E.tostring")[0])
b=r.find('.//'+q('office:text'))
secs=list(b.iter(q('text:section')))
for i in range(9):
 sec=copy.deepcopy(secs[i%3]);sec.set(q('text:name'),'Extra'+str(i));b.append(sec)
for i,sec in enumerate(b.iter(q('text:section'))):
 ps=list(sec); marker=ps[0][0];ps[0].remove(marker)
 marker.set(q('draw:name'),'TimelineMarker'+str(i))
 if i%2==0:
  # Left content, right date. Marker follows first paragraph.
  ps[0].text='Station '+str(i+1)
  ps[0].append(marker)
  ps[1].text=str(2026-i)
  for p in ps[2:]:sec.remove(p);sec.insert(list(sec).index(ps[1]),p)
 else:
  ps[0].text=str(2026-i);ps[0].append(marker);ps[1].text='Station '+str(i+1)
 if i==5:
  for j in range(45):
   p=E.Element(q('text:p'));p.text='Extended detail '+str(j+1)+' — project coordination and documentation.';sec.append(p)
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v02-alternating-flow.odt','w') as z:
 for n,v in d.items():z.writestr(n,v)
