#!/usr/bin/env python3
"""Native H07 research fixtures; no production serializer or API changes."""
from pathlib import Path
from zipfile import ZipFile, ZIP_STORED, ZIP_DEFLATED
import xml.etree.ElementTree as E
import hashlib, json, copy, re

BASE = Path(__file__).resolve().parent
NS = {p:'urn:oasis:names:tc:opendocument:xmlns:'+n+':1.0' for p,n in [
 ('office','office'),('style','style'),('text','text'),('table','table'),
 ('draw','drawing'),('fo','xsl-fo-compatible'),('svg','svg-compatible'),
 ('manifest','manifest')]}
NS.update(xlink='http://www.w3.org/1999/xlink',dc='http://purl.org/dc/elements/1.1/',
 ooow='http://openoffice.org/2004/writer')
for k,v in NS.items(): E.register_namespace(k,v)
def q(s):
 p,n=s.split(':'); return '{'+NS[p]+'}'+n
def node(tag, attrs=None, text=None):
 e=E.Element(q(tag),{q(k):str(v) for k,v in (attrs or {}).items()}); e.text=text; return e
def sub(parent,tag,attrs=None,text=None):
 e=node(tag,attrs,text);parent.append(e);return e
def xml(e): return E.tostring(e,encoding='utf-8',xml_declaration=True)
FULL={'name':'Ada Beispiel','street':'Am Wissenschaftspark','house':'128 a',
 'postal':'32049','city':'Herford','country':'Deutschland','phone':'+49 5221 123456',
 'mobile':'+49 171 1234567','email':'ada.beispiel@example.invalid',
 'website':'https://example.invalid/portfolio','profile':'https://example.invalid/ada'}
LONG=dict(FULL, street='An der alten naturwissenschaftlichen Forschungsstation',
 city='Bad Oeynhausen – Stadtteil Rehme', email='sehrlangerzusammenhaengenderkontaktnameohnetrennzeichen@example.invalid',
 website='https://example.invalid/ausfuehrlichesportfolioohnetrennzeichen/beruflicherwerdegang',
 profile='https://example.invalid/ada/entwicklung/forschung/coaching')
ROLES=[('address','map-pin','ANSCHRIFT',['street','house','postal','city','country']),
 ('phone','phone','TELEFON',['phone']),('mobile','smartphone','MOBIL',['mobile']),
 ('email','mail','E-MAIL',['email']),('website','globe','WEBSEITE',['website']),
 ('profile','user-round','PROFIL',['profile'])]
