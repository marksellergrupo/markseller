(() => {
  const root = document.documentElement;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fine = matchMedia('(hover:hover) and (pointer:fine)').matches;
  const q = (s, c = document) => c.querySelector(s), qa = (s, c = document) => [...c.querySelectorAll(s)];
  const ease = t => 1 - Math.pow(1 - t, 4);

  function countUp(el, to, suffix = '', dur = 1800, pad = 0) {
    const t0 = performance.now();
    (function f(now) {
      const p = Math.min((now - t0) / dur, 1), v = Math.round(to * ease(p));
      el.textContent = String(v).padStart(pad, '0') + suffix;
      if (p < 1) requestAnimationFrame(f);
    })(t0);
  }

  /* ---------- Texto dividido em palavras ---------- */
  function splitWords(el, baseDelay = 0) {
    let i = 0;
    const walk = node => {
      [...node.childNodes].forEach(n => {
        if (n.nodeType === 3) {
          const frag = document.createDocumentFragment();
          n.textContent.split(/(\s+)/).forEach(part => {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.append(' '); return; }
            const w = document.createElement('span'); w.className = 'w';
            const s = document.createElement('span'); s.textContent = part; s.style.setProperty('--i', i++);
            w.append(s); frag.append(w);
          });
          n.replaceWith(frag);
        } else if (n.nodeType === 1) walk(n);
      });
    };
    walk(el);
    el.style.setProperty('--d', baseDelay + 'ms');
  }

  const h1 = q('h1');
  if (h1) splitWords(h1, 100);
  const titles = qa('.section h2, .contact h2');
  titles.forEach(h => splitWords(h));
  const tio = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('split-in'); tio.unobserve(e.target); }
  }), { threshold: .3 });
  titles.forEach(h => tio.observe(h));

  [['.eyebrow', 0], ['.hero p', 500], ['.hero-actions', 650], ['.hero-stat', 800]].forEach(([s, d]) => {
    const el = q(s); if (el) { el.classList.add('hero-anim'); el.style.setProperty('--d', d + 'ms'); }
  });

  // contadores do mini-grid
  qa('.mini-grid b').forEach(b => {
    const m = b.textContent.trim().match(/^(\d+)(\D*)$/); if (!m) return;
    const to = +m[1], suf = m[2], pad = m[1].length > 1 && m[1][0] === '0' ? m[1].length : 0;
    const o = new IntersectionObserver(es => es.forEach(e => {
      if (e.isIntersecting) { setTimeout(() => countUp(b, to, suf, 1800, pad), 1200); o.disconnect(); }
    }), { threshold: .5 });
    if (!reduce) b.textContent = '0' + suf;
    o.observe(b);
  });

  /* ---------- Preloader ---------- */
  const pre = document.createElement('div');
  pre.className = 'preloader';
  pre.innerHTML = '<div class="preloader-inner"><img alt="Mark Seller" src="img/mark-seller.webp"><div class="preloader-bar"><i></i></div><div class="preloader-pct">0%</div></div>';
  document.body.prepend(pre);
  const bar = q('i', pre), pct = q('.preloader-pct', pre);
  let started = false;
  function runHero() {
    if (h1) h1.classList.add('split-in');
    const big = q('.hero-stat .big');
    if (big) { const to = parseInt(big.textContent, 10) || 0; big.textContent = '0'; setTimeout(() => countUp(big, to), 900); }
  }
  function start() {
    if (started) return; started = true;
    pre.classList.add('done');
    document.body.classList.add('loaded');
    setTimeout(runHero, 500);
    setTimeout(() => pre.remove(), 1500);
  }
  if (reduce) { start(); return; }

  const pending = qa('img').filter(i => !i.complete);
  const total = pending.length + 1; let done = 1, shown = 0;
  const t0 = performance.now();
  pending.forEach(i => { i.addEventListener('load', () => done++); i.addEventListener('error', () => done++); });
  (function tick() {
    const target = Math.min(done / total, Math.min((performance.now() - t0) / 1800, 1));
    shown += (target - shown) * .12;
    bar.style.transform = `scaleX(${shown})`;
    pct.textContent = Math.round(shown * 100) + '%';
    if (shown > .985 && target >= 1) { bar.style.transform = 'scaleX(1)'; pct.textContent = '100%'; setTimeout(start, 250); }
    else if (!started) requestAnimationFrame(tick);
  })();
  setTimeout(start, 6000);

  /* ---------- Carrossel infinito de clientes ---------- */
  const grid = q('.logo-grid');
  if (grid) {
    const cards = qa('.client-card', grid);
    cards.forEach(c => c.classList.remove('reveal', 'visible'));
    const mkSet = list => {
      const set = document.createElement('div'); set.className = 'logo-set';
      list.forEach(c => set.append(c)); return set;
    };
    const clones = list => list.map(c => {
      const k = c.cloneNode(true); k.setAttribute('aria-hidden', 'true'); k.tabIndex = -1;
      k.addEventListener('click', () => c.click()); return k;
    });
    const mkRow = (list, rev) => {
      const row = document.createElement('div'); row.className = 'logo-row' + (rev ? ' reverse' : '');
      row.append(mkSet(list), mkSet(clones(list))); return row;
    };
    // duas filas com todos os clientes, em sentidos opostos
    grid.classList.add('carousel');
    grid.replaceChildren(mkRow(cards, false), mkRow(clones(cards).reverse(), true));
  }

  /* ---------- Reveal escalonado ---------- */
  ['.services-grid', '.portfolio-grid', '.logo-grid', '.facts', '.pillar-side'].forEach(sel => {
    qa(sel).forEach(g => [...g.children].forEach((c, i) => c.style.setProperty('--rd', (i % 4) * 90 + 'ms')));
  });
  qa('.service, .fact, .micro-card, .strategy-note, .partner-copy > *, .contact .mini, .contact p, .contact-links').forEach(el => el.classList.add('reveal'));
  const rio = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); rio.unobserve(e.target); }
  }), { threshold: .12 });
  qa('.reveal').forEach(el => rio.observe(el));

  /* ---------- Barra de progresso, nav, topo ---------- */
  const prog = document.createElement('div'); prog.className = 'scroll-progress'; document.body.append(prog);
  const toTop = document.createElement('button'); toTop.className = 'to-top'; toTop.setAttribute('aria-label', 'Voltar ao topo'); toTop.textContent = '↑';
  toTop.onclick = () => scrollTo({ top: 0, behavior: 'smooth' }); document.body.append(toTop);
  const nav = q('.nav'), links = qa('.nav-links a');
  const sections = links.map(a => q(a.getAttribute('href'))).filter(Boolean);
  const track = q('.marquee-track');
  let lastY = scrollY, vel = 0, skew = 0;
  const parallax = [...qa('.about-media img'), ...qa('.strategy-img img')];

  function onScroll() {
    const y = scrollY, max = document.documentElement.scrollHeight - innerHeight;
    prog.style.transform = `scaleX(${max > 0 ? y / max : 0})`;
    nav.classList.toggle('scrolled', y > 40);
    nav.classList.toggle('hide', y > lastY && y > 300);
    toTop.classList.toggle('show', y > 700);
    vel = y - lastY; lastY = y;
    let cur = null; sections.forEach(s => { if (s.getBoundingClientRect().top < innerHeight * .4) cur = s; });
    links.forEach(a => a.classList.toggle('active', !!cur && a.getAttribute('href') === '#' + cur.id));
  }
  addEventListener('scroll', onScroll, { passive: true }); onScroll();

  const heroPerson = q('.hero-person'), heroEl = q('.hero');
  let mx = 0, my = 0, px = 0, py = 0;
  (function loop() {
    skew += (Math.max(-12, Math.min(12, vel * .5)) - skew) * .1; vel *= .9;
    if (track) track.style.transform = `skewX(${-skew}deg)`;
    parallax.forEach(el => {
      const r = el.parentElement.getBoundingClientRect();
      if (r.bottom < -100 || r.top > innerHeight + 100) return;
      el.style.transform = `translateY(${Math.max(-14, Math.min(14, (r.top + r.height / 2 - innerHeight / 2) * -.03))}px)`;
    });
    px += (mx - px) * .06; py += (my - py) * .06;
    if (heroPerson && scrollY < innerHeight) heroPerson.style.transform = `translate3d(${px * -24}px, ${py * -14 + scrollY * .12}px, 0)`;
    requestAnimationFrame(loop);
  })();
  if (heroEl) heroEl.addEventListener('mousemove', e => { mx = e.clientX / innerWidth - .5; my = e.clientY / innerHeight - .5; });

  /* ---------- Botões magnéticos, luz nos serviços, tilt 3D ---------- */
  if (fine) {
    qa('.btn, .nav-cta').forEach(b => {
      b.addEventListener('mousemove', e => {
        const r = b.getBoundingClientRect();
        b.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * .25}px, ${(e.clientY - r.top - r.height / 2) * .35}px)`;
      });
      b.addEventListener('mouseleave', () => { b.style.transform = ''; });
    });
    const tilt = (el, max) => {
      el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect(), a = (e.clientX - r.left) / r.width - .5, b = (e.clientY - r.top) / r.height - .5;
        el.style.transform = `perspective(900px) rotateY(${a * max}deg) rotateX(${-b * max}deg) translateY(-6px)`;
      });
      el.addEventListener('mouseleave', () => { el.style.transform = ''; });
    };
    qa('.portfolio-card').forEach(c => tilt(c, 6));
    qa('.client-card').forEach(c => tilt(c, 10));
  }
  qa('.service').forEach(s => s.addEventListener('mousemove', e => {
    const r = s.getBoundingClientRect();
    s.style.setProperty('--mx', e.clientX - r.left + 'px'); s.style.setProperty('--my', e.clientY - r.top + 'px');
  }));

  /* ---------- Portfólio: tamanhos alternados, clique e brilho ---------- */
  const pcards = qa('.portfolio-card');
  function layoutCards() {
    const vis = pcards.filter(c => c.style.display !== 'none');
    const pattern = ['wide', 'narrow', 'narrow', 'wide'];
    vis.forEach((c, i) => {
      c.classList.remove('wide', 'narrow', 'full');
      c.classList.add(vis.length - 1 === i && i % 2 === 0 ? 'full' : pattern[i % 4]);
    });
  }
  layoutCards();
  pcards.forEach(c => {
    c.addEventListener('click', e => { const t = q('.portfolio-thumb', c); if (t && !e.target.closest('.portfolio-thumb')) t.click(); });
    c.addEventListener('mousemove', e => {
      const r = c.getBoundingClientRect();
      c.style.setProperty('--mx', e.clientX - r.left + 'px'); c.style.setProperty('--my', e.clientY - r.top + 'px');
    });
  });

  /* ---------- Filtro do portfólio com animação ---------- */
  qa('.filter-btn').forEach(btn => btn.addEventListener('click', () => {
    setTimeout(() => {
      layoutCards();
      let i = 0;
      qa('.portfolio-card').forEach(c => {
        if (c.style.display === 'none') return;
        c.classList.remove('pop'); void c.offsetWidth;
        c.style.setProperty('--rd', (i++ % 6) * 70 + 'ms'); c.classList.add('pop', 'visible');
      });
    }, 20);
  }));

  /* ---------- Âncoras com scroll suave ---------- */
  qa('a[href^="#"]').forEach(a => a.addEventListener('click', e => {
    const h = a.getAttribute('href'), t = h.length > 1 && q(h); if (!t) return;
    e.preventDefault();
    scrollTo({ top: t.getBoundingClientRect().top + scrollY - 60, behavior: 'smooth' });
  }));
})();
