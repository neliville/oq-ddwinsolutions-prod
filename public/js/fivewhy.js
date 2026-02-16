// Données de l'analyse des 5 Pourquoi
const fiveWhyData = {
  problemStatement: "",
  whySteps: [],
  rootCause: "",
  rootCauseReady: false,
  planAction: "",
}

window.fiveWhyData = fiveWhyData

const MAX_WHY_STEPS = 7
const MIN_ROOT_CAUSE_STEPS = 4

// Note: L'ajout automatique a été désactivé - l'utilisateur doit cliquer sur le bouton

function trackExport(tool, format, metadata = {}) {
  if (typeof window.trackExport === "function") {
    window.trackExport(tool, format, metadata)
  }
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
  initializeFiveWhy()

  // Initialiser AOS si disponible
  const AOS = window.AOS // Declare AOS variable
  if (typeof AOS !== "undefined") {
    AOS.init({
      duration: 800,
      easing: "ease-in-out",
      once: true,
    })
  }
})

function initializeFiveWhy() {
  // Commencer avec un premier "Pourquoi"
  if (fiveWhyData.whySteps.length === 0) {
    fiveWhyData.whySteps.push({ question: "", answer: "" })
  }
  renderWhyChain()
}

// Fonction pour afficher les notifications
// Variable pour stocker la référence de la notification actuelle
let currentToast = null

function showNotification(message, type = "success") {
  const Toastify = window.Toastify // Declare Toastify variable
  
  // Fermer la notification précédente si elle existe
  if (currentToast) {
    currentToast.hideToast()
    currentToast = null
  }
  
  if (typeof Toastify !== "undefined") {
    currentToast = Toastify({
      text: message,
      duration: 3000,
      gravity: "top",
      position: "right",
      backgroundColor: type === "success" ? "#2ecc71" : type === "error" ? "#e74c3c" : "#3498db",
      stopOnFocus: true,
      callback: function() {
        currentToast = null
      }
    })
    currentToast.showToast()
  } else {
    alert(message)
  }
}

function updateProblemStatement() {
  const problemInput = document.getElementById("problemStatement")
  fiveWhyData.problemStatement = problemInput.value.trim()

  if (fiveWhyData.problemStatement) {
    // Mettre à jour la première question automatiquement
    if (fiveWhyData.whySteps.length > 0) {
      fiveWhyData.whySteps[0].question = `Pourquoi ${fiveWhyData.problemStatement.toLowerCase()} ?`
      renderWhyChain()
    }
    showNotification("Problème défini avec succès")
  }
}

function renderWhyChain() {
  const whyChain = document.getElementById("whyChain")
  whyChain.innerHTML = ""

  const limitReached = fiveWhyData.whySteps.length >= MAX_WHY_STEPS

  updateClarityScoreDisplay()
  fiveWhyData.whySteps.forEach((step, index) => {
    const stepDiv = document.createElement("div")
    stepDiv.className = "why-step"
    
    // Générer le HTML avec le modal intégré pour chaque étape
    const stepHtml = `
            <div class="why-number">
                <span>${index + 1}</span>
            </div>
            <div class="why-content">
                <div class="why-question">
                    <label for="question-${index}">Question ${index + 1} :</label>
                    <input 
                        type="text" 
                        id="question-${index}" 
                        value="${step.question.replace(/"/g, '&quot;')}" 
                        placeholder="Pourquoi...?"
                        onchange="updateWhyStep(${index}, 'question', this.value)"
                    >
                </div>
                <div class="why-answer">
                    <label for="answer-${index}">Réponse ${index + 1} :</label>
                    <textarea 
                        id="answer-${index}" 
                        placeholder="Parce que..."
                        onchange="updateWhyStep(${index}, 'answer', this.value)"
                        oninput="updateWhyStep(${index}, 'answer', this.value); checkForNextStep(${index})"
                    >${step.answer.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                </div>
                <div class="why-actions">
                    <button type="button" class="btn ${limitReached ? 'btn-secondary' : 'btn-primary'} why-add-btn" ${limitReached ? 'disabled' : ''} onclick="addWhyStepFrom(${index})">
                        <i class="fas fa-plus-circle me-2"></i> Ajouter un "Pourquoi"
                    </button>
                </div>
                ${
                  index > 0
                    ? `
                    <button class="remove-step-btn" type="button" onclick="removeWhyStep(${index})" aria-label="Supprimer cette étape">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                    </button>
                  `
                    : ''
                }
            </div>
        `
    
    stepDiv.innerHTML = stepHtml
    whyChain.appendChild(stepDiv)
  })

  // Réinitialiser les icônes Lucide après le rendu
  if (typeof lucide !== 'undefined') {
    lucide.createIcons()
  }
  
  renderRootCauseTrigger()
  updateRootCauseDisplay()
 
  // Mettre à jour la visibilité du bouton d'ajout après le rendu
  setTimeout(() => {
    updateAddButtonVisibility()
  }, 50)
}

