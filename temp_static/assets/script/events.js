document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("[data-header]");
  const nav = document.querySelector("[data-nav]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const updateHeader = () => {
    header?.classList.toggle("is-scrolled", window.scrollY > 24);
  };

  const closeNavigation = () => {
    if (!nav || !navToggle) return;
    const icon = navToggle.querySelector("i");
    nav.classList.remove("is-open");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Otvoriť menu");
    icon?.classList.add("fa-bars");
    icon?.classList.remove("fa-xmark");
    document.body.classList.remove("nav-open");
  };

  updateHeader();
  window.addEventListener("scroll", updateHeader, { passive: true });

  navToggle?.addEventListener("click", () => {
    const isOpen = nav?.classList.toggle("is-open") ?? false;
    const icon = navToggle.querySelector("i");
    navToggle.setAttribute("aria-expanded", String(isOpen));
    navToggle.setAttribute("aria-label", isOpen ? "Zavrieť menu" : "Otvoriť menu");
    icon?.classList.toggle("fa-bars", !isOpen);
    icon?.classList.toggle("fa-xmark", isOpen);
    document.body.classList.toggle("nav-open", isOpen);
  });

  nav?.querySelectorAll("a").forEach((link) => link.addEventListener("click", closeNavigation));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeNavigation();
  });

  const revealElements = document.querySelectorAll("[data-reveal]");

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealElements.forEach((element) => element.classList.add("is-visible"));
  } else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const delay = Number(entry.target.dataset.delay || 0);
        window.setTimeout(() => entry.target.classList.add("is-visible"), delay);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: "0px 0px -12px" });

    revealElements.forEach((element) => revealObserver.observe(element));
  }

  const filterButtons = [...document.querySelectorAll("[data-filter]")];
  const events = [...document.querySelectorAll("[data-event]")];
  const visibleCount = document.querySelector("[data-visible-count]");
  const emptyState = document.querySelector("[data-empty-events]");
  const showAllButton = document.querySelector("[data-show-all]");

  const applyFilter = (type) => {
    let count = 0;

    events.forEach((eventCard) => {
      const isVisible = type === "all" || eventCard.dataset.type === type;
      eventCard.classList.toggle("is-filtered-out", !isVisible);
      eventCard.setAttribute("aria-hidden", String(!isVisible));
      if (isVisible) count += 1;
    });

    filterButtons.forEach((button) => {
      const isActive = button.dataset.filter === type;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    if (visibleCount) visibleCount.textContent = String(count);
    if (emptyState) emptyState.hidden = count !== 0;
  };

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => applyFilter(button.dataset.filter || "all"));
  });

  showAllButton?.addEventListener("click", () => applyFilter("all"));

  document.querySelector("[data-newsletter]")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector("button");
    if (!button || !form.checkValidity()) return;
    button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
    button.setAttribute("aria-label", "Prihlásenie bolo úspešné");
    button.disabled = true;
  });

  const year = document.querySelector("[data-year]");
  if (year) year.textContent = String(new Date().getFullYear());
});
