// Tooltips for anything with data-tip (chart bars, heatmap cells) and the
// sidebar menu on narrow screens.
(() => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.menu-toggle');
  if (sidebar && toggle) {
    toggle.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  const tip = document.getElementById('tip');
  if (!tip) return;

  function fill(el) {
    let d;
    try {
      d = JSON.parse(el.dataset.tip);
    } catch {
      return false;
    }
    const value = document.createElement('strong');
    value.textContent = d.value;
    const label = document.createElement('div');
    label.className = 'tip-label';
    const key = document.createElement('i');
    key.className = 'tip-key';
    key.style.background = d.color || 'var(--s1)';
    label.append(key, document.createTextNode(d.label));
    tip.replaceChildren(value, label);
    for (const [k, v] of d.rows || []) {
      const row = document.createElement('div');
      row.className = 'tip-row';
      const a = document.createElement('span');
      a.textContent = k;
      const b = document.createElement('span');
      b.textContent = v;
      row.append(a, b);
      tip.append(row);
    }
    tip.hidden = false;
    return true;
  }

  function place(x, y) {
    const r = tip.getBoundingClientRect();
    const left = Math.min(x + 14, innerWidth - r.width - 8);
    const top = y + 14 + r.height > innerHeight ? y - r.height - 10 : y + 14;
    tip.style.left = `${Math.max(8, left)}px`;
    tip.style.top = `${Math.max(8, top)}px`;
  }

  const hide = () => { tip.hidden = true; };

  for (const el of document.querySelectorAll('[data-tip]')) {
    el.addEventListener('pointerenter', () => fill(el));
    el.addEventListener('pointermove', (e) => place(e.clientX, e.clientY));
    el.addEventListener('pointerleave', hide);
    el.addEventListener('focus', () => {
      if (!fill(el)) return;
      const r = el.getBoundingClientRect();
      place(r.left + 40, r.bottom - 8);
    });
    el.addEventListener('blur', hide);
  }
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
})();