function updateWhyStep(index, field, value) {
  if (fiveWhyData.whySteps[index]) {
    fiveWhyData.whySteps[index][field] = value.trim()
    renderRootCauseTrigger()
    updateRootCauseDisplay()
  }
}

function checkForNextStep(index) {
  // Récupérer la valeur actuelle du textarea pour être sûr d'avoir la dernière valeur
  const answerElement = document.getElementById(`answer-${index}`)
  const currentAnswer = answerElement ? answerElement.value.trim() : ''
  
  // Mettre à jour les données avec la valeur actuelle
  if (fiveWhyData.whySteps[index]) {
    fiveWhyData.whySteps[index].answer = currentAnswer
  }
  
  // Mettre à jour la visibilité du bouton d'ajout pour indiquer qu'une nouvelle étape peut être ajoutée
  updateAddButtonVisibility()
  renderRootCauseTrigger()
 
  // Ne plus ajouter automatiquement - l'utilisateur doit cliquer sur le bouton
}

// Fonction pour mettre à jour la visibilité et le style du bouton d'ajout
function updateAddButtonVisibility() {
  const addButton = document.getElementById('addWhyStepButton')
  const canAddMore = fiveWhyData.whySteps.length < MAX_WHY_STEPS

  if (addButton) {
    addButton.disabled = !canAddMore
    addButton.classList.remove('btn-secondary', 'pulse-animation')
    addButton.classList.add('btn-primary')
    addButton.innerHTML = canAddMore
      ? '<i class="fas fa-plus-circle me-2"></i> Ajouter un "Pourquoi"'
      : `<i class="fas fa-ban me-2"></i> Limite atteinte (${MAX_WHY_STEPS} étapes max)`
  }

  const stepButtons = document.querySelectorAll('.why-add-btn')
  stepButtons.forEach(button => {
    if (canAddMore) {
      button.disabled = false
      button.classList.remove('btn-secondary')
      button.classList.add('btn-primary')
    } else {
      button.disabled = true
      button.classList.remove('btn-primary')
      button.classList.add('btn-secondary')
    }
  })
}

function addWhyStep() {
  if (fiveWhyData.whySteps.length >= MAX_WHY_STEPS) {
    showNotification(`Limite de ${MAX_WHY_STEPS} étapes atteinte`, "error")
    return
  }

  const previousStep = fiveWhyData.whySteps[fiveWhyData.whySteps.length - 1]
  let newQuestion = "Pourquoi ?"

  // Générer automatiquement la question basée sur la réponse précédente
  if (previousStep && previousStep.answer.trim()) {
    newQuestion = `Pourquoi ${previousStep.answer.trim().toLowerCase()} ?`
  }

  fiveWhyData.whySteps.push({
    question: newQuestion,
    answer: "",
  })

  renderWhyChain()
  showNotification("Nouvelle étape ajoutée")
  
  // Mettre à jour la visibilité du bouton d'ajout
  updateAddButtonVisibility()

  // Focus sur la nouvelle réponse
  setTimeout(() => {
    const newAnswerField = document.getElementById(`answer-${fiveWhyData.whySteps.length - 1}`)
    if (newAnswerField) {
      newAnswerField.focus()
    }
  }, 100)
}

function addWhyStepFrom(index) {
  if (fiveWhyData.whySteps.length >= MAX_WHY_STEPS) {
    showNotification(`Limite de ${MAX_WHY_STEPS} étapes atteinte`, "error")
    return
  }

  const currentStep = fiveWhyData.whySteps[index]
  let newQuestion = "Pourquoi ?"

  if (currentStep && currentStep.answer.trim()) {
    newQuestion = `Pourquoi ${currentStep.answer.trim().toLowerCase()} ?`
  }

  fiveWhyData.whySteps.splice(index + 1, 0, {
    question: newQuestion,
    answer: "",
  })

  renderWhyChain()
  showNotification("Nouvelle étape ajoutée")

  setTimeout(() => {
    const newAnswerField = document.getElementById(`answer-${index + 1}`)
    if (newAnswerField) {
      newAnswerField.focus()
    }
  }, 100)
}

