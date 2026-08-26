document.addEventListener("DOMContentLoaded", () => {
  const causes = {
    education: {
      title: "Vzdelávanie a mentoring",
      description: "Podporte kurzy, študijné materiály a osobné vedenie mladých ľudí pri ich ďalšom kroku.",
      icon: "fa-graduation-cap",
      theme: "sage",
      contentTitle: "Prečo je táto pomoc dôležitá",
      contentLead: "Mladí ľudia často nepotrebujú hotové odpovede. Potrebujú bezpečný priestor, správne otázky a človeka, ktorý pri nich zostane dostatočne dlho.",
      contentBody: "Vďaka podpore môžeme pripravovať pravidelné stretnutia, zabezpečiť potrebné materiály a prispôsobiť pomoc konkrétnej situácii každého účastníka. Príspevok tak nepokrýva iba jednu aktivitu, ale vytvára podmienky pre dlhodobejšiu zmenu.",
      contentQuote: "Najväčšiu zmenu často neprinesie jedna veľká vec, ale človek, ktorý nám dovolí urobiť ďalší krok.",
      contentCaption: "Podpora vytvára priestor skúšať, učiť sa a rásť vlastným tempom",
      secondaryTitle: "Ako vašu pomoc využijeme",
      secondaryBody: "Dar využijeme na mentoringové stretnutia, odborných lektorov, študijné materiály a dostupnosť programu pre mladých ľudí, ktorí by si ho inak nemohli dovoliť."
    },
    workshops: {
      title: "Praktické dielne",
      description: "Pomôžte zabezpečiť techniku, tvorivé potreby a vybavenie pre workshopy nových zručností.",
      icon: "fa-screwdriver-wrench",
      theme: "apricot",
      contentTitle: "Priestor učiť sa vlastnými rukami",
      contentLead: "Praktická skúsenosť pomáha mladým ľuďom objaviť schopnosti, o ktorých možno ešte nevedeli.",
      contentBody: "Podpora dielní nám umožňuje zabezpečiť nástroje, materiály a vedenie skúsených lektorov. Účastníci môžu bezpečne skúšať, robiť chyby a odísť s výsledkom, ktorý vytvorili sami.",
      contentQuote: "Keď si človek niečo vytvorí vlastnými rukami, začne veriť, že dokáže vytvoriť aj svoju ďalšiu príležitosť.",
      contentCaption: "Dielne spájajú zvedavosť, praktickú skúsenosť a podporu komunity",
      secondaryTitle: "Čo vďaka daru zabezpečíme",
      secondaryBody: "Príspevky premieňame na kvalitné materiály, bezpečné vybavenie, odborné vedenie a priestor, v ktorom môže každý účastník pracovať vlastným tempom."
    },
    community: {
      title: "Komunitné aktivity",
      description: "Prispejte na otvorené stretnutia a bezpečné priestory, kde sa môžu spájať celé komunity.",
      icon: "fa-people-group",
      theme: "coral",
      contentTitle: "Komunita vzniká spoločným zážitkom",
      contentLead: "Otvorené podujatia vytvárajú príležitosť stretnúť ľudí, ktorí by sa inak možno nikdy nespoznali.",
      contentBody: "Dar nám pomáha pripravovať dostupný program, tvorivé aktivity a príjemné zázemie pre všetkých účastníkov. Takto budujeme vzťahy, ktoré pokračujú aj po skončení podujatia.",
      contentQuote: "Komunita nezačína veľkým projektom. Začína miestom, kde sa ľudia cítia vítaní.",
      contentCaption: "Spoločný priestor premieňa stretnutia na dlhodobé vzťahy",
      secondaryTitle: "Ako vzniká otvorené podujatie",
      secondaryBody: "Podpora pokrýva dostupný program, materiály, technické zabezpečenie aj zázemie pre dobrovoľníkov. Vďaka nej zostávajú aktivity otvorené pre každého."
    },
    direct: {
      title: "Priama pomoc",
      description: "Pomôžte mladým ľuďom prekonať náročné obdobie a získať podporu, ktorú práve potrebujú.",
      icon: "fa-hands-holding-child",
      theme: "plum",
      contentTitle: "Pomoc v správnej chvíli",
      contentLead: "Niekedy môže aj malá prekážka zastaviť mladého človeka na ceste k škole, práci alebo samostatnosti.",
      contentBody: "Priama pomoc reaguje na konkrétnu situáciu rýchlo a citlivo. Môže ísť o cestovné, základné vybavenie alebo odbornú podporu, ktorá človeku umožní pokračovať ďalej.",
      contentQuote: "Niekedy rozhoduje jediná vec: vedieť, že na ďalší krok nemusíme zostať sami.",
      contentCaption: "Citlivá pomoc reaguje na skutočnú situáciu konkrétneho človeka",
      secondaryTitle: "Pomáhame zodpovedne a citlivo",
      secondaryBody: "Každú potrebu posudzujeme individuálne a spolu s mladým človekom hľadáme riešenie, ktoré mu pomôže pokračovať bez vytvárania novej závislosti."
    }
  };

  const params = new URLSearchParams(window.location.search);
  const requestedCause = params.get("cause");
  const causeKey = causes[requestedCause] ? requestedCause : "education";
  const cause = causes[causeKey];
  document.body.dataset.causeTheme = cause.theme;
  document.querySelector(`[data-donation-option="${causeKey}"]`)?.setAttribute("hidden", "");
  document.title = `${cause.title} — Cammino`;

  if (causeKey !== "education") {
    const values = {
      "[data-cause-title]": cause.title,
      "[data-cause-description]": cause.description,
      "[data-content-title]": cause.contentTitle,
      "[data-content-lead]": cause.contentLead,
      "[data-content-body]": cause.contentBody,
      "[data-content-quote]": cause.contentQuote,
      "[data-content-caption]": cause.contentCaption,
      "[data-content-secondary-title]": cause.secondaryTitle,
      "[data-content-secondary-body]": cause.secondaryBody
    };
    Object.entries(values).forEach(([selector, value]) => {
      const element = document.querySelector(selector);
      if (element) element.textContent = value;
    });
  }

  const causeImage = document.querySelector("[data-cause-image]");
  if (causeImage) causeImage.alt = `Ilustračný obrázok pre ${cause.title.toLocaleLowerCase("sk")}`;
  const causeIcon = document.querySelector("[data-cause-icon]");
  if (causeIcon) causeIcon.className = `fa-solid ${cause.icon}`;
  const contentImage = document.querySelector("[data-content-image]");
  if (contentImage) contentImage.alt = `${cause.title} — ilustračná fotografia programu`;

  const main = document.querySelector("#main-content");
  const checkoutSection = document.querySelector(".donation-checkout-section");
  const article = main?.querySelector(":scope > article");
  if (main && checkoutSection && article) main.insertBefore(checkoutSection, article);

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const revealItems = document.querySelectorAll("[data-detail-reveal]");
  revealItems.forEach((item) => {
    item.setAttribute("data-nstarter-transient-class", "is-visible");
    item.style.setProperty("--detail-delay", `${item.dataset.delay || 0}ms`);
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

  const frequencyButtons = [...document.querySelectorAll("[data-frequency]")];
  const amountButtons = [...document.querySelectorAll("[data-amount]")];
  const customAmount = document.querySelector("[data-custom-amount]");
  const summaryAmount = document.querySelector("[data-summary-amount]");
  const summaryFrequency = document.querySelector("[data-summary-frequency]");
  let selectedAmount = 30;
  let selectedFrequency = "once";

  const updateSummary = () => {
    if (summaryAmount) summaryAmount.textContent = `${selectedAmount || 0} €`;
    if (summaryFrequency) summaryFrequency.textContent = selectedFrequency === "monthly" ? "Mesačný dar" : "Jednorazový dar";
  };

  frequencyButtons.forEach((button) => button.addEventListener("click", () => {
    selectedFrequency = button.dataset.frequency;
    frequencyButtons.forEach((item) => {
      const active = item === button;
      item.classList.toggle("is-active", active);
      item.setAttribute("aria-pressed", String(active));
    });
    updateSummary();
  }));

  amountButtons.forEach((button) => button.addEventListener("click", () => {
    selectedAmount = Number(button.dataset.amount);
    amountButtons.forEach((item) => item.classList.toggle("is-active", item === button));
    if (customAmount) customAmount.value = "";
    updateSummary();
  }));

  customAmount?.addEventListener("input", () => {
    selectedAmount = Math.max(0, Number(customAmount.value));
    amountButtons.forEach((button) => button.classList.remove("is-active"));
    updateSummary();
  });

  const donationForm = document.querySelector("[data-donation-form]");
  const successState = document.querySelector("[data-donation-success]");
  donationForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!donationForm.checkValidity()) { donationForm.reportValidity(); return; }
    if (selectedAmount < 1) { customAmount?.focus(); return; }
    donationForm.hidden = true;
    if (successState) successState.hidden = false;
  });

  document.querySelector("[data-form-reset]")?.addEventListener("click", () => {
    if (successState) successState.hidden = true;
    if (donationForm) donationForm.hidden = false;
  });

  const shareUrl = () => encodeURIComponent(window.location.href);
  const shareTitle = () => encodeURIComponent(document.title);
  document.querySelectorAll("[data-share]").forEach((button) => {
    button.addEventListener("click", async () => {
      const type = button.dataset.share;
      if (type === "facebook") window.open(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl()}`, "_blank", "noopener,noreferrer,width=720,height=520");
      if (type === "linkedin") window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl()}&title=${shareTitle()}`, "_blank", "noopener,noreferrer,width=720,height=520");
      if (type !== "copy") return;

      const feedback = document.querySelector("[data-copy-feedback]");
      try {
        await navigator.clipboard.writeText(window.location.href);
        if (feedback) feedback.textContent = "Odkaz skopírovaný";
      } catch {
        if (feedback) feedback.textContent = "Odkaz sa nepodarilo skopírovať";
      }
      window.setTimeout(() => { if (feedback) feedback.textContent = ""; }, 2200);
    });
  });
});
