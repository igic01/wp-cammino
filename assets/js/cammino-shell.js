document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("[data-header]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const nav = document.querySelector("[data-nav]");
  const year = document.querySelector("[data-year]");

  header?.setAttribute("data-nstarter-transient-class", "is-scrolled");
  nav?.setAttribute("data-nstarter-transient-class", "is-open");

  const updateHeader = () => {
    header?.classList.toggle("is-scrolled", window.scrollY > 24);
  };

  const closeNav = () => {
    nav?.classList.remove("is-open");
    navToggle?.setAttribute("aria-expanded", "false");
    document.body.classList.remove("nav-open");
  };

  updateHeader();
  window.addEventListener("scroll", updateHeader, { passive: true });

  navToggle?.addEventListener("click", () => {
    const isOpen = nav?.classList.toggle("is-open") ?? false;
    navToggle.setAttribute("aria-expanded", String(isOpen));
    document.body.classList.toggle("nav-open", isOpen);
  });

  nav?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeNav);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeNav();
  });

  if (year) {
    year.textContent = String(new Date().getFullYear());
  }
});

(() => {
  let activePopup = null;

  const close = () => {
    activePopup?.remove();
    activePopup = null;
    document.body.classList.remove("cammino-media-popup-open");
  };

  const open = (sourceImage, label = "Zavrieť náhľad") => {
    if (!(sourceImage instanceof HTMLImageElement)) return;

    close();

    const popup = document.createElement("button");
    const surface = document.createElement("span");
    const image = sourceImage.cloneNode(true);

    popup.type = "button";
    popup.className = "cammino-media-popup";
    popup.setAttribute("aria-label", label);
    surface.className = "cammino-media-popup__surface";
    image.removeAttribute("loading");

    surface.append(image);
    popup.append(surface);
    popup.addEventListener("click", close);
    document.body.append(popup);
    document.body.classList.add("cammino-media-popup-open");
    activePopup = popup;
    popup.focus({ preventScroll: true });
  };

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && activePopup) close();
  });

  window.CamminoMediaPopup = { open, close };
})();
