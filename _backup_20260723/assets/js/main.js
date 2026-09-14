/* ============================================================
   Glofind — main.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ----------------------------------------------------------
     Scroll Progress Bar
  ---------------------------------------------------------- */
  const progressBar = document.getElementById('scroll-progress');
  if (progressBar) {
    const updateProgress = () => {
      const scrolled = window.scrollY;
      const total = document.body.scrollHeight - window.innerHeight;
      progressBar.style.width = total > 0 ? (scrolled / total * 100) + '%' : '0%';
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  /* ----------------------------------------------------------
     Nav Scroll Effect  (#nav and .nav both)
  ---------------------------------------------------------- */
  const nav = document.getElementById('nav') || document.querySelector('nav.nav');
  if (nav) {
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  /* ----------------------------------------------------------
     Mobile Hamburger Menu  (#hamburger and .nav-hamburger)
  ---------------------------------------------------------- */
  const hamburger = document.getElementById('hamburger') || document.querySelector('.nav-hamburger');
  const navMenu = document.querySelector('.nav-menu') || document.querySelector('.nav-links');
  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      const isOpen = navMenu.classList.toggle('open');
      hamburger.classList.toggle('active', isOpen);
      hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      hamburger.setAttribute('aria-label', isOpen ? '메뉴 닫기' : '메뉴 열기');
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    navMenu.querySelectorAll('a:not(.nav-dropdown a)').forEach(link => {
      link.addEventListener('click', () => {
        navMenu.classList.remove('open');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', '메뉴 열기');
        document.body.style.overflow = '';
      });
    });
  }

  /* ----------------------------------------------------------
     Mobile: Services dropdown toggle (touch)
  ---------------------------------------------------------- */
  document.querySelectorAll('.nav-has-dropdown').forEach(item => {
    const link = item.querySelector(':scope > a');
    if (!link) return;
    link.addEventListener('click', e => {
      if (window.innerWidth <= 767) {
        e.preventDefault();
        item.classList.toggle('open');
      }
    });
  });

  /* ----------------------------------------------------------
     Custom Cursor (desktop only)
  ---------------------------------------------------------- */
  const cursor = document.getElementById('cursor');
  const trail = document.getElementById('cursor-trail');
  if (cursor && trail && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    let mx = -100, my = -100, tx = -100, ty = -100;
    let rafId;

    window.addEventListener('mousemove', e => {
      mx = e.clientX;
      my = e.clientY;
      cursor.style.left = mx + 'px';
      cursor.style.top  = my + 'px';
    }, { passive: true });

    const animateTrail = () => {
      tx += (mx - tx) * 0.12;
      ty += (my - ty) * 0.12;
      trail.style.left = tx + 'px';
      trail.style.top  = ty + 'px';
      rafId = requestAnimationFrame(animateTrail);
    };
    animateTrail();

    const enlarge = () => {
      cursor.style.transform = 'translate(-50%, -50%) scale(1.8)';
      trail.style.transform  = 'translate(-50%, -50%) scale(1.4)';
    };
    const reset = () => {
      cursor.style.transform = 'translate(-50%, -50%) scale(1)';
      trail.style.transform  = 'translate(-50%, -50%) scale(1)';
    };
    document.querySelectorAll('a, button, input, select, textarea, .pain-card, .service-card, .kpi-card, .case-card').forEach(el => {
      el.addEventListener('mouseenter', enlarge);
      el.addEventListener('mouseleave', reset);
    });
  } else {
    if (cursor) cursor.style.display = 'none';
    if (trail)  trail.style.display  = 'none';
    document.body.style.cursor = 'auto';
  }

  /* ----------------------------------------------------------
     Hero Parallax
  ---------------------------------------------------------- */
  const parallaxLayer = document.querySelector('.hero-parallax-layer');
  if (parallaxLayer) {
    const heroH = document.getElementById('hero')?.offsetHeight || window.innerHeight;
    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      if (y < heroH) {
        parallaxLayer.style.transform = `translateY(${y * 0.16}px)`;
      }
    }, { passive: true });
  }

  /* ----------------------------------------------------------
     Section Dot Navigation
  ---------------------------------------------------------- */
  const sectionDots = document.querySelectorAll('.section-dot');
  if (sectionDots.length) {
    const dotTargets = Array.from(sectionDots).map(d => document.querySelector(d.getAttribute('href')));
    const updateDots = () => {
      let activeIdx = 0;
      dotTargets.forEach((sec, i) => {
        if (sec && window.scrollY >= sec.offsetTop - window.innerHeight * 0.45) activeIdx = i;
      });
      sectionDots.forEach((d, i) => d.classList.toggle('active', i === activeIdx));
    };
    window.addEventListener('scroll', updateDots, { passive: true });
    updateDots();
  }

  /* ----------------------------------------------------------
     Reveal on Scroll (supports .reveal, .reveal-left, .reveal-right, .reveal-scale)
  ---------------------------------------------------------- */
  const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
  const revealAll = () => reveals.forEach(el => el.classList.add('revealed'));

  /* In iframes (preview panels) skip scroll-reveal so nothing stays hidden */
  let inIframe = false;
  try { inIframe = window !== window.top; } catch (e) { inIframe = true; }

  if (inIframe || !reveals.length || !('IntersectionObserver' in window) || window.innerHeight === 0) {
    revealAll();
  } else {
    const revealObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const parent   = entry.target.parentElement;
        const siblings = parent
          ? Array.from(parent.querySelectorAll('.reveal:not(.revealed), .reveal-left:not(.revealed), .reveal-right:not(.revealed), .reveal-scale:not(.revealed)'))
          : [];
        const idx = siblings.indexOf(entry.target);
        setTimeout(() => {
          entry.target.classList.add('revealed');
        }, Math.max(0, idx) * 70);
        revealObs.unobserve(entry.target);
      });
    }, { threshold: 0.1 });
    reveals.forEach(el => revealObs.observe(el));
  }

  /* ----------------------------------------------------------
     Counter-Up Animation
  ---------------------------------------------------------- */
  function countUp(el, target, duration) {
    duration = duration || 1800;
    const suffix = el.dataset.suffix || '';
    const start  = performance.now();
    const update = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const ease     = 1 - Math.pow(1 - progress, 3);
      const value    = Math.floor(ease * target);
      el.textContent = value + suffix;
      if (progress < 1) requestAnimationFrame(update);
      else el.textContent = target + suffix;
    };
    requestAnimationFrame(update);
  }

  const counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    const countObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el     = entry.target;
        const target = parseInt(el.dataset.count, 10);
        if (!isNaN(target)) countUp(el, target);
        countObs.unobserve(el);
      });
    }, { threshold: 0.5 });
    counters.forEach(el => countObs.observe(el));
  }

  /* ----------------------------------------------------------
     Contact Form Validation (UI only)
  ---------------------------------------------------------- */
  const contactForm = document.getElementById('contact-form');
  const formSuccess = document.getElementById('form-success');

  if (contactForm) {
    const validateField = (field) => {
      const errorEl = field.parentElement.querySelector('.form-error-msg');
      let valid = true;

      if (field.hasAttribute('required') && !field.value.trim()) {
        field.classList.add('error');
        if (errorEl) errorEl.classList.add('show');
        valid = false;
      } else if (field.type === 'email' && field.value.trim()) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(field.value.trim())) {
          field.classList.add('error');
          if (errorEl) {
            errorEl.textContent = '올바른 이메일 형식을 입력해주세요.';
            errorEl.classList.add('show');
          }
          valid = false;
        } else {
          field.classList.remove('error');
          if (errorEl) errorEl.classList.remove('show');
        }
      } else {
        field.classList.remove('error');
        if (errorEl) errorEl.classList.remove('show');
      }
      return valid;
    };

    contactForm.querySelectorAll('input, select, textarea').forEach(field => {
      field.addEventListener('input', () => validateField(field));
      field.addEventListener('blur',  () => validateField(field));
    });

    contactForm.addEventListener('submit', e => {
      e.preventDefault();
      let allValid = true;
      contactForm.querySelectorAll('input, select, textarea').forEach(field => {
        if (!validateField(field)) allValid = false;
      });
      if (allValid) {
        contactForm.style.display = 'none';
        if (formSuccess) formSuccess.classList.add('show');
      }
    });
  }

  /* ----------------------------------------------------------
     Newsletter Form (UI only)
  ---------------------------------------------------------- */
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', e => {
      e.preventDefault();
      const input = newsletterForm.querySelector('input[type="email"]');
      const btn   = newsletterForm.querySelector('button');
      if (input && !input.value.trim()) {
        input.focus();
        return;
      }
      if (btn) {
        btn.textContent = '구독 완료! ✓';
        btn.disabled    = true;
        btn.style.background = 'var(--success)';
      }
    });
  }

  /* ----------------------------------------------------------
     Smooth scroll for anchor links
  ---------------------------------------------------------- */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = 80;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

});
