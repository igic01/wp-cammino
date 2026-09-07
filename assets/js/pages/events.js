document.addEventListener("DOMContentLoaded", () => {
  const filterButtons = [...document.querySelectorAll("[data-event-filter]")];
  const eventCards = [...document.querySelectorAll("[data-event-card]")];
  const visibleCount = document.querySelector("[data-visible-count]");
  const countLabel = document.querySelector("[data-count-label]");
  const emptyState = document.querySelector("[data-empty-events]");
  const showAllButton = document.querySelector("[data-show-all]");

  const slovakCountLabel = (count) => {
    if (count === 1) return "podujatie";
    if (count >= 2 && count <= 4) return "podujatia";
    return "podujatí";
  };

  const applyFilter = (type) => {
    let count = 0;

    eventCards.forEach((card) => {
      const isVisible = type === "all" || card.dataset.eventType === type;
      card.classList.toggle("is-filtered-out", !isVisible);
      card.setAttribute("aria-hidden", String(!isVisible));
      if (isVisible) count += 1;
    });

    filterButtons.forEach((button) => {
      const isActive = button.dataset.eventFilter === type;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    if (visibleCount) visibleCount.textContent = String(count);
    if (countLabel) countLabel.textContent = slovakCountLabel(count);
    if (emptyState) emptyState.hidden = count !== 0;
  };

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => applyFilter(button.dataset.eventFilter || "all"));
  });

  showAllButton?.addEventListener("click", () => applyFilter("all"));
});
