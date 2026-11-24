exec(open('extend.py').read().split("with ZipFile('p2-v01-native.odt'")[0])
# Add separate wrapping test, using explicit spaces between atomic frames.
p=para('E05 wrapping:'); p=para('')
tags=[f for f in r.iter(q('draw:frame')) if f.get(q('draw:name'),'').startswith('Tag')]
for i in range(15):
 f=copy.deepcopy(tags[i%len(tags)]);f.set(q('draw:name'),'WrapTag'+str(i));p.append(f);f.tail=' '
# Native bitmap fill resource, two independent declarations, replace one only.
s=E.fromstring(data['styles.xml']); common=s.find(q('office:styles'))
data['Pictures/p2-replacement.png']=png(120,240)
for name,path in [('P2OriginalBitmap','Pictures/p2-grid.png'),('P2ReplacementBitmap','Pictures/p2-replacement.png')]:
 common.append(el('draw:fill-image',{'draw:name':name,'xlink:href':path,'xlink:type':'simple','xlink:show':'embed','xlink:actuate':'onLoad'}))
for name,fill in [('P2BitmapOriginal','P2OriginalBitmap'),('P2BitmapChanged','P2ReplacementBitmap')]:
 style(name,{'draw:fill':'bitmap','draw:fill-image-name':fill,'style:repeat':'stretch','draw:stroke':'none','style:wrap':'run-through','style:run-through':'foreground','style:horizontal-pos':'from-left','style:horizontal-rel':'paragraph','style:vertical-pos':'from-top','style:vertical-rel':'paragraph'})
p=para('E01 bitmap fill: original / independently replaced')
for i,st in enumerate(['P2BitmapOriginal','P2BitmapChanged']):
 n=el('draw:custom-shape',{'draw:name':'Bitmap'+str(i),'draw:style-name':st,'text:anchor-type':'paragraph','svg:x':str(i*3)+'cm','svg:y':'0.6cm','svg:width':'2cm','svg:height':'2cm'})
 n.append(el('text:p'));n.append(el('draw:enhanced-geometry',{'svg:viewBox':'0 0 21600 21600','draw:type':'ellipse','draw:enhanced-path':'U 10800 10800 10800 10800 0 360 Z N'}));p.append(n)
for i in range(7):para(' ')
E.SubElement(m,uri+'file-entry',{uri+'full-path':'Pictures/p2-replacement.png',uri+'media-type':'image/png'})
for part,node in [('content.xml',r),('styles.xml',s),('META-INF/manifest.xml',m)]:data[part]=E.tostring(node,encoding='utf-8',xml_declaration=True)
with ZipFile('p2-v01-final.odt','w') as z:
 for n,d in data.items():z.writestr(n,d)
