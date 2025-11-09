// Catégories standards du modèle étendu
const availableCategories = ["MATÉRIELS", "MESURE", "MACHINES", "MÉTHODES", "ENVIRONNEMENT", "PERSONNEL", "MANAGEMENT"]

const LOG_ENDPOINT = "https://prod-14.northeurope.logic.azure.com:443/workflows/e5f6c53b8fee498b910fd8ead7abe254/triggers/When_a_HTTP_request_is_received/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2FWhen_a_HTTP_request_is_received%2Frun&sv=1.0&sig=2CfWC8Xg8UCHtKiOt4MyodWfnTSRu2foSzsZxnl9Biw"

// Données du diagramme - 7 catégories par défaut
const diagramData = {
  problem: "Utilisez ce modèle d'analyse pour optimiser l'amélioration des processus et identifier les causes racines.",
  categories: [
    {
      id: 1,
      name: "PERSONNEL",
      causes: ["Formation insuffisante", "Fatigue du personnel", "Manque de motivation"],
    },
    {
      id: 2,
      name: "MATÉRIELS",
      causes: ["Qualité des matières premières", "Spécifications non conformes", "Stockage inadéquat"],
    },
    {
      id: 3,
      name: "MESURE",
      causes: ["Instruments de mesure défaillants", "Précision inadéquate"],
    },
    {
      id: 4,
      name: "MACHINES",
      causes: ["Équipement défaillant", "Maintenance préventive insuffisante", "Usure prématurée"],
    },
    {
      id: 5,
      name: "MÉTHODES",
      causes: ["Procédures inadéquates", "Manque de standardisation", "Instructions peu claires"],
    },
    {
      id: 6,
      name: "ENVIRONNEMENT",
      causes: ["Conditions de travail", "Température inadéquate", "Éclairage insuffisant"],
    },
    {
      id: 7,
      name: "MANAGEMENT",
      causes: ["Planification insuffisante", "Communication défaillante", "Ressources inadéquates"],
    },
  ],
}

let currentCategoryId = null
let currentCauseIndex = null
let editingCategory = false
let editingCause = false

// Variables pour le drag & drop
let isDragging = false
let dragElement = null
const dragOffset = { x: 0, y: 0 }

function logExport(format) {
  /*const payload = {
    format,                        // pdf / jpeg / json
    page: location.pathname,       // /ishikawa/index.html
    ts: new Date().toISOString(),  // horodatage ISO
    ip: null                       // par défaut – sera enrichi côté Logic App
  };

  /* 1. Envoi Logic App ---------------------------------- */
 /* if (typeof LOG_ENDPOINT === "string" && LOG_ENDPOINT.startsWith("https")) {
    fetch(LOG_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    }).catch(err => console.error("LOG_ENDPOINT error", err));
  }*/

  /* 2. (optionnel) Envoi Application Insights -----------
  if (window.appInsights?.trackEvent) {
    window.appInsights.trackEvent({
      name: "IshikawaExport",
      properties: payload
    });
  } */
} 

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


// Génération d'ID unique
function generateUniqueId() {
  return "OQ-" + Date.now().toString(36).toUpperCase() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase()
}

