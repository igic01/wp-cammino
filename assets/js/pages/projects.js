document.addEventListener("DOMContentLoaded", () => {
  const filterButtons = [...document.querySelectorAll("[data-project-filter]")];
  const projectCards = [...document.querySelectorAll("[data-project-card]")];
  const visibleCount = document.querySelector("[data-project-visible-count]");
  const countLabel = document.querySelector("[data-project-count-label]");
  const emptyState = document.querySelector("[data-empty-projects]");
  const prompt = document.querySelector("[data-project-prompt]");
  const resultsHeading = document.querySelector("[data-project-results-heading]");
  const selectedName = document.querySelector("[data-project-selected-name]");
  const projectGrid = document.querySelector("[data-project-grid]");

  const slovakCountLabel = (count) => {
    if (count === 1) return "projekt";
    if (count >= 2 && count <= 4) return "projekty";
    return "projektov";
  };

  const applyFilter = (category) => {
    let count = 0;

    projectCards.forEach((card) => {
      const categories = (card.dataset.projectCategories || "").split(/\s+/).filter(Boolean);
      const isVisible = categories.includes(category);
      card.classList.toggle("is-filtered-out", !isVisible);
      card.setAttribute("aria-hidden", String(!isVisible));
      if (isVisible) count += 1;
    });

    filterButtons.forEach((button) => {
      const isActive = button.dataset.projectFilter === category;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    const activeButton = filterButtons.find((button) => button.dataset.projectFilter === category);
    if (selectedName) selectedName.textContent = activeButton?.dataset.projectCategoryName || "";
    if (visibleCount) visibleCount.textContent = String(count);
    if (countLabel) countLabel.textContent = slovakCountLabel(count);
    if (emptyState) emptyState.hidden = count !== 0;
    if (prompt) prompt.hidden = true;
    if (resultsHeading) resultsHeading.hidden = false;
    if (projectGrid) projectGrid.hidden = count === 0;
  };

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => applyFilter(button.dataset.projectFilter || ""));
  });
});
