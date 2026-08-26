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

  const revealElements = document.querySelectorAll("[data-contact-reveal]");

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

  document.querySelectorAll("[data-contact-pop]").forEach((control) => {
    control.addEventListener("click", () => {
      control.classList.remove("is-popped");
      void control.offsetWidth;
      control.classList.add("is-popped");
      window.setTimeout(() => control.classList.remove("is-popped"), 650);
    });
  });

  document.querySelectorAll("[data-qr-toggle]").forEach((qrControl) => {
    const hint = qrControl.parentElement?.querySelector(".qr-hint");

    const toggleScan = () => {
      const isExpanded = qrControl.classList.toggle("is-expanded");
      qrControl.setAttribute("aria-pressed", String(isExpanded));
      qrControl.setAttribute("aria-label", isExpanded ? "Zmenšiť QR kód" : "Zväčšiť QR kód");
      if (hint) {
        hint.innerHTML = `<i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> ${isExpanded ? "Kliknutím zmenšíte QR" : "Kliknutím zväčšíte QR"}`;
      }
    };

    qrControl.addEventListener("click", toggleScan);
    qrControl.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      toggleScan();
    });
  });

  const contactForm = document.querySelector("[data-contact-form]");
  const formStatus = document.querySelector("[data-form-status]");
  const messageField = contactForm?.querySelector("textarea");

  messageField?.addEventListener("input", () => {
    messageField.style.height = "auto";
    messageField.style.height = `${Math.min(messageField.scrollHeight, 288)}px`;
  });

  contactForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    const submitButton = contactForm.querySelector('button[type="submit"]');
    if (!submitButton) return;

    const originalContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i> Odosielam';
    if (formStatus) {
      formStatus.textContent = "";
      formStatus.classList.remove("is-success");
    }

    window.setTimeout(() => {
      submitButton.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Správa pripravená';
      if (formStatus) {
        formStatus.textContent = "Ďakujeme. Ozveme sa vám čo najskôr.";
        formStatus.classList.add("is-success");
      }
      contactForm.reset();
      if (messageField) messageField.style.height = "";

      window.setTimeout(() => {
        submitButton.innerHTML = originalContent;
        submitButton.disabled = false;
      }, 2400);
    }, 750);
  });

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
