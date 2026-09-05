document.addEventListener("DOMContentLoaded", () => {
  const page = document.querySelector(".activities-main");
  if (!page) return;

  const motionPreference = window.matchMedia("(prefers-reduced-motion: reduce)");
  const isPreview = document.body.classList.contains("nstarter-editor-preview");
  const elements = [...page.querySelectorAll("[data-activities-reveal]")];
  const pendingTimers = new Set();
  let revealObserver;
  let orbitObserver;

  // The visual editor removes these runtime classes when saving a snapshot.
  elements.forEach((element) => {
    const classes = new Set((element.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean));
    ["activities-reveal-pending", "is-visible", "is-in-view"].forEach((name) => classes.add(name));
    element.setAttribute("data-nstarter-transient-class", [...classes].join(" "));
    element.classList.remove("activities-reveal-pending", "is-visible", "is-in-view");
  });

  const show = (element) => {
    element.classList.remove("activities-reveal-pending");
    element.classList.add("is-visible");
  };

  const showAll = () => {
    revealObserver?.disconnect();
    orbitObserver?.disconnect();
    pendingTimers.forEach((timer) => window.clearTimeout(timer));
    pendingTimers.clear();
    elements.forEach(show);
    page.querySelectorAll(".is-in-view").forEach((element) => element.classList.remove("is-in-view"));
  };

  if (isPreview || motionPreference.matches || !("IntersectionObserver" in window)) {
    showAll();
    return;
  }

  revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      revealObserver.unobserve(entry.target);
      const delay = Math.min(300, Math.max(0, Number(entry.target.dataset.delay) || 0));
      const timer = window.setTimeout(() => {
        show(entry.target);
        pendingTimers.delete(timer);
      }, delay);
      pendingTimers.add(timer);
    });
  }, { threshold: 0, rootMargin: "0px 0px -24px 0px" });

  elements.forEach((element) => {
    element.classList.add("activities-reveal-pending");
    revealObserver.observe(element);
  });

  // Only the globe has ambient motion; pause it whenever it leaves the viewport.
  orbitObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => entry.target.classList.toggle("is-in-view", entry.isIntersecting));
  }, { threshold: 0 });
  page.querySelectorAll(".activities-world").forEach((element) => orbitObserver.observe(element));

  // Keyboard and anchor navigation must never land on an invisible section.
  const revealTarget = (target) => {
    if (!(target instanceof Element)) return;
    const parent = target.closest("[data-activities-reveal]");
    if (parent) {
      revealObserver.unobserve(parent);
      show(parent);
    }
    target.querySelectorAll("[data-activities-reveal]").forEach((element) => {
      revealObserver.unobserve(element);
      show(element);
    });
  };

  page.addEventListener("focusin", (event) => revealTarget(event.target));
  page.addEventListener("click", (event) => {
    const link = event.target.closest('a[href^="#"]');
    if (link) revealTarget(document.getElementById(link.hash.slice(1)));
  });
  const revealHash = () => {
    try {
      revealTarget(document.getElementById(decodeURIComponent(window.location.hash.slice(1))));
    } catch {
      // Ignore malformed incoming URL fragments; normal page navigation still works.
    }
  };
  window.addEventListener("hashchange", revealHash);
  revealHash();
  motionPreference.addEventListener("change", (event) => {
    if (event.matches) showAll();
  });
});
