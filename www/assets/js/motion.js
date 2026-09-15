/* ============================================================
   Glofind — motion.js  (Tier 1 — 의존성 없음) · v2
   히어로 진입 · 제목 단어 스태거 · 대각선 스태거 리빌 · 스포트라이트
   드리프트 · 자기력 버튼 · 상하 이동 버튼 · 시장 네트워크(canvas, [data-net])
   Tier 2(GSAP·Lenis) 는 motion2.js — 없어도 이 파일만으로 완결된다.
   ============================================================ */
(() => {
  'use strict';

  const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const FINE    = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const raf     = requestAnimationFrame.bind(window);
  const clamp   = (v, a, b) => Math.min(b, Math.max(a, v));
  const $$      = (s, r = document) => [...r.querySelectorAll(s)];

  const REVEAL_SEL = '.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-media, .m-split, .value, .stats';

  const ready = (fn) => document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', fn, { once: true })
    : fn();

  ready(init);

  function init() {
    grain();
    scrollButton();

    if (REDUCED) {
      $$(REVEAL_SEL).forEach(el => el.classList.add('is-in'));
      document.documentElement.classList.add('is-hero-in', 'is-hero-settled');
      networks();
      return;
    }

    splitHeadings();
    playHero();
    networks();
    reveals();
    spotlight();
    scrollLinked();
    if (FINE) { magnets(); topo(); }
  }

  function grain() {
    if (document.getElementById('grain')) return;
    const g = document.createElement('div');
    g.id = 'grain'; g.setAttribute('aria-hidden', 'true');
    document.body.appendChild(g);
  }

  /* 제목 단어 분해 — <br>·<em>·<span> 구조 보존 */
  function splitHeadings() {
    $$('.hero__title, .m-split').forEach(h => {
      if (h.dataset.split) return;
      h.dataset.split = '1';
      h.setAttribute('aria-label', h.textContent.replace(/\s+/g, ' ').trim());
      let i = 0;
      const walk = (node) => {
        [...node.childNodes].forEach(child => {
          if (child.nodeType === Node.TEXT_NODE) {
            const frag = document.createDocumentFragment();
            child.textContent.split(/(\s+)/).forEach(part => {
              if (!part) return;
              if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
              const w = document.createElement('span'); w.className = 'm-word'; w.setAttribute('aria-hidden', 'true');
              const inner = document.createElement('i'); inner.textContent = part;
              inner.style.setProperty('--wd', (120 + i++ * 42) + 'ms');
              w.appendChild(inner); frag.appendChild(w);
            });
            child.replaceWith(frag);
          } else if (child.nodeType === Node.ELEMENT_NODE && child.tagName !== 'BR') walk(child);
        });
      };
      walk(h);
    });
  }

  /* 히어로 진입 — 어떤 경로로도 반드시 열려야 한다 */
  function playHero() {
    const root = document.documentElement;
    const img  = document.querySelector('.topo__layer--1 img');
    const commit = () => {
      if (root.classList.contains('is-hero-in')) return;
      root.classList.add('is-hero-in');
      setTimeout(() => root.classList.add('is-hero-settled'), 1800);
    };
    const start = () => { if (!root.classList.contains('is-hero-in')) raf(() => raf(commit)); };
    if (img && !img.complete) { img.addEventListener('load', start, { once: true }); img.addEventListener('error', start, { once: true }); }
    setTimeout(start, 60);
    setTimeout(commit, 320);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') start(); });
  }

  /* 시장 네트워크 — 시안 점·선. [data-net="hero"] 다크 / [data-net="light"] 밝은 면 */
  function networks() { $$('canvas[data-net]').forEach(network); }
  function network(cv) {
    const ctx = cv.getContext('2d');
    const cs = getComputedStyle(document.documentElement);
    const cyan = cs.getPropertyValue('--cyan').trim() || '#00ADBD';
    const light = cv.dataset.net === 'light';
    const core = light ? (cs.getPropertyValue('--surface').trim() || '#fff') : (cs.getPropertyValue('--ink').trim() || '#191F28');
    const labelColor = light ? (cs.getPropertyValue('--cyan-ink').trim() || cyan) : cyan;

    // 시장 이름은 FAQ 카피(미국·유럽·동남아·일본·중동)
    const nodes = light
      ? [{ x: .12, y: .5, l: 'SEOUL', o: true }, { x: .34, y: .26, l: 'EU' }, { x: .5, y: .62, l: 'SEA' }, { x: .66, y: .3, l: 'JP' }, { x: .82, y: .5, l: 'US' }, { x: .95, y: .74, l: 'ME' }]
      : [{ x: .30, y: .34, l: 'SEOUL', o: true }, { x: .08, y: .22, l: 'EU' }, { x: .50, y: .14, l: 'JP' }, { x: .74, y: .24, l: 'US' }, { x: .90, y: .42, l: 'ME' }, { x: .58, y: .46, l: 'SEA' }];
    const links = [[0,1],[0,2],[0,3],[0,4],[0,5],[2,3]].map(([a, b], i) => ({ a, b, t: (i * .17) % 1, v: .0022 + i * .0004 }));

    let W = 0, H = 0, dpr = 1, mx = .5, my = .5, tx = .5, ty = .5, t0 = 0, live = false, rafId = 0;
    const size = () => {
      const r = cv.getBoundingClientRect();
      dpr = Math.min(devicePixelRatio || 1, 2);
      W = Math.max(1, Math.round(r.width)); H = Math.max(1, Math.round(r.height));
      cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    const pos = (n) => ({ x: n.x * W + (mx - .5) * (n.o ? 6 : 14), y: n.y * H + (my - .5) * (n.o ? 4 : 10) });
    const draw = (now) => {
      if (!live) return;
      rafId = raf(draw);
      if (!t0) t0 = now;
      const el = (now - t0) / 1000;
      mx += (tx - mx) * .06; my += (ty - my) * .06;
      ctx.clearRect(0, 0, W, H); ctx.lineWidth = 1;
      links.forEach(L => {
        const a = pos(nodes[L.a]), b = pos(nodes[L.b]);
        ctx.strokeStyle = cyan; ctx.globalAlpha = light ? .5 : .42;
        ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
        L.t = (L.t + L.v) % 1;
        const px = a.x + (b.x - a.x) * L.t, py = a.y + (b.y - a.y) * L.t;
        ctx.globalAlpha = .95; ctx.fillStyle = cyan; ctx.beginPath(); ctx.arc(px, py, 2.2, 0, Math.PI * 2); ctx.fill();
      });
      ctx.font = '600 10px Montserrat, sans-serif';
      if ('letterSpacing' in ctx) ctx.letterSpacing = '0.18em';
      nodes.forEach(n => {
        const p = pos(n);
        if (n.o) { const r = 6 + ((el * .8) % 1) * 22, a = 1 - ((el * .8) % 1); ctx.globalAlpha = a * .7; ctx.strokeStyle = cyan; ctx.beginPath(); ctx.arc(p.x, p.y, r, 0, Math.PI * 2); ctx.stroke(); }
        ctx.globalAlpha = 1; ctx.fillStyle = core; ctx.beginPath(); ctx.arc(p.x, p.y, n.o ? 7 : 5.5, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = cyan; ctx.beginPath(); ctx.arc(p.x, p.y, n.o ? 4 : 3, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = labelColor; ctx.globalAlpha = .95; ctx.fillText(n.l, p.x + 10, p.y - 8);
      });
      ctx.globalAlpha = 1;
    };
    const start = () => { if (live) return; live = true; t0 = 0; rafId = raf(draw); };
    const stop  = () => { live = false; cancelAnimationFrame(rafId); };
    size();
    addEventListener('resize', size, { passive: true });
    if ('IntersectionObserver' in window) new IntersectionObserver(es => es.forEach(e => e.isIntersecting ? start() : stop()), { threshold: .05 }).observe(cv);
    else start();
    document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' ? start() : stop());
    if (FINE) {
      const host = cv.closest('section') || cv;
      host.addEventListener('mousemove', e => {
        const r = cv.getBoundingClientRect();
        tx = clamp((e.clientX - r.left) / r.width, -.5, 1.5); ty = clamp((e.clientY - r.top) / r.height, -.5, 1.5);
      }, { passive: true });
    }
  }

  /* 3D 토포 — 커서 위치로 캔버스가 기울고 층이 깊이별로 어긋난다 (Halide: /25 · 층당 .2) */
  function topo() {
    const stage = document.querySelector('.topo');
    if (!stage) return;
    const layers = $$('.topo__layer', stage);
    let tx = 0, ty = 0, cx = 0, cy = 0, ticking = false, live = false;
    const tick = () => {
      ticking = false;
      cx += (tx - cx) * .08; cy += (ty - cy) * .08;
      stage.style.transform = `rotateX(${55 + cy / 2}deg) rotateZ(${-25 + cx / 2}deg)`;
      layers.forEach((l, i) => { l.style.transform = `translateZ(${(i + 1) * 15}px) translate(${cx * (i + 1) * .2}px, ${cy * (i + 1) * .2}px)`; });
      if (Math.abs(tx - cx) > .05 || Math.abs(ty - cy) > .05) { ticking = true; raf(tick); }
    };
    addEventListener('mousemove', e => {
      if (!document.documentElement.classList.contains('is-hero-settled')) return;
      if (scrollY > innerHeight) return;
      tx = (innerWidth / 2 - e.clientX) / 25; ty = (innerHeight / 2 - e.clientY) / 25;
      if (!ticking) { ticking = true; raf(tick); }
    }, { passive: true });
  }

  /* 리빌 — 좌상→우하 대각선 스태거 (열 60ms · 행 90ms). IO + 스크롤 판정 + 안전망 */
  function reveals() {
    const items = $$(REVEAL_SEL);
    if (!items.length) return;
    const groups = new Map();
    items.forEach(el => { const key = el.parentElement || document.body; if (!groups.has(key)) groups.set(key, []); groups.get(key).push(el); });
    groups.forEach(list => {
      if (list.length < 2) { list.forEach(el => el.style.setProperty('--rd', '0ms')); return; }
      const box  = list.map(el => el.getBoundingClientRect());
      const cols = [...new Set(box.map(b => Math.round(b.left / 24)))].sort((a, b) => a - b);
      const rows = [...new Set(box.map(b => Math.round(b.top  / 24)))].sort((a, b) => a - b);
      list.forEach((el, n) => {
        const c = cols.indexOf(Math.round(box[n].left / 24)), r = rows.indexOf(Math.round(box[n].top / 24));
        el.style.setProperty('--rd', (c * 60 + r * 90) + 'ms');
      });
    });
    const sweep = () => {
      const vh = innerHeight || 800;
      items.forEach(el => {
        if (el.classList.contains('is-in')) return;
        const r = el.getBoundingClientRect();
        if (r.top < vh * 0.92 && r.bottom > 0 && r.left < innerWidth && r.right > 0) el.classList.add('is-in');
      });
    };
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
      }, { threshold: .08, rootMargin: '0px 0px -8% 0px' });
      items.forEach(el => io.observe(el));
    }
    addEventListener('scroll', sweep, { passive: true });
    addEventListener('resize', sweep, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(sweep).observe(document.body);
    document.fonts?.ready.then(sweep);
    setTimeout(sweep, 700);
    sweep();
  }

  /* 스포트라이트 — [data-spot] 안에서 뷰포트 중앙에 가장 가까운 [data-spot-item] 만 잉크 100% */
  function spotlight() {
    const zones = $$('[data-spot]');
    if (!zones.length) return;
    let ticking = false;
    const update = () => {
      ticking = false;
      const mid = (innerHeight || 800) / 2, band = (innerHeight || 800) * .32;
      zones.forEach(z => {
        const items = $$('[data-spot-item]', z);
        let best = null, bd = Infinity;
        items.forEach(it => {
          const r = it.getBoundingClientRect();
          const c = r.top + r.height / 2;
          const d = Math.abs(c - mid);
          if (d < bd && d < band) { bd = d; best = it; }
        });
        items.forEach(it => it.classList.toggle('is-focus', it === best));
        z.classList.toggle('spot-on', !!best);
      });
    };
    const onScroll = () => { if (!ticking) { ticking = true; raf(update); } };
    addEventListener('scroll', onScroll, { passive: true });
    addEventListener('resize', onScroll, { passive: true });
    update();
  }

  /* 드리프트 — 제목이 스크롤 내내 ±14px 산다 (히어로 제목은 GSAP 가 잡으므로 제외) */
  function scrollLinked() {
    const drifters = $$('.t-h2, .t-display-2, .stat--big b');
    drifters.forEach(el => el.classList.add('m-drift'));
    let ticking = false;
    const update = () => {
      ticking = false;
      const vh = innerHeight || 800;
      drifters.forEach(el => {
        const r = el.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        const t = clamp((vh - r.top) / (vh + r.height), 0, 1);
        el.style.setProperty('--drift', ((0.5 - t) * 28).toFixed(2) + 'px');
      });
    };
    const onScroll = () => { if (!ticking) { ticking = true; raf(update); } };
    addEventListener('scroll', onScroll, { passive: true });
    addEventListener('resize', onScroll, { passive: true });
    update();
  }

  /* 자기력 버튼 — 반경 110px, 면 8px · 라벨 3px */
  function magnets() {
    const R = 110, FACE = 8, LABEL = 3;
    const targets = $$('.hero__actions .btn, .pain__intro .btn, .cta__actions .btn, .page-hero__actions .btn, #m-scrollbtn');
    if (!targets.length) return;
    targets.forEach(el => {
      el.classList.add('m-magnet');
      if (!el.querySelector('.m-label') && el.childNodes.length) {
        const label = document.createElement('span'); label.className = 'm-label';
        while (el.firstChild) label.appendChild(el.firstChild);
        el.appendChild(label);
      }
    });
    let ticking = false, mx = 0, my = 0;
    const apply = () => {
      ticking = false;
      targets.forEach(el => {
        const r = el.getBoundingClientRect();
        const dx = mx - (r.left + r.width / 2), dy = my - (r.top + r.height / 2), d = Math.hypot(dx, dy);
        if (d < R) {
          const k = (1 - d / R) * 2;
          el.classList.add('is-pulled');
          el.style.setProperty('--mx', (dx / R * FACE * k).toFixed(2) + 'px'); el.style.setProperty('--my', (dy / R * FACE * k).toFixed(2) + 'px');
          el.style.setProperty('--lx', (dx / R * LABEL * k).toFixed(2) + 'px'); el.style.setProperty('--ly', (dy / R * LABEL * k).toFixed(2) + 'px');
        } else if (el.classList.contains('is-pulled')) {
          el.classList.remove('is-pulled');
          ['--mx', '--my', '--lx', '--ly'].forEach(v => el.style.setProperty(v, '0px'));
        }
      });
    };
    addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; if (!ticking) { ticking = true; raf(apply); } }, { passive: true });
  }

  /* 상하 이동 버튼 */
  function scrollButton() {
    if (document.getElementById('m-scrollbtn')) return;
    const btn = document.createElement('button');
    btn.id = 'm-scrollbtn'; btn.type = 'button'; btn.setAttribute('aria-label', '다음 섹션으로 이동');
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="4" x2="12" y2="20"/><polyline points="6 14 12 20 18 14"/></svg>';
    document.body.appendChild(btn);
    const sync = () => {
      const up = scrollY > (innerHeight || 800) * .6;
      btn.classList.toggle('is-up', up); btn.classList.add('is-on');
      btn.setAttribute('aria-label', up ? '맨 위로 이동' : '다음 섹션으로 이동');
    };
    btn.addEventListener('click', () => {
      const up = btn.classList.contains('is-up');
      const next = document.querySelector('main > section:nth-of-type(2)');
      const top = up ? 0 : ((next?.offsetTop || innerHeight) - 72);
      if (window.__lenis) { window.__lenis.scrollTo(top); return; }
      scrollTo({ top, behavior: REDUCED ? 'auto' : 'smooth' });
    });
    addEventListener('scroll', sync, { passive: true });
    sync();
  }
})();
