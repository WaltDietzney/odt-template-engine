#!/usr/bin/env python3
"""Retain the authored header field; construct all contact declarations via PHP."""
from pathlib import Path
from zipfile import ZipFile,ZIP_STORED,ZIP_DEFLATED
import xml.dom.minidom as M
B=Path(__file__).resolve().parent
R=B.parents[3]
with ZipFile(R/'research/p2-capability-audit/v04/composition/seed.odt') as z:files={n:z.read(n) for n in z.namelist()}
d=M.parseString(files['content.xml']);text='urn:oasis:names:tc:opendocument:xmlns:text:1.0';office='urn:oasis:names:tc:opendocument:xmlns:office:1.0'
for decl in list(d.getElementsByTagNameNS(text,'user-field-decl')):
 if decl.getAttributeNS(text,'name')!='name':decl.parentNode.removeChild(decl)
 else:decl.setAttributeNS(office,'office:string-value','')
files['content.xml']=d.toxml(encoding='utf-8')
with ZipFile(B/'composition/seed.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',files.pop('mimetype'),compress_type=ZIP_STORED)
 for name,content in files.items():z.writestr(name,content)
