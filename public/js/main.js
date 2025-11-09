// Main JavaScript functionality
let bootstrapWaitPromise = null

async function waitForBootstrap(timeout = 3000) {
  if (window.bootstrap?.Modal && window.bootstrap?.Toast) {
    return window.bootstrap
  }

  if (window.bootstrapReady instanceof Promise && window.bootstrapReady !== bootstrapWaitPromise) {
    try {
      const lib = await window.bootstrapReady
      if (lib?.Modal && lib?.Toast) {
        return lib
      }
    } catch (error) {
      console.warn("Erreur lors de l'initialisation Bootstrap fournie par l'import map :", error)
    }
  }

  if (bootstrapWaitPromise) {
    return bootstrapWaitPromise
  }

  bootstrapWaitPromise = new Promise((resolve) => {
    const start = Date.now()
    const checkInterval = setInterval(() => {
      if (window.bootstrap?.Modal && window.bootstrap?.Toast) {
        clearInterval(checkInterval)
        resolve(window.bootstrap)
      } else if (Date.now() - start > timeout) {
        clearInterval(checkInterval)
        resolve(window.bootstrap || null)
      }
    }, 40)
  }).finally(() => {
    bootstrapWaitPromise = null
  })

  return bootstrapWaitPromise
}

window.bootstrapReady = waitForBootstrap

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
    'form[id*="newsletterForm"], form:has(input[type="email"][placeholder*="Votre adresse email"])',
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

async function showToast(message, type = "info") {
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
  const bootstrap = await waitForBootstrap()
  if (bootstrap?.Toast) {
    const toast = new bootstrap.Toast(toastElement)
    toast.show()
  } else {
    toastElement.classList.add("show")
    toastElement.style.opacity = "1"
    setTimeout(() => {
      toastElement.classList.remove("show")
      toastElement.remove()
    }, 4000)
  }

  // Initialize icons in the new toast
  if (window.lucide) {
    window.lucide.createIcons()
  }

  // Remove toast element after it's hidden
  const destroy = () => {
    toastElement.remove()
  }
  toastElement.addEventListener("hidden.bs.toast", destroy)
  toastElement.querySelector(".btn-close")?.addEventListener("click", destroy)
}

// Modal de confirmation pour les suppressions et réinitialisations
function showConfirmationModal(options = {}) {
  return new Promise((resolve) => {
    // Créer le modal si il n'existe pas
    let modal = document.getElementById('confirmationModal')
    if (!modal) {
      modal = document.createElement('div')
      modal.id = 'confirmationModal'
      modal.className = 'modal'
      modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px; margin: 10% auto; padding: 0; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); z-index: 10001; overflow: hidden;">
          <div class="modal-header" style="background: ${options.type === 'danger' ? 'var(--danger-color, #dc2626)' : options.type === 'warning' ? 'var(--warning-color, #d97706)' : 'var(--primary-color, #2563eb)'}; color: #ffffff; padding: 1.5rem; border-top-left-radius: 12px; border-top-right-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #ffffff;">${options.title || 'Confirmation'}</h2>
            <span class="close" style="color: #ffffff; font-size: 2rem; font-weight: bold; cursor: pointer; opacity: 0.9; transition: opacity 0.2s;">&times;</span>
          </div>
          <div class="modal-body" style="padding: 1.5rem; background: white; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <p style="margin: 0 0 1.5rem 0; color: var(--dark-color, #1e293b); font-size: 1rem; line-height: 1.6;">${options.message || 'Êtes-vous sûr de vouloir continuer ?'}</p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
              <button class="btn btn-secondary" id="confirmationCancel" style="min-width: 100px; padding: 0.75rem 1.5rem; border-radius: 8px; border: 2px solid #e2e8f0; background: white; color: var(--dark-color); cursor: pointer; font-weight: 500; transition: all 0.2s;">Annuler</button>
              <button class="btn btn-primary" id="confirmationConfirm" style="min-width: 100px; padding: 0.75rem 1.5rem; border-radius: 8px; border: none; background: ${options.type === 'danger' ? 'var(--danger-color, #dc2626)' : options.type === 'warning' ? 'var(--warning-color, #d97706)' : 'var(--primary-color, #2563eb)'}; color: white; cursor: pointer; font-weight: 500; transition: all 0.2s;">${options.confirmText || 'Confirmer'}</button>
            </div>
          </div>
        </div>
      `
      document.body.appendChild(modal)
    } else {
      // Mettre à jour le contenu du modal existant
      const header = modal.querySelector('.modal-header')
      const body = modal.querySelector('.modal-body')
      const headerBg = options.type === 'danger' ? 'var(--danger-color, #dc2626)' : options.type === 'warning' ? 'var(--warning-color, #d97706)' : 'var(--primary-color, #2563eb)'
      header.style.background = headerBg
      header.style.color = '#ffffff'
      const closeBtn = header.querySelector('.close')
      if (closeBtn) {
        closeBtn.style.color = '#ffffff'
      }
      const titleEl = header.querySelector('h2')
      if (titleEl) {
        titleEl.style.color = '#ffffff'
        titleEl.textContent = options.title || 'Confirmation'
      }
      body.querySelector('p').textContent = options.message || 'Êtes-vous sûr de vouloir continuer ?'
      const confirmBtn = body.querySelector('#confirmationConfirm')
      confirmBtn.textContent = options.confirmText || 'Confirmer'
      confirmBtn.style.background = options.type === 'danger' ? 'var(--danger-color, #dc2626)' : options.type === 'warning' ? 'var(--warning-color, #d97706)' : 'var(--primary-color, #2563eb)'
    }

    // Afficher le modal
    modal.style.display = 'block'

    // Gérer les clics
    const cancelBtn = modal.querySelector('#confirmationCancel')
    const confirmBtn = modal.querySelector('#confirmationConfirm')
    const closeBtn = modal.querySelector('.close')

    const cleanup = () => {
      modal.style.display = 'none'
      cancelBtn.removeEventListener('click', onCancel)
      confirmBtn.removeEventListener('click', onConfirm)
      closeBtn.removeEventListener('click', onCancel)
      document.removeEventListener('click', onOutsideClick)
    }

    const onCancel = () => {
      cleanup()
      resolve(false)
    }

    const onConfirm = () => {
      cleanup()
      resolve(true)
    }

    const onOutsideClick = (e) => {
      if (e.target === modal) {
        onCancel()
      }
    }

    cancelBtn.addEventListener('click', onCancel)
    confirmBtn.addEventListener('click', onConfirm)
    closeBtn.addEventListener('click', onCancel)
    document.addEventListener('click', onOutsideClick)
  })
}

function trackExport(tool, format, metadata = {}) {
  try {
    fetch('/analytics/track-export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        tool,
        format,
        metadata: {
          ...metadata,
          url: window.location.pathname,
        },
      }),
    }).catch(() => {
      // Ignorer silencieusement les erreurs réseau pour ne pas gêner l'utilisateur
    });
  } catch (error) {
    // Pas de propagation : l'export doit rester fonctionnel même si le tracking échoue
  }
}

// Export functions for use in other modules
window.scrollToSection = scrollToSection
window.showToast = showToast
window.showConfirmationModal = showConfirmationModal
window.trackExport = trackExport
