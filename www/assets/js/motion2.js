/* ============================================================
   Glofind — motion2.js  (Tier 2 — GSAP + ScrollTrigger + Lenis) · v2
   Lenis · 헤더 숨김 · 히어로 미디어 축소(커튼) · 티커 속도 · 사진 스크럽
   역량 가로 스크롤 핀 · 프로세스 스택 상태 · 비교표 행 와이프
   피크/커서 링 · 푸터 드리프트
   전부 가드로 감싼다 — 라이브러리가 없으면 Tier 1(motion.js)만 남는다.
   ============================================================ */
(() => {
  'use strict';

  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    console.warn('GSAP 미로드 — Tier 1 모션만 동작');
    return;
  }
  gsap.registerPlugin(ScrollTrigger);
  document.documentElement.classList.add('has-gsap');

  const FINE = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const WIDE = () => innerWidth >= 1024;
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  const ready = (fn) => document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', fn, { once: true })
    : fn();

  ready(() => {
    smooth();
    navHide();
    navActive();
    heroCurtain();
    ticker();
    photoScrub();
    capsPin();
    stackState();
    openers();
    textMask();
    tableWipe();
    footerDrift();
    addEventListener('load', () => ScrollTrigger.refresh());
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
  });

  /* ── Lenis ── */
  function smooth() {
    if (typeof Lenis === 'undefined') { console.warn('Lenis 미로드 — 기본 스크롤로 동작'); return; }
    const lenis = new Lenis({ duration: 1.15, smoothWheel: true });
    window.__lenis = lenis;
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(t => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
    document.addEventListener('click', e => {
      const a = e.target.closest('a[href^="#"]');
      if (!a) return;
      const id = a.getAttribute('href');
      if (id.length < 2) return;
      const t = document.querySelector(id);
      if (!t) return;
      e.preventDefault(); e.stopPropagation();
      lenis.scrollTo(t, { offset: -72 });
    }, true);
    const menu = $('#nav-menu');
    if (menu && 'MutationObserver' in window) {
      new MutationObserver(() => menu.classList.contains('is-open') ? lenis.stop() : lenis.start())
        .observe(menu, { attributes: true, attributeFilter: ['class'] });
    }
  }

  /* ── 헤더 ── */
  function navHide() {
    const nav = $('#nav');
    if (!nav) return;
    ScrollTrigger.create({
      start: 240, end: 'max',
      onUpdate: self => nav.classList.toggle('is-hidden', self.direction === 1),
      onLeaveBack: () => nav.classList.remove('is-hidden'),
    });
  }

  /* ── 네비 — 스크롤 위치의 섹션을 밑줄로 표시 (design-system §5: 네비 위치는 불변, 상태만 바뀐다) ── */
  function navActive() {
    const links = $$('#nav a[data-section]');
    if (!links.length) return;
    links.forEach(a => {
      const sec = document.getElementById(a.dataset.section);
      if (!sec) return;
      ScrollTrigger.create({
        trigger: sec, start: 'top 45%', end: 'bottom 45%',
        onToggle: self => a.classList.toggle('is-active', self.isActive),
      });
    });
  }

  /* ── 히어로 — 스크롤 0→100vh 동안 프레임이 안쪽으로 닫히고(clip) 타이포는 위로 빠진다.
     히어로는 sticky 라 다음 섹션이 커튼처럼 그 위로 올라온다 (설계서 02) ── */
  function heroCurtain() {
    const hero = $('#hero'), frame = $('.hero__stage');
    if (!hero || !frame || hero.hasAttribute('data-tmask')) return; // 텍스트 마스크 히어로는 커튼 스크럽 대신 textMask()
    const tl = gsap.timeline({ scrollTrigger: { trigger: hero, start: 'top top', end: '+=100%', scrub: 1.2 } });
    tl.fromTo(frame, { clipPath: 'inset(0px 0px 0px 0px)' }, { clipPath: 'inset(0px 7vw 0px 7vw)', ease: 'none' }, 0)
      .to('.hero__stage', { yPercent: -8, ease: 'none' }, 0)
      .to('.hero__label', { y: -40, autoAlpha: 0, ease: 'none' }, 0)
      .to('.hero__title', { y: -60, autoAlpha: 0, ease: 'none' }, .05)
      .to('.hero__pitch, .hero__foot', { y: -30, autoAlpha: 0, ease: 'none' }, .1);
  }

  /* ── 티커 ── */
  function ticker() {
    const track = $('.ticker__track');
    if (!track) return;
    const anim = gsap.to(track, { xPercent: -50, ease: 'none', duration: 42, repeat: -1 });
    let hover = false;
    ScrollTrigger.create({
      onUpdate: self => {
        const v = Math.min(Math.abs(self.getVelocity()) / 350, 4);
        const dir = self.direction || 1;
        gsap.to(anim, { timeScale: hover ? .15 * dir : dir * (1 + v), duration: .7, overwrite: true });
      },
    });
    track.parentElement.addEventListener('mouseenter', () => { hover = true;  gsap.to(anim, { timeScale: .15, duration: .5, overwrite: true }); });
    track.parentElement.addEventListener('mouseleave', () => { hover = false; gsap.to(anim, { timeScale: 1,   duration: .5, overwrite: true }); });
  }

  /* ── 사진 스크럽 (배율 여유 위에서 상하로만) ── */
  function photoScrub() {
    const set = (sel, trigger, from, to, scale) => {
      const el = $(sel);
      if (!el) return;
      gsap.fromTo(el, { yPercent: from, scale }, {
        yPercent: to, scale, ease: 'none',
        scrollTrigger: { trigger: $(trigger) || el, start: 'top bottom', end: 'bottom top', scrub: true },
      });
    };
    set('.mission__figure img', '.mission', -6, 6, 1.14);
  }

  /* ── 역량 — 가로 스크롤 핀 (1024 이상). 진행 바·카운터·지나간 패널 채도 (설계서 06) ── */
  function capsPin() {
    const sec = $('.caps'), pin = $('.caps__pin'), track = $('#caps-track');
    if (!sec || !pin || !track) return;
    const bar = $('.caps__bar i'), count = $('.caps__count b');
    const caps = $$('.cap', track);
    let st = null;

    const build = () => {
      if (st) { st.kill(); st = null; gsap.set(track, { clearProps: 'transform' }); }
      if (!WIDE()) return;
      const dist = () => Math.max(0, track.scrollWidth - innerWidth);
      st = gsap.to(track, {
        x: () => -dist(),
        ease: 'none',
        scrollTrigger: {
          trigger: sec, start: 'top top', end: () => '+=' + (dist() + innerHeight * .6),
          pin: pin, scrub: 1.2, anticipatePin: 1, invalidateOnRefresh: true,
          onUpdate: self => {
            const p = self.progress;
            if (bar) bar.style.transform = `scaleX(${.25 + p * .75})`;
            const n = Math.min(caps.length, Math.max(1, Math.round(p * (caps.length - 1)) + 1));
            if (count) count.textContent = String(n).padStart(2, '0');
            caps.forEach((c, i) => c.classList.toggle('is-past', i < n - 1));
          },
        },
      }).scrollTrigger;
    };
    build();
    let w = innerWidth;
    addEventListener('resize', () => { if ((innerWidth >= 1024) !== (w >= 1024)) { w = innerWidth; build(); ScrollTrigger.refresh(); } else w = innerWidth; });

    // 1024 미만: 마우스 드래그로도 넘긴다 (터치는 네이티브 스크롤 유지)
    let drag = null;
    track.addEventListener('pointerdown', e => {
      if (WIDE() || e.pointerType !== 'mouse') return;
      drag = { x: e.clientX, left: track.scrollLeft, moved: false };
      track.style.scrollSnapType = 'none';
    });
    addEventListener('pointermove', e => {
      if (!drag) return;
      const dx = e.clientX - drag.x;
      if (Math.abs(dx) > 4) drag.moved = true;
      track.scrollLeft = drag.left - dx;
    });
    const endDrag = () => {
      if (!drag) return;
      const wasMoved = drag.moved; drag = null;
      track.style.scrollSnapType = '';
      if (wasMoved) track.addEventListener('click', ev => ev.preventDefault(), { capture: true, once: true });
    };
    addEventListener('pointerup', endDrag); addEventListener('pointercancel', endDrag);

    // 1024 미만: 스냅 캐러셀의 스크롤 위치를 진행 바에 연결
    track.addEventListener('scroll', () => {
      if (WIDE()) return;
      const p = track.scrollLeft / Math.max(1, track.scrollWidth - track.clientWidth);
      if (bar) bar.style.transform = `scaleX(${.25 + p * .75})`;
      if (count) count.textContent = String(Math.min(caps.length, Math.round(p * (caps.length - 1)) + 1)).padStart(2, '0');
    }, { passive: true });
  }

  /* ── 프로세스 — 덮인 카드는 .is-under (설계서 08). sticky 는 CSS ── */
  function stackState() {
    const steps = $$('[data-stack] .step');
    if (!steps.length) return;
    steps.forEach((s, i) => {
      const next = steps[i + 1];
      if (!next) return;
      ScrollTrigger.create({
        trigger: next, start: 'top 45%', end: 'bottom top', // 다음 카드가 실제로 덮기 시작할 때 눌리도록 (60%는 너무 이르다)
        onEnter: () => s.classList.add('is-under'),
        onLeaveBack: () => s.classList.remove('is-under'),
      });
    });
  }



  /* ── [data-tmask] — 글자가 확대되며 구멍으로 다음 섹션이 비치는 전환 ── */
  function textMask() {
    const sec = $('[data-tmask]'); if (!sec) return;
    const texts = $$('.tmask__t', sec), whites = $$('.tmask__white', sec), cover = $('.tmask__cover', sec), content = $('.tmask__content', sec);
    const white = whites;
    const t = texts.find(el => getComputedStyle(el).display !== 'none') || texts[0];
    const str = t.textContent, idx = str.indexOf('nter'); // 'n' of Enter — 확대 원점
    let ox = 720, oy = 450;
    try { const a = t.getStartPositionOfChar(idx), b = t.getEndPositionOfChar(idx); ox = (a.x + b.x) / 2; oy = (a.y + b.y) / 2 - 0.35 * parseFloat(getComputedStyle(t).fontSize); } catch (e) {}
    gsap.set(texts, { svgOrigin: ox + ' ' + oy });
    const tl = gsap.timeline({ scrollTrigger: { trigger: sec, start: 'top top', end: 'bottom bottom', scrub: 0.6 } });
    tl.to(white, { opacity: 0, duration: 0.12, ease: 'none' }, 0.04)
      .to(texts, { scale: 18, duration: 1, ease: 'power1.in' }, 0)
      .to(cover, { opacity: 0, duration: 0.3, ease: 'none' }, 0.55)
      .to(content, { opacity: 1, y: 0, duration: 0.25, ease: 'power2.out' }, 0.72);
  }

  /* ── [data-open] — 스크롤에 따라 섹션/이미지가 안쪽에서 바깥으로 펼쳐진다 (한화에너지 참고) ── */
  function openers() {
    $$('[data-open]').forEach(el => {
      const media = el.matches('section') ? el : el;
      const from = el.matches('section') ? 'inset(0% 7% round 0px)' : 'inset(10% 8% round 0px)';
      gsap.set(media, { clipPath: from, transition: 'none' });
      gsap.to(media, {
        clipPath: 'inset(0% 0% round 0px)', ease: 'none',
        scrollTrigger: { trigger: el, start: 'top 92%', end: 'top 38%', scrub: 0.5 }
      });
    });
  }

  /* ── 비교표 — 행이 위에서부터 순차 와이프 (설계서 07) ── */
  function tableWipe() {
    const t = $('table[data-rows]');
    if (!t) return;
    const rows = $$('tbody tr', t);
    gsap.set(rows, { autoAlpha: 0, x: -12 });
    gsap.to(rows, { autoAlpha: 1, x: 0, duration: .6, ease: 'power3.out', stagger: .07,
      scrollTrigger: { trigger: t, start: 'top 80%', once: true } });
    const line = $('thead .col-glo', t);
    if (line) gsap.fromTo(line, { backgroundSize: '0% 2px' }, { backgroundSize: '100% 2px', duration: .9, ease: 'power3.out',
      scrollTrigger: { trigger: t, start: 'top 80%', once: true } });
  }

  /* ── 커서 링 — 링크 위 1.8배, [data-cursor] 위에서는 라벨 링 (설계서 05) ── */
  function ring() {
    const el = document.createElement('div');
    el.id = 'm-ring'; el.setAttribute('aria-hidden', 'true');
    const i = document.createElement('i'); const lab = document.createElement('b');
    el.appendChild(i); el.appendChild(lab);
    document.body.appendChild(el);
    const xTo = gsap.quickTo(el, 'x', { duration: .32, ease: 'power3' });
    const yTo = gsap.quickTo(el, 'y', { duration: .32, ease: 'power3' });
    let shown = false;
    addEventListener('mousemove', e => {
      xTo(e.clientX); yTo(e.clientY);
      if (!shown) { shown = true; el.classList.add('is-on'); }
    }, { passive: true });
    document.addEventListener('mouseleave', () => el.classList.remove('is-on'));
    document.addEventListener('mouseenter', () => { if (shown) el.classList.add('is-on'); });
    document.addEventListener('pointerover', e => {
      const t = e.target;
      const text = t.closest?.('input, textarea, select');
      const labeled = t.closest?.('[data-cursor]');
      el.classList.toggle('is-text', !!text);
      el.classList.toggle('is-label', !!labeled);
      if (labeled) lab.textContent = labeled.dataset.cursor;
      el.classList.toggle('is-hover', !text && !labeled && !!t.closest?.('a, button, summary, label'));
    }, { passive: true });
    addEventListener('mousedown', () => el.classList.add('is-press'));
    addEventListener('mouseup',   () => el.classList.remove('is-press'));
  }

  /* ── 푸터 ── */
  function footerDrift() {
    const d = $('.footer__display');
    if (!d) return;
    gsap.fromTo(d, { xPercent: 3 }, { xPercent: -3, ease: 'none',
      scrollTrigger: { trigger: 'footer', start: 'top bottom', end: 'bottom bottom', scrub: 1 } });
  }
})();