// Fonction pour afficher les notifications
function showNotification(message, type = "success") {
  const Toastify = window.Toastify // Declare Toastify variable
  if (typeof Toastify !== "undefined") {
    Toastify({
      text: message,
      duration: 3000,
      gravity: "top",
      position: "right",
      backgroundColor: type === "success" ? "#2ecc71" : type === "error" ? "#e74c3c" : "#3498db",
      stopOnFocus: true,
    }).showToast()
  } else {
    alert(message)
  }
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
  renderDiagram()
  document.getElementById("problemInput").value = diagramData.problem
  updateAddCategoryButton()
  initializeDragAndDrop()

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

function initializeDragAndDrop() {
  document.addEventListener("mousedown", handleMouseDown)
  document.addEventListener("mousemove", handleMouseMove)
  document.addEventListener("mouseup", handleMouseUp)
}

function handleMouseDown(e) {
  const categoryHeader = e.target.closest(".category-header")
  if (categoryHeader && !e.target.closest(".category-actions")) {
    isDragging = true
    dragElement = categoryHeader.closest(".category-zone")

    const rect = dragElement.getBoundingClientRect()
    const containerRect = document.getElementById("fishbone").getBoundingClientRect()

    dragOffset.x = e.clientX - rect.left
    dragOffset.y = e.clientY - rect.top

    dragElement.classList.add("dragging")
    e.preventDefault()
  }
}

function handleMouseMove(e) {
  if (isDragging && dragElement) {
    const container = document.getElementById("fishbone")
    const containerRect = container.getBoundingClientRect()

    const x = e.clientX - containerRect.left - dragOffset.x
    const y = e.clientY - containerRect.top - dragOffset.y

    // Limiter le déplacement dans les limites du conteneur
    const maxX = container.offsetWidth - dragElement.offsetWidth
    const maxY = container.offsetHeight - dragElement.offsetHeight

    const constrainedX = Math.max(0, Math.min(x, maxX))
    const constrainedY = Math.max(0, Math.min(y, maxY))

    dragElement.style.left = constrainedX + "px"
    dragElement.style.top = constrainedY + "px"
    dragElement.style.bottom = "auto"

    // Mettre à jour les lignes de connexion en temps réel
    updateConnectionLines()
  }
}

function handleMouseUp(e) {
  if (isDragging && dragElement) {
    dragElement.classList.remove("dragging")
    isDragging = false
    dragElement = null
    // Mise à jour finale des lignes
    updateConnectionLines()
  }
}

// Fonction pour calculer et dessiner les lignes de connexion dynamiques
function updateConnectionLines() {
  const svg = document.getElementById("connectionSvg")
  const spine = document.querySelector(".spine")
  const categoryZones = document.querySelectorAll(".category-zone")

  if (!svg || !spine) return

  // Vider le SVG
  svg.innerHTML = ""

  // Calculer la position de l'épine centrale
  const spineRect = spine.getBoundingClientRect()
  const fishboneRect = document.getElementById("fishbone").getBoundingClientRect()

  const spineY = spineRect.top - fishboneRect.top + spineRect.height / 2
  const spineStartX = spineRect.left - fishboneRect.left
  const spineEndX = spineRect.right - fishboneRect.left

  // Pour chaque catégorie, dessiner une ligne de connexion
  categoryZones.forEach((zone, index) => {
    const zoneRect = zone.getBoundingClientRect()
    const categoryHeader = zone.querySelector(".category-header")

    if (!categoryHeader) return

    const headerRect = categoryHeader.getBoundingClientRect()

    // Position du centre de la catégorie
    const categoryX = headerRect.left - fishboneRect.left + headerRect.width / 2
    const categoryY = headerRect.top - fishboneRect.top + headerRect.height / 2

    // Trouver le point de connexion sur l'épine (le plus proche)
    let spineConnectionX
    if (categoryX < spineStartX) {
      spineConnectionX = spineStartX
    } else if (categoryX > spineEndX) {
      spineConnectionX = spineEndX
    } else {
      spineConnectionX = categoryX
    }

    // Créer la ligne SVG
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line")
    line.setAttribute("x1", categoryX)
    line.setAttribute("y1", categoryY)
    line.setAttribute("x2", spineConnectionX)
    line.setAttribute("y2", spineY)
    line.setAttribute("class", "connection-line-svg")

    svg.appendChild(line)
  })
}

// Rendu du diagramme
function renderDiagram() {
  const fishbone = document.getElementById("fishbone")
  const problemBox = document.getElementById("problemBox")

  problemBox.textContent = diagramData.problem

  // Supprimer les zones existantes (sauf spine, svg, fish-head, problem-box)
  const existingZones = fishbone.querySelectorAll(".category-zone")
  existingZones.forEach((zone) => zone.remove())

  // Créer les zones de catégories
  diagramData.categories.forEach((category, index) => {
    createCategoryZone(category, index)
  })

  updateAddCategoryButton()

  // Mettre à jour les lignes après un court délai pour s'assurer que le DOM est mis à jour
  setTimeout(updateConnectionLines, 50)
}

function createCategoryZone(category, index) {
  const fishbone = document.getElementById("fishbone")
  const zoneDiv = document.createElement("div")
  zoneDiv.className = "category-zone"
  zoneDiv.setAttribute("role", "region")
  zoneDiv.setAttribute("aria-label", `Catégorie ${category.name}`)

  // Position initiale basée sur l'index
  const positions = [
    { left: "15%", top: "10%" }, // PERSONNEL
    { left: "35%", top: "11%" }, // MATÉRIELS
    { left: "55%", top: "10%" }, // MESURE
    { left: "15%", top: "60%" }, // MACHINES
    { left: "35%", top: "60%" }, // MÉTHODES
    { left: "55%", top: "65%" }, // ENVIRONNEMENT
    { left: "75%", top: "60%" }, // MANAGEMENT
  ]

  const position = positions[index] || { left: "20%", top: "20%" }
  zoneDiv.style.left = position.left
  zoneDiv.style.top = position.top

  zoneDiv.innerHTML = `
        <div class="category">
            <div class="category-header" tabindex="0" role="button" aria-label="Catégorie ${category.name}">
                <h3>${category.name}</h3>
                <div class="category-actions">
                    <button onclick="editCategory(${category.id})" aria-label="Modifier la catégorie ${category.name}">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                    </button>
                    <button onclick="deleteCategory(${category.id})" aria-label="Supprimer la catégorie ${category.name}">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="causes-list" id="causes-${category.id}">
                ${category.causes
                  .map(
                    (cause, causeIndex) => `
                    <div class="cause-item" onclick="editCause(${category.id}, ${causeIndex})" tabindex="0" role="button" aria-label="Cause: ${cause}">
                        ${cause}
                        <button onclick="deleteCause(${category.id}, ${causeIndex}); event.stopPropagation();" class="delete-cause" aria-label="Supprimer la cause ${cause}">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                `,
                  )
                  .join("")}
                <button class="add-cause-btn" onclick="addCause(${category.id})" aria-label="Ajouter une cause à ${category.name}">
                    <i class="fas fa-plus" aria-hidden="true"></i> Ajouter une cause
                </button>
            </div>
        </div>
    `

  fishbone.appendChild(zoneDiv)
}

// Gestion des catégories
function addCategory() {
  if (diagramData.categories.length >= 10) {
    showNotification("Limite de 10 catégories atteinte", "error")
    return
  }

  currentCategoryId = null
  editingCategory = true
  populateCategoryModal()
  openCategoryModal()
}

function editCategory(categoryId) {
  currentCategoryId = categoryId
  editingCategory = true
  populateCategoryModal()
  openCategoryModal()
}

function deleteCategory(categoryId) {
  if (diagramData.categories.length <= 1) {
    showNotification("Au moins une catégorie est requise", "error")
    return
  }

  if (confirm("Êtes-vous sûr de vouloir supprimer cette catégorie et toutes ses causes ?")) {
    diagramData.categories = diagramData.categories.filter((cat) => cat.id !== categoryId)
    renderDiagram()
    showNotification("Catégorie supprimée avec succès")
  }
}

function populateCategoryModal() {
  const select = document.getElementById("categorySelect")
  const customGroup = document.getElementById("customCategoryGroup")
  const categoryName = document.getElementById("categoryName")

  // Vider et repeupler le select
  select.innerHTML = '<option value="">-- Sélectionner une catégorie --</option>'

  // Ajouter les catégories disponibles qui ne sont pas déjà utilisées
  const usedCategories = diagramData.categories.map((cat) => cat.name)
  availableCategories.forEach((cat) => {
    if (
      !usedCategories.includes(cat) ||
      (currentCategoryId && diagramData.categories.find((c) => c.id === currentCategoryId)?.name === cat)
    ) {
      const option = document.createElement("option")
      option.value = cat
      option.textContent = cat
      select.appendChild(option)
    }
  })

  // Ajouter l'option personnalisée
  const customOption = document.createElement("option")
  customOption.value = "custom"
  customOption.textContent = "Catégorie personnalisée..."
  select.appendChild(customOption)

  // Si on édite une catégorie existante
  if (currentCategoryId) {
    const category = diagramData.categories.find((cat) => cat.id === currentCategoryId)
    if (category) {
      if (availableCategories.includes(category.name)) {
        select.value = category.name
        customGroup.style.display = "none"
      } else {
        select.value = "custom"
        categoryName.value = category.name
        customGroup.style.display = "block"
      }
    }
  } else {
    customGroup.style.display = "none"
    categoryName.value = ""
  }

  // Gérer le changement de sélection
  select.onchange = function () {
    if (this.value === "custom") {
      customGroup.style.display = "block"
      categoryName.focus()
    } else {
      customGroup.style.display = "none"
    }
  }
}

function openCategoryModal() {
  document.getElementById("categoryModal").style.display = "block"
  document.getElementById("categoryModalTitle").textContent = currentCategoryId
    ? "Modifier la catégorie"
    : "Ajouter une catégorie"
}

function closeCategoryModal() {
  document.getElementById("categoryModal").style.display = "none"
  currentCategoryId = null
  editingCategory = false
}

function saveCategoryModal() {
  const select = document.getElementById("categorySelect")
  const categoryName = document.getElementById("categoryName")

  let newName = ""
  if (select.value === "custom") {
    newName = categoryName.value.trim().toUpperCase()
    if (!newName) {
      showNotification("Veuillez saisir un nom de catégorie", "error")
      return
    }
  } else if (select.value) {
    newName = select.value
  } else {
    showNotification("Veuillez sélectionner une catégorie", "error")
    return
  }

  // Vérifier les doublons (sauf si on édite la même catégorie)
  const existingCategory = diagramData.categories.find((cat) => cat.name === newName && cat.id !== currentCategoryId)
  if (existingCategory) {
    showNotification("Cette catégorie existe déjà", "error")
    return
  }

  if (currentCategoryId) {
    // Modifier une catégorie existante
    const category = diagramData.categories.find((cat) => cat.id === currentCategoryId)
    if (category) {
      category.name = newName
      showNotification("Catégorie modifiée avec succès")
    }
  } else {
    // Ajouter une nouvelle catégorie
    const newCategory = {
      id: Date.now(),
      name: newName,
      causes: [],
    }
    diagramData.categories.push(newCategory)
    showNotification("Catégorie ajoutée avec succès")
  }

  closeCategoryModal()
  renderDiagram()
}

// Gestion des causes
function addCause(categoryId) {
  currentCategoryId = categoryId
  currentCauseIndex = null
  editingCause = true
  document.getElementById("causeName").value = ""
  openCauseModal()
}

function editCause(categoryId, causeIndex) {
  currentCategoryId = categoryId
  currentCauseIndex = causeIndex
  editingCause = true

  const category = diagramData.categories.find((cat) => cat.id === categoryId)
  if (category && category.causes[causeIndex]) {
    document.getElementById("causeName").value = category.causes[causeIndex]
  }

  openCauseModal()
}

function deleteCause(categoryId, causeIndex) {
  const category = diagramData.categories.find((cat) => cat.id === categoryId)
  if (category) {
    category.causes.splice(causeIndex, 1)
    renderDiagram()
    showNotification("Cause supprimée avec succès")
  }
}

function openCauseModal() {
  document.getElementById("causeModal").style.display = "block"
  document.getElementById("causeModalTitle").textContent =
    currentCauseIndex !== null ? "Modifier la cause" : "Ajouter une cause"
  document.getElementById("causeName").focus()
}

function closeCauseModal() {
  document.getElementById("causeModal").style.display = "none"
  currentCategoryId = null
  currentCauseIndex = null
  editingCause = false
}

function saveCauseModal() {
  const causeName = document.getElementById("causeName").value.trim()

  if (!causeName) {
    showNotification("Veuillez saisir une description de cause", "error")
    return
  }

  const category = diagramData.categories.find((cat) => cat.id === currentCategoryId)
  if (!category) {
    showNotification("Catégorie non trouvée", "error")
    return
  }

  if (currentCauseIndex !== null) {
    // Modifier une cause existante
    category.causes[currentCauseIndex] = causeName
    showNotification("Cause modifiée avec succès")
  } else {
    // Ajouter une nouvelle cause
    category.causes.push(causeName)
    showNotification("Cause ajoutée avec succès")
  }

  closeCauseModal()
  renderDiagram()
}

// Gestion du problème
function updateProblem() {
  const problemInput = document.getElementById("problemInput")
  diagramData.problem = problemInput.value.trim() || "Problème non défini"
  document.getElementById("problemBox").textContent = diagramData.problem
  showNotification("Problème mis à jour")
}

function editProblem() {
  const problemInput = document.getElementById("problemInput")
  problemInput.focus()
  problemInput.select()
}

// Fonctions utilitaires
function updateAddCategoryButton() {
  const addBtn = document.getElementById("addCategoryBtn")
  if (diagramData.categories.length >= 10) {
    addBtn.disabled = true
    addBtn.innerHTML = '<i class="fas fa-ban" aria-hidden="true"></i> Limite atteinte (10 max)'
  } else {
    addBtn.disabled = false
    addBtn.innerHTML = '<i class="fas fa-plus" aria-hidden="true"></i> Ajouter une catégorie'
  }
}

function resetAllCauses() {
  if (confirm("Êtes-vous sûr de vouloir vider toutes les causes ? Cette action est irréversible.")) {
    diagramData.categories.forEach((category) => {
      category.causes = []
    })
    renderDiagram()
    showNotification("Toutes les causes ont été supprimées")
  }
}

// Fonctions d'export
function exportPDF() {
  const diagramContainer = document.getElementById("diagramContainer")
  const { jsPDF } = window.jspdf
  const html2canvas = window.html2canvas // Declare html2canvas variable

  if (!jsPDF || !html2canvas) {
    showNotification("Erreur : Bibliothèque PDF ou html2canvas non chargée", "error")
    return
  }

  showNotification("Génération du PDF en cours...", "info")

  // Configuration pour l'export
  const originalStyle = diagramContainer.style.cssText
  diagramContainer.style.cssText += `
        background: white !important;
        padding: 20px !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    `

  html2canvas(diagramContainer, {
    scale: 2,
    useCORS: true,
    allowTaint: true,
    backgroundColor: "#ffffff",
    width: diagramContainer.scrollWidth,
    height: diagramContainer.scrollHeight,
  })
    .then((canvas) => {
      const imgData = canvas.toDataURL("image/png")
      const pdf = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: "a4",
      })

      const pdfWidth = pdf.internal.pageSize.getWidth()
      const pdfHeight = pdf.internal.pageSize.getHeight()
      const imgWidth = canvas.width
      const imgHeight = canvas.height
      const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight)
      const imgX = (pdfWidth - imgWidth * ratio) / 2
      const imgY = (pdfHeight - imgHeight * ratio) / 2

      pdf.addImage(imgData, "PNG", imgX, imgY, imgWidth * ratio, imgHeight * ratio)

      // Ajouter un filigrane
      pdf.setFontSize(8)
      pdf.setTextColor(150, 150, 150)
      pdf.text("Généré par OUTILS-QUALITÉ - www.outils-qualite.com", 10, pdfHeight - 5)

      // Ajouter les métadonnées dans le footer
      const currentDate = new Date().toLocaleDateString("fr-FR")
      const metadata = `Diagramme d'Ishikawa - ${diagramData.problem.substring(0, 50)}... - ${currentDate}`
      pdf.setFontSize(6)
      pdf.text(metadata, 10, pdfHeight - 10)

      const filename = `ishikawa-${Date.now()}.pdf`
      pdf.save(filename)
     

      showNotification("PDF exporté avec succès" )

      trackExport("Ishikawa","PDF");
    })
    .catch((error) => {
      console.error("Erreur lors de l'export PDF:", error)
      showNotification("Erreur lors de l'export PDF", "error")
    })
    .finally(() => {
      diagramContainer.style.cssText = originalStyle
    })
}

