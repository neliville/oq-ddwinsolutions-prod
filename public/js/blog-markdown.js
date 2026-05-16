// Système de lecture des fichiers Markdown par catégorie
class BlogMarkdownReader {
  constructor() {
    this.categories = {
      "amelioration-continue": "Amélioration continue",
      methodologie: "Méthodologies",
      outils: "Outils d'analyse",
      collaboration: "Travail en équipe",
      lean: "Lean Management",
      qualite: "Qualité",
    }
    this.articles = []
    this.currentCategory = "all"
    this.baseUrl = window.location.origin
  }

  async init() {
    await this.loadAllArticles()
    this.renderArticles()
    this.initializeFilters()
    this.initializeRouting()
  }

  async loadAllArticles() {
    // Charger les articles depuis les données statiques (simulation)
    this.articles = [
      {
        id: "introduction-amelioration-continue",
        title: "Introduction à l'amélioration continue",
        excerpt:
          "Découvrez les principes fondamentaux de l'amélioration continue et comment l'implémenter dans votre organisation pour optimiser vos processus.",
        author: "Équipe OUTILS-QUALITÉ",
        date: "2024-01-15",
        readTime: "8 min",
        views: 245,
        category: "amelioration-continue",
        categoryName: "Amélioration continue",
        tags: ["amélioration continue", "kaizen", "lean", "qualité"],
        image: "🔄",
        filename: "introduction-amelioration-continue.md",
        content: this.getArticleContent("introduction-amelioration-continue"),
      },
      {
        id: "cycle-pdca-pratique",
        title: "Le cycle PDCA en pratique",
        excerpt:
          "Guide pratique pour appliquer le cycle Plan-Do-Check-Act dans vos projets d'amélioration continue avec des exemples concrets.",
        author: "Expert Qualité",
        date: "2024-01-18",
        readTime: "6 min",
        views: 189,
        category: "amelioration-continue",
        categoryName: "Amélioration continue",
        tags: ["PDCA", "amélioration", "méthode"],
        image: "🔄",
        filename: "cycle-pdca-pratique.md",
        content: this.getArticleContent("cycle-pdca-pratique"),
      },
      {
        id: "lean-manufacturing-bases",
        title: "Lean Manufacturing : Les bases",
        excerpt:
          "Introduction aux principes du Lean Manufacturing et aux outils pour éliminer les gaspillages dans vos processus de production.",
        author: "Consultant Lean",
        date: "2024-01-22",
        readTime: "10 min",
        views: 312,
        category: "amelioration-continue",
        categoryName: "Amélioration continue",
        tags: ["lean", "manufacturing", "gaspillage"],
        image: "🔄",
        filename: "lean-manufacturing-bases.md",
        content: this.getArticleContent("lean-manufacturing-bases"),
      },
      {
        id: "ishikawa-guide-complet",
        title: "Maîtriser le diagramme Ishikawa - Guide complet",
        excerpt:
          "Guide pratique pour créer et utiliser efficacement le diagramme d'Ishikawa dans vos analyses de causes racines avec notre outil interactif.",
        author: "Expert Qualité",
        date: "2024-01-20",
        readTime: "12 min",
        views: 189,
        category: "methodologie",
        categoryName: "Méthodologies",
        tags: ["ishikawa", "analyse causes", "résolution problèmes", "qualité"],
        image: "📊",
        filename: "ishikawa-guide-complet.md",
        content: this.getArticleContent("ishikawa-guide-complet"),
      },
      {
        id: "5-pourquoi-bonnes-pratiques",
        title: "5 Pourquoi : Bonnes pratiques et pièges à éviter",
        excerpt:
          "Maîtrisez la méthode des 5 Pourquoi avec nos conseils d'experts et évitez les erreurs courantes dans vos analyses de causes racines.",
        author: "Consultant Qualité",
        date: "2024-01-25",
        readTime: "7 min",
        views: 156,
        category: "methodologie",
        categoryName: "Méthodologies",
        tags: ["5 pourquoi", "analyse", "causes racines"],
        image: "📊",
        filename: "5-pourquoi-bonnes-pratiques.md",
        content: this.getArticleContent("5-pourquoi-bonnes-pratiques"),
      },
    ]
  }

