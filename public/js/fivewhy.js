// Données de l'analyse des 5 Pourquoi
const fiveWhyData = {
  problemStatement: "",
  whySteps: [],
  rootCause: "",
}

// Note: L'ajout automatique a été désactivé - l'utilisateur doit cliquer sur le bouton

const LOG_ENDPOINT = "https://prod-14.northeurope.logic.azure.com:443/workflows/e5f6c53b8fee498b910fd8ead7abe254/triggers/When_a_HTTP_request_is_received/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2FWhen_a_HTTP_request_is_received%2Frun&sv=1.0&sig=2CfWC8Xg8UCHtKiOt4MyodWfnTSRu2foSzsZxnl9Biw"

function trackExport(tool, format) {
  fetch(LOG_ENDPOINT, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      Tool:   tool,                // "Ishikawa"
      Format: format,              // "PDF" | "JPEG" | "JSON"
      UA:     navigator.userAgent, // Facultatif
      Page:   location.pathname,   // Facultatif
      Time:   new Date().toISOString()
    })
  }).catch(console.error);
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
                ${
                  index > 0
                    ? `
                    <div data-controller="dialog" id="deleteStepModal-${index}" style="display: inline;">
                        <button class="remove-step-btn" type="button" data-action="click->dialog#open" aria-label="Supprimer cette étape">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                        <dialog data-dialog-target="dialog" class="delete-confirmation-dialog" data-step-index="${index}">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i data-lucide="alert-triangle" width="24" height="24" class="text-warning me-2"></i>
                                        Confirmation de suppression
                                    </h5>
                                    <button type="button" class="btn-close" data-action="dialog#close" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir supprimer cette étape ? Cette action est irréversible.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-action="dialog#close">Annuler</button>
                                    <button type="button" class="btn btn-danger" onclick="confirmRemoveWhyStep(${index}); this.closest('dialog').close();">
                                        <i data-lucide="trash-2" width="18" height="18" class="me-1"></i>
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </dialog>
                    </div>
                `
                    : ""
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
  
  // Forcer Stimulus à détecter les nouveaux contrôleurs Dialog
  // Stimulus détecte automatiquement, mais on force pour s'assurer que ça fonctionne
  setTimeout(() => {
    if (window.Stimulus) {
      // Sélectionner tous les nouveaux éléments avec data-controller="dialog"
      const dialogElements = whyChain.querySelectorAll('[data-controller="dialog"]')
      dialogElements.forEach(element => {
        // Stimulus devrait détecter automatiquement, mais on force le chargement
        try {
          window.Stimulus.load(element)
        } catch (e) {
          // Si load() n'existe pas, Stimulus détectera automatiquement
          console.debug('Stimulus auto-détection activée pour', element)
        }
      })
    }
  }, 10)

  // Vérifier si on doit afficher la cause racine
  checkRootCause()
  
  // Mettre à jour la visibilité du bouton d'ajout après le rendu
  setTimeout(() => {
    updateAddButtonVisibility()
  }, 50)
}

