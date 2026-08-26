(() => {
  const header = document.querySelector('[data-header]');
  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const progress = document.querySelector('[data-reading-progress]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const updateScrollState = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 18);
    if (!progress) return;
    const distance = document.documentElement.scrollHeight - window.innerHeight;
    progress.style.transform = `scaleX(${distance > 0 ? Math.min(window.scrollY / distance, 1) : 0})`;
  };

  updateScrollState();
  window.addEventListener('scroll', updateScrollState, { passive: true });

  navToggle?.addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('nav-open');
    navToggle.setAttribute('aria-expanded', String(isOpen));
    navToggle.setAttribute('aria-label', isOpen ? 'Zavrieť menu' : 'Otvoriť menu');
    navToggle.querySelector('i')?.classList.toggle('fa-bars', !isOpen);
    navToggle.querySelector('i')?.classList.toggle('fa-xmark', isOpen);
  });

  nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    document.body.classList.remove('nav-open');
    navToggle?.setAttribute('aria-expanded', 'false');
  }));

  const revealItems = document.querySelectorAll('[data-article-reveal]');
  revealItems.forEach((item) => item.style.setProperty('--reveal-delay', `${item.dataset.delay || 0}ms`));

  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -7% 0px' });
    revealItems.forEach((item) => observer.observe(item));
  }

  const shareUrl = () => encodeURIComponent(window.location.href);
  const shareTitle = () => encodeURIComponent(document.title);
  document.querySelectorAll('[data-share]').forEach((button) => {
    button.addEventListener('click', async () => {
      const type = button.dataset.share;
      if (type === 'facebook') window.open(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl()}`, '_blank', 'noopener,noreferrer,width=720,height=520');
      if (type === 'linkedin') window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl()}&title=${shareTitle()}`, '_blank', 'noopener,noreferrer,width=720,height=520');
      if (type === 'copy') {
        const feedback = document.querySelector('[data-copy-feedback]');
        try {
          await navigator.clipboard.writeText(window.location.href);
          if (feedback) feedback.textContent = 'Odkaz skopírovaný';
        } catch {
          if (feedback) feedback.textContent = 'Odkaz sa nepodarilo skopírovať';
        }
        window.setTimeout(() => { if (feedback) feedback.textContent = ''; }, 2200);
      }
    });
  });

  document.querySelector('[data-cover-sticker]')?.addEventListener('click', (event) => {
    const sticker = event.currentTarget;
    sticker.classList.remove('is-pulsing');
    requestAnimationFrame(() => sticker.classList.add('is-pulsing'));
    window.setTimeout(() => sticker.classList.remove('is-pulsing'), 580);
  });

  document.querySelectorAll('[data-year]').forEach((item) => { item.textContent = new Date().getFullYear(); });
})();