async function removeWhyStep(index) {
  if (index === 0) {
    showNotification("Impossible de supprimer la première étape", "error")
    return
  }

  const confirmed = await showConfirmationModalBootstrap(
    "Confirmation de suppression",
    "Êtes-vous sûr de vouloir supprimer cette étape ?",
    "Supprimer",
    "Annuler",
    "btn-danger"
  );

  if (!confirmed) {
    return
  }

  confirmRemoveWhyStep(index)
}

// Fonction pour confirmer la suppression d'une étape (appelée par le modal)
function confirmRemoveWhyStep(stepIndex) {
  if (stepIndex === 0) {
    showNotification("Impossible de supprimer la première étape", "error")
    return
  }

  fiveWhyData.whySteps.splice(stepIndex, 1)
  renderWhyChain()
  showNotification("Étape supprimée")
}

function renderRootCauseTrigger() {
  const trigger = document.getElementById("rootCauseTrigger")
  if (!trigger) return

  trigger.innerHTML = ""

  const stepsCount = fiveWhyData.whySteps.length

  if (stepsCount < MIN_ROOT_CAUSE_STEPS) {
        trigger.style.display = "none"
        trigger.setAttribute('data-visible', 'false')
      trigger.style.display = "none"
      trigger.setAttribute('data-visible', 'false')
    if (fiveWhyData.rootCauseReady) {
      fiveWhyData.rootCauseReady = false
      fiveWhyData.rootCause = ""
    }
    return
  }

    trigger.style.display = "flex"
    trigger.setAttribute('data-visible', 'true')

  const answeredCount = fiveWhyData.whySteps.filter((step) => step.answer.trim()).length
  const canGenerate = answeredCount >= MIN_ROOT_CAUSE_STEPS

  const button = document.createElement("button")
  button.type = "button"
  button.className = "btn root-cause-btn"
  button.disabled = !canGenerate
  button.innerHTML = `${fiveWhyData.rootCauseReady ? '<i class="fas fa-sync-alt me-2"></i> Mettre à jour la cause racine' : '<i class="fas fa-lightbulb me-2"></i> Générer la cause racine'}`
  button.addEventListener("click", generateRootCause)

  trigger.appendChild(button)

  const helper = document.createElement("p")
  helper.className = "root-cause-helper"
  helper.textContent = canGenerate
    ? (fiveWhyData.rootCauseReady
        ? "Mettez à jour vos réponses puis cliquez pour rafraîchir la cause racine."
        : "Cliquez pour générer la cause racine à partir des réponses saisies.")
    : `Complétez au moins ${MIN_ROOT_CAUSE_STEPS} réponses pour activer le bouton.`

  trigger.appendChild(helper)
  updateClarityScoreDisplay()
}

function updateClarityScoreDisplay() {
  const el = document.getElementById("fiveWhyClarityScore")
  if (!el) return
  const hasProblem = Boolean((fiveWhyData.problemStatement || "").trim())
  const answeredSteps = fiveWhyData.whySteps.filter((step) => (step.answer || "").trim())
  const completedCount = answeredSteps.length
  if (!hasProblem && completedCount === 0) {
    el.style.display = "none"
    el.textContent = ""
    return
  }
  const score = hasProblem ? Math.min(5, Math.max(0, completedCount)) : 0
  el.style.display = "block"
  el.textContent = "Score de clarté : " + score + "/5" + (score >= 4 ? " — Pistes principales identifiables." : score >= 2 ? " — Complétez encore quelques étapes pour affiner." : " — Définissez le problème et au moins une réponse.")
}

function generateRootCause() {
  const answeredSteps = fiveWhyData.whySteps.filter((step) => step.answer.trim())

  if (answeredSteps.length < MIN_ROOT_CAUSE_STEPS) {
    showNotification(`Veuillez renseigner au moins ${MIN_ROOT_CAUSE_STEPS} réponses complètes.`, "error")
    return
  }

  const latestAnswer = answeredSteps[answeredSteps.length - 1].answer.trim()

  fiveWhyData.rootCause = latestAnswer
  fiveWhyData.rootCauseReady = true
  updateRootCauseDisplay({ force: true, answerOverride: latestAnswer })
  renderRootCauseTrigger()
  updateClarityScoreDisplay()

  const rootCauseDiv = document.getElementById("rootCause")
  if (rootCauseDiv) {
    rootCauseDiv.scrollIntoView({ behavior: "smooth", block: "center" })
  }

  showNotification("Cause racine générée avec succès !")
}

