document.addEventListener("DOMContentLoaded", () => {
  const elements = [...document.querySelectorAll("[data-main-project-reveal]")];
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const editorPreview = document.body.classList.contains("nstarter-editor-preview");
  const animatedCounters = new WeakSet();

  if (!editorPreview) {
    const galleryImages = ".main-project-gallery__item > img";
    let imageDialog = null;
    let enlargedImage = null;
    let openedFrom = null;

    document.querySelectorAll(galleryImages).forEach((image) => {
      image.tabIndex = 0;
      image.setAttribute("role", "button");
      image.setAttribute("aria-label", image.alt ? `Zväčšiť obrázok: ${image.alt}` : "Zväčšiť obrázok");
    });

    const openImage = (image) => {
      if (!imageDialog) {
        imageDialog = document.createElement("dialog");
        imageDialog.className = "main-project-gallery__dialog";
        imageDialog.setAttribute("aria-label", "Zväčšený obrázok");
        const close = document.createElement("button");
        close.type = "button";
        close.className = "main-project-gallery__close";
        close.setAttribute("aria-label", "Zavrieť obrázok");
        close.textContent = "×";
        enlargedImage = document.createElement("img");
        imageDialog.append(close, enlargedImage);
        document.body.append(imageDialog);
        close.addEventListener("click", () => imageDialog.close());
        imageDialog.addEventListener("click", (event) => {
          if (event.target === imageDialog) imageDialog.close();
        });
        imageDialog.addEventListener("close", () => openedFrom?.focus());
      }

      openedFrom = image;
      enlargedImage.src = image.currentSrc || image.src;
      enlargedImage.alt = image.alt || "";
      if (!imageDialog.open) imageDialog.showModal();
    };

    document.addEventListener("click", (event) => {
      const image = event.target.closest?.(galleryImages);
      if (!image) return;
      event.preventDefault();
      openImage(image);
    });
    document.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      const image = event.target.matches?.(galleryImages) ? event.target : null;
      if (!image) return;
      event.preventDefault();
      openImage(image);
    });
  }

  const parseCounter = (counter) => {
    const source = counter.textContent.trim();
    const match = source.match(/^(.*?)(\d[\d\s.,]*)(.*?)$/);
    if (!match) return null;

    const target = Number(match[2].replace(/\D/g, ""));
    if (!Number.isFinite(target)) return null;

    return { prefix: match[1], target, suffix: match[3] };
  };

  const animateCounter = (counter) => {
    if (animatedCounters.has(counter)) return;

    const value = parseCounter(counter);
    if (!value) return;
    animatedCounters.add(counter);

    const duration = 1450;
    const startedAt = performance.now();
    const render = (time) => {
      const progress = Math.min((time - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 4);
      counter.textContent = `${value.prefix}${Math.round(value.target * eased)}${value.suffix}`;
      if (progress < 1) requestAnimationFrame(render);
    };

    requestAnimationFrame(render);
  };

  elements.forEach((element) => {
    const transientClasses = new Set(
      (element.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean),
    );
    transientClasses.add("is-visible");
    element.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));
  });

  if (editorPreview || reducedMotion || !("IntersectionObserver" in window)) {
    elements.forEach((element) => element.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const delay = Number(entry.target.dataset.delay || 0);
        window.setTimeout(() => {
          entry.target.classList.add("is-visible");
          entry.target.querySelectorAll("[data-main-project-counter]").forEach(animateCounter);
        }, delay);
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -35px" },
  );

  elements.forEach((element) => observer.observe(element));
});
