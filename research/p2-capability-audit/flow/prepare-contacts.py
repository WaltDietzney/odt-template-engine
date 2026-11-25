#!/usr/bin/env python3
"""Migrate the accepted contact evidence to native frame bounds without touching originals."""
from pathlib import Path
import shutil,re
B=Path(__file__).resolve().parent;R=B.parents[2]
source=B.parent/'user-fields/integration/composition';target=B/'contacts/composition';target.mkdir(parents=True,exist_ok=True)
for name in ['generate.php','lifecycle.php','carrier.php','BuildingBlocks.php','ContactComposition.php','data.json','seed.odt','verify.py']:
 shutil.copy2(source/name,target/name)
native=B/'contacts/native';native.mkdir(exist_ok=True)
shutil.copytree(B.parent/'v04/native/assets',native/'assets',dirs_exist_ok=True)
s=(B.parent/'v04/native/bootstrap.php').read_text().replace('dirname(__DIR__, 4)','dirname(__DIR__, 5)');(native/'bootstrap.php').write_text(s)
p=target/'BuildingBlocks.php';s=p.read_text();a=s.index('/** Bounded adapter:');z=s.index('/** Supply the missing',a);p.write_text(s[:a]+s[z:])
p=target/'ContactComposition.php';s=p.read_text().replace('extends FlowTextBox','extends DrawTextBox').replace('new FlowTextBox','new DrawTextBox');s=s.replace("            $column->setFrameLayout(","            $column->setTextBoxSizing(['min-height' => '0.1cm']);\n            $column->setFrameLayout(");s=re.sub(r'(        parent::__construct\([^\n]+;)',r"\1\n        $this->setTextBoxSizing(['min-height' => '0.1cm']);",s);p.write_text(s)
shutil.copy2(B.parent/'v04/native/render.py',target/'render.py')