  getArticleContent(articleId) {
    const contents = {
      "introduction-amelioration-continue": `
# Introduction à l'amélioration continue

L'amélioration continue est une philosophie de gestion qui vise à améliorer constamment les processus, produits et services d'une organisation. Cette approche, également connue sous le nom de **Kaizen** en japonais, est devenue un pilier fondamental de la gestion moderne.

## Qu'est-ce que l'amélioration continue ?

L'amélioration continue repose sur l'idée que de petites améliorations régulières peuvent conduire à des gains significatifs à long terme. Plutôt que d'attendre des changements majeurs, cette approche encourage :

* **L'identification constante** des opportunités d'amélioration
* **L'implication de tous les employés** dans le processus
* **L'expérimentation** et l'apprentissage continu
* **La mesure et l'évaluation** des résultats

## Les principes fondamentaux

### 1. La culture du changement

L'amélioration continue nécessite une culture d'entreprise qui :
- Encourage l'innovation et la prise d'initiative
- Accepte l'échec comme une opportunité d'apprentissage
- Valorise la collaboration et le partage de connaissances

### 2. L'approche systématique

Utilisation d'outils et de méthodologies structurées comme :
- Le cycle PDCA (Plan-Do-Check-Act)
- Les outils de résolution de problèmes
- Les indicateurs de performance

### 3. L'engagement de la direction

Le leadership doit :
- Donner l'exemple en participant activement
- Allouer les ressources nécessaires
- Reconnaître et récompenser les efforts d'amélioration

## Bénéfices de l'amélioration continue

L'implémentation d'une démarche d'amélioration continue apporte de nombreux avantages :

**Pour l'organisation :**
- Réduction des coûts et des gaspillages
- Amélioration de la qualité des produits/services
- Augmentation de la productivité
- Meilleure réactivité face aux changements du marché

**Pour les employés :**
- Développement des compétences
- Augmentation de la motivation et de l'engagement
- Sentiment d'appartenance renforcé
- Opportunités de croissance professionnelle

## Comment commencer ?

### Étape 1 : Évaluation de l'état actuel
Analysez vos processus existants pour identifier les points d'amélioration prioritaires.

### Étape 2 : Formation et sensibilisation
Formez vos équipes aux outils et méthodes d'amélioration continue.

### Étape 3 : Mise en place d'un système de suggestion
Créez un canal pour que les employés puissent proposer des améliorations.

### Étape 4 : Implémentation progressive
Commencez par des projets pilotes avant de généraliser l'approche.

## Outils recommandés

Pour vous accompagner dans votre démarche, utilisez nos outils gratuits :

- **[Analyse des causes](/ishikawa.html)** : Identifiez les causes racines des problèmes
- **[Méthode des 5 Pourquoi](/5pourquoi.html)** : Creusez en profondeur pour trouver la vraie cause

## Conclusion

L'amélioration continue n'est pas une destination, mais un voyage. Elle demande de la patience, de la persévérance et un engagement à long terme. Cependant, les organisations qui embrassent cette philosophie sont mieux positionnées pour prospérer dans un environnement en constante évolution.

Commencez dès aujourd'hui par identifier un petit processus à améliorer et utilisez nos outils pour vous guider dans cette démarche !
      `,
      "ishikawa-guide-complet": `
# Maîtriser le diagramme Ishikawa - Guide complet

Le diagramme d'Ishikawa, également appelé diagramme en arête de poisson ou diagramme de causes et effets, est l'un des outils les plus puissants pour l'analyse des causes racines. Développé par Kaoru Ishikawa dans les années 1960, cet outil visuel aide les équipes à identifier systématiquement toutes les causes possibles d'un problème.

## Qu'est-ce que le diagramme d'Ishikawa ?

Le diagramme d'Ishikawa est une représentation graphique qui ressemble à un squelette de poisson, d'où son surnom. Il se compose de :

* **La tête du poisson** : le problème ou l'effet à analyser
* **L'épine dorsale** : la ligne principale qui relie la tête aux causes
* **Les arêtes** : les catégories de causes principales
* **Les sous-arêtes** : les causes spécifiques dans chaque catégorie

## Les catégories classiques (5M + E)

### 1. **Matières** (Matériaux)
- Qualité des matières premières
- Spécifications des composants
- Conditions de stockage
- Fournisseurs

### 2. **Machines** (Équipements)
- État des équipements
- Maintenance préventive
- Calibrage des instruments
- Capacité de production

### 3. **Méthodes** (Procédures)
- Procédures de travail
- Instructions techniques
- Standards de qualité
- Formation du personnel

### 4. **Main-d'œuvre** (Personnel)
- Compétences et formation
- Motivation et engagement
- Charge de travail
- Communication

### 5. **Mesure** (Contrôle)
- Systèmes de mesure
- Indicateurs de performance
- Fréquence des contrôles
- Précision des instruments

### 6. **Environnement** (Milieu)
- Conditions de travail
- Température et humidité
- Éclairage et bruit
- Espace de travail

## Méthodologie de construction

### Étape 1 : Définir le problème
Formulez clairement le problème à analyser. Il doit être :
- **Spécifique** : bien défini et mesurable
- **Observable** : basé sur des faits
- **Significatif** : ayant un impact réel

### Étape 2 : Constituer l'équipe
Rassemblez une équipe multidisciplinaire incluant :
- Des personnes directement concernées par le problème
- Des experts techniques
- Des représentants de différents services

### Étape 3 : Brainstorming des causes
Pour chaque catégorie, listez toutes les causes possibles :
- Encouragez la créativité
- Ne jugez pas les idées
- Construisez sur les idées des autres
- Visez la quantité avant la qualité

### Étape 4 : Organiser et hiérarchiser
- Regroupez les causes similaires
- Identifiez les causes principales et secondaires
- Priorisez selon l'impact et la probabilité

## Bonnes pratiques

### ✅ À faire
- **Impliquer toute l'équipe** dans la construction
- **Utiliser des données factuelles** plutôt que des opinions
- **Creuser en profondeur** avec des sous-causes
- **Valider les causes** par des preuves
- **Mettre à jour régulièrement** le diagramme

### ❌ À éviter
- Se limiter aux causes évidentes
- Confondre causes et symptômes
- Négliger certaines catégories
- Arrêter l'analyse trop tôt
- Oublier de documenter le processus

## Utilisation avec notre outil

Notre **[outil Ishikawa interactif](/ishikawa.html)** vous permet de :

1. **Créer facilement** votre diagramme
2. **Déplacer les catégories** selon vos besoins
3. **Ajouter/modifier** les causes en temps réel
4. **Exporter** en PDF ou JPEG pour vos présentations

### Fonctionnalités avancées
- Jusqu'à 7 catégories personnalisables
- Drag & drop pour repositionner les éléments
- Sauvegarde automatique de votre travail
- Export professionnel avec métadonnées

## Cas d'usage pratiques

### Exemple 1 : Retards de livraison
**Problème** : Les livraisons clients sont en retard de 2 jours en moyenne

**Causes identifiées :**
- *Matières* : Retards fournisseurs, qualité non conforme
- *Machines* : Pannes fréquentes, maintenance insuffisante
- *Méthodes* : Planification inadéquate, processus non optimisés
- *Main-d'œuvre* : Manque de formation, absentéisme
- *Mesure* : Suivi insuffisant, indicateurs inadaptés
- *Environnement* : Espace de stockage insuffisant

### Exemple 2 : Défauts qualité
**Problème** : Taux de défauts supérieur à 3%

**Analyse par catégories :**
- Identification des causes techniques et humaines
- Priorisation selon l'impact sur la qualité
- Plan d'action ciblé sur les causes principales

## Complémentarité avec d'autres outils

Le diagramme d'Ishikawa fonctionne parfaitement avec :

- **[Méthode des 5 Pourquoi](/5pourquoi.html)** : pour approfondir chaque cause
- **Analyse de Pareto** : pour prioriser les causes (bientôt disponible)
- **Plans d'action 5W2H** : pour structurer les solutions

## Conseils d'expert

### Pour une analyse efficace :
1. **Limitez la session** à 1-2 heures maximum
2. **Documentez tout** pendant la séance
3. **Assignez un facilitateur** neutre
4. **Suivez une méthode** structurée
5. **Planifiez les actions** de vérification

### Pour maintenir l'engagement :
- Alternez entre réflexion individuelle et collective
- Utilisez des techniques de créativité
- Visualisez les idées en temps réel
- Célébrez les découvertes importantes

## Conclusion

Le diagramme d'Ishikawa est un outil puissant qui, bien utilisé, peut transformer votre approche de résolution de problèmes. La clé du succès réside dans la rigueur de la méthode et l'implication de toute l'équipe dans le processus d'analyse.

**Testez dès maintenant** notre outil interactif et découvrez comment le diagramme d'Ishikawa peut révolutionner votre approche de l'analyse des causes !

---

*Cet article vous a été utile ? Partagez-le avec votre équipe et n'hésitez pas à nous faire part de vos retours d'expérience.*
      `,
    }

    return contents[articleId] || "Contenu de l'article en cours de rédaction..."
  }

