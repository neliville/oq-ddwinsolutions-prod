# 📋 Checklist SEO - Pages Publiques

> **Objectif** : Optimiser le référencement de toutes les pages publiques du site

---

## 🎯 Pages à optimiser

### Pages principales
- [ ] Page d'accueil (`/`)
- [ ] Outil Ishikawa (`/ishikawa`)
- [ ] Outil 5 Pourquoi (`/5pourquoi`)
- [ ] Page Outils (`/outils`)
- [ ] Page Blog (`/blog`)
- [ ] Page Contact (`/contact`)
- [ ] Page Connexion (`/login`)
- [ ] Pages légales (`/politique-de-confidentialite`, `/mentions-legales`)

### Pages dynamiques
- [ ] Articles de blog (`/blog/{category}/{slug}`)
- [ ] Catégories de blog (si pages dédiées)
- [ ] Tags de blog (si pages dédiées)

---

## ✅ Checklist par élément SEO

### 1. Meta Tags HTML

#### Title Tag
- [ ] Présent sur toutes les pages
- [ ] Unique par page
- [ ] 50-60 caractères maximum
- [ ] Contient mots-clés principaux
- [ ] Format : `Mots-clés | Nom du site`

#### Meta Description
- [ ] Présent sur toutes les pages
- [ ] Unique par page
- [ ] 150-160 caractères
- [ ] Appelant à l'action
- [ ] Contient mots-clés pertinents

#### Meta Keywords
- [ ] Optionnel (pas prioritaire, peut être ignoré)

#### Meta Robots
- [ ] Configuré correctement (index, follow par défaut)
- [ ] Noindex pour pages privées (admin, dashboard)

---

### 2. Open Graph Tags (Facebook, LinkedIn, etc.)

- [ ] `og:title` - Titre de la page
- [ ] `og:description` - Description
- [ ] `og:image` - Image de partage (1200x630px recommandé)
- [ ] `og:url` - URL canonique
- [ ] `og:type` - Type de contenu (website, article, etc.)
- [ ] `og:site_name` - Nom du site
- [ ] `og:locale` - Langue (fr_FR)

---

### 3. Twitter Card Tags

- [ ] `twitter:card` - Type de card (summary, summary_large_image)
- [ ] `twitter:title` - Titre
- [ ] `twitter:description` - Description
- [ ] `twitter:image` - Image (1200x630px pour large_image)
- [ ] `twitter:site` - Compte Twitter (si applicable)
- [ ] `twitter:creator` - Auteur (si applicable)

---

### 4. Schema.org JSON-LD

#### Organisation (toutes les pages)
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Nom de l'organisation",
  "url": "https://www.site.com",
  "logo": "https://www.site.com/img/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "customer service"
  }
}
```

- [ ] Organisation schema dans header ou footer
- [ ] ContactPoint pour le support
- [ ] Logo de l'organisation

#### WebSite (page d'accueil)
```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Nom du site",
  "url": "https://www.site.com",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://www.site.com/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
```

- [ ] WebSite schema avec SearchAction (si recherche disponible)

#### Article (articles de blog)
```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "Titre de l'article",
  "image": "URL de l'image",
  "datePublished": "2024-12-20",
  "dateModified": "2024-12-20",
  "author": {
    "@type": "Person",
    "name": "Nom de l'auteur"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Nom de l'organisation",
    "logo": {
      "@type": "ImageObject",
      "url": "URL du logo"
    }
  }
}
```

- [ ] Article schema pour chaque article de blog
- [ ] Author schema
- [ ] Publisher schema avec logo

#### SoftwareApplication (outils Ishikawa et 5 Pourquoi)
```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Nom de l'outil",
  "applicationCategory": "WebApplication",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "ratingCount": "100"
  }
}
```

- [ ] SoftwareApplication schema pour outils
- [ ] Offers schema (gratuit)
- [ ] AggregateRating si applicable

#### BreadcrumbList (pages avec hiérarchie)
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Accueil",
      "item": "https://www.site.com"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Blog",
      "item": "https://www.site.com/blog"
    }
  ]
}
```

- [ ] BreadcrumbList schema pour pages avec navigation hiérarchique

---

### 5. Sitemap et Robots.txt

