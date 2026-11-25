"""Gera o blog da Mark Seller.

Uso (a partir da pasta do projecto):  python tools/blog.py

- Lê os artigos de blog-posts/*.md (cabeçalho com title, date, category, cover, read, excerpt).
- Escreve blog.html (listagem) e um ficheiro blog-<slug>.html por artigo.
"""
import glob, html, os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from template import page, phero, rl  # noqa: E402  (muda também a pasta de trabalho)

MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']
esc = html.escape


def data_pt(iso):
    a, m, d = iso.split('-')
    return '%d de %s de %s' % (int(d), MESES[int(m) - 1], a)


def data_curta(iso):
    a, m, d = iso.split('-')
    return '%d %s %s' % (int(d), MESES[int(m) - 1][:3], a)


def inline(t):
    t = esc(t, quote=False)
    t = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', t)
    return re.sub(r'\*(.+?)\*', r'<em>\1</em>', t)


def md(text):
    out, para, items = [], [], []

    def flush():
        nonlocal para, items
        if para: out.append('<p>%s</p>' % inline(' '.join(para))); para = []
        if items: out.append('<ul>%s</ul>' % ''.join('<li>%s</li>' % inline(i) for i in items)); items = []

    for line in text.strip().split('\n'):
        s = line.strip()
        if not s: flush()
        elif s.startswith('## '): flush(); out.append('<h2>%s</h2>' % inline(s[3:]))
        elif s.startswith('- '):
            if para: flush()
            items.append(s[2:])
        elif s.startswith('> '): flush(); out.append('<blockquote>%s</blockquote>' % inline(s[2:]))
        else:
            if items: flush()
            para.append(s)
    flush()
    return '\n'.join(out)


def load():
    posts = []
    for f in glob.glob('blog-posts/*.md'):
        raw = open(f, encoding='utf-8').read()
        m = re.match(r'---\n(.*?)\n---\n(.*)', raw, re.S)
        meta = dict(l.split(':', 1) for l in m.group(1).split('\n') if ':' in l)
        meta = {k.strip(): v.strip() for k, v in meta.items()}
        meta['slug'] = os.path.splitext(os.path.basename(f))[0]
        meta['url'] = 'blog-%s.html' % meta['slug']
        meta['body'] = md(m.group(2))
        posts.append(meta)
    return sorted(posts, key=lambda p: p['date'], reverse=True)


def card(p):
    return ('<article class="post reveal" data-category="%s"><a class="post-img" href="%s"><img loading="lazy" alt="%s" src="%s"/></a>'
            '<div class="post-meta"><div class="chips"><span class="hot">%s</span><span>%s · %s min</span></div>'
            '<h3><a href="%s">%s</a></h3><p>%s</p><a class="link-arrow" href="%s">Ler artigo <i>↗</i></a></div></article>') % (
        esc(p['category']), p['url'], esc(p['title']), p['cover'], esc(p['category']), data_curta(p['date']), p['read'],
        p['url'], esc(p['title']), esc(p['excerpt']), p['url'])


def listing(posts):
    first, rest = posts[0], posts[1:]
    cats = []
    for p in rest:
        if p['category'] not in cats: cats.append(p['category'])
    chips = '<button class="chip on" data-filter="TODOS" type="button">Todos</button>' + ''.join(
        '<button class="chip" data-filter="%s" type="button">%s</button>' % (esc(c), esc(c)) for c in cats)
    feat = ('<article class="post-feat reveal"><a class="post-feat-img" href="%s"><img alt="%s" src="%s"/></a>'
            '<div><div class="chips"><span class="hot">%s</span><span>%s · %s min de leitura</span></div>'
            '<h2 class="title">%s</h2><p class="lead dark">%s</p>'
            '<a class="btn primary" href="%s">%s<i aria-hidden="true">↗</i></a></div></article>') % (
        first['url'], esc(first['title']), first['cover'], esc(first['category']), data_pt(first['date']), first['read'],
        esc(first['title']), esc(first['excerpt']), first['url'], rl('Ler artigo'))
    body = phero('Blog', 'Blog da <span class="accent">Mark Seller</span>',
                 'Artigos sobre comunicação, publicidade e marketing, com ideias práticas para quem gere marcas e negócios em Angola.') + \
        '<section class="sec"><div class="wrap">%s<div class="filters blog-filters">%s<span class="count"><b id="count">%d</b> artigos</span></div>' \
        '<div class="posts" id="posts">%s</div></div></section>' % (feat, chips, len(rest), ''.join(card(p) for p in rest))
    return page('blog.html', 'Blog — Mark Seller', 'Artigos sobre comunicação, publicidade e marketing para quem gere marcas e negócios em Angola.', body, 'blog.html')