  renderArticles() {
    const grid = document.getElementById("blogPostsGrid")
    const noPostsMessage = document.getElementById("noPostsMessage")

    if (!grid) return

    const filteredArticles =
      this.currentCategory === "all"
        ? this.articles
        : this.articles.filter((article) => article.category === this.currentCategory)

    grid.innerHTML = ""

    if (filteredArticles.length === 0) {
      if (noPostsMessage) noPostsMessage.style.display = "block"
      return
    }

    if (noPostsMessage) noPostsMessage.style.display = "none"

    filteredArticles.forEach((article, index) => {
      const articleElement = this.createArticleElement(article, index)
      grid.appendChild(articleElement)
    })

  }

  createArticleElement(article, index) {
    const articleDiv = document.createElement("div")
    articleDiv.className = "col-lg-4 col-md-6"

    articleDiv.innerHTML = `
      <article class="card border-0 shadow-sm h-100 article-card">
        <div class="card-header pb-4 border-0 bg-white">
          <div class="text-center mb-4">
            <div class="text-4xl mb-3">${article.image}</div>
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="badge bg-primary">${article.categoryName}</span>
              <div class="d-flex align-items-center gap-1 text-sm text-muted">
                <i class="fas fa-eye"></i>
                <span>${article.views}</span>
              </div>
            </div>
          </div>
          <h3 class="h5 fw-bold mb-3 article-title">${article.title}</h3>
          <p class="text-muted mb-0">${article.excerpt}</p>
        </div>

        <div class="card-body pt-0">
          <div class="d-flex align-items-center justify-content-between text-sm text-muted mb-4">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center gap-1">
                <i class="fas fa-calendar"></i>
                <span>${new Date(article.date).toLocaleDateString("fr-FR")}</span>
              </div>
              <div class="d-flex align-items-center gap-1">
                <i class="fas fa-clock"></i>
                <span>${article.readTime}</span>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-3">
            <button class="btn btn-primary" onclick="blogReader.showArticleDetail('${article.id}')">
              <i class="fas fa-book-open me-2"></i>Lire l'article
            </button>

            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-primary" onclick="blogReader.shareArticle('facebook', '${article.id}')" title="Partager sur Facebook">
                <i class="fab fa-facebook"></i>
              </button>
              <button class="btn btn-sm btn-outline-info" onclick="blogReader.shareArticle('twitter', '${article.id}')" title="Partager sur Twitter">
                <i class="fab fa-twitter"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary" onclick="blogReader.shareArticle('linkedin', '${article.id}')" title="Partager sur LinkedIn">
                <i class="fab fa-linkedin"></i>
              </button>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-1">
            ${article.tags
              .slice(0, 3)
              .map((tag) => `<span class="badge bg-secondary">${tag}</span>`)
              .join("")}
          </div>
        </div>
      </article>
    `

    return articleDiv
  }