function updateRootCauseDisplay({ force = false, answerOverride = null } = {}) {
  const rootCauseDiv = document.getElementById("rootCause")
  const rootCauseText = document.getElementById("rootCauseText")
  const planActionBlock = document.getElementById("planActionBlock")
  const planActionStatement = document.getElementById("planActionStatement")

  if (!rootCauseDiv || !rootCauseText) return

  const answeredSteps = fiveWhyData.whySteps.filter((step) => step.answer.trim())

  function showPlanActionBlock(show) {
    if (planActionBlock) planActionBlock.style.display = show ? "block" : "none"
  }

  if (force) {
    if (answeredSteps.length < MIN_ROOT_CAUSE_STEPS) {
      fiveWhyData.rootCauseReady = false
      fiveWhyData.rootCause = ""
      rootCauseDiv.style.display = "none"
      rootCauseText.textContent = "La cause racine sera affichée ici une fois l'analyse terminée."
      showPlanActionBlock(false)
      return
    }

    const latestAnswer = answerOverride ?? answeredSteps[answeredSteps.length - 1].answer.trim()
    fiveWhyData.rootCauseReady = true
    fiveWhyData.rootCause = latestAnswer
    rootCauseText.textContent = `La cause racine identifiée est : "${latestAnswer}"`
    rootCauseDiv.style.display = "block"
    showPlanActionBlock(true)
    if (planActionStatement && fiveWhyData.planAction) planActionStatement.value = fiveWhyData.planAction
    return
  }

  if (!fiveWhyData.rootCauseReady) {
    rootCauseDiv.style.display = "none"
    rootCauseText.textContent = "La cause racine sera affichée ici une fois l'analyse terminée."
    showPlanActionBlock(false)
    return
  }

  if (answeredSteps.length < MIN_ROOT_CAUSE_STEPS) {
    fiveWhyData.rootCauseReady = false
    fiveWhyData.rootCause = ""
    rootCauseDiv.style.display = "none"
    rootCauseText.textContent = "La cause racine sera affichée ici une fois l'analyse terminée."
    showPlanActionBlock(false)
    return
  }

  const lastAnswer = answerOverride ?? answeredSteps[answeredSteps.length - 1].answer.trim()
  fiveWhyData.rootCause = lastAnswer
  rootCauseText.textContent = `La cause racine identifiée est : "${lastAnswer}"`
  rootCauseDiv.style.display = "block"
  showPlanActionBlock(true)
  if (planActionStatement && fiveWhyData.planAction) planActionStatement.value = fiveWhyData.planAction
}

async function resetAnalysis() {
  const confirmed = await showConfirmationModalBootstrap(
    "Confirmation",
    "Êtes-vous sûr de vouloir recommencer l'analyse ? Toutes les données seront perdues.",
    "Oui, recommencer",
    "Annuler"
  );

  if (confirmed) {
    fiveWhyData.problemStatement = ""
    fiveWhyData.whySteps = [{ question: "", answer: "" }]
    fiveWhyData.rootCause = ""
    fiveWhyData.rootCauseReady = false
    fiveWhyData.planAction = ""

    document.getElementById("problemStatement").value = ""
    const planActionStatement = document.getElementById("planActionStatement")
    if (planActionStatement) planActionStatement.value = ""
    const planActionBlock = document.getElementById("planActionBlock")
    if (planActionBlock) planActionBlock.style.display = "none"
    renderWhyChain()
    updateRootCauseDisplay()
    updateClarityScoreDisplay()
    showNotification("Analyse réinitialisée")
  }
}

/**
 * Fonction utilitaire pour afficher un modal de confirmation Bootstrap
 * Remplace confirm() natif par un modal Bootstrap
 * Compatible avec la fonction globale de main.js
 */