TABLER={'smartphone':'device-mobile','globe':'world','user-round':'user'}
class Document:
 def __init__(self,title,data):
  self.title,self.data=title,data;self.resources={};self.used=set();self.serial=0
  self.content=node('office:document-content',{'office:version':'1.2'})
  self.auto=sub(self.content,'office:automatic-styles')
  self.body=sub(sub(self.content,'office:body'),'office:text')
  self.decls=sub(self.body,'text:user-field-decls')
  self.styles=node('office:document-styles',{'office:version':'1.2'})
  self.common=sub(self.styles,'office:styles')
  fr=sub(self.common,'style:style',{'style:name':'Frame','style:family':'graphic'})
  sub(fr,'style:graphic-properties',{'style:wrap':'parallel','fo:padding':'0cm','fo:border':'none','style:horizontal-pos':'left','style:horizontal-rel':'paragraph','style:vertical-pos':'top','style:vertical-rel':'paragraph'})
  default=sub(self.common,'style:default-style',{'style:family':'paragraph'})
  sub(default,'style:paragraph-properties',{'fo:margin-top':'0cm','fo:margin-bottom':'0cm','fo:orphans':'1','fo:widows':'1'})
  sub(default,'style:text-properties',{'fo:font-family':'Liberation Sans','fo:font-size':'10pt','fo:language':'de','fo:country':'DE'})
  self.style('Body','paragraph',{'fo:margin-bottom':'0.18cm'},{'fo:color':'#24384b'})
  self.style('Title','paragraph',{'fo:margin-bottom':'0.35cm'},{'fo:font-size':'19pt','fo:font-weight':'bold','fo:color':'#375e80'})
  self.style('White','paragraph',{'fo:margin-bottom':'0.12cm'},{'fo:color':'#ffffff','fo:font-size':'10pt'})
  self.style('Label','paragraph',{'fo:margin-bottom':'0.06cm'},{'fo:font-size':'7pt','fo:font-weight':'bold','fo:color':'#87accb'})
  self.style('CellP','paragraph',{'fo:margin-bottom':'0cm'},{'fo:font-size':'10pt','fo:color':'#24384b'})
  self.style('Hang','paragraph',{'fo:margin-left':'0.85cm','fo:text-indent':'-0.85cm','fo:margin-bottom':'0.22cm'},{'fo:color':'#24384b'})
  h=self.auto[-1]; props=h.find(q('style:paragraph-properties'))
  tabs=sub(props,'style:tab-stops');sub(tabs,'style:tab-stop',{'style:position':'0.85cm','style:type':'left'})
  self.style('Icon','graphic',{'style:vertical-pos':'top','style:vertical-rel':'text','style:wrap':'none'},group='graphic')
  self.style('Backing','graphic',{'draw:fill':'solid','draw:fill-color':'#dce8f2','draw:stroke':'solid','svg:stroke-color':'#87accb','svg:stroke-width':'0.025cm'},group='graphic')
  self.style('Sidebar','graphic',{'draw:fill':'solid','draw:fill-color':'#203a52','draw:stroke':'none','fo:padding-left':'0.4cm','fo:padding-right':'0.4cm','fo:padding-top':'0.4cm','fo:padding-bottom':'0.4cm','draw:auto-grow-height':'true','draw:auto-grow-width':'false','style:wrap':'none'},group='graphic')
  self.style('PlainFrame','graphic',{'draw:fill':'none','draw:stroke':'none','fo:padding-left':'0.1cm','fo:padding-right':'0.1cm','fo:padding-top':'0.1cm','fo:padding-bottom':'0.1cm','draw:auto-grow-height':'true','draw:auto-grow-width':'false','style:wrap':'none'},group='graphic')
  self.style('Row','table-row',{'style:min-row-height':'0.65cm','fo:keep-together':'always'},group='table-row')
  self.style('Cell','table-cell',{'fo:padding-left':'0.04cm','fo:padding-right':'0.04cm','fo:padding-top':'0.09cm','fo:padding-bottom':'0.09cm','fo:border':'none','style:vertical-align':'top'},group='table-cell')
  self.style('IconCol','table-column',{'style:column-width':'0.85cm'},group='table-column')
  self.style('TextCol','table-column',{'style:column-width':'4.35cm'},group='table-column')
  self.style('ContactTable','table',{'style:width':'5.2cm','table:align':'left','fo:margin-left':'0cm','fo:margin-right':'0cm'},group='table')
  sa=sub(self.styles,'office:automatic-styles')
  pl=sub(sa,'style:page-layout',{'style:name':'Page'})
  sub(pl,'style:page-layout-properties',{'fo:page-width':'21cm','fo:page-height':'29.7cm','style:print-orientation':'portrait','fo:margin-top':'1.5cm','fo:margin-bottom':'1.5cm','fo:margin-left':'1.5cm','fo:margin-right':'1.5cm'})
  hs=sub(pl,'style:header-style');sub(hs,'style:header-footer-properties',{'fo:min-height':'0.3cm','fo:margin-bottom':'0.3cm'})
  masters=sub(self.styles,'office:master-styles')
  master=sub(masters,'style:master-page',{'style:name':'Standard','style:page-layout-name':'Page'})
  self.header=sub(master,'style:header')
  p=sub(self.header,'text:p');p.text='H07 · '; self.field(p,'name')
  self.p(self.body,title,'Title')
 def style(self,name,family,props,text=None,group='paragraph'):
  s=sub(self.auto,'style:style',{'style:name':name,'style:family':family})
  if name in ['Sidebar','PlainFrame']:s.set(q('style:parent-style-name'),'Frame')
  sub(s,'style:'+group+'-properties',props)
  if text:sub(s,'style:text-properties',text)
  return s
 def p(self,parent,text=None,style='Body'):
  return sub(parent,'text:p',{'text:style-name':style},text)
 def field(self,p,key):
  if key not in self.used:
   sub(self.decls,'text:user-field-decl',{'text:name':key,'office:value-type':'string','office:string-value':self.data.get(key,'')})
   self.used.add(key)
  return sub(p,'text:user-field-get',{'text:name':key},self.data.get(key,''))
 def values(self,p,role,keys):
  active=[k for k in keys if self.data.get(k,'').strip()]
  for i,key in enumerate(active):
   if i:
    if role=='address' and key in ['postal','country']:sub(p,'text:line-break')
    else:sub(p,'text:s')
   self.field(p,key)
 def image(self,parent,icon,family='lucide',color='#375e80',stroke='1.8',backing=None):
  name=TABLER.get(icon,icon) if family=='tabler' else icon
  source=(BASE/'assets'/f'{family}-{name}.svg').read_bytes()
  # Deliberately bounded local static SVG subset, not a general sanitizer.
  root=E.fromstring(source)
  allowed={'svg','path','circle','rect','ellipse','line','polyline','polygon','g'}
  for e in root.iter():
   if e.tag.split('}')[-1] not in allowed:raise ValueError('Unsafe SVG element')
   for k,v in e.attrib.items():
    if k.lower().startswith('on') or 'href' in k.lower() or 'url(' in v or '<!' in v:raise ValueError('Unsafe SVG attribute')
  root.set('stroke',color);root.set('stroke-width',stroke);root.set('width','24');root.set('height','24')
  raw=E.tostring(root); digest=hashlib.sha256(raw).hexdigest()
  path='Pictures/'+digest+'.svg';self.resources[path]=raw
  self.serial+=1
  host=parent
  if backing:
   host=sub(parent,'draw:g',{'draw:name':'IconGroup'+str(self.serial),'text:anchor-type':'as-char','svg:width':'0.65cm','svg:height':'0.65cm'})
   shape='draw:ellipse' if backing in ['circle','ellipse'] else 'draw:rect'
   a={'draw:style-name':'Backing','svg:x':'0cm','svg:y':'0cm','svg:width':'0.65cm','svg:height':'0.65cm'}
   if backing=='ellipse':a['svg:height']='0.5cm'
   if backing=='rectangle':a['svg:height']='0.5cm'
   if backing=='rounded':a['draw:corner-radius']='0.12cm'
   sub(host,shape,a)
  a={'draw:name':'ContactIcon'+str(self.serial),'draw:style-name':'Icon','svg:width':'0.45cm','svg:height':'0.45cm'}
  if backing:a.update({'svg:x':'0.1cm','svg:y':'0.1cm'})
  else:a['text:anchor-type']='as-char'
  f=sub(host,'draw:frame',a)
  sub(f,'draw:image',{'xlink:href':path,'xlink:type':'simple','xlink:show':'embed','xlink:actuate':'onLoad'})
 def rows(self):return [r for r in ROLES if any(self.data.get(k,'').strip() for k in r[3])]
 def frame(self,parent,dark=False,width='6cm',name='ContactSidebar'):
  p=self.p(parent)
  f=sub(p,'draw:frame',{'draw:name':name,'draw:style-name':'Sidebar' if dark else 'PlainFrame','text:anchor-type':'as-char','svg:width':width,'svg:height':'1cm'})
  return sub(f,'draw:text-box',{'fo:min-height':'1cm'})
 def compose(self,variant,parent=None,family='lucide'):
  parent=self.body if parent is None else parent
  rows=self.rows()
  if not rows:
   self.p(parent,'Leerer Kontaktblock: kein Frame, keine Zeile, kein Symbol.')
   return
  if variant=='horizontal':
   for idx,(role,icon,label,keys) in enumerate(rows):
    p=parent[-1] if idx%3 and len(parent) else self.p(parent)
    if idx%3:sub(p,'text:s')
    f=sub(p,'draw:frame',{'draw:name':'Horizontal-'+role,'draw:style-name':'PlainFrame','text:anchor-type':'as-char','svg:width':'5.4cm','svg:height':'1cm'})
    box=sub(f,'draw:text-box',{'fo:min-height':'1cm'})
    ip=self.p(box);self.image(ip,icon,family)
    vp=self.p(box);self.values(vp,role,keys)
   return
  dark=variant=='sidebar';box=self.frame(parent,dark)
  if variant=='classic':
   for role,icon,label,keys in rows:
    p=self.p(box,style='Hang');self.image(p,icon,family);sub(p,'text:tab');self.values(p,role,keys)
   return
  t=sub(box,'table:table',{'table:name':'ContactTable'+str(self.serial),'table:style-name':'ContactTable'})
  sub(t,'table:table-column',{'table:style-name':'IconCol'});sub(t,'table:table-column',{'table:style-name':'TextCol'})
  for i,(role,icon,label,keys) in enumerate(rows):
   row=sub(t,'table:table-row',{'table:style-name':'Row'})
   c=sub(row,'table:table-cell',{'table:style-name':'Cell'});p=self.p(c,style='CellP')
   self.image(p,icon,family,backing=['circle','ellipse','rectangle','rounded','square',None][i] if dark else None)
   c=sub(row,'table:table-cell',{'table:style-name':'Cell'})
   if variant=='editorial':self.p(c,label,'Label')
   p=self.p(c,style='White' if dark else 'CellP');self.values(p,role,keys)
 def save(self,name):
  self.p(self.body,'ENDE KONTAKT · Kontrollmarke')
  # Header styles must be visible from styles.xml, not only content.xml.
  sa=self.styles.find(q('office:automatic-styles'))
  for s in self.auto:sa.append(copy.deepcopy(s))
  files={'content.xml':xml(self.content),'styles.xml':xml(self.styles)}
  files.update(self.resources)
  m=node('manifest:manifest',{'manifest:version':'1.2'})
  sub(m,'manifest:file-entry',{'manifest:full-path':'/','manifest:media-type':'application/vnd.oasis.opendocument.text','manifest:version':'1.2'})
  for path in files:sub(m,'manifest:file-entry',{'manifest:full-path':path,'manifest:media-type':'image/svg+xml' if path.endswith('.svg') else 'text/xml'})
  files['META-INF/manifest.xml']=xml(m)
  with ZipFile(BASE/(name+'.odt'),'w',ZIP_DEFLATED) as z:
   z.writestr('mimetype','application/vnd.oasis.opendocument.text',compress_type=ZIP_STORED)
   for path,data in files.items():z.writestr(path,data)
  out=BASE/'xml'/name;out.mkdir(parents=True,exist_ok=True)
  for path in ['content.xml','styles.xml','META-INF/manifest.xml']:(out/Path(path).name).write_bytes(files[path])
  return name
