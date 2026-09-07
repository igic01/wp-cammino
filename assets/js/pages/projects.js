document.addEventListener("DOMContentLoaded", () => {
  const filterButtons = [...document.querySelectorAll("[data-project-filter]")];
  const projectCards = [...document.querySelectorAll("[data-project-card]")];
  const visibleCount = document.querySelector("[data-project-visible-count]");
  const countLabel = document.querySelector("[data-project-count-label]");
  const emptyState = document.querySelector("[data-empty-projects]");
  const showAllButton = document.querySelector("[data-project-show-all]");

  const slovakCountLabel = (count) => {
    if (count === 1) return "projekt";
    if (count >= 2 && count <= 4) return "projekty";
    return "projektov";
  };

  const applyFilter = (category) => {
    let count = 0;

    projectCards.forEach((card) => {
      const categories = (card.dataset.projectCategories || "").split(/\s+/).filter(Boolean);
      const isVisible = category === "all" || categories.includes(category);
      card.classList.toggle("is-filtered-out", !isVisible);
      card.setAttribute("aria-hidden", String(!isVisible));
      if (isVisible) count += 1;
    });

    filterButtons.forEach((button) => {
      const isActive = button.dataset.projectFilter === category;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    if (visibleCount) visibleCount.textContent = String(count);
    if (countLabel) countLabel.textContent = slovakCountLabel(count);
    if (emptyState) emptyState.hidden = count !== 0;
  };

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => applyFilter(button.dataset.projectFilter || "all"));
  });

  showAllButton?.addEventListener("click", () => applyFilter("all"));
});
