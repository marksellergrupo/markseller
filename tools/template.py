import re, os
os.chdir(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))

# ---------- peças ----------
def rl(text):  # texto que rola no hover
    return '<span class="roll"><span class="r" data-t="%s">%s</span></span>' % (text, text)

NAV = [('sobre.html', 'Sobre nós'), ('clientes.html', 'Clientes'), ('projectos.html', 'Projectos'), ('blog.php', 'Blog'), ('contactos.html', 'Contactos')]
SERV = [('Marketing e Publicidade', '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15 9a4 4 0 0 1 0 6M18 6a8 8 0 0 1 0 12"/>',
         'Planeamos campanhas para lançar produtos, promover serviços e gerar oportunidades comerciais. Definimos o público, a mensagem e os canais de acordo com o objectivo da campanha.'),
        ('Assessoria de Imprensa', '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-5.4A8 8 0 1 1 21 12z"/><path d="M8 11h8M8 14h5"/>',
         'Apoiamos a sua organização na relação com os órgãos de comunicação social. Preparamos mensagens institucionais e identificamos oportunidades para dar visibilidade às suas iniciativas.'),
        ('Gestão de Carreira', '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
         'Apoiamos profissionais, executivos e figuras públicas na gestão da sua imagem e no planeamento da sua presença pública.'),
        ('Branding', '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20M9 3l3 6 3-6M12 9v12"/>',
         'Criamos e renovamos marcas. Definimos o posicionamento, a identidade visual e o tom de voz para que a sua empresa seja reconhecida em cada ponto de contacto.'),
        ('Gestão de Redes Sociais', '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
         'Planeamos conteúdos, gerimos a publicação e acompanhamos o desempenho para perceber o que desperta interesse, gera interacção e traz pedidos de contacto.'),
        ('Design de Produto', '<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
         'Desenvolvemos soluções de design para produtos físicos e digitais, desde o conceito até à apresentação, com atenção à funcionalidade e à experiência de utilização.')]

def header(current):
    links = ''.join('<a href="%s"%s>%s</a>' % (h, ' aria-current="page"' if h == current else '', rl(t)) for h, t in NAV)
    big = ''.join('<a href="%s"><span>%02d</span>%s</a>' % (h, i + 1, t) for i, (h, t) in enumerate([('index.html', 'Início')] + NAV))
    serv = ''.join('<a href="index.html#servicos">%s</a>' % s[0] for s in SERV)
    return '''<header class="site-header"><div class="bar">
<a class="logo" href="index.html" aria-label="Mark Seller — início"><img alt="Mark Seller" src="img/mark-seller.webp"/></a>
<nav class="primary" aria-label="Principal">%s</nav>
<a class="pill-cta" href="contactos.html">%s<i aria-hidden="true">↗</i></a>
<button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu"><span>Menu</span><b aria-hidden="true"></b></button>
</div></header>
<div class="menu" id="menu" aria-hidden="true"><div class="menu-in">
<div class="menu-col"><p class="eyebrow">O que fazemos</p><div class="menu-serv">%s</div>
<p class="eyebrow" style="margin-top:34px">Tem um projecto em mente?</p><p class="menu-note">Conte-nos o que pretende alcançar. A primeira conversa não tem compromisso.</p>
<a class="pill-cta solid" href="contactos.html">%s<i aria-hidden="true">↗</i></a></div>
<nav class="menu-col menu-big" aria-label="Menu">%s</nav>
<div class="menu-col menu-meta"><a href="mailto:geral@marksellerangola.com">geral@marksellerangola.com</a><a href="tel:+244923859536">(+244) 923 859 536</a>
<div class="socials"><a href="#">Instagram</a><a href="#">Facebook</a><a href="#">LinkedIn</a><a href="#">YouTube</a></div></div>
</div></div>''' % (links, rl('Fale connosco'), serv, rl('Vamos conversar'), big)

def footer():
    ql = ''.join('<a href="%s">%s</a>' % x for x in [('index.html', 'Início')] + NAV)
    sv = ''.join('<a href="index.html#servicos">%s</a>' % s[0] for s in SERV)
    return '''<section class="cta-band"><div class="wrap center">
<p class="eyebrow">Contacto</p>
<h2 class="title reveal-h">Prefere falar <span class="accent">directamente?</span></h2>
<p class="lead dark">Escreva-nos, ligue ou marque uma conversa. Conte-nos o que pretende alcançar e em que fase está o seu projecto.</p>
<div class="btn-row"><a class="btn primary" href="contactos.html">%s<i aria-hidden="true">↗</i></a><a class="btn ghost" href="mailto:geral@marksellerangola.com">geral@marksellerangola.com</a></div>
</div></section>
<footer class="site-footer"><div class="wrap">
<div class="f-grid">
<div><a class="logo-w" href="index.html">MARK SELLER</a><p>Agência angolana de comunicação, marketing e publicidade.</p><p class="f-contact"><a href="tel:+244923859536">(+244) 923 859 536</a><br><a href="mailto:geral@marksellerangola.com">geral@marksellerangola.com</a></p></div>
<div><h4>Ligações</h4>%s</div>
<div><h4>Serviços</h4>%s</div>
<div><h4>Siga-nos</h4><a href="#">Instagram</a><a href="#">Facebook</a><a href="#">LinkedIn</a><a href="#">YouTube</a></div>
</div>
<div class="giant" aria-hidden="true">MARK SELLER</div>
<div class="f-bottom"><span>© 2026 Mark Seller. Todos os direitos reservados.</span><span>marksellerangola.com</span></div>
</div></footer>
<a class="float-cta" href="contactos.html" aria-label="Fale com a nossa equipa"><span class="dot"></span>Fale com a nossa equipa</a>
<div class="lightbox" aria-hidden="true"><button class="lb-close" aria-label="Fechar">×</button><img alt="Visualização ampliada do projecto"/></div>''' % (rl('Falar com a Mark Seller'), ql, sv)

def page(file, title, desc, body, current=None):
    return '''<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport"/>
<meta content="#fe5a03" name="theme-color"/>
<meta content="%s" name="description"/>
<link rel="shortcut icon" href="img/mark-seller.webp" type="image/x-icon">
<title>%s</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,500;1,600&family=Inter:wght@400;500;600&family=Poppins:wght@400;500;600&display=swap">
<link rel="stylesheet" href="css/site.css">
<script>document.documentElement.classList.add("js")</script>
</head>
<body>
<div class="preloader" aria-hidden="true"><div class="pl-in"><span class="pl-name">MARK SELLER</span><span class="pl-count">0%%</span><span class="pl-bar"><i></i></span></div></div>
%s
<main>
%s
</main>
%s
<script src="js/site.js"></script>
</body>
</html>
''' % (desc, title, header(current), body, footer())

def phero(label, h1, p):
    return '''<section class="page-hero"><div class="ph-bg" aria-hidden="true"></div><div class="wrap">
<p class="crumb"><a href="index.html">Início</a><span>/</span>%s</p>
<h1 class="title reveal-h">%s</h1><p class="lead">%s</p></div></section>''' % (label, h1, p)