def generate():
 outputs=[]
 for variant in ['classic','sidebar','horizontal','editorial']:
  d=Document('H07 · '+variant+' · native User Fields',FULL)
  d.compose(variant,family='tabler' if variant in ['sidebar','editorial'] else 'lucide')
  outputs.append(d.save('contact-'+variant))
 for case,data in [('long',LONG),('minimal',{'name':'Ada Beispiel','email':FULL['email']}),
                   ('empty',{'name':'Ada Beispiel'}),('one-address',{'name':'Ada Beispiel','city':'Herford'})]:
  d=Document('H07 · '+case+' · dynamischer Kontaktfluss',data)
  for variant in ['classic','sidebar','horizontal','editorial']:
   d.p(d.body,variant);d.compose(variant)
  outputs.append(d.save('contact-'+case))
 d=Document('H07 · native Hidden Paragraph · Diagnose',dict(FULL,phone=''))
 p=d.p(d.body,'VORHER')
 p=d.p(d.body);d.image(p,'phone');d.field(p,'phone')
 sub(p,'text:hidden-paragraph',{'text:condition':'ooow:phone == ""','text:is-hidden':'true'})
 d.p(d.body,'NACHHER · Deklaration und Icon bleiben im Paket')
 outputs.append(d.save('contact-hidden'))
 # Reuse one identical resource more than once, and replace one named image later.
 d=Document('H07 · SVG Ressourcenteilung',FULL)
 for fam in ['lucide','tabler']:
  p=d.p(d.body,fam+' · ');d.image(p,'mail',fam);d.image(p,'mail',fam);d.image(p,'mail',fam,color='#b94336',stroke='2.5')
 outputs.append(d.save('contact-resources'))
 # H02-like fixed page sidebar anchored in master header. Content in styles part.
 d=Document('H07 · Sidebar im Seitenkopf nach H02',LONG)
 d.compose('sidebar',d.header,'tabler')
 f=d.header.find('.//'+q('draw:frame'))
 f.set(q('text:anchor-type'),'paragraph');f.set(q('svg:x'),'0cm');f.set(q('svg:y'),'0.7cm');f.set(q('svg:height'),'23cm')
 # Style frame placement explicitly; page body stays independent.
 s=d.auto.find("./"+q('style:style')+"[@"+q('style:name')+"='Sidebar']")
 gp=s.find(q('style:graphic-properties'))
 gp.set(q('style:horizontal-pos'),'from-left');gp.set(q('style:horizontal-rel'),'paragraph')
 gp.set(q('style:vertical-pos'),'from-top');gp.set(q('style:vertical-rel'),'paragraph')
 d.p(d.body,'Textfluss rechts neben der Sidebar muss im CV über die bestehende H02-Section freigehalten werden.')
 outputs.append(d.save('contact-header-sidebar'))
 (BASE/'outputs.json').write_text(json.dumps(outputs,indent=2)+'\n')
 print(json.dumps(outputs))
if __name__=='__main__':generate()
