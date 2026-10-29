// Progressive enhancement only: every page works without this file.
(() => {
  const root = document.documentElement;
  const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Scroll reveal. Things already on screen stay visible, so nothing flashes on load.
  const reveals = [...document.querySelectorAll('.reveal')];
  if ('IntersectionObserver' in window && !calm) {
    reveals.forEach((el) => { if (el.getBoundingClientRect().top < window.innerHeight) el.classList.add('in'); });
    root.classList.add('js');
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    reveals.forEach((el) => { if (!el.classList.contains('in')) io.observe(el); });
  }

  // Count the stats up once when they come into view.
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window && !calm) {
    const run = (el) => {
      const end = Number(el.dataset.count); const suffix = el.dataset.suffix || ''; const t0 = performance.now();
      const step = (t) => {
        const k = Math.min(1, (t - t0) / 1400); const eased = 1 - Math.pow(1 - k, 3);
        el.textContent = Math.round(end * eased) + suffix;
        if (k < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    const co = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { run(en.target); co.unobserve(en.target); }
    }), { threshold: .6 });
    counters.forEach((el) => co.observe(el));
  }

  document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('.nav');
    const toggle = document.querySelector('.nav-toggle');
    if (nav) {
      const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
    }
    if (nav && toggle) {
      toggle.addEventListener('click', () => toggle.setAttribute('aria-expanded', String(nav.classList.toggle('open'))));
      nav.querySelectorAll('.nav-links a').forEach((a) => a.addEventListener('click', () => {
        nav.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }));
    }

    // Confirm destructive admin actions (inline onclick is blocked by our CSP).
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
      form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
      });
    });
  });
})();
