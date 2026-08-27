document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.querySelectorAll("#nstarter-snapshot > .site-header, #nstarter-snapshot > .site-footer")
    .forEach((element) => element.remove());

  document.querySelector(".details-heading > p")?.remove();

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

  const qrControls = document.querySelectorAll("[data-qr-toggle]");
  const mobileQrMedia = window.matchMedia("(max-width: 760px)");

  const closeExpandedQrControls = (exceptControl = null) => {
    qrControls.forEach((control) => {
      if (control === exceptControl || !control.classList.contains("is-expanded")) return;

      const controlHint = control.parentElement?.querySelector(".qr-hint");
      control.classList.remove("is-expanded");
      control.setAttribute("aria-pressed", "false");
      control.setAttribute("aria-label", "Zväčšiť QR kód");

      if (controlHint) {
        controlHint.innerHTML = '<i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> Kliknutím zväčšíte QR';
      }
    });
  };

  qrControls.forEach((qrControl) => {
    const hint = qrControl.parentElement?.querySelector(".qr-hint");
    qrControl.setAttribute("data-nstarter-transient-class", "is-expanded");
    qrControl.setAttribute("data-nstarter-transient-attributes", "aria-pressed aria-label");

    const toggleQr = () => {
      if (mobileQrMedia.matches) {
        closeExpandedQrControls();
        window.CamminoMediaPopup?.open(qrControl.querySelector("img"), "Zavrieť QR kód");
        return;
      }

      const isExpanded = qrControl.classList.toggle("is-expanded");
      if (isExpanded) closeExpandedQrControls(qrControl);
      qrControl.setAttribute("aria-pressed", String(isExpanded));
      qrControl.setAttribute("aria-label", isExpanded ? "Zmenšiť QR kód" : "Zväčšiť QR kód");

      if (hint) {
        hint.innerHTML = `<i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> ${isExpanded ? "Kliknutím zmenšíte QR" : "Kliknutím zväčšíte QR"}`;
      }
    };

    qrControl.addEventListener("click", (event) => {
      event.stopPropagation();
      toggleQr();
    });

    qrControl.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      event.stopPropagation();
      toggleQr();
    });
  });

  document.addEventListener("click", () => closeExpandedQrControls());

  const messageField = document.querySelector(".contact-form-runtime .wpcf7 textarea");

  messageField?.addEventListener("input", () => {
    messageField.style.height = "auto";
    messageField.style.height = `${Math.min(messageField.scrollHeight, 288)}px`;
  });
});