function updateWhyStep(index, field, value) {
  if (fiveWhyData.whySteps[index]) {
    fiveWhyData.whySteps[index][field] = value.trim()
    checkRootCause()
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
  
  // Ne plus ajouter automatiquement - l'utilisateur doit cliquer sur le bouton
}

// Fonction pour mettre à jour la visibilité et le style du bouton d'ajout
function updateAddButtonVisibility() {
  const addButton = document.getElementById('addWhyStepButton')
  if (!addButton) return
  
  const lastStep = fiveWhyData.whySteps[fiveWhyData.whySteps.length - 1]
  const hasLastAnswer = lastStep && lastStep.answer && lastStep.answer.trim().length > 0
  const canAddMore = fiveWhyData.whySteps.length < 10
  
  if (hasLastAnswer && canAddMore) {
    // Mettre en évidence le bouton si une réponse existe dans la dernière étape
    addButton.classList.remove('btn-secondary')
    addButton.classList.add('btn-primary', 'pulse-animation')
    addButton.innerHTML = '<i class="fas fa-plus-circle me-2"></i> Ajouter un "Pourquoi" <i class="fas fa-arrow-right ms-2"></i>'
    addButton.disabled = false
  } else if (!canAddMore) {
    // Limite atteinte
    addButton.classList.remove('btn-primary', 'pulse-animation')
    addButton.classList.add('btn-secondary')
    addButton.innerHTML = '<i class="fas fa-ban me-2"></i> Limite atteinte (10 étapes max)'
    addButton.disabled = true
  } else {
    // Pas de réponse dans la dernière étape - bouton moins visible mais toujours cliquable
    addButton.classList.remove('btn-primary', 'pulse-animation')
    addButton.classList.add('btn-secondary')
    addButton.innerHTML = '<i class="fas fa-plus me-2"></i> Ajouter un "Pourquoi"'
    addButton.disabled = false
  }
}

function addWhyStep() {
  if (fiveWhyData.whySteps.length >= 10) {
    showNotification("Limite de 10 étapes atteinte", "error")
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

function removeWhyStep(index) {
  if (index === 0) {
    showNotification("Impossible de supprimer la première étape", "error")
    return
  }

  // Le modal sera ouvert automatiquement par Stimulus Dialog via l'attribut data-action
  // Cette fonction n'est plus utilisée directement, mais conservée pour compatibilité
  const deleteStepModal = document.getElementById(`deleteStepModal-${index}`)
  if (deleteStepModal) {
    const dialog = deleteStepModal.querySelector('[data-dialog-target="dialog"]')
    if (dialog && !dialog.open) {
      dialog.showModal()
    }
    return
  }
  
  // Fallback sur confirm si le modal n'est pas disponible
  if (confirm("Êtes-vous sûr de vouloir supprimer cette étape ?")) {
    confirmRemoveWhyStep(index)
  }
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
  
  // Fermer tous les modals ouverts
  document.querySelectorAll('[data-dialog-target="dialog"]').forEach(dialog => {
    if (dialog.open) {
      dialog.close()
    }
  })
}

function checkRootCause() {
  const rootCauseDiv = document.getElementById("rootCause")
  const rootCauseText = document.getElementById("rootCauseText")

  // Considérer qu'on a trouvé la cause racine si :
  // 1. On a au moins 3 étapes
  // 2. Les 3 dernières étapes ont des réponses
  // 3. La dernière réponse semble être une cause fondamentale

  const completedSteps = fiveWhyData.whySteps.filter((step) => step.answer.trim())

  if (completedSteps.length >= 3) {
    const lastAnswer = completedSteps[completedSteps.length - 1].answer.trim()
    fiveWhyData.rootCause = lastAnswer

    rootCauseText.textContent = `La cause racine identifiée est : "${lastAnswer}"`
    rootCauseDiv.style.display = "block"
  } else {
    rootCauseDiv.style.display = "none"
    fiveWhyData.rootCause = ""
  }
}

function resetAnalysis() {
  if (confirm("Êtes-vous sûr de vouloir recommencer l'analyse ? Toutes les données seront perdues.")) {
    fiveWhyData.problemStatement = ""
    fiveWhyData.whySteps = [{ question: "", answer: "" }]
    fiveWhyData.rootCause = ""

    document.getElementById("problemStatement").value = ""
    renderWhyChain()
    showNotification("Analyse réinitialisée")
  }
}

// Fonctions d'export
function exportPDF() {
  const container = document.getElementById("fiveWhyContainer")
  const { jsPDF } = window.jspdf

  if (!jsPDF) {
    showNotification("Erreur : Bibliothèque PDF non chargée", "error")
    return
  }

  if (!fiveWhyData.problemStatement.trim()) {
    showNotification("Veuillez d'abord définir le problème", "error")
    return
  }

  showNotification("Génération du PDF en cours...", "info")

  // Configuration pour l'export
  const originalStyle = container.style.cssText
  container.style.cssText += `
        background: white !important;
        padding: 20px !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    `

  window
    .html2canvas(container, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: "#ffffff",
      width: container.scrollWidth,
      height: container.scrollHeight,
    })
    .then((canvas) => {
      const imgData = canvas.toDataURL("image/png")
      const pdf = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4",
      })

      const pdfWidth = pdf.internal.pageSize.getWidth()
      const pdfHeight = pdf.internal.pageSize.getHeight()
      const imgWidth = canvas.width
      const imgHeight = canvas.height
      const ratio = Math.min(pdfWidth / imgWidth, (pdfHeight - 20) / imgHeight)
      const imgX = (pdfWidth - imgWidth * ratio) / 2
      const imgY = 10

      pdf.addImage(imgData, "PNG", imgX, imgY, imgWidth * ratio, imgHeight * ratio)

      // Ajouter un filigrane
      pdf.setFontSize(8)
      pdf.setTextColor(150, 150, 150)
      pdf.text("Généré par OUTILS-QUALITÉ - www.outils-qualite.com", 10, pdfHeight - 5)

      // Ajouter les métadonnées dans le footer
      const currentDate = new Date().toLocaleDateString("fr-FR")
      const metadata = `Analyse 5 Pourquoi - ${fiveWhyData.problemStatement.substring(0, 50)}... - ${currentDate}`
      pdf.setFontSize(6)
      pdf.text(metadata, 10, pdfHeight - 10)

      const filename = `5pourquoi-${Date.now()}.pdf`
      pdf.save(filename)

      showNotification("PDF exporté avec succès")
      trackExport("5pourquoi","PDF");

    })
    .catch((error) => {
      console.error("Erreur lors de l'export PDF:", error)
      showNotification("Erreur lors de l'export PDF", "error")
    })
    .finally(() => {
      container.style.cssText = originalStyle
    })
}

