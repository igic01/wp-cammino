document.addEventListener("DOMContentLoaded", () => {
  const elements = [...document.querySelectorAll("[data-impact-stories-reveal]")];
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
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const delay = Number(entry.target.dataset.delay || 0);
        window.setTimeout(() => entry.target.classList.add("is-visible"), delay);
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -30px" },
  );

  elements.forEach((element) => observer.observe(element));
});
