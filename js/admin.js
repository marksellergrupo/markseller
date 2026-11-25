(() => {
  const q = (s, c = document) => c.querySelector(s), qa = (s, c = document) => [...c.querySelectorAll(s)];

  // confirmação antes de eliminar
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) e.preventDefault();
  });

  const form = q('#post-form');
  if (!form) return;
  const csrf = q('input[name=csrf]', form).value;
  const title = q('#f-title'), slug = q('#f-slug'), excerpt = q('#f-excerpt'), content = q('#f-content');
  const isNew = form.dataset.new === '1';

  // slug automático a partir do título (só em artigos novos e enquanto não for editado à mão)
  const slugify = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);
  let slugTouched = !isNew || slug.value !== '';
  slug.addEventListener('input', () => { slugTouched = true; });
  title.addEventListener('input', () => { if (!slugTouched) slug.value = slugify(title.value); });

  // contador do resumo e tempo de leitura
  const exCount = q('#ex-count'), info = q('#read-info');
  const upd = () => {
    exCount.textContent = excerpt.value.length + '/400';
    const words = (content.value.match(/\p{L}+/gu) || []).length;
    info.textContent = words + ' palavras · cerca de ' + Math.max(1, Math.ceil(words / 200)) + ' min de leitura';
  };
  excerpt.addEventListener('input', upd); content.addEventListener('input', upd); upd();

  // barra de formatação
  const wrapSel = (before, after, ph) => {
    const s = content.selectionStart, e = content.selectionEnd, sel = content.value.slice(s, e) || ph;
    content.setRangeText(before + sel + after, s, e, 'end');
    content.focus(); content.setSelectionRange(s + before.length, s + before.length + sel.length); upd();
  };
  const linePrefix = (prefix, ph) => {
    const s = content.selectionStart, v = content.value, start = v.lastIndexOf('\n', s - 1) + 1;
    const lineEnd = (v.indexOf('\n', s) === -1) ? v.length : v.indexOf('\n', s);
    const line = v.slice(start, lineEnd) || ph;
    content.setRangeText(prefix + line, start, lineEnd, 'end'); content.focus(); upd();
  };
  const actions = {
    h2: () => linePrefix('## ', 'Título da secção'), h3: () => linePrefix('### ', 'Subtítulo'),
    bold: () => wrapSel('**', '**', 'texto'), italic: () => wrapSel('*', '*', 'texto'),
    ul: () => linePrefix('- ', 'item'), ol: () => linePrefix('1. ', 'item'), quote: () => linePrefix('> ', 'Frase em destaque'),
    link: () => { const u = prompt('Endereço da ligação (https://…)'); if (u) wrapSel('[', '](' + u + ')', 'texto'); },
  };
  q('#toolbar').addEventListener('click', e => { const b = e.target.closest('[data-md]'); if (b) actions[b.dataset.md](); });

  // imagem dentro do texto
  const imgFile = q('#img-file');
  q('#btn-img').addEventListener('click', () => imgFile.click());
  imgFile.addEventListener('change', async () => {
    const f = imgFile.files[0]; if (!f) return;
    const fd = new FormData(); fd.append('file', f);
    try {
      const r = await fetch(form.dataset.uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': csrf }, credentials: 'same-origin' });
      const j = await r.json();
      if (!r.ok) throw new Error(j.error || 'Falha no envio.');
      const alt = prompt('Legenda da imagem (opcional)') || '';
      const s = content.selectionStart;
      content.setRangeText('\n\n![' + alt.replace(/[\]\n]/g, '') + '](' + j.url + ')\n\n', s, s, 'end'); upd();
    } catch (err) { alert(err.message); }
    imgFile.value = '';
  });

  // separadores Escrever / Pré-visualizar
  const prev = q('#preview'), toolbar = q('#toolbar');
  qa('.tab', form).forEach(t => t.addEventListener('click', async () => {
    qa('.tab', form).forEach(x => x.classList.toggle('on', x === t));
    const show = t.dataset.tab === 'preview';
    content.hidden = show; toolbar.hidden = show; prev.hidden = !show;
    if (!show) return;
    prev.textContent = 'A carregar…';
    try {
      const fd = new FormData(); fd.append('content', content.value);
      const r = await fetch(form.dataset.previewUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': csrf }, credentials: 'same-origin' });
      prev.innerHTML = r.ok ? (await r.text()) || '<p class="muted">Sem conteúdo.</p>' : '<p class="muted">Não foi possível gerar a pré-visualização.</p>';
    } catch (e) { prev.textContent = 'Não foi possível gerar a pré-visualização.'; }
  }));

  // pré-visualização da capa
  const box = q('#cover-prev');
  const showCover = src => { box.innerHTML = ''; const i = new Image(); i.alt = ''; i.src = src; box.append(i); };
  q('#cover-file').addEventListener('change', e => { const f = e.target.files[0]; if (f) { showCover(URL.createObjectURL(f)); q('#cover-path').value = ''; } });
  q('#cover-path').addEventListener('change', e => { if (e.target.value) { showCover('../' + e.target.value); q('#cover-file').value = ''; } });

  // aviso ao sair com alterações por guardar
  let dirty = false;
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('submit', () => { dirty = false; });
  addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