  showArticleDetail(articleId) {
    const article = this.articles.find((a) => a.id === articleId)
    if (!article) return

    // Incrémenter les vues
    article.views++

    // Mettre à jour l'URL sans recharger la page
    const newUrl = `${this.baseUrl}/blog/${article.category}/${article.id}.html`
    window.history.pushState({ articleId }, article.title, newUrl)

    // Mettre à jour le titre de la page
    document.title = `${article.title} | Blog OUTILS-QUALITÉ`

    // Mettre à jour les meta tags
    this.updateMetaTags(article)

    const blogContainer = document.getElementById("blog")
    if (!blogContainer) return

    blogContainer.innerHTML = `
      <div class="min-vh-100 bg-white">
        <div class="bg-gradient-primary text-white py-5">
          <div class="container">
            <div class="row">
              <div class="col-lg-8 mx-auto">
                <button class="btn btn-outline-light mb-4" onclick="blogReader.showBlogList()">
                  <i class="fas fa-arrow-left me-2"></i>Retour au blog
                </button>
                <div class="text-center mb-4">
                  <div class="display-1 mb-3">${article.image}</div>
                  <h1 class="display-4 fw-bold mb-4">${article.title}</h1>
                  <div class="d-flex flex-wrap justify-content-center gap-4 text-sm opacity-90">
                    <div class="d-flex align-items-center gap-2">
                      <i class="fas fa-user"></i>
                      <span>${article.author}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <i class="fas fa-calendar"></i>
                      <span>${new Date(article.date).toLocaleDateString("fr-FR")}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <i class="fas fa-clock"></i>
                      <span>${article.readTime}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <i class="fas fa-eye"></i>
                      <span>${article.views}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="container py-5">
          <div class="row">
            <div class="col-lg-8 mx-auto">
              <!-- Social Share -->
              <div class="card mb-5 border-0 shadow-sm">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold">Partager cet article</h5>
                    <div class="d-flex gap-2">
                      <button class="btn btn-outline-primary btn-sm" onclick="blogReader.shareArticle('facebook', '${article.id}')">
                        <i class="fab fa-facebook me-2"></i>Facebook
                      </button>
                      <button class="btn btn-outline-info btn-sm" onclick="blogReader.shareArticle('twitter', '${article.id}')">
                        <i class="fab fa-twitter me-2"></i>Twitter
                      </button>
                      <button class="btn btn-outline-primary btn-sm" onclick="blogReader.shareArticle('linkedin', '${article.id}')">
                        <i class="fab fa-linkedin me-2"></i>LinkedIn
                      </button>
                      <button class="btn btn-outline-secondary btn-sm" onclick="blogReader.shareArticle('copy', '${article.id}')">
                        <i class="fas fa-link me-2"></i>Copier le lien
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Article Content -->
              <div class="article-content">
                ${this.markdownToHtml(article.content)}
              </div>

              <!-- Tags -->
              <div class="mt-5 pt-4 border-top">
                <h5 class="mb-3 fw-bold">Tags</h5>
                <div class="d-flex flex-wrap gap-2">
                  ${article.tags.map((tag) => `<span class="badge bg-primary fs-6"><i class="fas fa-tag me-1"></i>${tag}</span>`).join("")}
                </div>
              </div>

              <!-- Related Articles -->
              <div class="mt-5 pt-4 border-top">
                <h5 class="mb-4 fw-bold">Articles similaires</h5>
                <div class="row g-3">
                  ${this.getRelatedArticles(article, 3)
                    .map(
                      (relatedArticle) => `
                    <div class="col-md-4">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                          <div class="text-center mb-2 fs-2">${relatedArticle.image}</div>
                          <h6 class="fw-bold mb-2">${relatedArticle.title}</h6>
                          <p class="text-muted small mb-3">${relatedArticle.excerpt.substring(0, 80)}...</p>
                          <button class="btn btn-outline-primary btn-sm w-100" onclick="blogReader.showArticleDetail('${relatedArticle.id}')">
                            <i class="fas fa-book-open me-1"></i>Lire
                          </button>
                        </div>
                      </div>
                    </div>
                  `,
                    )
                    .join("")}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `

    // Scroll to top
    window.scrollTo(0, 0)
  }

