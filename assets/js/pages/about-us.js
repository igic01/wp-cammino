document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#nstarter-snapshot > .site-header, #nstarter-snapshot > .site-footer")
    .forEach((element) => element.remove());

  const header = document.querySelector("[data-header]");
  const nav = document.querySelector("[data-nav]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  header?.setAttribute("data-nstarter-transient-class", "is-scrolled");
  nav?.setAttribute("data-nstarter-transient-class", "is-open");

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

  const revealElements = document.querySelectorAll("[data-about-reveal]");

  revealElements.forEach((element) => {
    element.setAttribute("data-nstarter-transient-class", "is-visible");
  });

  document.querySelectorAll("[data-word-highlight]").forEach((heading) => {
    const words = heading.textContent.trim().split(/\s+/);
    heading.replaceChildren();

    words.forEach((word, index) => {
      const wordElement = document.createElement("span");
      wordElement.className = "mission-word";
      wordElement.textContent = word;
      wordElement.setAttribute("data-nstarter-transient-class", "is-highlighted");
      wordElement.addEventListener("click", () => {
        wordElement.classList.toggle("is-highlighted");
      });
      heading.append(wordElement);
      if (index < words.length - 1) heading.append(document.createTextNode(" "));
    });
  });

  document.querySelectorAll("[data-floating-control]").forEach((control) => {
    control.setAttribute("data-nstarter-transient-class", "is-popped");
    control.addEventListener("click", () => {
      control.classList.remove("is-popped");
      void control.offsetWidth;
      control.classList.add("is-popped");
    });

    control.addEventListener("animationend", (event) => {
      if (event.animationName === "europe-control-pop") {
        control.classList.remove("is-popped");
      }
    });
  });

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

  document.querySelectorAll(".newsletter").forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const button = form.querySelector("button");
      if (!button || !form.checkValidity()) return;
      const original = button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
      button.disabled = true;
      window.setTimeout(() => {
        button.innerHTML = original;
        button.disabled = false;
        form.reset();
      }, 2600);
    });
  });

  const year = document.querySelector("[data-year]");
  if (year) year.textContent = String(new Date().getFullYear());
});