function exportJPEG() {
  const diagramContainer = document.getElementById("diagramContainer")
  const html2canvas = window.html2canvas // Declare html2canvas variable

  showNotification("Génération de l'image en cours...", "info")

  // Configuration pour l'export
  const originalStyle = diagramContainer.style.cssText
  diagramContainer.style.cssText += `
        background: white !important;
        padding: 20px !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    `

  html2canvas(diagramContainer, {
    scale: 3,
    useCORS: true,
    allowTaint: true,
    backgroundColor: "#ffffff",
    width: diagramContainer.scrollWidth,
    height: diagramContainer.scrollHeight,
  })
    .then((canvas) => {
      // Créer un nouveau canvas avec métadonnées
      const finalCanvas = document.createElement("canvas")
      const ctx = finalCanvas.getContext("2d")
      const padding = 40
      finalCanvas.width = canvas.width + padding * 2
      finalCanvas.height = canvas.height + padding * 2

      // Fond blanc
      ctx.fillStyle = "#ffffff"
      ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height)

      // Dessiner l'image principale
      ctx.drawImage(canvas, padding, padding)

      // Ajouter un filigrane
      ctx.font = "16px Arial"
      ctx.fillStyle = "rgba(0, 0, 0, 0.1)"
      ctx.save()
      ctx.translate(finalCanvas.width / 2, finalCanvas.height / 2)
      ctx.rotate(-Math.PI / 6)
      ctx.textAlign = "center"
      ctx.fillText("OUTILS-QUALITÉ", 0, 0)
      ctx.restore()

      // Ajouter les métadonnées en bas
      ctx.font = "12px Arial"
      ctx.fillStyle = "#666666"
      ctx.textAlign = "left"
      const currentDate = new Date().toLocaleDateString("fr-FR")
      const metadata = `Diagramme d'Ishikawa - ${diagramData.problem.substring(0, 80)}... - ${currentDate}`
      ctx.fillText(metadata, padding, finalCanvas.height - 10)

      // Télécharger l'image
      const link = document.createElement("a")
      link.download = `ishikawa-${Date.now()}.jpg`
      link.href = finalCanvas.toDataURL("image/jpeg", 0.9)
      link.click()

      showNotification("Image exportée avec succès")
      trackExport("Ishikawa","JPEG");

    })
    .catch((error) => {
      console.error("Erreur lors de l'export JPEG:", error)
      showNotification("Erreur lors de l'export JPEG", "error")
    })
    .finally(() => {
      diagramContainer.style.cssText = originalStyle
    })
}