  getRelatedArticles(currentArticle, count = 3) {
    return this.articles
      .filter(
        (article) =>
          article.id !== currentArticle.id &&
          (article.category === currentArticle.category ||
            article.tags.some((tag) => currentArticle.tags.includes(tag))),
      )
      .slice(0, count)
  }

  updateMetaTags(article) {
    // Mettre à jour les meta tags pour le SEO
    const description = document.querySelector('meta[name="description"]')
    if (description) {
      description.setAttribute("content", article.excerpt)
    }

    const ogTitle = document.querySelector('meta[property="og:title"]')
    if (ogTitle) {
      ogTitle.setAttribute("content", `${article.title} | Blog OUTILS-QUALITÉ`)
    }

    const ogDescription = document.querySelector('meta[property="og:description"]')
    if (ogDescription) {
      ogDescription.setAttribute("content", article.excerpt)
    }

    const twitterTitle = document.querySelector('meta[property="twitter:title"]')
    if (twitterTitle) {
      twitterTitle.setAttribute("content", `${article.title} | Blog OUTILS-QUALITÉ`)
    }

    const twitterDescription = document.querySelector('meta[property="twitter:description"]')
    if (twitterDescription) {
      twitterDescription.setAttribute("content", article.excerpt)
    }
  }

  showBlogList() {
    // Restaurer l'URL du blog
    window.history.pushState({}, "Blog Qualité | OUTILS-QUALITÉ", `${this.baseUrl}/blog.html`)

    // Restaurer le titre
    document.title = "Blog Qualité | OUTILS-QUALITÉ"

    // Recharger la page blog
    window.location.reload()
  }

