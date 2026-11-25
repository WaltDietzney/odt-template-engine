#!/usr/bin/env python3
"""Prepare a blank input from a repository fixture, without touching sample outputs."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED,ZIP_STORED
from xml.dom import minidom as M
B=Path(__file__).resolve().parent;R=B.parents[2]
with ZipFile(R/'tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') as z:files={n:z.read(n) for n in z.namelist()}
d=M.parseString(files['content.xml']);body=d.getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0','text')[0]
for n in list(body.childNodes):body.removeChild(n)
files['content.xml']=d.toxml(encoding='utf-8')
with ZipFile(B/'seed.odt','w',ZIP_DEFLATED) as z:
 z.writestr('mimetype',files.pop('mimetype'),compress_type=ZIP_STORED)
 for n,data in files.items():z.writestr(n,data)
