// Main JavaScript functionality (sans Bootstrap)

document.addEventListener("DOMContentLoaded", () => {
  const lucide = window.lucide
  if (lucide) {
    lucide.createIcons()
  }

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

})

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
  if (typeof window.appNotify === "function") {
    window.appNotify(message, type)
    return
  }

  let toastContainer = document.querySelector(".app-toast-container")
  if (!toastContainer) {
    toastContainer = document.createElement("div")
    toastContainer.className =
      "app-toast-container fixed bottom-4 right-4 z-[1080] flex flex-col gap-2 max-w-sm w-full pointer-events-none"
    document.body.appendChild(toastContainer)
  }

  const toastId = "toast-" + Date.now()
  const headerBg =
    type === "success"
      ? "bg-emerald-600"
      : type === "error"
        ? "bg-red-600"
        : "bg-slate-700"
  const title =
    type === "success" ? "Succès" : type === "error" ? "Erreur" : "Information"
  const icon =
    type === "success" ? "check-circle" : type === "error" ? "alert-circle" : "info"

  const wrap = document.createElement("div")
  wrap.id = toastId
  wrap.className = "pointer-events-auto rounded-lg border border-slate-200 bg-white shadow-lg overflow-hidden text-sm"
  wrap.setAttribute("role", "alert")
  wrap.innerHTML = `
        <div class="flex items-center gap-2 px-3 py-2 text-white ${headerBg}"> 
            <i data-lucide="${icon}" width="16" height="16" class="shrink-0" aria-hidden="true"></i>
            <strong class="font-semibold">${title}</strong>
            <button type="button" class="ml-auto inline-flex h-7 w-7 items-center justify-center rounded text-white/90 hover:bg-white/10" aria-label="Fermer">
              <i data-lucide="x" width="14" height="14"></i>
            </button>
        </div>
        <div class="px-3 py-2 text-slate-800">${message}</div>
    `

  toastContainer.appendChild(wrap)

  if (window.lucide) {
    window.lucide.createIcons()
  }

  const destroy = () => {
    wrap.remove()
  }

  wrap.querySelector("button")?.addEventListener("click", destroy)
  setTimeout(destroy, 4000)
}

function showConfirmationModal(options = {}) {
  return new Promise((resolve) => {
    let modal = document.getElementById("confirmationModal")
    if (!modal) {
      modal = document.createElement("div")
      modal.id = "confirmationModal"
      modal.className = "modal"
      modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px; margin: 10% auto; padding: 0; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); z-index: 10001; overflow: hidden;">
          <div class="modal-header" style="background: ${options.type === "danger" ? "var(--danger-color, #dc2626)" : options.type === "warning" ? "var(--warning-color, #d97706)" : "var(--primary-color, #2563eb)"}; color: #ffffff; padding: 1.5rem; border-top-left-radius: 12px; border-top-right-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #ffffff;">${options.title || "Confirmation"}</h2>
            <span class="close" style="color: #ffffff; font-size: 2rem; font-weight: bold; cursor: pointer; opacity: 0.9; transition: opacity 0.2s;">&times;</span>
          </div>
          <div class="modal-body" style="padding: 1.5rem; background: white; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <p style="margin: 0 0 1.5rem 0; color: var(--dark-color, #1e293b); font-size: 1rem; line-height: 1.6;">${options.message || "Êtes-vous sûr de vouloir continuer ?"}</p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
              <button type="button" id="confirmationCancel" style="min-width: 100px; padding: 0.75rem 1.5rem; border-radius: 8px; border: 2px solid #e2e8f0; background: white; color: var(--dark-color); cursor: pointer; font-weight: 500; transition: all 0.2s;">Annuler</button>
              <button type="button" id="confirmationConfirm" style="min-width: 100px; padding: 0.75rem 1.5rem; border-radius: 8px; border: none; background: ${options.type === "danger" ? "var(--danger-color, #dc2626)" : options.type === "warning" ? "var(--warning-color, #d97706)" : "var(--primary-color, #2563eb)"}; color: white; cursor: pointer; font-weight: 500; transition: all 0.2s;">${options.confirmText || "Confirmer"}</button>
            </div>
          </div>
        </div>
      `
      document.body.appendChild(modal)
    } else {
      const header = modal.querySelector(".modal-header")
      const body = modal.querySelector(".modal-body")
      const headerBg =
        options.type === "danger"
          ? "var(--danger-color, #dc2626)"
          : options.type === "warning"
            ? "var(--warning-color, #d97706)"
            : "var(--primary-color, #2563eb)"
      header.style.background = headerBg
      header.style.color = "#ffffff"
      const closeBtn = header.querySelector(".close")
      if (closeBtn) {
        closeBtn.style.color = "#ffffff"
      }
      const titleEl = header.querySelector("h2")
      if (titleEl) {
        titleEl.style.color = "#ffffff"
        titleEl.textContent = options.title || "Confirmation"
      }
      body.querySelector("p").textContent =
        options.message || "Êtes-vous sûr de vouloir continuer ?"
      const confirmBtn = body.querySelector("#confirmationConfirm")
      confirmBtn.textContent = options.confirmText || "Confirmer"
      confirmBtn.style.background =
        options.type === "danger"
          ? "var(--danger-color, #dc2626)"
          : options.type === "warning"
            ? "var(--warning-color, #d97706)"
            : "var(--primary-color, #2563eb)"
    }

    modal.style.display = "block"

    const cancelBtn = modal.querySelector("#confirmationCancel")
    const confirmBtn = modal.querySelector("#confirmationConfirm")
    const closeBtn = modal.querySelector(".close")

    const cleanup = () => {
      modal.style.display = "none"
      cancelBtn.removeEventListener("click", onCancel)
      confirmBtn.removeEventListener("click", onConfirm)
      closeBtn.removeEventListener("click", onCancel)
      document.removeEventListener("click", onOutsideClick)
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

    cancelBtn.addEventListener("click", onCancel)
    confirmBtn.addEventListener("click", onConfirm)
    closeBtn.addEventListener("click", onCancel)
    document.addEventListener("click", onOutsideClick)
  })
}

async function fetchExportBranding() {
  if (window.OqExportBranding?.load) {
    const branding = await window.OqExportBranding.load()
    return branding?.raw ?? branding ?? {}
  }
  return {}
}

async function trackExport(tool, format, metadata = {}) {
  try {
    const brandingPayload = await fetchExportBranding()
    const mergedMetadata = {
      ...brandingPayload,
      ...metadata,
      url: window.location.pathname,
    }
    fetch("/analytics/track-export", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        tool,
        format,
        metadata: mergedMetadata,
      }),
    }).catch(() => {})
  } catch (error) {
    // Ignorer
  }
}

window.scrollToSection = scrollToSection
window.showToast = showToast
window.showConfirmationModal = showConfirmationModal
window.trackExport = trackExport