  shareArticle(platform, articleId) {
    const article = this.articles.find((a) => a.id === articleId)
    if (!article) return

    const url = `${this.baseUrl}/blog/${article.category}/${article.id}.html`
    const text = `${article.title} - ${article.excerpt.substring(0, 100)}...`

    let shareUrl = ""

    switch (platform) {
      case "facebook":
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
        break
      case "twitter":
        shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`
        break
      case "linkedin":
        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`
        break
      case "copy":
        navigator.clipboard.writeText(url)
        this.showNotification("Lien copié dans le presse-papiers !", "success")
        return
    }

    if (shareUrl) {
      window.open(shareUrl, "_blank", "width=600,height=400")
    }
  }

  markdownToHtml(markdown) {
    // Parser Markdown simple amélioré
    let html = markdown

    // Titres
    html = html.replace(/^### (.*$)/gim, "<h3 class='h4 fw-bold mt-4 mb-3'>$1</h3>")
    html = html.replace(/^## (.*$)/gim, "<h2 class='h3 fw-bold mt-5 mb-4'>$1</h2>")
    html = html.replace(/^# (.*$)/gim, "<h1 class='h2 fw-bold mt-5 mb-4'>$1</h1>")

    // Gras et italique
    html = html.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
    html = html.replace(/\*(.*?)\*/g, "<em>$1</em>")

    // Liens
    html = html.replace(
      /\[([^\]]+)\]$$([^)]+)$$/g,
      '<a href="$2" class="text-primary text-decoration-none fw-semibold">$1</a>',
    )

    // Listes
    html = html.replace(/^\* (.*$)/gim, "<li class='mb-2'>$1</li>")
    html = html.replace(/^- (.*$)/gim, "<li class='mb-2'>$1</li>")
    html = html.replace(/(<li>.*<\/li>)/s, "<ul class='list-unstyled ps-3'>$1</ul>")

    // Code inline
    html = html.replace(/`([^`]+)`/g, "<code class='bg-light px-2 py-1 rounded'>$1</code>")

    // Blocs de code
    html = html.replace(/```([^`]+)```/g, "<pre class='bg-light p-3 rounded'><code>$1</code></pre>")

    // Paragraphes
    html = html.replace(/\n\n/g, "</p><p class='mb-4'>")
    html = "<p class='mb-4'>" + html + "</p>"

    // Nettoyer les paragraphes vides
    html = html.replace(/<p class='mb-4'><\/p>/g, "")

    return html
  }

  initializeFilters() {
    const filterButtons = document.querySelectorAll("#categoryFilters button")
    filterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        // Mettre à jour les boutons actifs
        filterButtons.forEach((btn) => {
          btn.classList.remove("active", "btn-primary")
          btn.classList.add("btn-outline-primary")
        })

        button.classList.add("active", "btn-primary")
        button.classList.remove("btn-outline-primary")

        // Filtrer les articles
        this.currentCategory = button.dataset.category
        this.renderArticles()
      })
    })
  }

  initializeRouting() {
    // Gérer les boutons précédent/suivant du navigateur
    window.addEventListener("popstate", (event) => {
      if (event.state && event.state.articleId) {
        this.showArticleDetail(event.state.articleId)
      } else {
        this.showBlogList()
      }
    })

    // Vérifier si on arrive directement sur un article
    const path = window.location.pathname
    const articleMatch = path.match(/\/blog\/([^/]+)\/([^/]+)\.html/)
    if (articleMatch) {
      const [, category, articleId] = articleMatch
      const article = this.articles.find((a) => a.id === articleId && a.category === category)
      if (article) {
        setTimeout(() => this.showArticleDetail(articleId), 100)
      }
    }
  }

  showNotification(message, type = "success") {
    if (typeof window.appNotify === "function") {
      window.appNotify(message, type)
      return
    }

    if (typeof window.showToast === "function") {
      window.showToast(message, type)
    }
  }
}

