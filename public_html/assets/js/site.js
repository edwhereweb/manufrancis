// Progressive enhancement only: every page works without this file.
document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('.nav');
  const toggle = document.querySelector('.nav-toggle');
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
