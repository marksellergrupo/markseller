
  const q=(s,c=document)=>c.querySelector(s), qa=(s,c=document)=>[...c.querySelectorAll(s)];
  const io=new IntersectionObserver(entries=>entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); }), {threshold:.12});
  qa('.reveal').forEach(el=>io.observe(el));

  // interactive pillars
  qa('.pillar-tab').forEach(btn=>btn.addEventListener('click', ()=>{
    qa('.pillar-tab').forEach(b=>b.classList.remove('active')); btn.classList.add('active');
    const id = btn.dataset.pillar;
    qa('.pillar-panel').forEach(p=>p.classList.remove('active'));
    q('#panel-'+id).classList.add('active');
  }));

  // portfolio filters
  const cards = qa('.portfolio-card');
  const countEl = q('#visible-count');
  function applyFilter(filter){
    let visible = 0;
    cards.forEach(card=>{
      const show = filter==='TODOS' || card.dataset.category===filter;
      card.style.display = show ? 'flex' : 'none';
      if(show) visible++;
    });
    if(countEl) countEl.textContent = visible;
  }
  qa('.filter-btn').forEach(btn=>btn.addEventListener('click', ()=>{
    qa('.filter-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active');
    applyFilter(btn.dataset.filter);
  }));
  applyFilter('TODOS');

  // client pop feedback
  const pop=q('.client-pop'); let popTimer;
  qa('.client-card').forEach(card=>card.addEventListener('click', ()=>{
    if(!pop) return;
    pop.textContent = card.dataset.client + ' — cliente Mark Seller';
    pop.classList.add('show'); clearTimeout(popTimer); popTimer=setTimeout(()=>pop.classList.remove('show'),1800);
  }));

  // lightbox
  const lightbox=q('.lightbox'), lightboxImg=q('.lightbox img');
  qa('[data-lightbox]').forEach(el=>el.addEventListener('click', ()=>{
    lightboxImg.src = el.dataset.lightbox; lightbox.classList.add('open'); lightbox.setAttribute('aria-hidden','false');
  }));
  function closeLb(){ if(!lightbox) return; lightbox.classList.remove('open'); lightbox.setAttribute('aria-hidden','true'); lightboxImg.src=''; }
  if(lightbox){ q('.close-lightbox').addEventListener('click', closeLb);
  lightbox.addEventListener('click', e=>{ if(e.target===lightbox) closeLb(); }); }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeLb(); });

  // menu móvel
  const navEl=q('.nav'), navBtn=q('.nav-toggle');
  if(navEl && navBtn){
    navBtn.addEventListener('click', ()=>{ const o=navEl.classList.toggle('open'); navBtn.setAttribute('aria-expanded', o); navBtn.textContent=o?'✕':'☰'; });
    qa('.nav-links a').forEach(a=>a.addEventListener('click', ()=>{ navEl.classList.remove('open'); navBtn.textContent='☰'; }));
  }