function showConfirmationModalBootstrap(title, message, confirmText = 'Confirmer', cancelText = 'Annuler', confirmClass = 'btn-primary') {
  return new Promise((resolve) => {
    const modalElement = document.getElementById('globalConfirmationModal');
    if (!modalElement) {
      // Fallback vers la fonction de main.js si elle existe
      if (window.showConfirmationModal && typeof window.showConfirmationModal === 'function') {
        return window.showConfirmationModal({
          title,
          message,
          confirmText,
          cancelText,
          type: confirmClass === 'btn-danger' ? 'danger' : confirmClass === 'btn-warning' ? 'warning' : 'primary'
        });
      }
      // Dernier fallback vers confirm() natif
      const result = window.confirm(message);
      resolve(result);
      return;
    }

    // Mettre à jour le message et les textes
    const messageElement = modalElement.querySelector('[data-confirmation-modal-target="message"]');
    const titleElement = modalElement.querySelector('.modal-title');
    const confirmButton = modalElement.querySelector('button[data-action*="onConfirmed"]');
    const cancelButton = modalElement.querySelector('button.btn-secondary');

    if (messageElement) {
      messageElement.textContent = message;
    }
    if (titleElement) {
      const icon = titleElement.querySelector('i');
      titleElement.innerHTML = '';
      if (icon) {
        titleElement.appendChild(icon);
      }
      titleElement.appendChild(document.createTextNode(' ' + title));
    }
    if (confirmButton) {
      confirmButton.textContent = confirmText;
      confirmButton.className = `btn ${confirmClass}`;
    }
    if (cancelButton) {
      cancelButton.textContent = cancelText;
    }

    // Stocker la fonction resolve dans un identifiant unique
    const resolveId = 'confirmResolve_' + Date.now();
    window[resolveId] = resolve;
    modalElement.dataset.confirmPromiseResolve = resolveId;

    // Réinitialiser les icônes Lucide
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }

    // Ouvrir le modal
    const modalController = window.Stimulus?.getControllerForElementAndIdentifier?.(modalElement, 'bootstrap-modal');
    if (modalController && typeof modalController.show === 'function') {
      modalController.show();
    } else {
      // Fallback vers Bootstrap natif
      const bootstrapLib = window.bootstrap;
      if (bootstrapLib?.Modal) {
        const modalInstance = new bootstrapLib.Modal(modalElement);
        modalInstance.show();
      } else {
        // Dernier fallback vers confirm() natif
        const result = window.confirm(message);
        resolve(result);
      }
    }
  });
}

// Fonctions d'export
function buildFiveWhyExportData() {
  const container = document.getElementById("fiveWhyContainer")
  if (!container) {
    throw new Error("Impossible de trouver la zone d’analyse à exporter.")
  }

  const problem = (fiveWhyData.problemStatement || "").toString().trim()
  if (!problem) {
    throw new Error("Veuillez d'abord définir le problème.")
  }

  const steps = Array.isArray(fiveWhyData.whySteps) ? fiveWhyData.whySteps : []
  const sanitizedSteps = steps.map((step, index) => ({
    stepNumber: index + 1,
    question: (step?.question || "").toString().trim(),
    answer: (step?.answer || "").toString().trim(),
  }))

  const completedSteps = sanitizedSteps.filter((step) => step.answer.length).length
  const rootCause = (fiveWhyData.rootCause || "").toString().trim()
  const planActionEl = document.getElementById("planActionStatement")
  const planAction = (planActionEl ? planActionEl.value : fiveWhyData.planAction || "").toString().trim()
  if (planAction) fiveWhyData.planAction = planAction
  const exportDate = new Date()

  return {
    container,
    problem,
    sanitizedSteps,
    completedSteps,
    totalSteps: sanitizedSteps.length,
    rootCause,
    hasRootCause: Boolean(rootCause),
    planAction,
    exportDate,
    exportLocale: exportDate.toLocaleString("fr-FR"),
    titleText: problem.length ? `Analyse 5 Pourquoi – ${problem.slice(0, 60)}` : "Analyse 5 Pourquoi",
    descriptionText: "Méthode des 5 Pourquoi – identification de la cause racine d’un problème.",
  }
}

