document.addEventListener("DOMContentLoaded", () => {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.querySelector("[data-scroll-to-donation]")?.addEventListener("click", (event) => {
    event.preventDefault();
    document.querySelector("#donation-form")?.scrollIntoView({ behavior: reducedMotion ? "auto" : "smooth", block: "start" });
  });

  const revealItems = document.querySelectorAll("[data-us-reveal]");
  revealItems.forEach((item) => {
    item.setAttribute("data-nstarter-transient-class", "is-visible");
    item.style.setProperty("--us-delay", `${item.dataset.delay || 0}ms`);
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
    }, { threshold: 0.08, rootMargin: "0px 0px -4% 0px" });
    revealItems.forEach((item) => observer.observe(item));
  }

  const frequencyButtons = [...document.querySelectorAll("[data-us-frequency]")];
  const amountButtons = [...document.querySelectorAll("[data-us-amount]")];
  const customAmount = document.querySelector("[data-us-custom-amount]");
  const summaryAmount = document.querySelector("[data-us-summary-amount]");
  const summaryFrequency = document.querySelector("[data-us-summary-frequency]");
  let selectedAmount = 30;
  let selectedFrequency = "once";

  const updateSummary = () => {
    if (summaryAmount) summaryAmount.textContent = `${selectedAmount || 0} €`;
    if (summaryFrequency) summaryFrequency.textContent = selectedFrequency === "monthly" ? "Mesačný dar" : "Jednorazový dar";
  };

  frequencyButtons.forEach((button) => button.addEventListener("click", () => {
    selectedFrequency = button.dataset.usFrequency;
    frequencyButtons.forEach((item) => {
      const active = item === button;
      item.classList.toggle("is-active", active);
      item.setAttribute("aria-pressed", String(active));
    });
    updateSummary();
  }));

  amountButtons.forEach((button) => button.addEventListener("click", () => {
    selectedAmount = Number(button.dataset.usAmount);
    amountButtons.forEach((item) => item.classList.toggle("is-active", item === button));
    if (customAmount) customAmount.value = "";
    updateSummary();
  }));

  customAmount?.addEventListener("input", () => {
    selectedAmount = Math.max(0, Number(customAmount.value));
    amountButtons.forEach((button) => button.classList.remove("is-active"));
    updateSummary();
  });

  const form = document.querySelector("[data-us-donation-form]");
  const success = document.querySelector("[data-us-success]");
  form?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    if (selectedAmount < 1) { customAmount?.focus(); return; }
    form.hidden = true;
    if (success) success.hidden = false;
  });

  document.querySelector("[data-us-form-reset]")?.addEventListener("click", () => {
    if (success) success.hidden = true;
    if (form) form.hidden = false;
  });
});
