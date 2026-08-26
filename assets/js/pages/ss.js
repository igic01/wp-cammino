document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const storyItems = document.querySelector(".stories-items");
  const jumpNavigation = document.querySelector("[data-story-jump]");
  const observedReveals = new WeakSet();
  let sectionObserver = null;

  document.querySelectorAll("#nstarter-snapshot > .site-header, #nstarter-snapshot > .site-footer")
    .forEach((element) => element.remove());

  // Move legacy visible navigation labels onto their story sections.
  document.querySelectorAll(".success-story [data-story-nav-label]").forEach((label) => {
    const story = label.closest(".success-story");
    if (story && !story.dataset.storyNavLabel) {
      story.dataset.storyNavLabel = label.textContent.trim();
    }
    label.remove();
  });

  // Older saved snapshots kept the URL variable on the small link wrapper.
  // Promote it to the result panel so the editor outline matches the click area.
  document.querySelectorAll(".success-story__result > .story-link-variable[data-nstarter-variable-section]")
    .forEach((wrapper) => {
      const resultPanel = wrapper.parentElement;
      const link = wrapper.querySelector(":scope > a[data-nstarter-variable-output]");
      if (!resultPanel || !link) return;

      [...wrapper.attributes].forEach((attribute) => {
        if (attribute.name.startsWith("data-nstarter-variable-")) {
          resultPanel.setAttribute(attribute.name, attribute.value);
        }
      });

      resultPanel.classList.add("story-link-variable");
      resultPanel.insertBefore(link, wrapper);
      wrapper.remove();
    });

  const revealObserver = !reducedMotion && "IntersectionObserver" in window
    ? new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.1, rootMargin: "0px 0px -6% 0px" })
    : null;

  const registerRevealItems = (root = document) => {
    root.querySelectorAll("[data-story-reveal]").forEach((item) => {
      if (observedReveals.has(item)) return;
      observedReveals.add(item);
      item.setAttribute("data-nstarter-transient-class", "is-visible");
      item.style.setProperty("--story-delay", `${item.dataset.delay || 0}ms`);

      if (revealObserver) {
        revealObserver.observe(item);
      } else {
        item.classList.add("is-visible");
      }
    });
  };

  const storySections = () => storyItems
    ? [...storyItems.children].filter((item) => item.matches(".success-story[data-nstarter-variable-item]"))
    : [];

  const setCurrentStory = (storyId) => {
    jumpNavigation?.querySelectorAll("a").forEach((link) => {
      link.classList.toggle("is-current", link.getAttribute("href") === `#${storyId}`);
    });
  };

  const rebuildStoryNavigation = () => {
    if (!jumpNavigation) return;

    sectionObserver?.disconnect();
    jumpNavigation.replaceChildren();

    const sections = storySections();
    sections.forEach((section, offset) => {
      if (!section.id) section.id = `story-${offset + 1}`;
      const label = section.dataset.storyNavLabel || `Príbeh ${offset + 1}`;
      const link = document.createElement("a");
      const number = document.createElement("span");

      number.textContent = String(offset + 1).padStart(2, "0");
      link.href = `#${section.id}`;
      link.setAttribute("data-nstarter-transient-class", "is-current");
      link.append(number, document.createTextNode(label));
      jumpNavigation.append(link);
    });

    if (!("IntersectionObserver" in window) || !sections.length) return;

    sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) setCurrentStory(entry.target.id);
      });
    }, { threshold: 0.35 });

    sections.forEach((section) => sectionObserver.observe(section));
  };

  registerRevealItems();
  rebuildStoryNavigation();

  if (storyItems && "MutationObserver" in window) {
    const storyMutationObserver = new MutationObserver((mutations) => {
      let navigationChanged = false;

      mutations.forEach((mutation) => {
        if (mutation.type === "childList" && mutation.target === storyItems) {
          navigationChanged = true;
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) registerRevealItems(node);
          });
        }

      });

      if (navigationChanged) rebuildStoryNavigation();
    });

    storyMutationObserver.observe(storyItems, { childList: true, subtree: true, characterData: true });
  }
});
