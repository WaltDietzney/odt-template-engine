exec(open('generate.py').read().split('for i,(label,heading)')[0])
for name in ['BandBlue','BandDark','BandLight']:
 g=next(n for n in a if n.get(q('style:name'))==name)[0]
 g.set(q('style:run-through'),'foreground')
 g.set(q('style:vertical-pos'),'from-top');g.set(q('draw:textarea-vertical-align'),'middle');g.set(q('fo:padding-left'),'0.25cm')
s=style('GroupText','paragraph','style:paragraph-properties',{'fo:margin':'0cm','fo:text-align':'start'})
s.append(el('style:text-properties',{'fo:color':'#ffffff','fo:font-size':'15pt','fo:font-weight':'bold'}))
style('GroupLabel','graphic','style:graphic-properties',{'draw:fill':'none','draw:stroke':'none','draw:textarea-vertical-align':'middle','fo:padding':'0.1cm','draw:auto-grow-height':'false','draw:auto-grow-width':'false'})
for i,(label,heading) in enumerate([('A | Plain band','Berufserfahrung'),('B | Folded ribbon','Ausbildung und berufliche Weiterbildung'),('C | Angled accent','Qualifikationen')]):
 b.append(el('text:p',t=label))
 p=el('text:p');b.append(p)
 group=el('draw:g',{'draw:name':'HeadingGroup'+str(i),'text:anchor-type':'as-char'});p.append(group)
 if i==1:shape(group,'Fold','BandDark','0','0.9','0.55','0.35','M 0 0 L 21600 0 21600 21600 Z N',0)
 shape(group,'Band'+str(i),'BandBlue','0','0','12','0.9',rect if i==0 else ('M 0 0 L 21600 0 20400 10800 21600 21600 0 21600 Z N' if i==1 else 'M 0 0 L 19800 0 21600 21600 0 21600 Z N'),1)
 if i==2:shape(group,'Accent','BandLight','11.2','0','1.25','0.9','M 0 0 L 4320 0 21600 21600 17280 21600 Z N',2)
 band=next(n for n in group if n.get(q('draw:name'))=='Band'+str(i))
 for n in group:
  n.attrib.pop(q('text:anchor-type'),None)
 t=band.find(q('text:p'));t.set(q('text:style-name'),'GroupText');t.text=heading
 b.append(el('text:p',t='Editable body text follows the grouped heading.'))
 b.append(el('text:p',t=' '))
d['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v03-integrated.odt','w') as z:
 for n,v in d.items():z.writestr(n,v)