def article(p, posts):
    others = [x for x in posts if x['slug'] != p['slug']]
    others.sort(key=lambda x: x['category'] != p['category'])
    related = ''.join(card(x) for x in others[:3])
    body = '''<section class="page-hero art-hero"><div class="ph-bg" aria-hidden="true"></div><div class="wrap">
<p class="crumb"><a href="index.html">Início</a><span>/</span><a href="blog.html">Blog</a><span>/</span>%s</p>
<div class="chips light"><span>%s</span><span>%s</span><span>%s min de leitura</span></div>
<h1 class="title reveal-h">%s</h1></div></section>
<div class="read-bar" aria-hidden="true"><i></i></div>
<section class="sec art"><div class="wrap narrow">
<figure class="art-cover"><img alt="%s" src="%s"/></figure>
<div class="art-body">%s</div>
<div class="art-foot"><div><b>Equipa Mark Seller</b><span>Comunicação, marketing e publicidade em Angola</span></div>
<button class="btn ghost-dark" type="button" data-copy>Copiar ligação<i aria-hidden="true">↗</i></button></div>
</div></section>
<section class="sec related"><div class="wrap"><div class="sec-head row"><div><p class="eyebrow">Continue a ler</p><h2 class="title reveal-h">Outros <span class="accent">artigos</span></h2></div>
<a class="btn ghost-dark" href="blog.html">%s<i aria-hidden="true">↗</i></a></div><div class="posts">%s</div></div></section>''' % (
        esc(p['category']), esc(p['category']), data_pt(p['date']), p['read'], esc(p['title']), esc(p['title']), p['cover'],
        p['body'], rl('Todos os artigos'), related)
    return page(p['url'], esc(p['title']) + ' — Blog Mark Seller', esc(p['excerpt']), body, 'blog.html')


def home_section(posts):
    return '''<section class="sec blog-sec" id="blog"><div class="wrap">
<div class="sec-head row"><div><p class="eyebrow">Blog</p><h2 class="title reveal-h">Blog da <span class="accent">Mark Seller</span></h2>
<p class="lead dark">Artigos sobre comunicação, publicidade e marketing, com ideias práticas para quem gere marcas e negócios em Angola.</p></div>
<a class="btn ghost-dark" href="blog.html">%s<i aria-hidden="true">↗</i></a></div>
<div class="posts">%s</div></div></section>''' % (rl('Ir para o blog'), ''.join(card(p) for p in posts[:3]))


if __name__ == '__main__':
    posts = load()
    idx = open('index.html', encoding='utf-8').read()
    idx = re.sub(r'<section class="sec blog-sec" id="blog">.*?</section>', lambda m: home_section(posts), idx, count=1, flags=re.S)
    open('index.html', 'w', encoding='utf-8', newline='\n').write(idx)
    open('blog.html', 'w', encoding='utf-8', newline='\n').write(listing(posts))
    for p in posts:
        open(p['url'], 'w', encoding='utf-8', newline='\n').write(article(p, posts))
    print('blog.html +', len(posts), 'artigos')