// Categories configuration
const blogCategories = [
  {
    id: "amelioration-continue",
    name: "Amélioration Continue",
    color: "success",
    icon: "trending-up",
  },
  {
    id: "methodologie",
    name: "Méthodologie",
    color: "primary",
    icon: "book-open",
  },
  {
    id: "outils",
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

const allArticles = []
let currentFilter = "all"

async function loadBlogContent() {
  const articlesContainer = document.getElementById("articlesContainer")
  const categoryFilters = document.getElementById("categoryFilters")

  if (!articlesContainer) return

  try {
    // Load articles from each category
    for (const category of blogCategories) {
      try {
        const response = await fetch(`/blog/${category.id}/index.json`)
        if (response.ok) {
          const categoryData = await response.json()

          // Add category info to each article
          categoryData.articles.forEach((article) => {
            article.category = category
            article.categoryId = category.id
            allArticles.push(article)
          })
        }
      } catch (error) {
        console.log(`Category ${category.id} not found, skipping...`)
      }
    }

    // Sort articles by date (newest first)
    allArticles.sort((a, b) => new Date(b.date) - new Date(a.date))

    // Create category filters
    createCategoryFilters()

    // Display articles
    displayArticles(allArticles)
  } catch (error) {
    console.error("Error loading blog content:", error)
    showNoPostsMessage()
  }
}

function createCategoryFilters() {
  const categoryFilters = document.getElementById("categoryFilters")
  if (!categoryFilters) return

  // Clear existing filters except "All"
  const allButton = categoryFilters.querySelector('[data-category="all"]')
  categoryFilters.innerHTML = ""
  categoryFilters.appendChild(allButton)

  // Add category filters for categories that have articles
  const categoriesWithArticles = [...new Set(allArticles.map((article) => article.categoryId))]

  categoriesWithArticles.forEach((categoryId) => {
    const category = blogCategories.find((cat) => cat.id === categoryId)
    if (category) {
      const button = document.createElement("button")
      button.className = `btn btn-outline-${category.color} me-2`
      button.setAttribute("data-category", categoryId)
      button.innerHTML = `
                <i data-lucide="${category.icon}" width="16" height="16" class="me-1"></i>
                ${category.name}
            `
      categoryFilters.appendChild(button)
    }
  })

  // Initialize Lucide icons
  if (window.lucide) {
    window.lucide.createIcons()
  }
}

function setupCategoryFilters() {
  const categoryFilters = document.getElementById("categoryFilters")
  if (!categoryFilters) return

  categoryFilters.addEventListener("click", (e) => {
    const button = e.target.closest("button[data-category]")
    if (!button) return

    const category = button.getAttribute("data-category")

    // Update active state
    categoryFilters.querySelectorAll("button").forEach((btn) => {
      btn.classList.remove("btn-primary", "btn-success", "btn-info", "btn-warning", "btn-danger")
      btn.classList.add(
        "btn-outline-primary",
        "btn-outline-success",
        "btn-outline-info",
        "btn-outline-warning",
        "btn-outline-danger",
      )
    })

    button.classList.remove(
      "btn-outline-primary",
      "btn-outline-success",
      "btn-outline-info",
      "btn-outline-warning",
      "btn-outline-danger",
    )

    if (category === "all") {
      button.classList.add("btn-primary")
    } else {
      const categoryData = blogCategories.find((cat) => cat.id === category)
      if (categoryData) {
        button.classList.add(`btn-${categoryData.color}`)
      }
    }

    // Filter articles
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

function displayArticles(articles) {
  const articlesContainer = document.getElementById("articlesContainer")
  const noPostsMessage = document.getElementById("noPostsMessage")

  if (!articlesContainer) return

  if (articles.length === 0) {
    showNoPostsMessage()
    return
  }

  // Hide no posts message
  if (noPostsMessage) {
    noPostsMessage.style.display = "none"
  }

  articlesContainer.innerHTML = ""

  articles.forEach((article, index) => {
    const articleCard = createArticleCard(article, index)
    articlesContainer.appendChild(articleCard)
  })

  // Initialize Lucide icons
  if (window.lucide) {
    window.lucide.createIcons()
  }
}

function createArticleCard(article, index) {
  const cardDiv = document.createElement("div")
  cardDiv.className = "col-lg-6 col-xl-4"

  const formattedDate = new Date(article.date).toLocaleDateString("fr-FR", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })

  cardDiv.innerHTML = `
        <article class="card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-${article.category.color} me-2">
                        <i data-lucide="${article.category.icon}" width="14" height="14" class="me-1"></i>
                        ${article.category.name}
                    </span>
                    <small class="text-muted">${formattedDate}</small>
                </div>
                
                <h3 class="card-title h5 fw-bold mb-3">
                    <a href="/blog/${article.category.slug}/${article.slug}" 
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
                
                ${article.featured ? '<div class="position-absolute top-0 end-0 p-2"><i data-lucide="star" width="20" height="20" class="text-warning"></i></div>' : ""}
            </div>
        </article>
    `

  return cardDiv
}

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

    // Show loading state
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Inscription...'
    submitBtn.disabled = true

    try {
      // Simulate API call (replace with actual newsletter API)
      await new Promise((resolve) => setTimeout(resolve, 1000))

      if (window.showToast) {
        window.showToast("Merci ! Vous êtes maintenant abonné à notre newsletter.", "success")
      }

      this.reset()
    } catch (error) {
      console.error("Newsletter error:", error)
      if (window.showToast) {
        window.showToast("Erreur lors de l'inscription. Veuillez réessayer.", "error")
      }
    } finally {
      // Reset button
      submitBtn.innerHTML = originalText
      submitBtn.disabled = false
    }
  })
}

// Search functionality (if needed)
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

// Export functions for global use
window.searchArticles = searchArticles
window.filterArticles = filterArticles

// Initialiser le lecteur de blog
let blogReader = null

document.addEventListener("DOMContentLoaded", async () => {
  await loadBlogContent()
  setupCategoryFilters()
  setupNewsletterForm()
  if (document.getElementById("blogPostsGrid")) {
    blogReader = new BlogMarkdownReader()
    blogReader.init()
  }
})
