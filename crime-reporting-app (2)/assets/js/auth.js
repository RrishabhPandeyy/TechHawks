document.addEventListener("DOMContentLoaded", () => {
  // Auth tabs functionality
  const tabItems = document.querySelectorAll(".auth-tabs .tab-item")

  tabItems.forEach((tab) => {
    tab.addEventListener("click", function () {
      const tabId = this.getAttribute("data-tab")

      // Remove active class from all tabs
      document.querySelectorAll(".auth-tabs .tab-item").forEach((item) => {
        item.classList.remove("active")
      })

      // Add active class to clicked tab
      this.classList.add("active")

      // Hide all tab panes
      document.querySelectorAll(".auth-tabs .tab-pane").forEach((pane) => {
        pane.classList.remove("active")
      })

      // Show the corresponding tab pane
      document.getElementById(tabId).classList.add("active")
    })
  })
})