function exportJSON() {
  
  const exportData = {
    metadata: {
      tool: "Diagramme d'Ishikawa",
      version: "1.0",
      exportDate: new Date().toISOString(),
      source: "OUTILS-QUALITÉ",
    },
    diagram: {
      problem: diagramData.problem,
      categories: diagramData.categories.map((cat) => ({
        id: cat.id,
        name: cat.name,
        causes: [...cat.causes],
      })),
    },
  }

  const dataStr = JSON.stringify(exportData, null, 2)
  const dataBlob = new Blob([dataStr], { type: "application/json" })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement("a")
  link.href = url
  link.download = `ishikawa-${Date.now()}.json`
  link.click()
  URL.revokeObjectURL(url)

  showNotification("Données exportées en JSON")
      trackExport("Ishikawa","JSON");

}

// Gestion des événements clavier pour l'accessibilité
document.addEventListener("keydown", (e) => {
  // Fermer les modales avec Escape
  if (e.key === "Escape") {
    if (document.getElementById("categoryModal").style.display === "block") {
      closeCategoryModal()
    }
    if (document.getElementById("causeModal").style.display === "block") {
      closeCauseModal()
    }
  }

  // Sauvegarder avec Ctrl+S
  if (e.ctrlKey && e.key === "s") {
    e.preventDefault()
    exportJSON()
  }
})

// Fermer les modales en cliquant à l'extérieur
window.onclick = (event) => {
  const categoryModal = document.getElementById("categoryModal")
  const causeModal = document.getElementById("causeModal")

  if (event.target === categoryModal) {
    closeCategoryModal()
  }
  if (event.target === causeModal) {
    closeCauseModal()
  }
}

// Redimensionnement de la fenêtre
window.addEventListener("resize", () => {
  setTimeout(updateConnectionLines, 100)
})

// Export des fonctions globales
window.addCategory = addCategory
window.editCategory = editCategory
window.deleteCategory = deleteCategory
window.addCause = addCause
window.editCause = editCause
window.deleteCause = deleteCause
window.updateProblem = updateProblem
window.editProblem = editProblem
window.resetAllCauses = resetAllCauses
window.exportPDF = exportPDF
window.exportJPEG = exportJPEG
window.exportJSON = exportJSON
window.closeCategoryModal = closeCategoryModal
window.saveCategoryModal = saveCategoryModal
window.closeCauseModal = closeCauseModal
window.saveCauseModal = saveCauseModal