function buildFiveWhyCanvas(capturedCanvas, context) {
  const padding = 56
  const headerHeight = 108
  const footerHeight = 88
  const finalCanvas = document.createElement("canvas")
  finalCanvas.width = capturedCanvas.width + padding * 2
  finalCanvas.height = capturedCanvas.height + padding * 2 + headerHeight + footerHeight
  const ctx = finalCanvas.getContext("2d")

  ctx.fillStyle = "#ffffff"
  ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height)

  ctx.textAlign = "center"
  ctx.fillStyle = "#1f2937"
  ctx.font = "bold 30px Arial, sans-serif"
  ctx.fillText(context.titleText, finalCanvas.width / 2, headerHeight / 2 + 8)

  ctx.font = "16px Arial, sans-serif"
  ctx.fillStyle = "#475569"
  ctx.fillText(`Exporté le ${context.exportLocale}`, finalCanvas.width / 2, headerHeight - 24)

  ctx.font = "14px Arial, sans-serif"
  ctx.fillStyle = "#334155"
  ctx.fillText(context.descriptionText.substring(0, 150), finalCanvas.width / 2, headerHeight - 2)

  const contentOffsetY = headerHeight + padding
  ctx.drawImage(capturedCanvas, padding, contentOffsetY)

  ctx.save()
  ctx.translate(finalCanvas.width / 2, contentOffsetY + capturedCanvas.height / 2)
  ctx.rotate(-Math.PI / 6)
  ctx.font = "26px Arial, sans-serif"
  ctx.fillStyle = "rgba(148, 163, 184, 0.18)"
  ctx.fillText("OUTILS-QUALITÉ", 0, 0)
  ctx.restore()

  const summaryStart = contentOffsetY + capturedCanvas.height + padding
  ctx.textAlign = "center"
  ctx.fillStyle = "#1f2937"
  ctx.font = "15px Arial, sans-serif"
  ctx.fillText(
    `Étapes complétées : ${context.completedSteps}/${context.totalSteps} · Longueur du problème : ${context.problem.length} caractères`,
    finalCanvas.width / 2,
    summaryStart
  )

  ctx.fillStyle = "#475569"
  ctx.font = "14px Arial, sans-serif"
  const rootCauseLabel = context.hasRootCause
    ? `Cause racine : ${context.rootCause.slice(0, 80)}${context.rootCause.length > 80 ? "…" : ""}`
    : "Cause racine non identifiée"
  ctx.fillText(rootCauseLabel, finalCanvas.width / 2, summaryStart + 24)
  if (context.planAction) {
    ctx.font = "12px Arial, sans-serif"
    ctx.fillStyle = "#475569"
    const planLabel = `Plan d'action : ${context.planAction.slice(0, 100)}${context.planAction.length > 100 ? "…" : ""}`
    ctx.fillText(planLabel, finalCanvas.width / 2, summaryStart + 44)
  }

  ctx.fillStyle = "#94a3b8"
  ctx.font = "12px Arial, sans-serif"
  ctx.fillText("© OUTILS-QUALITÉ - www.outils-qualite.com", finalCanvas.width / 2, finalCanvas.height - footerHeight / 2)

  return finalCanvas
}

