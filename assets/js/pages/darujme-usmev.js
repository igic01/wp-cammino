document.addEventListener("DOMContentLoaded", () => {
  const page = document.querySelector(".smile-main");
  if (!page) return;

  const motionPreference = window.matchMedia("(prefers-reduced-motion: reduce)");
  const isPreview = document.body.classList.contains("nstarter-editor-preview");
  const elements = [...page.querySelectorAll("[data-smile-reveal]")];
  const timers = new Set();
  let observer;

  // Save the content, not the current animation frame, in the visual editor.
  elements.forEach((element) => {
    const classes = new Set((element.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean));
    ["smile-reveal-pending", "is-visible"].forEach((name) => classes.add(name));
    element.setAttribute("data-nstarter-transient-class", [...classes].join(" "));
    element.classList.remove("smile-reveal-pending", "is-visible");
  });

  const show = (element) => {
    element.classList.remove("smile-reveal-pending");
    element.classList.add("is-visible");
    observer?.unobserve(element);
  };
  const showAll = () => {
    observer?.disconnect();
    timers.forEach((timer) => window.clearTimeout(timer));
    timers.clear();
    elements.forEach(show);
  };

  if (isPreview || motionPreference.matches || !("IntersectionObserver" in window)) {
    showAll();
    return;
  }

  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);
      const delay = Math.min(300, Math.max(0, Number(entry.target.dataset.delay) || 0));
      const timer = window.setTimeout(() => {
        show(entry.target);
        timers.delete(timer);
      }, delay);
      timers.add(timer);
    });
  }, { threshold: 0, rootMargin: "0px 0px -24px 0px" });

  elements.forEach((element) => {
    element.classList.add("smile-reveal-pending");
    observer.observe(element);
  });

  const revealTarget = (target) => {
    if (!(target instanceof Element)) return;
    const parent = target.closest("[data-smile-reveal]");
    if (parent) show(parent);
    target.querySelectorAll("[data-smile-reveal]").forEach(show);
  };
  const revealHash = () => {
    try {
      revealTarget(document.getElementById(decodeURIComponent(window.location.hash.slice(1))));
    } catch {
      // A malformed incoming fragment must not interrupt normal page navigation.
    }
  };
  page.addEventListener("focusin", (event) => revealTarget(event.target));
  page.addEventListener("click", (event) => {
    const link = event.target.closest('a[href^="#"]');
    if (link) revealTarget(document.getElementById(link.hash.slice(1)));
  });
  window.addEventListener("hashchange", revealHash);
  revealHash();
  motionPreference.addEventListener("change", (event) => {
    if (event.matches) showAll();
  });
});
