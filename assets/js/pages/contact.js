document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.querySelectorAll("#nstarter-snapshot > .site-header, #nstarter-snapshot > .site-footer")
    .forEach((element) => element.remove());

  const revealElements = document.querySelectorAll("[data-contact-reveal]");
  revealElements.forEach((element) => {
    element.setAttribute("data-nstarter-transient-class", "is-visible");
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

  document.querySelectorAll("[data-contact-pop]").forEach((control) => {
    control.setAttribute("data-nstarter-transient-class", "is-popped");
    control.addEventListener("click", () => {
      control.classList.remove("is-popped");
      void control.offsetWidth;
      control.classList.add("is-popped");
      window.setTimeout(() => control.classList.remove("is-popped"), 650);
    });
  });

  document.querySelectorAll("[data-qr-toggle]").forEach((qrControl) => {
    const hint = qrControl.parentElement?.querySelector(".qr-hint");
    qrControl.setAttribute("data-nstarter-transient-class", "is-expanded");
    qrControl.setAttribute("data-nstarter-transient-attributes", "aria-pressed aria-label");

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

  const messageField = document.querySelector(".contact-form-runtime .wpcf7 textarea");

  messageField?.addEventListener("input", () => {
    messageField.style.height = "auto";
    messageField.style.height = `${Math.min(messageField.scrollHeight, 288)}px`;
  });
});