function exportFiveWhy(format) {
  let context
  try {
    context = buildFiveWhyExportData()
  } catch (error) {
    showNotification(error.message, "error")
    return
  }

  const baseMetadata = {
    steps: context.totalSteps,
    completedSteps: context.completedSteps,
    hasRootCause: context.hasRootCause,
  }

  if (format === "json") {
    const payload = {
      metadata: {
        tool: "Méthode des 5 Pourquoi",
        version: "1.1",
        exportDate: context.exportDate.toISOString(),
        exportLocale: context.exportLocale,
        source: "OUTILS-QUALITÉ",
        title: context.problem.slice(0, 120),
      },
      analysis: {
        problem: context.problem,
        totalSteps: context.totalSteps,
        completedSteps: context.completedSteps,
        rootCause: context.hasRootCause ? context.rootCause : null,
        planAction: context.planAction || null,
        steps: context.sanitizedSteps,
      },
      rawData: {
        problemStatement: fiveWhyData.problemStatement,
        whySteps: context.sanitizedSteps,
        rootCause: context.rootCause,
        rootCauseReady: fiveWhyData.rootCauseReady || context.hasRootCause,
        planAction: context.planAction || "",
      },
    }

    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json" })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = `5pourquoi-${Date.now()}.json`
    link.click()
    URL.revokeObjectURL(url)
    showNotification("Export JSON généré.", "success")
    trackExport("5pourquoi", "JSON", baseMetadata)
    return
  }

  const { jsPDF } = window.jspdf || {}
  if (format === "pdf" && !jsPDF) {
    showNotification("Erreur : bibliothèque PDF non chargée.", "error")
    return
  }

  const originalStyle = context.container.style.cssText
  context.container.style.cssText += `
    background: white !important;
    padding: 24px !important;
    box-shadow: none !important;
    border-radius: 0 !important;
  `

  let exportSuccess = false

  window
    .html2canvas(context.container, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: "#ffffff",
      width: context.container.scrollWidth,
      height: context.container.scrollHeight,
    })
    .then((canvas) => {
      try {
        const exportCanvas = buildFiveWhyCanvas(canvas, context)

        if (format === "pdf") {
          const pdf = new jsPDF("portrait")
          const pageWidth = pdf.internal.pageSize.getWidth()
          const pageHeight = pdf.internal.pageSize.getHeight()
          const imgData = exportCanvas.toDataURL("image/png", 0.95)
          const ratio = Math.min(pageWidth / exportCanvas.width, pageHeight / exportCanvas.height)
          const imgWidth = exportCanvas.width * ratio
          const imgHeight = exportCanvas.height * ratio
          const marginX = (pageWidth - imgWidth) / 2
          const marginY = (pageHeight - imgHeight) / 2
          pdf.addImage(imgData, "PNG", marginX, marginY, imgWidth, imgHeight)
          pdf.save(`5pourquoi-${Date.now()}.pdf`)
          exportSuccess = true
          showNotification("Export PDF généré.", "success")
          try {
            trackExport("5pourquoi", "PDF", baseMetadata)
          } catch (trackError) {
            console.warn("Erreur lors du tracking de l'export PDF:", trackError)
          }
        } else {
          const mime = format === "jpeg" ? "image/jpeg" : "image/png"
          const dataUrl = exportCanvas.toDataURL(mime, 0.95)
          const link = document.createElement("a")
          link.href = dataUrl
          const extension = format === "jpeg" ? "jpg" : "png"
          link.download = `5pourquoi-${Date.now()}.${extension}`
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          exportSuccess = true
          showNotification(`Export ${format === "jpeg" ? "JPEG" : "PNG"} généré.`, "success")
          try {
            trackExport("5pourquoi", format.toUpperCase(), baseMetadata)
          } catch (trackError) {
            console.warn(`Erreur lors du tracking de l'export ${format.toUpperCase()}:`, trackError)
          }
        }
      } catch (exportError) {
        console.error("Erreur lors de la génération de l'export:", exportError)
        showNotification("Erreur lors de la génération de l'export.", "error")
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la capture html2canvas:", error)
      if (!exportSuccess) {
        showNotification("Erreur lors de la génération de l'export.", "error")
      }
    })
    .finally(() => {
      try {
        context.container.style.cssText = originalStyle
      } catch (styleError) {
        console.warn("Erreur lors de la restauration du style:", styleError)
      }
    })
}

function exportPDF() {
  exportFiveWhy("pdf")
}

function exportJPEG() {
  exportFiveWhy("jpeg")
}

function exportPNG() {
  exportFiveWhy("png")
}

function exportJSON() {
  exportFiveWhy("json")
}

// Gestion des événements clavier
document.addEventListener("keydown", (e) => {
  // Sauvegarder avec Ctrl+S
  if (e.ctrlKey && e.key === "s") {
    e.preventDefault()
    exportJSON()
  }

  // Ajouter une étape avec Ctrl+Enter
  if (e.ctrlKey && e.key === "Enter") {
    e.preventDefault()
    addWhyStep()
  }
})

// Export des fonctions globales
window.updateProblemStatement = updateProblemStatement
window.addWhyStep = addWhyStep
window.removeWhyStep = removeWhyStep
window.confirmRemoveWhyStep = confirmRemoveWhyStep
window.resetAnalysis = resetAnalysis
window.exportPDF = exportPDF
window.exportJPEG = exportJPEG
window.exportPNG = exportPNG
window.exportJSON = exportJSON
window.updateWhyStep = updateWhyStep
window.checkForNextStep = checkForNextStep
window.updateAddButtonVisibility = updateAddButtonVisibility
window.addWhyStepFrom = addWhyStepFrom
window.generateRootCause = generateRootCause
window.renderWhyChain = renderWhyChain
window.updateRootCauseDisplay = updateRootCauseDisplay
window.renderRootCauseTrigger = renderRootCauseTrigger
