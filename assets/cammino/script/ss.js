document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("[data-header]");
  const nav = document.querySelector("[data-nav]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const updateHeader = () => header?.classList.toggle("is-scrolled", window.scrollY > 24);
  updateHeader();
  window.addEventListener("scroll", updateHeader, { passive: true });

  const closeNavigation = () => {
    if (!nav || !navToggle) return;
    nav.classList.remove("is-open");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Otvoriť menu");
    navToggle.querySelector("i")?.classList.add("fa-bars");
    navToggle.querySelector("i")?.classList.remove("fa-xmark");
    document.body.classList.remove("nav-open");
  };

  navToggle?.addEventListener("click", () => {
    const isOpen = nav?.classList.toggle("is-open") ?? false;
    navToggle.setAttribute("aria-expanded", String(isOpen));
    navToggle.setAttribute("aria-label", isOpen ? "Zavrieť menu" : "Otvoriť menu");
    navToggle.querySelector("i")?.classList.toggle("fa-bars", !isOpen);
    navToggle.querySelector("i")?.classList.toggle("fa-xmark", isOpen);
    document.body.classList.toggle("nav-open", isOpen);
  });

  nav?.querySelectorAll("a").forEach((link) => link.addEventListener("click", closeNavigation));
  document.addEventListener("keydown", (event) => { if (event.key === "Escape") closeNavigation(); });

  const revealItems = document.querySelectorAll("[data-story-reveal]");
  revealItems.forEach((item) => item.style.setProperty("--story-delay", `${item.dataset.delay || 0}ms`));

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  } else {
    const observer = new IntersectionObserver((entries, currentObserver) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        currentObserver.unobserve(entry.target);
      });
    }, { threshold: 0.1, rootMargin: "0px 0px -6% 0px" });
    revealItems.forEach((item) => observer.observe(item));
  }

  const storySections = [...document.querySelectorAll(".success-story[id]")];
  const jumpLinks = [...document.querySelectorAll(".story-jump a")];
  if ("IntersectionObserver" in window && storySections.length) {
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        jumpLinks.forEach((link) => link.classList.toggle("is-current", link.getAttribute("href") === `#${entry.target.id}`));
      });
    }, { threshold: 0.35 });
    storySections.forEach((section) => sectionObserver.observe(section));
  }

  document.querySelectorAll(".newsletter").forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const button = form.querySelector("button");
      if (!button || !form.checkValidity()) return;
      const original = button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
      button.disabled = true;
      window.setTimeout(() => { button.innerHTML = original; button.disabled = false; form.reset(); }, 2200);
    });
  });

  document.querySelectorAll("[data-year]").forEach((item) => { item.textContent = String(new Date().getFullYear()); });
});
