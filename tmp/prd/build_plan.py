from pathlib import Path
import re
from docx.shared import Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

root=Path(r'E:\BPS\DASHBOARD-MONITORING-FLEXIBLE')
source=(root/'tmp/prd/build_prd.py').read_text(encoding='utf-8')
prefix=source.split("doc.add_paragraph('PRODUCT REQUIREMENTS DOCUMENT'")[0]
exec(compile(prefix, 'shared_document_style', 'exec'))
out=root/'output/implementation-plan'
out.mkdir(parents=True,exist_ok=True)
doc.core_properties.title='Implementation Plan Dashboard Monitoring Flexible'
doc.core_properties.subject='Rencana implementasi rinci berdasarkan PRD versi 1.0'
doc.core_properties.keywords='implementation plan, monitoring, tahapan, pengujian, rilis'
foot.runs[0].text='IMPLEMENTATION PLAN  |  Versi 1.0  |  '
foot.runs[0].font.size=Pt(8)
md=(root/'docs/superpowers/plans/2026-10-16-dashboard-monitoring-flexible.md').read_text(encoding='utf-8')

def inline(par,text):
    for token in re.split(r'(\*\*.*?\*\*|`[^`]+`)',text):
        if token.startswith('**'):
            par.add_run(token[2:-2]).bold=True
        elif token.startswith('`'):
            run=par.add_run(token[1:-1]); run.font.name='Consolas'; run.font.size=Pt(9)
        else: par.add_run(token)

lines=md.splitlines()
i=0
while i<len(lines):
    line=lines[i].strip()
    if not line:
        i+=1; continue
    if line=='<!-- page -->':
        doc.add_page_break(); i+=1; continue
    if line.startswith('|'):
        rows=[]
        while i<len(lines) and lines[i].strip().startswith('|'):
            vals=[v.strip() for v in lines[i].strip().strip('|').split('|')]
            if not all(re.fullmatch(r'[-: ]+',v) for v in vals): rows.append(vals)
            i+=1
        widths=([5.7,11.3] if len(rows[0])==2 else [4.6,4.6,7.8])
        if rows[0][0] in ['Tugas','Kebutuhan PRD']: widths=[5.1,4.0,7.9]
        if rows[0][0]=='Area': widths=[3.1,5.7,8.2]
        table(rows[0],rows[1:],widths)
        continue
    if line.startswith('# '):
        doc.add_paragraph('RENCANA IMPLEMENTASI PRODUK',style='Subtitle')
        doc.add_paragraph('Dashboard Monitoring Flexible',style='Title')
    elif line.startswith('## '):
        doc.add_heading(line[3:],level=1)
    elif line.startswith('### '):
        label=line[4:]
        if label.startswith('T0') or label.startswith('T10'):
            doc.add_heading(label,level=1)
        else: doc.add_heading(label,level=2)
    elif line.startswith('> '):
        # Agent-only execution note remains in Markdown; office readers get an equivalent handoff in section 9.
        pass
    elif line.startswith('- [ ] '):
        a=doc.add_paragraph()
        a.paragraph_format.space_after=Pt(6)
        a.paragraph_format.left_indent=Cm(.35)
        a.paragraph_format.first_line_indent=Cm(-.35)
        inline(a,'[ ] '+line[6:])
    elif line.startswith('- '):
        a=doc.add_paragraph(style='List Bullet')
        a.paragraph_format.space_after=Pt(4)
        inline(a,line[2:])
    else:
        a=doc.add_paragraph(); inline(a,line)
    i+=1

for paragraph in doc.paragraphs:
    if paragraph.text.startswith('**'): raise ValueError('Unparsed markdown')
path=out/'Implementation_Plan_Dashboard_Monitoring_Flexible_v1.0.docx'
doc.save(path)
print(path)
print('Planned pages',md.count('<!-- page -->')+1)
