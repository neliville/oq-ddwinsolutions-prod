// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 📚 BLOG MANAGER AVEC SUPPORT DES IMAGES
// Version 2.0 - Gestion complète des images (featured + contenu)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// Configuration des catégories
const blogCategories = [
  {
    id: "amelioration-continue",
    name: "Amélioration Continue",
    color: "success",
    icon: "trending-up",
  },
  {
    id: "methodologie",
    name: "Méthodologies",
    color: "primary",
    icon: "book-open",
  },
  {
    id: "outils-qualite",
    name: "Outils",
    color: "info",
    icon: "tool",
  },
  {
    id: "lean",
    name: "Lean Management",
    color: "warning",
    icon: "zap",
  },
  {
    id: "qualite",
    name: "Qualité",
    color: "danger",
    icon: "shield-check",
  },
]

// Variables globales
let allArticles = []
let currentFilter = "all"

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CHARGEMENT DES ARTICLES AVEC IMAGES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

async function loadBlogContent() {
  const articlesContainer = document.getElementById("articlesContainer")
  const categoryFilters = document.getElementById("categoryFilters")

  if (!articlesContainer) return

  try {
    // Charger les articles de chaque catégorie
    for (const category of blogCategories) {
      try {
        const response = await fetch(`/blog/${category.id}/index.json`)
        if (response.ok) {
          const categoryData = await response.json()

          // Ajouter les infos de catégorie à chaque article
          categoryData.articles.forEach((article) => {
            article.category = category
            article.categoryId = category.id
            
            // S'assurer que l'article a une image (placeholder si manquant)
            if (!article.image) {
              article.image = null // Sera géré par un placeholder
            }
            
            // Ajouter imageAlt par défaut si manquant
            if (!article.imageAlt && article.title) {
              article.imageAlt = article.title
            }
            
            allArticles.push(article)
          })
        }
      } catch (error) {
        console.log(`Catégorie ${category.id} non trouvée, ignorée...`)
      }
    }

    // Trier les articles par date (plus récent en premier)
    allArticles.sort((a, b) => new Date(b.date) - new Date(a.date))

    // Créer les filtres de catégorie
    createCategoryFilters()

    // Afficher les articles
    displayArticles(allArticles)
  } catch (error) {
    console.error("Erreur lors du chargement du blog:", error)
    showNoPostsMessage()
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CRÉATION DES FILTRES DE CATÉGORIE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function createCategoryFilters() {
  const categoryFilters = document.getElementById("categoryFilters")
  if (!categoryFilters) return

  // Garder le bouton "Tous"
  const allButton = categoryFilters.querySelector('[data-category="all"]')
  categoryFilters.innerHTML = ""
  if (allButton) {
    categoryFilters.appendChild(allButton)
  } else {
    // Créer le bouton "Tous" s'il n'existe pas
    const allBtn = document.createElement("button")
    allBtn.className = "btn btn-primary me-2"
    allBtn.setAttribute("data-category", "all")
    allBtn.innerHTML = '<i data-lucide="grid" width="16" height="16" class="me-1"></i>Tous'
    categoryFilters.appendChild(allBtn)
  }

  // Ajouter les filtres pour les catégories qui ont des articles
  const categoriesWithArticles = [...new Set(allArticles.map((article) => article.categoryId))]

  categoriesWithArticles.forEach((categoryId) => {
    const category = blogCategories.find((cat) => cat.id === categoryId)
    if (category) {
      const button = document.createElement("button")
      button.className = `btn btn-outline-${category.color} me-2 mb-2`
      button.setAttribute("data-category", categoryId)
      button.innerHTML = `
        <i data-lucide="${category.icon}" width="16" height="16" class="me-1"></i>
        ${category.name}
      `
      categoryFilters.appendChild(button)
    }
  })

  // Initialiser les icônes Lucide
  if (window.lucide) {
    window.lucide.createIcons()
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// GESTION DES FILTRES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function setupCategoryFilters() {
  const categoryFilters = document.getElementById("categoryFilters")
  if (!categoryFilters) return

  categoryFilters.addEventListener("click", (e) => {
    const button = e.target.closest("button[data-category]")
    if (!button) return

    const category = button.getAttribute("data-category")

    // Mettre à jour l'état actif
    categoryFilters.querySelectorAll("button").forEach((btn) => {
      btn.classList.remove("btn-primary", "btn-success", "btn-info", "btn-warning", "btn-danger")
      const classes = btn.className.split(" ").filter((c) => !c.startsWith("btn-outline-"))
      btn.className = classes.join(" ") + " btn-outline-primary"
    })

    button.classList.remove("btn-outline-primary", "btn-outline-success", "btn-outline-info", "btn-outline-warning", "btn-outline-danger")

    if (category === "all") {
      button.classList.add("btn-primary")
    } else {
      const categoryData = blogCategories.find((cat) => cat.id === category)
      if (categoryData) {
        button.classList.add(`btn-${categoryData.color}`)
      }
    }

    // Filtrer les articles
    currentFilter = category
    filterArticles(category)
  })
}

function filterArticles(category) {
  if (category === "all") {
    displayArticles(allArticles)
  } else {
    const filteredArticles = allArticles.filter((article) => article.categoryId === category)
    displayArticles(filteredArticles)
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// AFFICHAGE DES ARTICLES AVEC IMAGES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function displayArticles(articles) {
  const articlesContainer = document.getElementById("articlesContainer")
  const noPostsMessage = document.getElementById("noPostsMessage")

  if (!articlesContainer) return

  if (articles.length === 0) {
    showNoPostsMessage()
    return
  }

  // Masquer le message "Aucun article"
  if (noPostsMessage) {
    noPostsMessage.style.display = "none"
  }

  articlesContainer.innerHTML = ""

  articles.forEach((article, index) => {
    const articleCard = createArticleCard(article, index)
    articlesContainer.appendChild(articleCard)
  })

  // Initialiser les icônes Lucide
  if (window.lucide) {
    window.lucide.createIcons()
  }

  // Rafraîchir AOS si disponible
  if (window.AOS) {
    window.AOS.refresh()
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CRÉATION D'UNE CARTE ARTICLE AVEC IMAGE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function createArticleCard(article, index) {
  const cardDiv = document.createElement("div")
  cardDiv.className = "col-lg-6 col-xl-4"
  cardDiv.setAttribute("data-aos", "fade-up")
  cardDiv.setAttribute("data-aos-delay", (index * 100).toString())

  const formattedDate = new Date(article.date).toLocaleDateString("fr-FR", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })

  // Générer l'image ou le placeholder
  let imageHtml = ""
  if (article.image) {
    imageHtml = `
      <img src="${article.image}" 
           alt="${article.imageAlt || article.title}" 
           class="card-img-top" 
           style="height: 200px; object-fit: cover;"
           loading="lazy"
           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
      <div class="card-img-top d-none align-items-center justify-content-center bg-gradient text-white" 
           style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="text-center">
          <i data-lucide="${article.category.icon}" width="48" height="48" class="mb-2"></i>
          <p class="mb-0 px-3">${article.title}</p>
        </div>
      </div>
    `
  } else {
    // Placeholder si pas d'image
    imageHtml = `
      <div class="card-img-top d-flex align-items-center justify-content-center bg-gradient text-white" 
           style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="text-center">
          <i data-lucide="${article.category.icon}" width="48" height="48" class="mb-2"></i>
          <p class="mb-0 px-3">${article.title}</p>
        </div>
      </div>
    `
  }

  cardDiv.innerHTML = `
    <article class="card h-100 border-0 shadow-sm hover-lift">
      ${imageHtml}
      
      <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
          <span class="badge bg-${article.category.color} me-2">
            <i data-lucide="${article.category.icon}" width="14" height="14" class="me-1"></i>
            ${article.category.name}
          </span>
          <small class="text-muted">${formattedDate}</small>
        </div>
        
        <h3 class="card-title h5 fw-bold mb-3">
          <a href="/article-template.html?category=${article.categoryId}&id=${article.id}" 
             class="text-decoration-none text-dark stretched-link">
            ${article.title}
          </a>
        </h3>
        
        <p class="card-text text-muted mb-3">${article.excerpt}</p>
        
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex flex-wrap gap-1">
            ${article.tags
              .slice(0, 2)
              .map((tag) => `<span class="badge bg-light text-dark">${tag}</span>`)
              .join("")}
            ${article.tags.length > 2 ? `<span class="badge bg-light text-dark">+${article.tags.length - 2}</span>` : ""}
          </div>
          <small class="text-muted">
            <i data-lucide="clock" width="14" height="14" class="me-1"></i>
            ${article.readTime}
          </small>
        </div>
      </div>
    </article>
  `

  return cardDiv
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FONCTIONS UTILITAIRES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function showNoPostsMessage() {
  const articlesContainer = document.getElementById("articlesContainer")
  const noPostsMessage = document.getElementById("noPostsMessage")

  if (articlesContainer) {
    articlesContainer.innerHTML = ""
  }

  if (noPostsMessage) {
    noPostsMessage.style.display = "block"
  }
}

function setupNewsletterForm() {
  const newsletterForm = document.getElementById("newsletterForm")
  if (!newsletterForm) return

  newsletterForm.addEventListener("submit", async function (e) {
    e.preventDefault()

    const emailInput = this.querySelector('input[type="email"]')
    const submitBtn = this.querySelector('button[type="submit"]')
    const originalText = submitBtn.innerHTML

    // Afficher l'état de chargement
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Inscription...'
    submitBtn.disabled = true

    try {
      // Simuler un appel API (remplacer par votre vraie API)
      await new Promise((resolve) => setTimeout(resolve, 1000))

      if (window.showToast) {
        window.showToast("Merci ! Vous êtes maintenant abonné à notre newsletter.", "success")
      } else {
        alert("Merci ! Vous êtes maintenant abonné à notre newsletter.")
      }

      this.reset()
    } catch (error) {
      console.error("Erreur newsletter:", error)
      if (window.showToast) {
        window.showToast("Erreur lors de l'inscription. Veuillez réessayer.", "error")
      } else {
        alert("Erreur lors de l'inscription. Veuillez réessayer.")
      }
    } finally {
      // Réinitialiser le bouton
      submitBtn.innerHTML = originalText
      submitBtn.disabled = false
    }
  })
}

// Fonction de recherche
function searchArticles(query) {
  if (!query.trim()) {
    displayArticles(allArticles)
    return
  }

  const filteredArticles = allArticles.filter((article) => {
    const searchText = `${article.title} ${article.excerpt} ${article.tags.join(" ")}`.toLowerCase()
    return searchText.includes(query.toLowerCase())
  })

  displayArticles(filteredArticles)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// GESTION DU PARTAGE SOCIAL (Pour article-template.html)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function updateSocialMetaTags(article) {
  // Mettre à jour les meta tags Open Graph pour le partage social
  
  // Open Graph
  updateOrCreateMetaTag("property", "og:title", article.title)
  updateOrCreateMetaTag("property", "og:description", article.excerpt)
  updateOrCreateMetaTag("property", "og:type", "article")
  updateOrCreateMetaTag("property", "og:url", window.location.href)
  
  if (article.image) {
    const imageUrl = article.image.startsWith("http") 
      ? article.image 
      : window.location.origin + article.image
    updateOrCreateMetaTag("property", "og:image", imageUrl)
    updateOrCreateMetaTag("property", "og:image:alt", article.imageAlt || article.title)
    updateOrCreateMetaTag("property", "og:image:width", "1200")
    updateOrCreateMetaTag("property", "og:image:height", "630")
  }
  
  // Twitter Card
  updateOrCreateMetaTag("name", "twitter:card", "summary_large_image")
  updateOrCreateMetaTag("name", "twitter:title", article.title)
  updateOrCreateMetaTag("name", "twitter:description", article.excerpt)
  
  if (article.image) {
    const imageUrl = article.image.startsWith("http") 
      ? article.image 
      : window.location.origin + article.image
    updateOrCreateMetaTag("name", "twitter:image", imageUrl)
    updateOrCreateMetaTag("name", "twitter:image:alt", article.imageAlt || article.title)
  }
  
  // Article meta
  if (article.date) {
    updateOrCreateMetaTag("property", "article:published_time", article.date)
  }
  if (article.tags && article.tags.length > 0) {
    article.tags.forEach(tag => {
      addMetaTag("property", "article:tag", tag)
    })
  }
}

function updateOrCreateMetaTag(attribute, attributeValue, content) {
  let metaTag = document.querySelector(`meta[${attribute}="${attributeValue}"]`)
  
  if (!metaTag) {
    metaTag = document.createElement("meta")
    metaTag.setAttribute(attribute, attributeValue)
    document.head.appendChild(metaTag)
  }
  
  metaTag.setAttribute("content", content)
}

function addMetaTag(attribute, attributeValue, content) {
  // Ajouter un meta tag sans supprimer les existants (pour les tags multiples)
  const metaTag = document.createElement("meta")
  metaTag.setAttribute(attribute, attributeValue)
  metaTag.setAttribute("content", content)
  document.head.appendChild(metaTag)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STYLES CSS ADDITIONNELS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// Ajouter les styles pour l'effet hover
const styleElement = document.createElement("style")
styleElement.textContent = `
  .hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
  }
  
  .bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  
  .card-img-top {
    border-top-left-radius: calc(0.375rem - 1px);
    border-top-right-radius: calc(0.375rem - 1px);
  }
`
document.head.appendChild(styleElement)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// INITIALISATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// Exporter les fonctions pour utilisation globale
window.searchArticles = searchArticles
window.filterArticles = filterArticles
window.updateSocialMetaTags = updateSocialMetaTags

// Initialiser au chargement de la page
document.addEventListener("DOMContentLoaded", async () => {
  await loadBlogContent()
  setupCategoryFilters()
  setupNewsletterForm()
  
  // Initialiser AOS si disponible
  if (window.AOS) {
    window.AOS.init({
      duration: 600,
      easing: "ease-out-cubic",
      once: true,
      offset: 50,
    })
  }
})
