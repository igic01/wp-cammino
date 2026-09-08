document.addEventListener("DOMContentLoaded", () => {
  const elements = [...document.querySelectorAll("[data-contact2-reveal]")];
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const editorPreview = document.body.classList.contains("nstarter-editor-preview");

  elements.forEach((element) => {
    const transientClasses = new Set(
      (element.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean),
    );
    transientClasses.add("is-visible");
    element.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));
  });

  if (editorPreview || reducedMotion || !("IntersectionObserver" in window)) {
    elements.forEach((element) => element.classList.add("is-visible"));
  } else {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const delay = Number(entry.target.dataset.delay || 0);
          window.setTimeout(() => entry.target.classList.add("is-visible"), delay);
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.1 },
    );

    elements.forEach((element) => observer.observe(element));
  }

  const formCard = document.querySelector(".contact2-form-card");
  if (formCard) {
    const transientClasses = new Set(
      (formCard.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean),
    );
    transientClasses.add("is-form-active");
    formCard.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));
    formCard.addEventListener("focusin", () => formCard.classList.add("is-form-active"));
    formCard.addEventListener("focusout", (event) => {
      if (!formCard.contains(event.relatedTarget)) formCard.classList.remove("is-form-active");
    });
  }

  document.querySelectorAll(".contact2-form-runtime textarea").forEach((textarea) => {
    textarea.addEventListener("input", () => {
      textarea.style.height = "auto";
      textarea.style.height = `${Math.min(textarea.scrollHeight, 352)}px`;
    });
  });
});