#### Sitemap.xml
- [ ] Sitemap dynamique généré depuis les routes
- [ ] Toutes les pages publiques incluses
- [ ] Articles de blog publiés inclus
- [ ] Priorités définies (1.0 pour homepage, 0.8 pour outils, 0.6 pour articles)
- [ ] Fréquences de mise à jour (weekly, monthly)
- [ ] Dernière date de modification

#### Robots.txt
- [ ] Fichier robots.txt présent
- [ ] User-agent: * configuré
- [ ] Sitemap URL ajoutée : `Sitemap: https://www.site.com/sitemap.xml`
- [ ] Pages admin bloquées : `Disallow: /admin`
- [ ] Pages privées bloquées : `Disallow: /dashboard`

---

### 6. URLs SEO-friendly

- [ ] URLs avec slugs pour blog : `/blog/{category}/{slug}`
- [ ] URLs propres sans paramètres inutiles
- [ ] Pas de caractères spéciaux dans les URLs
- [ ] Redirections 301 pour anciennes URLs si migration
- [ ] URLs canoniques sur toutes les pages

---

### 7. Contenu et Structure

#### Balises HTML5 sémantiques
- [ ] `<header>` pour l'en-tête
- [ ] `<nav>` pour la navigation
- [ ] `<main>` pour le contenu principal
- [ ] `<article>` pour les articles de blog
- [ ] `<section>` pour les sections de contenu
- [ ] `<aside>` pour les barres latérales
- [ ] `<footer>` pour le pied de page

#### Hiérarchie des titres
- [ ] Un seul `<h1>` par page
- [ ] Hiérarchie cohérente (h1 → h2 → h3)
- [ ] Titres descriptifs avec mots-clés

#### Images
- [ ] Toutes les images ont l'attribut `alt`
- [ ] Alt text descriptif et contextuel
- [ ] Images optimisées (compression, formats modernes)
- [ ] Lazy loading pour images en dessous de la ligne de flottaison

#### Liens
- [ ] Liens internes pertinents
- [ ] Anchor text descriptif
- [ ] Pas de liens cassés
- [ ] Liens externes avec `rel="nofollow"` si nécessaire

---

### 8. Performance et Technique

#### Temps de chargement
- [ ] Page load < 3 secondes
- [ ] First Contentful Paint optimisé
- [ ] Time to Interactive optimisé

#### Mobile-Friendly
- [ ] Viewport meta tag présent
- [ ] Responsive design sur tous les appareils
- [ ] Test Google Mobile-Friendly positif

#### Accessibilité
- [ ] Contraste des couleurs (WCAG AA minimum)
- [ ] Navigation au clavier fonctionnelle
- [ ] Attributs ARIA appropriés
- [ ] Labels pour tous les formulaires

---

### 9. Contenu SEO

#### Mots-clés
- [ ] Recherche de mots-clés effectuée
- [ ] Mots-clés intégrés naturellement dans le contenu
- [ ] Long-tail keywords pour niche

#### Contenu riche
- [ ] Contenu unique et de qualité sur chaque page
- [ ] Minimum 300 mots pour pages importantes
- [ ] Articles de blog minimum 800-1200 mots

---

### 10. Liens et Réseaux

#### Liens internes
- [ ] Maillage interne cohérent
- [ ] Liens contextuels dans les articles
- [ ] Navigation claire et logique

#### Liens externes (futur)
- [ ] Backlinks de qualité
- [ ] Profil de liens naturel

---

## 📊 Outils de validation

### Outils à utiliser
- [ ] Google Search Console (verification)
- [ ] Google PageSpeed Insights
- [ ] Schema.org Validator
- [ ] Open Graph Debugger (Facebook)
- [ ] Twitter Card Validator
- [ ] W3C Validator (HTML)
- [ ] Lighthouse (Chrome DevTools)

---

## 🎯 Priorités d'implémentation

1. **Priorité 1** : Meta tags (title, description) sur toutes les pages
2. **Priorité 2** : Schema.org (Organization, WebSite, Article)
3. **Priorité 3** : Open Graph et Twitter Cards
4. **Priorité 4** : Sitemap.xml dynamique
5. **Priorité 5** : Optimisations techniques (images, performance)

---

## 📝 Notes

- Le SEO est un travail continu, pas une tâche unique
- Surveiller les performances via Google Search Console
- Adapter les stratégies selon les résultats
- Contenu de qualité > techniques SEO


