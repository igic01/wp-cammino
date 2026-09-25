(() => {
  const switcher = document.querySelector("[data-language-switcher]");
  if (!switcher) return;

  const original = switcher.querySelector('[data-language="sk"]');
  const translated = switcher.querySelector('[data-language="en"]');
  const siteUrl = switcher.dataset.siteUrl;
  const originalUrl = switcher.dataset.originalUrl;
  const englishUrl = switcher.dataset.englishUrl;
  if (!original || !translated || !siteUrl || !originalUrl || !englishUrl) return;

  const key = "cammino-language";
  const onSite = window.location.origin === new URL(siteUrl).origin;
  const preview = document.body.classList.contains("nstarter-editor-preview");
  const params = new URLSearchParams(window.location.search);
  const requested = params.get("cammino_lang");

  const remember = (language) => {
    try { window.localStorage.setItem(key, language); } catch (_) { /* Storage may be disabled. */ }
    document.cookie = `${key}=${language}; Max-Age=31536000; Path=/; SameSite=Lax`;
  };

  const saved = () => {
    try {
      const value = window.localStorage.getItem(key);
      if (value === "sk" || value === "en") return value;
    } catch (_) { /* Use the cookie if storage is disabled. */ }
    const value = document.cookie.split("; ").find((part) => part.startsWith(`${key}=`));
    return value?.split("=")[1] === "en" ? "en" : "sk";
  };

  const setActive = (language) => {
    for (const [link, code] of [[original, "sk"], [translated, "en"]]) {
      if (language === code) link.setAttribute("aria-current", "true");
      else link.removeAttribute("aria-current");
    }
  };

  if (onSite && requested === "sk") {
    remember("sk");
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete("cammino_lang");
    window.history.replaceState(window.history.state, "", cleanUrl);
  }

  const language = onSite ? saved() : "en";
  setActive(language);

  if (onSite && !preview && language === "en") {
    window.location.replace(englishUrl);
    return;
  }

  original.addEventListener("click", (event) => {
    remember("sk");
    if (onSite) {
      event.preventDefault();
      setActive("sk");
    } else {
      event.preventDefault();
      window.location.assign(originalUrl);
    }
  });

  translated.addEventListener("click", (event) => {
    remember("en");
    setActive("en");
    if (!onSite) event.preventDefault();
  });
})();

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
