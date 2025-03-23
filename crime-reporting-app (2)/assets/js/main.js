document.addEventListener("DOMContentLoaded", () => {
  // Mobile menu toggle
  const mobileMenuToggle = document.getElementById("mobileMenuToggle")
  const mainNav = document.getElementById("mainNav")

  if (mobileMenuToggle && mainNav) {
    mobileMenuToggle.addEventListener("click", () => {
      mainNav.classList.toggle("show")
    })
  }

  // Tabs functionality
  const tabItems = document.querySelectorAll(".tab-item")

  tabItems.forEach((tab) => {
    tab.addEventListener("click", function () {
      const tabId = this.getAttribute("data-tab")
      const tabPane = document.getElementById(tabId)

      if (!tabPane) return

      // Remove active class from all tabs and panes
      this.parentElement.querySelectorAll(".tab-item").forEach((item) => {
        item.classList.remove("active")
      })

      document.querySelectorAll(".tab-pane").forEach((pane) => {
        pane.classList.remove("active")
      })

      // Add active class to clicked tab and corresponding pane
      this.classList.add("active")
      tabPane.classList.add("active")
    })
  })

  // Modal functionality
  const modalTriggers = document.querySelectorAll('[data-toggle="modal"]')
  const modalCloseButtons = document.querySelectorAll('[data-dismiss="modal"]')

  modalTriggers.forEach((trigger) => {
    trigger.addEventListener("click", function () {
      const modalId = this.getAttribute("data-target")
      const modal = document.querySelector(modalId)

      if (modal) {
        modal.classList.add("show")
      }
    })
  })

  modalCloseButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const modal = this.closest(".modal")

      if (modal) {
        modal.classList.remove("show")
      }
    })
  })

  // Close modal when clicking on overlay
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", function () {
      this.closest(".modal").classList.remove("show")
    })
  })

  // Auto-hide flash messages after 5 seconds
  const flashMessages = document.querySelectorAll(".flash-message")

  if (flashMessages.length > 0) {
    setTimeout(() => {
      flashMessages.forEach((message) => {
        message.style.opacity = "0"
        setTimeout(() => {
          message.style.display = "none"
        }, 300)
      })
    }, 5000)
  }
})

