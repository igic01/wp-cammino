document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const revealItems = document.querySelectorAll("[data-current-reveal]");

  revealItems.forEach((item) => {
    const transientClasses = new Set((item.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean));
    transientClasses.add("is-visible");
    item.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));
    item.setAttribute("data-nstarter-transient-attributes", "style");
    item.style.setProperty("--current-delay", `${item.dataset.delay || 0}ms`);
  });

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealItems.forEach((item) => item.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver((entries, currentObserver) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-visible");
      currentObserver.unobserve(entry.target);
    });
  }, { threshold: 0.08, rootMargin: "0px 0px -4% 0px" });

  revealItems.forEach((item) => observer.observe(item));
});
