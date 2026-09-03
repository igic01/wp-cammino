document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const hero = document.querySelector(".hero");
  const heroVisual = document.querySelector(".hero-visual");
  const heroImage = heroVisual?.querySelector(".hero-image-wrap img");
  const storyVisuals = document.querySelectorAll(".story-visual");

  if (heroVisual) {
    const transientClasses = new Set((heroVisual.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean));
    transientClasses.add("is-visible");
    heroVisual.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));

    let heroRevealed = false;
    const revealHero = () => {
      if (heroRevealed) return;
      heroRevealed = true;
      const delay = reducedMotion ? 0 : Number(heroVisual.dataset.delay || 0);
      window.setTimeout(() => heroVisual.classList.add("is-visible"), delay);
    };

    if (!heroImage) {
      revealHero();
    } else if (reducedMotion) {
      revealHero();
    } else if (heroImage.complete) {
      if (heroImage.naturalWidth > 0 && typeof heroImage.decode === "function") {
        heroImage.decode().catch(() => {}).finally(revealHero);
      } else {
        revealHero();
      }
    } else {
      heroImage.addEventListener("load", revealHero, { once: true });
      heroImage.addEventListener("error", revealHero, { once: true });
    }
  }

  if (hero && !reducedMotion) {
    hero.setAttribute("data-nstarter-transient-attributes", "style");
    let pointerFrame;
    hero.addEventListener("pointermove", (event) => {
      if (window.innerWidth < 820 || !heroVisual?.classList.contains("is-visible")) return;
      cancelAnimationFrame(pointerFrame);
      pointerFrame = requestAnimationFrame(() => {
        const bounds = hero.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - 0.5;
        const y = (event.clientY - bounds.top) / bounds.height - 0.5;
        hero.style.setProperty("--hero-shift-x", `${x * 18}px`);
        hero.style.setProperty("--hero-shift-y", `${y * 14}px`);
      });
    });
    hero.addEventListener("pointerleave", () => {
      hero.style.setProperty("--hero-shift-x", "0px");
      hero.style.setProperty("--hero-shift-y", "0px");
    });
  }

  if (storyVisuals.length && !reducedMotion) {
    storyVisuals.forEach((storyVisual) => {
      storyVisual.setAttribute("data-nstarter-transient-class", "is-in-view");
    });
    if (!("IntersectionObserver" in window)) {
      storyVisuals.forEach((storyVisual) => storyVisual.classList.add("is-in-view"));
    } else {
      const storyObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => entry.target.classList.toggle("is-in-view", entry.isIntersecting));
      }, { threshold: 0.12 });
      storyVisuals.forEach((storyVisual) => storyObserver.observe(storyVisual));
    }
  }

  const impactCounters = document.querySelectorAll("[data-impact-counter]");
  const numberFormatter = new Intl.NumberFormat("en-US");
  const animatedCounters = new WeakSet();

  const impactCounterTarget = (counter) => {
    const variableSection = counter.closest("[data-nstarter-variable-section]");
    const rawValue = variableSection?.dataset.nstarterVariableValue
      || counter.textContent.replace(/[^\d-]/g, "");
    const value = Number(rawValue);
    return Number.isFinite(value) ? Math.max(0, Math.trunc(value)) : 0;
  };

  const renderImpactCounter = (counter, value) => {
    counter.textContent = numberFormatter.format(Math.round(value));
  };

  const animateImpactCounter = (counter) => {
    if (animatedCounters.has(counter)) return;
    animatedCounters.add(counter);

    const target = impactCounterTarget(counter);
    if (reducedMotion || target === 0) {
      renderImpactCounter(counter, target);
      return;
    }

    const duration = 1500;
    const startTime = performance.now();
    const tick = (currentTime) => {
      const progress = Math.min((currentTime - startTime) / duration, 1);
      const easedProgress = 1 - Math.pow(1 - progress, 4);
      renderImpactCounter(counter, target * easedProgress);
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };

  if (reducedMotion || !("IntersectionObserver" in window)) {
    impactCounters.forEach((counter) => renderImpactCounter(counter, impactCounterTarget(counter)));
  } else {
    const impactObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        animateImpactCounter(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.35 });

    impactCounters.forEach((counter) => {
      renderImpactCounter(counter, 0);
      impactObserver.observe(counter);
    });
  }

  impactCounters.forEach((counter) => {
    const variableSection = counter.closest("[data-nstarter-variable-section]");
    if (!variableSection || !("MutationObserver" in window)) return;
    const valueObserver = new MutationObserver(() => {
      renderImpactCounter(counter, impactCounterTarget(counter));
    });
    valueObserver.observe(variableSection, {
      attributes: true,
      attributeFilter: ["data-nstarter-variable-value"],
    });
  });

  const revealElements = document.querySelectorAll("[data-reveal]:not(.hero-visual)");
  revealElements.forEach((element) => {
    const transientClasses = new Set((element.dataset.nstarterTransientClass || "").split(/\s+/).filter(Boolean));
    transientClasses.add("is-visible");
    element.setAttribute("data-nstarter-transient-class", [...transientClasses].join(" "));
  });

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealElements.forEach((element) => element.classList.add("is-visible"));
    return;
  }

  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const delay = Number(entry.target.dataset.delay || 0);
      window.setTimeout(() => entry.target.classList.add("is-visible"), delay);
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: "0px 0px -35px" });

  revealElements.forEach((element) => revealObserver.observe(element));
});
