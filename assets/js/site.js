/* ============================================================
   Glofind — site.js  (v2)
   헤더 · 모바일 메뉴 · 오도미터 · 아코디언 · 폼 · 앵커 · 지연 영상
   모션은 motion.js / motion2.js 가 담당한다.
   ============================================================ */
(() => {
  'use strict';

  const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  const ready = (fn) => document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', fn, { once: true })
    : fn();

  ready(() => {
    header();
    mobileMenu();
    odometers();
    counters();
    accordion();
    lazyVideo();
    contactForm();
    newsletter();
    anchors();
    clock();
  });

  /* ── 헤더 — 히어로(다크)를 벗어나면 흰 바. 밝은 페이지 히어로면 처음부터 잉크색 ── */
  function header() {
    const nav = $('#nav');
    if (!nav) return;
    if (!$('.hero')) nav.classList.add('is-light');
    const sync = () => nav.classList.toggle('is-scrolled', scrollY > 24);
    addEventListener('scroll', sync, { passive: true });
    sync();
  }

  /* ── 모바일 메뉴 ── */
  function mobileMenu() {
    const btn  = $('#hamburger');
    const menu = $('#nav-menu');
    if (!btn || !menu) return;
    const set = (open) => {
      menu.classList.toggle('is-open', open);
      btn.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', String(open));
      btn.setAttribute('aria-label', open ? '메뉴 닫기' : '메뉴 열기');
      document.body.style.overflow = open ? 'hidden' : '';
    };
    btn.addEventListener('click', () => set(!menu.classList.contains('is-open')));
    $$('a', menu).forEach(a => a.addEventListener('click', () => set(false)));
    addEventListener('keydown', e => { if (e.key === 'Escape' && menu.classList.contains('is-open')) { set(false); btn.focus(); } });
    matchMedia('(min-width: 768px)').addEventListener('change', e => { if (e.matches) set(false); });
  }

  /* ── 오도미터 — 자릿수별 세로 릴이 굴러 멈춘다 (설계서 04) ── */
  function odometers() {
    const els = $$('[data-odo]');
    if (!els.length) return;

    els.forEach(el => {
      const target = String(parseInt(el.dataset.odo, 10));
      el.classList.add('odo');
      el.setAttribute('aria-label', target);
      el.innerHTML = '';
      [...target].forEach((d, i) => {
        const col = document.createElement('span'); col.className = 'odo__d'; col.setAttribute('aria-hidden', 'true');
        const reel = document.createElement('span'); reel.className = 'odo__r';
        for (let n = 0; n <= 9; n++) { const s = document.createElement('span'); s.textContent = n; reel.appendChild(s); }
        reel.style.transitionDelay = (i * 80) + 'ms';
        reel.dataset.to = d;
        col.appendChild(reel); el.appendChild(col);
      });
    });

    const run = (el) => {
      if (el.dataset.done) return;
      el.dataset.done = '1';
      $$('.odo__r', el).forEach(r => {
        const to = parseInt(r.dataset.to, 10);
        if (REDUCED) { r.style.transition = 'none'; }
        requestAnimationFrame(() => { r.style.transform = `translateY(${-to}em)`; });
      });
    };

    if (!('IntersectionObserver' in window) || REDUCED) { els.forEach(run); return; }
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: .5 });
    els.forEach(el => io.observe(el));
    setTimeout(() => els.forEach(el => { const r = el.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) run(el); }), 1500);
  }

  /* ── 카운트업 (서브페이지 data-count) ── */
  function counters() {
    const els = $$('[data-count]');
    if (!els.length) return;
    const run = (el) => {
      const target = parseInt(el.dataset.count, 10);
      if (Number.isNaN(target)) return;
      const suffix = el.dataset.suffix || '';
      if (REDUCED) { el.textContent = target + suffix; return; }
      const t0 = performance.now(), dur = 1500;
      const tick = (now) => {
        const p = Math.min((now - t0) / dur, 1);
        el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target) + suffix;
        if (p < 1) requestAnimationFrame(tick); else el.textContent = target + suffix;
      };
      requestAnimationFrame(tick);
    };
    if (!('IntersectionObserver' in window)) { els.forEach(run); return; }
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: .4 });
    els.forEach(el => io.observe(el));
  }

  /* ── 아코디언 — grid-rows 0fr→1fr. 한 번에 하나만 열린다 (설계서 10) ── */
  function accordion() {
    $$('[data-accordion]').forEach(list => {
      const items = $$('.faq__item', list);
      const open = (item, on) => {
        item.classList.toggle('is-open', on);
        $('.faq__q', item)?.setAttribute('aria-expanded', String(on));
      };
      items.forEach(item => {
        $('.faq__q', item)?.addEventListener('click', () => {
          const on = !item.classList.contains('is-open');
          items.forEach(i => { if (i !== item) open(i, false); });
          open(item, on);
        });
      });
    });
  }

  /* ── 영상 지연 로드 — 뷰포트 근처에서 src 를 붙이고 재생. 모바일 히어로는 CSS 로 숨긴다 ── */
  function lazyVideo() {
    const vids = $$('video[data-src]');
    if (!vids.length || REDUCED) return;
    vids.forEach(v => v.addEventListener('playing', () => v.classList.add('is-playing'), { once: true }));
    const load = (v) => {
      if (v.src) return;
      if (getComputedStyle(v).display === 'none') return;
      v.src = v.dataset.src;
      v.load();
      v.play().catch(() => {});
    };
    if (!('IntersectionObserver' in window)) { vids.forEach(load); return; }
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        const v = e.target;
        if (e.isIntersecting) { load(v); v.play?.().catch(() => {}); }
        else if (v.src) v.pause();
      });
    }, { rootMargin: '200px 0px' });
    vids.forEach(v => io.observe(v));
    // IO 가 못 도는 환경(백그라운드 탭)용 안전망 — 뷰포트 근처면 직접 로드
    const sweep = () => vids.forEach(v => { const r = v.getBoundingClientRect(); if (r.bottom > -200 && r.top < innerHeight + 200) load(v); });
    addEventListener('scroll', sweep, { passive: true });
    setTimeout(sweep, 1200);
  }

  /* ── 상담 폼 ── */
  function contactForm() {
    const form = $('#contact-form');
    const done = $('#form-done');
    if (!form) return;
    const errOf = (f) => (f.closest('.field') || f.parentElement).querySelector('.err');
    const check = (f) => {
      const err = errOf(f);
      let ok = true, msg = '';
      if (f.hasAttribute('required') && (f.type === 'checkbox' ? !f.checked : !f.value.trim())) {
        ok = false;
        msg = f.tagName === 'SELECT' ? '서비스를 선택해주세요.' : (err?.dataset.base || err?.textContent || '필수 항목입니다.');
      } else if (f.type === 'email' && f.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value.trim())) {
        ok = false; msg = '올바른 이메일 형식을 입력해주세요.';
      }
      f.classList.toggle('is-error', !ok);
      f.setAttribute('aria-invalid', String(!ok));
      if (err) { if (!ok) err.textContent = msg; err.classList.toggle('is-on', !ok); }
      return ok;
    };
    const fields = $$('input, select, textarea', form);
    fields.forEach(f => {
      const err = errOf(f);
      if (err) err.dataset.base = err.textContent;
      f.addEventListener('blur',  () => check(f));
      f.addEventListener('input', () => { if (f.classList.contains('is-error')) check(f); });
      f.addEventListener('change', () => { if (f.type === 'checkbox') check(f); });
    });
    form.addEventListener('submit', e => {
      e.preventDefault();
      let ok = true, first = null;
      fields.forEach(f => { if (!check(f)) { ok = false; first = first || f; } });
      if (!ok) { first?.focus(); return; }
      form.style.display = 'none';
      done?.classList.add('is-on');
      done?.focus?.();
    });
  }

  /* ── 뉴스레터 ── */
  function newsletter() {
    const form = $('#newsletter-form');
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const input = $('input', form), btn = $('button', form);
      if (!input.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim())) { input.focus(); return; }
      if (btn) { btn.innerHTML = '<span>구독 완료 ✓</span>'; btn.disabled = true; }
      input.disabled = true;
    });
  }

  /* ── 서울 현지 시각 — 푸터 (분 단위 갱신) ── */
  function clock() {
    const els = $$('[data-clock]');
    if (!els.length) return;
    const fmt = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Seoul', hour: '2-digit', minute: '2-digit', hour12: false });
    const tick = () => { const t = fmt.format(new Date()); els.forEach(e => { e.textContent = t; }); };
    tick();
    setInterval(tick, 30000);
  }

  /* ── 앵커 — Lenis 가 있으면 motion2 가 capture 로 먼저 잡는다 ── */
  function anchors() {
    $$('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const id = a.getAttribute('href');
        if (id.length < 2) return;
        const t = document.querySelector(id);
        if (!t) return;
        e.preventDefault();
        scrollTo({ top: t.getBoundingClientRect().top + scrollY - 72, behavior: REDUCED ? 'auto' : 'smooth' });
      });
    });
  }
})();
