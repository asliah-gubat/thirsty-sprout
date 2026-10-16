/* Care buttons (water, feed, decorate) for the garden and plant-progress pages.
 * Any element with data-plant="ID" data-state="{json}" is treated as a plant card. */
(function () {
  const MOOD_TEXT = { happy: 'Happy', needy: 'Needs care', wilting: 'Wilting' };
  const MOOD_COLOR = { happy: '#3F8F4A', needy: '#E3A82B', wilting: '#B5452B' };
  const cfg = window.GARDEN_CFG;

  function toast(msg) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(toast.t);
    toast.t = setTimeout(() => el.classList.add('hidden'), 3000);
  }

  const q = (card, sel) => card.querySelector(sel);

  function paint(card, s) {
    card.querySelectorAll('[data-canvas]').forEach((c) => PlantArt.draw(c, s));
    const set = (sel, text) => { const el = q(card, sel); if (el) el.textContent = text; };
    const bar = (sel, pct, color) => { const el = q(card, sel); if (el) { el.style.width = pct + '%'; if (color) el.style.background = color; } };

    set('[data-name]', s.name);
    set('[data-stage]', s.stage_name);
    set('[data-type]', s.type_label);
    set('[data-mood]', MOOD_TEXT[s.mood]);
    set('[data-hydtext]', s.hydration + '%');
    set('[data-hungtext]', s.hunger + '%');
    set('[data-xptext]', s.next_xp ? s.xp + ' / ' + s.next_xp + ' XP' : s.xp + ' XP (fully grown)');
    bar('[data-hydbar]', s.hydration, s.hydration < 30 ? '#B5452B' : '#2F86B0');
    bar('[data-hungbar]', s.hunger, s.hunger < 30 ? '#B5452B' : '#7A9E3A');
    bar('[data-xpbar]', s.xp_pct);
    const moodEl = q(card, '[data-mood]');
    if (moodEl) moodEl.style.color = MOOD_COLOR[s.mood];
    const sel = q(card, '[data-decorate]');
    if (sel) sel.value = s.decoration;

    // stage timeline on the progress page
    card.querySelectorAll('[data-step]').forEach((el) => {
      const i = parseInt(el.dataset.step, 10);
      el.classList.toggle('bg-leaf', i <= s.stage);
      el.classList.toggle('text-white', i <= s.stage);
      el.classList.toggle('bg-white', i > s.stage);
    });
    card.dataset.state = JSON.stringify(s);
  }

  async function care(card, action, item) {
    const { ok, data } = await Sprout.send(cfg.careUrl.replace('__ID__', card.dataset.plant), { action, item });
    if (!ok) return toast('Could not do that right now. Try again.');
    paint(card, data);
    toast(data.message + (data.leveled_up ? ' It grew into a ' + data.stage_name + '!' : ''));
  }

  document.querySelectorAll('[data-plant]').forEach((card) => {
    paint(card, JSON.parse(card.dataset.state));
    card.querySelectorAll('[data-care]').forEach((btn) =>
      btn.addEventListener('click', () => care(card, btn.dataset.care)));
    q(card, '[data-decorate]')?.addEventListener('change', (e) => care(card, 'decorate', e.target.value));
  });

  // keep thirst moving while the page stays open
  if (cfg.stateUrl) {
    setInterval(async () => {
      try {
        const res = await fetch(cfg.stateUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        (await res.json()).forEach((s) => {
          const card = document.querySelector('[data-plant="' + s.id + '"]');
          if (card) paint(card, s);
        });
      } catch (e) { /* offline, try later */ }
    }, 30000);
  }
})();