function exportJPEG() {
  const container = document.getElementById("fiveWhyContainer")

  if (!fiveWhyData.problemStatement.trim()) {
    showNotification("Veuillez d'abord définir le problème", "error")
    return
  }

  showNotification("Génération de l’image en cours...", "info")

  // Ajout d’un style temporaire pour un bon rendu
  const originalStyle = container.style.cssText
  container.style.cssText += `
    background: white !important;
    padding: 20px !important;
    box-shadow: none !important;
    border-radius: 0 !important;
  `

  window.html2canvas(container, {
    scale: 3,
    useCORS: true,
    allowTaint: true,
    backgroundColor: "#ffffff",
    width: container.scrollWidth,
    height: container.scrollHeight,
  })
  .then((canvas) => {
    // Nouveau canvas avec padding et watermark
    const finalCanvas = document.createElement("canvas")
    const ctx = finalCanvas.getContext("2d")
    const padding = 40
    finalCanvas.width = canvas.width + padding * 2
    finalCanvas.height = canvas.height + padding * 2

    ctx.fillStyle = "#ffffff"
    ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height)
    ctx.drawImage(canvas, padding, padding)

    ctx.font = "16px Arial"
    ctx.fillStyle = "rgba(0, 0, 0, 0.1)"
    ctx.save()
    ctx.translate(finalCanvas.width / 2, finalCanvas.height / 2)
    ctx.rotate(-Math.PI / 6)
    ctx.textAlign = "center"
    ctx.fillText("OUTILS-QUALITÉ", 0, 0)
    ctx.restore()

    ctx.font = "12px Arial"
    ctx.fillStyle = "#666666"
    ctx.textAlign = "left"
    const currentDate = new Date().toLocaleDateString("fr-FR")
    const metadata = `Analyse 5 Pourquoi – ${fiveWhyData.problemStatement.substring(0, 60)}… – ${currentDate}`
    ctx.fillText(metadata, padding, finalCanvas.height - 10)

    // Téléchargement
    const link = document.createElement("a")
    const filename = `5pourquoi-${Date.now()}.jpg`
    link.download = filename
    link.href = finalCanvas.toDataURL("image/jpeg", 0.9)
    link.click()
    URL.revokeObjectURL(link.href)

    // Notification succès
    showNotification(`Le fichier ${filename} a bien été téléchargé`, "success")
      trackExport("5pourquoi","JPEG");

  })
  .catch((error) => {
    console.error("Erreur lors de l'export JPEG :", error)
    showNotification("Erreur lors de l’export JPEG", "error")
  })
  .finally(() => {
    // Restauration du style
    container.style.cssText = originalStyle
  })
}


function exportJSON() {
  if (!fiveWhyData.problemStatement.trim()) {
    showNotification("Veuillez d'abord définir le problème", "error")
    return
  }

  const exportData = {
    metadata: {
      tool: "Méthode des 5 Pourquoi",
      version: "1.0",
      exportDate: new Date().toISOString(),
      source: "OUTILS-QUALITÉ",
    },
    analysis: {
      problemStatement: fiveWhyData.problemStatement,
      whySteps: fiveWhyData.whySteps.map((step, index) => ({
        stepNumber: index + 1,
        question: step.question,
        answer: step.answer,
      })),
      rootCause: fiveWhyData.rootCause,
      completedSteps: fiveWhyData.whySteps.filter((step) => step.answer.trim()).length,
    },
  }

  const dataStr = JSON.stringify(exportData, null, 2)
  const dataBlob = new Blob([dataStr], { type: "application/json" })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement("a")
  const filename = `5pourquoi-${Date.now()}.json`
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)

  showNotification(`Le JSON ${filename} a bien été téléchargé`, "success")

  trackExport("5pourquoi","JSON");

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
window.exportJSON = exportJSON
window.updateWhyStep = updateWhyStep
window.checkForNextStep = checkForNextStep
window.updateAddButtonVisibility = updateAddButtonVisibility
