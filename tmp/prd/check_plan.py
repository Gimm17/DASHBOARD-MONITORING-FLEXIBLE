from pathlib import Path
from pypdf import PdfReader
import pypdfium2 as pdfium
from PIL import Image
root=Path(r'E:\BPS\DASHBOARD-MONITORING-FLEXIBLE')
path=root/'output/implementation-plan/Implementation_Plan_Dashboard_Monitoring_Flexible_v1.0.pdf'
out=root/'tmp/prd/plan-render'
out.mkdir(parents=True,exist_ok=True)
r=PdfReader(path)
pdf=pdfium.PdfDocument(str(path))
texts=[]
for i,pg in enumerate(pdf):
    img=pg.render(scale=1.6).to_pil().convert('RGB')
    img.save(out/f'page-{i+1}.png')
    text=r.pages[i].extract_text()
    texts.append(text)
    print(f'Page {i+1:02}: {len(text.split())} words | {text[:100].replace(chr(10), " ")}')
joined='\n'.join(texts)
assert len(r.pages)==19
assert all(f'T{i:02}' in joined for i in range(11))
assert all(f'FR-{i:02}' in joined for i in [1,3,4,7,8,10,11,13,14,16,17,21,22,24])
assert '29 sampai 46' in joined
assert '53,64%' in joined
assert all(len(t.split())>120 for t in texts)
for start in range(0,len(r.pages),2):
    imgs=[Image.open(out/f'page-{i+1}.png') for i in range(start,min(start+2,len(r.pages)))]
    spread=Image.new('RGB',(sum(im.width for im in imgs)+30,max(im.height for im in imgs)+30),'#cdd3d8')
    x=10
    for im in imgs:
        spread.paste(im,(x,10)); x+=im.width+10
    spread.save(out/f'spread-{start//2+1}.png')
print('Verified structure, tasks, requirement mapping and examples. Total words:',len(joined.split()))
