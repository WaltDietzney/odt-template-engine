#!/usr/bin/env python3
"""Add heading semantics to accepted geometry; no new shape investigation."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED,ZIP_STORED
import xml.etree.ElementTree as E
import io,json,copy
BASE=Path(__file__).resolve().parent
source=BASE.parent/'p2-v03-fixed.odt'
with ZipFile(source) as z:files={n:z.read(n) for n in z.namelist()}
ns=dict(v for _,v in E.iterparse(io.BytesIO(files['content.xml']),events=['start-ns']))
ns.update(dict(v for _,v in E.iterparse(io.BytesIO(files['styles.xml']),events=['start-ns'])))
for k,v in ns.items():
 if k:E.register_namespace(k,v)
def q(s):
 p,n=s.split(':');return '{'+ns[p]+'}'+n
def el(tag,attrs=None,text=None):
 e=E.Element(q(tag),{q(k):v for k,v in (attrs or {}).items()});e.text=text;return e
r=E.fromstring(files['content.xml']);a=r.find(q('office:automatic-styles'));b=r.find('.//'+q('office:text'))
# Keep geometry and fixed dimensions; make decoration textless and behind body text.
for name in ['BandBlue','BandDark','BandLight']:
 s=next(s for s in a if s.get(q('style:name'))==name)
 s.find(q('style:graphic-properties')).set(q('style:run-through'),'background')
gstyle=el('style:style',{'style:name':'SemanticDecoration','style:family':'graphic'})
gstyle.append(el('style:graphic-properties',{
 'style:wrap':'run-through','style:run-through':'background',
 'style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph-content',
 'style:vertical-pos':'from-top','style:vertical-rel':'paragraph-content'}));a.append(gstyle)
hstyle=el('style:style',{'style:name':'SemanticHeading','style:family':'paragraph','style:parent-style-name':'Heading_20_1'})
hstyle.append(el('style:paragraph-properties',{
 'fo:margin-left':'0.25cm','fo:margin-right':'6.001cm',
 'fo:margin-top':'0.4cm','fo:margin-bottom':'0.5cm','fo:text-indent':'0cm',
 'fo:line-height':'0.9cm','fo:keep-with-next':'always'}))
hstyle.append(el('style:text-properties',{'fo:font-family':'Liberation Sans','fo:font-size':'15pt','fo:font-weight':'bold','fo:color':'#ffffff'}));a.append(hstyle)
labels={}
for p in list(b):
 group=p.find(q('draw:g'))
 if group is None:continue
 band=next(n for n in group if n.get(q('draw:name'),'').startswith('Band'))
 label=''.join(band.find(q('text:p')).itertext())
 key='heading_'+str(len(labels));labels[key]=label
 for shape in group:
  # Child coordinates own local placement, as in existing SkillBarGroup.
  # Preserve accepted horizontal geometry. Calibrated decoration offset for this body heading line.
  shape.set(q('svg:y'),str(round(float(shape.get(q('svg:y'),'0cm')[:-2])+0.45,4))+'cm')
  shape.attrib.pop(q('draw:text-style-name'),None)
  for text in list(shape):
   if text.tag in [q('text:p'),q('text:h')]:
    shape.remove(text)
  shape.insert(0,el('text:p'))
 group.set(q('text:anchor-type'),'paragraph')
 group.set(q('draw:style-name'),'SemanticDecoration')
 h=el('text:h',{'text:style-name':'SemanticHeading','text:outline-level':'1'})
 h.append(group);group.tail='{{'+key+'}}'
 i=list(b).index(p);b.remove(p);b.insert(i,h)
# Explicitly provide the common parent referenced above.
styles=E.fromstring(files['styles.xml']);common=styles.find(q('office:styles'))
if not any(n.get(q('style:name'))=='Heading_20_1' for n in common):
 common.append(el('style:style',{'style:name':'Heading_20_1','style:family':'paragraph','style:default-outline-level':'1'}))
files['styles.xml']=E.tostring(styles,encoding='utf-8',xml_declaration=True)
files['content.xml']=E.tostring(r,encoding='utf-8',xml_declaration=True)
for part in ['content.xml','styles.xml']:
 text=files[part].decode()
 missing=''.join(' xmlns:'+k+'="'+v+'"' for k,v in ns.items() if k and ('xmlns:'+k+'=') not in text)
 root='<office:document-content' if part=='content.xml' else '<office:document-styles'
 files[part]=text.replace(root,root+missing,1).encode()
with ZipFile(BASE/'semantic-template.odt','w',ZIP_DEFLATED) as z:
 for n,data in files.items():z.writestr(n,data,compress_type=ZIP_STORED if n=='mimetype' else ZIP_DEFLATED)
(BASE/'labels.json').write_text(json.dumps(labels,indent=2,ensure_ascii=False)+'\n')
print(json.dumps(labels,ensure_ascii=False))
