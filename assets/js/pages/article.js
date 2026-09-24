document.addEventListener("DOMContentLoaded", () => {
  const progress = document.querySelector("[data-reading-progress]");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const updateProgress = () => {
    if (!progress) return;
    const distance = document.documentElement.scrollHeight - window.innerHeight;
    const value = distance > 0 ? Math.min(window.scrollY / distance, 1) : 0;
    progress.style.transform = `scaleX(${value})`;
  };

  updateProgress();
  window.addEventListener("scroll", updateProgress, { passive: true });

  const revealItems = document.querySelectorAll("[data-article-reveal]");
  revealItems.forEach((item) => {
    item.setAttribute("data-nstarter-transient-class", "is-visible");
    item.style.setProperty("--reveal-delay", `${item.dataset.delay || 0}ms`);
  });

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  } else {
    const observer = new IntersectionObserver((entries, currentObserver) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        currentObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -7% 0px" });

    revealItems.forEach((item) => observer.observe(item));
  }

  const shareUrl = () => encodeURIComponent(window.location.href);
  const shareTitle = () => encodeURIComponent(document.title);

  document.querySelectorAll("[data-share]").forEach((button) => {
    button.addEventListener("click", async () => {
      const type = button.dataset.share;

      if (type === "facebook") {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl()}`, "_blank", "noopener,noreferrer,width=720,height=520");
      }

      if (type === "linkedin") {
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl()}&title=${shareTitle()}`, "_blank", "noopener,noreferrer,width=720,height=520");
      }

      if (type === "copy") {
        const feedback = document.querySelector("[data-copy-feedback]");
        try {
          await navigator.clipboard.writeText(window.location.href);
          if (feedback) feedback.textContent = "Odkaz skopírovaný";
        } catch {
          if (feedback) feedback.textContent = "Odkaz sa nepodarilo skopírovať";
        }
        window.setTimeout(() => {
          if (feedback) feedback.textContent = "";
        }, 2200);
      }
    });
  });

  if (!document.body.classList.contains("nstarter-editor-preview")) {
    const articleImages = ".article-content img, .article-cover__frame img";
    let imageDialog = null;
    let enlargedImage = null;
    let openedFrom = null;

    document.querySelectorAll(articleImages).forEach((image) => {
      image.tabIndex = 0;
      image.setAttribute("role", "button");
      image.setAttribute("aria-label", image.alt ? `Zväčšiť obrázok: ${image.alt}` : "Zväčšiť obrázok");
    });

    const openImage = (image) => {
      if (!imageDialog) {
        imageDialog = document.createElement("dialog");
        imageDialog.className = "article-image-dialog";
        imageDialog.setAttribute("aria-label", "Zväčšený obrázok");
        const close = document.createElement("button");
        close.type = "button";
        close.className = "article-image-dialog__close";
        close.setAttribute("aria-label", "Zavrieť obrázok");
        close.textContent = "×";
        enlargedImage = document.createElement("img");
        imageDialog.append(close, enlargedImage);
        document.body.appendChild(imageDialog);
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
      const image = event.target.closest?.(articleImages);
      if (!image) return;
      event.preventDefault();
      openImage(image);
    });
    document.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      const image = event.target.matches?.(articleImages) ? event.target : null;
      if (!image) return;
      event.preventDefault();
      openImage(image);
    });
  }
});
