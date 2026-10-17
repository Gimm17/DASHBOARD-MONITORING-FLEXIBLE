from pathlib import Path
from pypdf import PdfReader
import pypdfium2 as pdfium
from PIL import Image, ImageOps, ImageDraw
root=Path(r'E:\BPS\DASHBOARD-MONITORING-FLEXIBLE')
path=root/'output/prd/PRD_Dashboard_Monitoring_Flexible_v1.0.pdf'
out=root/'tmp/prd/render'
out.mkdir(parents=True,exist_ok=True)
r=PdfReader(path)
pdf=pdfium.PdfDocument(str(path))
texts=[]
for i,pg in enumerate(pdf):
    img=pg.render(scale=1.6).to_pil().convert('RGB')
    img.save(out/f'page-{i+1}.png')
    text=r.pages[i].extract_text()
    texts.append(text)
    print(f'Page {i+1:02}: {len(text.split())} words | {text[:70].replace(chr(10), " ")}')
joined='\n'.join(texts)
assert len(r.pages)==16
assert all(f'FR-{i:02}' in joined for i in range(1,25))
assert all(f'UAT-{i:02}' in joined for i in range(1,13))
assert all(f'NFR-{i:02}' in joined for i in range(1,11))
assert all(f'D{i:02}' in joined for i in range(1,10))
for start in range(0,len(r.pages),2):
    imgs=[Image.open(out/f'page-{i+1}.png') for i in range(start,min(start+2,len(r.pages)))]
    spread=Image.new('RGB',(sum(im.width for im in imgs)+30,max(im.height for im in imgs)+30),'#cdd3d8')
    x=10
    for im in imgs:
        spread.paste(im,(x,10)); x+=im.width+10
    spread.save(out/f'spread-{start//2+1}.png')
print('Verified pages, FR, NFR, UAT, decision IDs. Total words:',len(joined.split()))
