(() => {
  const q = (s, c = document) => c.querySelector(s), qa = (s, c = document) => [...c.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fine = matchMedia('(hover:hover) and (pointer:fine)').matches;
  const body = document.body;

  /* ---------- títulos: revelação por palavra ---------- */
  function split(el) {
    let n = 0;
    const walk = node => [...node.childNodes].forEach(c => {
      if (c.nodeType === 3) {
        const f = document.createDocumentFragment();
        c.textContent.split(/(\s+)/).forEach(t => {
          if (!t) return;
          if (/^\s+$/.test(t)) { f.append(' '); return; }
          const w = document.createElement('span'); w.className = 'sp';
          const i = document.createElement('i'); i.textContent = t; i.style.setProperty('--w', n++); w.append(i); f.append(w);
        });
        c.replaceWith(f);
      } else if (c.nodeType === 1) walk(c);
    });
    walk(el);
  }
  const heads = qa('.reveal-h, .hero .title');
  heads.forEach(split);
  const hio = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); hio.unobserve(e.target); } }), { threshold: .25 });

  /* ---------- carregamento ---------- */
  const pre = q('.preloader');
  let seen = false; try { seen = sessionStorage.getItem('ms-pl') === '1'; } catch (e) {}
  function ready() {
    if (body.classList.contains('ready')) return;
    body.classList.add('ready');
    if (pre) { pre.classList.add('done'); setTimeout(() => pre.remove(), 1500); }
    qa('.hero .title').forEach(h => setTimeout(() => h.classList.add('in'), 500));
    heads.filter(h => !h.closest('.hero')).forEach(h => hio.observe(h));
  }
  if (!pre || reduce || seen) { if (pre) pre.remove(); ready(); }
  else {
    const cnt = q('.pl-count'), bar = q('.pl-bar i'), t0 = performance.now(), dur = 1700;
    const imgs = qa('img').filter(i => !i.complete && i.loading !== 'lazy');
    let loaded = 0; imgs.forEach(i => { const f = () => loaded++; i.addEventListener('load', f); i.addEventListener('error', f); });
    (function tick(now) {
      const time = Math.min((now - t0) / dur, 1);
      const real = imgs.length ? loaded / imgs.length : 1;
      const p = Math.min(time, Math.max(real, time * .85));
      const v = 1 - Math.pow(1 - p, 3);
      cnt.textContent = Math.round(v * 100) + '%'; bar.style.transform = 'scaleX(' + v + ')';
      if (time >= 1 && real >= .999 || now - t0 > 5000) { cnt.textContent = '100%'; bar.style.transform = 'scaleX(1)'; try { sessionStorage.setItem('ms-pl', '1'); } catch (e) {} setTimeout(ready, 250); }
      else requestAnimationFrame(tick);
    })(t0);
  }

  /* ---------- revelar ---------- */
  const rio = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); rio.unobserve(e.target); } }), { threshold: .12 });
  qa('.reveal, .about-media').forEach((el, i) => { el.style.transitionDelay = (i % 3) * 90 + 'ms'; rio.observe(el); });

  /* ---------- contadores ---------- */
  qa('[data-count]').forEach(el => {
    const to = +el.dataset.count;
    const o = new IntersectionObserver(es => es.forEach(e => {
      if (!e.isIntersecting) return; o.disconnect();
      if (reduce) return;
      const t0 = performance.now();
      (function f(now) { const p = Math.min((now - t0) / 1600, 1); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 4))); if (p < 1) requestAnimationFrame(f); })(t0);
    }), { threshold: .6 });
    o.observe(el);
  });

  /* ---------- cabeçalho ---------- */
  const header = q('.site-header'), floatCta = q('.float-cta');
  let lastY = scrollY;
  const menuBtn = q('.menu-btn'), menu = q('.menu');
  function setMenu(open) {
    body.classList.toggle('menu-open', open);
    menuBtn.setAttribute('aria-expanded', open); menu.setAttribute('aria-hidden', !open);
    body.style.overflow = open ? 'hidden' : '';
    if (open) header.classList.remove('hide');
  }
  menuBtn.addEventListener('click', () => setMenu(!body.classList.contains('menu-open')));
  qa('.menu a').forEach(a => a.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });
  menuBtn.firstElementChild.textContent = 'Menu';
  menuBtn.addEventListener('click', () => { menuBtn.firstElementChild.textContent = body.classList.contains('menu-open') ? 'Fechar' : 'Menu'; });

  /* ---------- hero: rolagem dirigida + rato ---------- */
  const hero = q('.hero');
  const bubbles = qa('.bubble');
  let mx = 0, my = 0, cx = 0, cy = 0;
  if (hero) {
    const stars = q('.stars', hero);
    for (let i = 0; i < 46; i++) {
      const s = document.createElement('i');
      s.style.cssText = 'left:' + Math.random() * 100 + '%;top:' + Math.random() * 100 + '%;animation-delay:' + (Math.random() * 4).toFixed(2) + 's;animation-duration:' + (3 + Math.random() * 4).toFixed(1) + 's';
      stars.append(s);
    }
    hero.addEventListener('mousemove', e => { mx = e.clientX / innerWidth - .5; my = e.clientY / innerHeight - .5; });
  }
  function heroProgress() {
    if (!hero) return;
    const r = hero.getBoundingClientRect(), max = hero.offsetHeight - innerHeight;
    const p = max > 0 ? Math.max(0, Math.min(1, -r.top / max)) : 0;
    hero.style.setProperty('--p', p.toFixed(4));
  }
  (function loop() {
    cx += (mx - cx) * .06; cy += (my - cy) * .06;
    if (!reduce) bubbles.forEach(b => { const d = +b.dataset.depth; b.style.setProperty('--mx', (cx * -d * 2).toFixed(1) + 'px'); b.style.setProperty('--my', (cy * -d * 2).toFixed(1) + 'px'); });
    requestAnimationFrame(loop);
  })();

  function onScroll() {
    const y = scrollY;
    header.classList.toggle('scrolled', y > 30);
    if (!body.classList.contains('menu-open')) header.classList.toggle('hide', y > lastY + 4 && y > 400 ? true : (y < lastY - 4 ? false : header.classList.contains('hide')));
    lastY = y;
    if (floatCta) floatCta.classList.toggle('show', y > 600);
    heroProgress();
  }
  addEventListener('scroll', onScroll, { passive: true }); addEventListener('resize', onScroll); onScroll();

  /* ---------- botões magnéticos ---------- */
  if (fine && !reduce) qa('.btn, .pill-cta').forEach(b => {
    b.addEventListener('mousemove', e => { const r = b.getBoundingClientRect(); b.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * .18}px,${(e.clientY - r.top - r.height / 2) * .28}px)`; });
    b.addEventListener('mouseleave', () => { b.style.transform = ''; });
  });

  /* ---------- clientes: carrossel infinito ---------- */
  const row = q('.logo-row');
  if (row && !reduce) [...row.children].forEach(c => { const k = c.cloneNode(true); k.setAttribute('aria-hidden', 'true'); row.append(k); });

  /* ---------- projectos: filtro + lightbox ---------- */
  const works = qa('#works .work'), count = q('#count');
  qa('.chip').forEach(c => c.addEventListener('click', () => {
    qa('.chip').forEach(x => x.classList.remove('on')); c.classList.add('on');
    let n = 0;
    works.forEach(w => { const show = c.dataset.filter === 'TODOS' || w.dataset.category === c.dataset.filter; w.style.display = show ? '' : 'none'; if (show) { n++; w.classList.remove('visible'); void w.offsetWidth; w.classList.add('visible'); } });
    if (count) count.textContent = n;
  }));
  const lb = q('.lightbox'), lbImg = lb && q('img', lb);
  function closeLb() { lb.classList.remove('open'); lb.setAttribute('aria-hidden', 'true'); lbImg.removeAttribute('src'); }
  qa('[data-lightbox]').forEach(b => b.addEventListener('click', () => { lbImg.src = b.dataset.lightbox; lb.classList.add('open'); lb.setAttribute('aria-hidden', 'false'); }));
  if (lb) { q('.lb-close', lb).addEventListener('click', closeLb); lb.addEventListener('click', e => { if (e.target === lb) closeLb(); }); document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLb(); }); }
})();

/* ---------- blog ---------- */
(() => {
  const q = (s, c = document) => c.querySelector(s), qa = (s, c = document) => [...c.querySelectorAll(s)];
  const posts = qa('#posts .post'), cnt = q('.blog-filters #count');
  qa('.blog-filters .chip').forEach(c => c.addEventListener('click', () => {
    qa('.blog-filters .chip').forEach(x => x.classList.remove('on')); c.classList.add('on');
    let n = 0;
    posts.forEach(p => { const show = c.dataset.filter === 'TODOS' || p.dataset.category === c.dataset.filter; p.style.display = show ? '' : 'none'; if (show) { n++; p.classList.remove('visible'); void p.offsetWidth; p.classList.add('visible'); } });
    if (cnt) cnt.textContent = n;
  }));
  const bar = q('.read-bar i'), art = q('.art-body');
  if (bar && art) {
    const upd = () => { const r = art.getBoundingClientRect(), t = r.height - innerHeight * .6; bar.style.transform = 'scaleX(' + Math.max(0, Math.min(1, -r.top / t + .05)) + ')'; };
    addEventListener('scroll', upd, { passive: true }); upd();
  }
  const cp = q('[data-copy]');
  if (cp) cp.addEventListener('click', () => {
    const done = () => { cp.firstChild.textContent = 'Ligação copiada '; setTimeout(() => { cp.firstChild.textContent = 'Copiar ligação'; }, 2000); };
    (navigator.clipboard ? navigator.clipboard.writeText(location.href) : Promise.reject()).then(done, done);
  });
})();

/* ---------- início: últimos artigos do blog (vêm da base de dados) ---------- */
(() => {
  const box = document.getElementById('home-posts');
  if (!box) return;
  const el = (tag, cls, txt) => { const n = document.createElement(tag); if (cls) n.className = cls; if (txt != null) n.textContent = txt; return n; };
  fetch('api/posts.php?limit=3', { headers: { Accept: 'application/json' } })
    .then(r => r.ok ? r.json() : { posts: [] })
    .then(({ posts }) => {
      if (!posts.length) return;
      posts.forEach(p => {
        const art = el('article', 'post reveal visible'); art.dataset.category = p.category;
        const a = el('a', 'post-img'); a.href = p.url;
        const img = new Image(); img.loading = 'lazy'; img.alt = p.title; img.src = p.cover; a.append(img);
        const meta = el('div', 'post-meta'), chips = el('div', 'chips');
        chips.append(el('span', 'hot', p.category), el('span', '', p.date + ' · ' + p.minutes + ' min'));
        const h = el('h3'), ha = el('a', '', p.title); ha.href = p.url; h.append(ha);
        const more = el('a', 'link-arrow', 'Ler artigo '); more.href = p.url; more.append(el('i', '', '↗'));
        meta.append(chips, h, el('p', '', p.excerpt), more);
        art.append(a, meta); box.append(art);
      });
      box.hidden = false;
    })
    .catch(() => {});
})();
