// Main JavaScript functionality
document.addEventListener("DOMContentLoaded", () => {
  // Initialize Lucide icons
  const lucide = window.lucide // Declare lucide variable
  if (lucide) {
    lucide.createIcons()
  }

  // Navbar scroll effect
  const navbar = document.getElementById("mainNav")
  if (navbar) {
    window.addEventListener("scroll", () => {
      if (window.scrollY > 50) {
        navbar.classList.add("scrolled")
      } else {
        navbar.classList.remove("scrolled")
      }
    })
  }

  // Initialize AOS if available
  const AOS = window.AOS // Declare AOS variable
  if (AOS) {
    AOS.init({
      duration: 800,
      easing: "ease-in-out",
      once: true,
      offset: 100,
    })
  }

  // Newsletter form handling avec Logic Apps
  const newsletterForms = document.querySelectorAll(
    'form[id*="newsletterForm"], form:has(input[type="email"][placeholder*="email"])',
  )

  newsletterForms.forEach((form) => {
    form.addEventListener("submit", async function (e) {
      e.preventDefault()
      const emailInput = this.querySelector('input[type="email"]')
      const email = emailInput.value

      if (email) {
        const submitBtn = this.querySelector('button[type="submit"]')
        const originalText = submitBtn.innerHTML

        // Show loading state
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Inscription...'
        submitBtn.disabled = true

        try {
          // REMPLACEZ PAR VOTRE URL LOGIC APPS NEWSLETTER
          const response = await fetch(
            "https://prod-01.northeurope.logic.azure.com:443/workflows/91bdaba3dc654ab8b099b2ca37a3d995/triggers/When_a_HTTP_request_is_received/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2FWhen_a_HTTP_request_is_received%2Frun&sv=1.0&sig=EwK5bu7btKHK9xz_Iz8dDUCEzZ7KGpXH40fgnasP6Zg",
            {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                email: email,
                source: "newsletter-form",
                timestamp: new Date().toISOString(),
                preferences: {
                  articles: true,
                  tools: true,
                  updates: true,
                },
              }),
            },
          )

          if (response.ok) {
            showToast("Merci ! Vous êtes maintenant abonné à notre newsletter.", "success")
            this.reset()
          } else {
            throw new Error("Erreur réseau")
          }
        } catch (error) {
          console.error("Newsletter error:", error)
          showToast("Erreur lors de l'inscription. Veuillez réessayer.", "error")
        } finally {
          // Reset button
          submitBtn.innerHTML = originalText
          submitBtn.disabled = false
        }
      }
    })
  })
})

// Utility functions
function scrollToSection(sectionId) {
  const section = document.getElementById(sectionId)
  if (section) {
    section.scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }
}

function showToast(message, type = "info") {
  // Create toast element if it doesn't exist
  let toastContainer = document.querySelector(".toast-container")
  if (!toastContainer) {
    toastContainer = document.createElement("div")
    toastContainer.className = "toast-container position-fixed bottom-0 end-0 p-3"
    document.body.appendChild(toastContainer)
  }

  const toastId = "toast-" + Date.now()
  const toastHtml = `
        <div id="${toastId}" class="toast" role="alert">
            <div class="toast-header bg-${type === "success" ? "success" : type === "error" ? "danger" : "primary"} text-white">
                <i data-lucide="${type === "success" ? "check-circle" : type === "error" ? "alert-circle" : "info"}" width="16" height="16" class="me-2"></i>
                <strong class="me-auto">${type === "success" ? "Succès" : type === "error" ? "Erreur" : "Information"}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `

  toastContainer.insertAdjacentHTML("beforeend", toastHtml)

  const toastElement = document.getElementById(toastId)
  const bootstrap = window.bootstrap // Declare bootstrap variable
  if (bootstrap) {
    const toast = new bootstrap.Toast(toastElement)
    toast.show()
  }

  // Initialize icons in the new toast
  if (window.lucide) {
    window.lucide.createIcons()
  }

  // Remove toast element after it's hidden
  toastElement.addEventListener("hidden.bs.toast", function () {
    this.remove()
  })
}

// Export functions for use in other modules
window.scrollToSection = scrollToSection
window.showToast = showToast
