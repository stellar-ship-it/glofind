import re, base64, pathlib, sys, os
ROOT = pathlib.Path("/Users/seilki/글로핀드 (클로드수정)")
OUT  = pathlib.Path(__file__).parent
MIME = {'.webp':'image/webp','.jpg':'image/jpeg','.jpeg':'image/jpeg','.png':'image/png','.mp4':'video/mp4','.svg':'image/svg+xml'}
def data_uri(rel):
    p = ROOT/rel
    return f"data:{MIME[p.suffix.lower()]};base64," + base64.b64encode(p.read_bytes()).decode()
def css(rel):
    return re.sub(r"@import url\([^)]*\);\n?", "", (ROOT/rel).read_text(encoding='utf-8'))
def build(page, links):
    html = (ROOT/page).read_text(encoding='utf-8')
    sub = pathlib.Path(page).parent.as_posix() if '/' in page else ''
    if sub:
        html = html.replace('../', '')
        html = re.sub(r'href="(?!https?:|#|mailto:|assets/|services/|insights/|/)([^"]+\.html[^"]*)"', lambda m: f'href="{sub}/{m.group(1)}"', html)
    title = re.search(r"<title>(.*?)</title>", html).group(1)
    styles = "".join(f"<style>\n{css(m)}\n</style>\n" for m in re.findall(r'<link rel="stylesheet" href="([^"?]+)', html))
    body = html.split("<body>")[1].split("</body>")[0]
    body = re.sub(r'(src|data-img|data-src|poster)="(assets/images/[^"]+)"', lambda m: f'{m.group(1)}="{data_uri(m.group(2))}"', body)
    body = body.replace('https://unpkg.com/lenis@1.1.13/dist/lenis.min.js', 'https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js')
    body = re.sub(r'<script src="(assets/js/[^"?]+)[^"]*"></script>', lambda m: "<script>\n" + (ROOT/m.group(1)).read_text(encoding='utf-8') + "\n</script>", body)
    for a, b in links.items(): body = body.replace(f'href="{a}"', f'href="{b}"')
    for a, b in links.items(): body = body.replace(f'href="{a}#', f'href="{b}#')
    body = re.sub(r'href="(services/[^"]+|cases\.html|insights/[^"]+|privacy\.html|index\.html[^"]*|about\.html|/)"', 'href="#" data-dead="1"', body)
    body += '\n<script>document.addEventListener("click",e=>{const a=e.target.closest("a[data-dead]");if(a){e.preventDefault();alert("시안 단계 — 이 링크는 시안 아티팩트 간에는 연결되지 않습니다 (index · about · cases · services · insights 아티팩트를 각각 열어주세요)");}},true);</script>'
    out = (f"<title>{title}</title>\n<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap\">\n" + styles + body)
    (OUT/(page.replace('/','-'))).write_text(out, encoding='utf-8'); print(page, round(len(out.encode())/1024), 'KB')
links = dict(x.split('=',1) for x in sys.argv[1:]) if len(sys.argv) > 1 else {}
pages = os.environ.get('PAGES','index.html,about.html').split(',')
for pg in pages: build(pg, links)
