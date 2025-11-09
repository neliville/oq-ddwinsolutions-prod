# 🎯 Guide d'Intégration Symfony - Diagramme Ishikawa v3

## 📋 Vue d'ensemble

Ce guide vous permettra de transformer votre page actuelle (layout en cartes) en diagramme Ishikawa interactif v3 avec causes draggables.

**Design actuel :** Layout en cartes statiques
**Design cible :** Diagramme Ishikawa dynamique avec Canvas HTML5

---

## 🗂️ Structure du Projet

```
votre-projet-symfony/
│
├── src/
│   ├── Controller/
│   │   └── IshikawaController.php          ✅ À créer/modifier
│   │
│   └── Entity/
│       ├── Diagram.php                      ✅ Déjà fourni
│       ├── Category.php                     ✅ Déjà fourni
│       └── Cause.php                        ✅ Déjà fourni
│
├── templates/
│   └── ishikawa/
│       ├── index.html.twig                  ✅ À créer (remplace votre page actuelle)
│       └── partials/
│           └── diagram_canvas.html.twig     ✅ À créer
│
├── public/
│   ├── css/
│   │   └── ishikawa-canvas.css              ✅ À créer (extrait du HTML v3)
│   │
│   └── js/
│       ├── ishikawa-canvas.js               ✅ À créer (logique de dessin)
│       └── ishikawa-interactions.js         ✅ À créer (drag & drop)
│
└── assets/                                   (Si Webpack Encore)
    ├── styles/
    │   └── ishikawa.scss
    └── js/
        └── ishikawa-app.js
```

---

## 📝 Étape 1 : Préparer les Entités (Déjà fait ✅)

Vous avez déjà les 3 entités nécessaires :
- `Diagram.php`
- `Category.php`
- `Cause.php`

**Action :** Vérifier qu'elles sont bien en place et que les migrations sont appliquées.

```bash
php bin/console doctrine:schema:validate
```

---

## 📝 Étape 2 : Créer/Modifier le Controller

### Option A : Nouveau Controller (Recommandé)

```php
// src/Controller/IshikawaCanvasController.php
<?php

namespace App\Controller;

use App\Entity\Diagram;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ishikawa-canvas')]
class IshikawaCanvasController extends AbstractController
{
    #[Route('/', name: 'app_ishikawa_canvas')]
    public function index(): Response
    {
        return $this->render('ishikawa/canvas.html.twig', [
            'page_title' => 'Diagramme Ishikawa Interactif'
        ]);
    }
    
    #[Route('/load/{id}', name: 'app_ishikawa_load', methods: ['GET'])]
    public function load(Diagram $diagram): Response
    {
        // Sérialiser et retourner les données du diagramme
        // Voir IshikawaController.php fourni pour la logique complète
    }
}
```

### Option B : Modifier Controller Existant

Si vous avez déjà un controller pour la page actuelle, ajoutez une nouvelle route :

```php
#[Route('/ishikawa-canvas-view', name: 'app_ishikawa_canvas_view')]
public function canvasView(): Response
{
    return $this->render('ishikawa/canvas.html.twig');
}
```

---

## 📝 Étape 3 : Créer le Template Principal

### Fichier : `templates/ishikawa/canvas.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Diagramme Ishikawa{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('css/ishikawa-canvas.css') }}">
{% endblock %}

{% block body %}
<div class="ishikawa-container">
    <!-- Header avec actions -->
    <div class="ishikawa-header">
        <h1>🐟 Diagramme d'Ishikawa</h1>
        <div class="actions">
            <button class="btn btn-primary" onclick="saveDiagram()">💾 Sauvegarder</button>
            <button class="btn btn-secondary" onclick="exportPNG()">📥 Export PNG</button>
        </div>
    </div>

    <!-- Canvas principal -->
    <div class="canvas-wrapper">
        <canvas id="ishikawaCanvas" width="1400" height="800"></canvas>
    </div>

    <!-- Sidebar avec catégories -->
    <div class="sidebar">
        <div class="sidebar-section">
            <h3>Catégories</h3>
            <div id="categoriesList"></div>
            <button class="btn btn-success" onclick="addCategory()">+ Ajouter</button>
        </div>
    </div>
</div>

<!-- Modales (copiées du HTML v3) -->
{% include 'ishikawa/partials/modals.html.twig' %}

{% endblock %}

{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('js/ishikawa-canvas.js') }}"></script>
    <script src="{{ asset('js/ishikawa-interactions.js') }}"></script>
    <script>
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initIshikawaDiagram();
        });
    </script>
{% endblock %}
```

---

## 📝 Étape 4 : Extraire le CSS du HTML v3

### Fichier : `public/css/ishikawa-canvas.css`

**Action :** Copier TOUT le contenu de la balise `<style>` du fichier `diagramme_5m_v3_debug.html`

Le fichier doit contenir environ 600 lignes de CSS incluant :
- Variables CSS (`:root`)
- Styles du container
- Styles de la sidebar
- Styles du canvas
- Styles des modales
- Animations
- Media queries responsive

---

## 📝 Étape 5 : Extraire le JavaScript

### Fichier 1 : `public/js/ishikawa-canvas.js` (Logique de dessin)

**Contenu à extraire du v3 debug :**

```javascript
// Variables globales
const canvas = document.getElementById('ishikawaCanvas');
const ctx = canvas.getContext('2d');
let categories = [];
let problemText = "Problème à résoudre";

// Fonctions de dessin
function drawDiagram() { /* ... */ }
function drawFishboneCategory(category, spineY) { /* ... */ }
function roundRect(ctx, x, y, width, height, radius) { /* ... */ }
function adjustColorBrightness(color, percent) { /* ... */ }

// Fonction d'initialisation
function initIshikawaDiagram() {
    // Charger les catégories par défaut ou depuis API
    loadDefaultCategories();
    drawDiagram();
}

function loadDefaultCategories() {
    categories = [
        { name: "PERSONNEL", color: "#2B7FD9", spineX: 280, angle: 130, branchLength: 200, causes: [] },
        { name: "MATÉRIELS", color: "#2B7FD9", spineX: 480, angle: 145, branchLength: 180, causes: [] },
        { name: "MÉTHODES", color: "#2B7FD9", spineX: 680, angle: 155, branchLength: 160, causes: [] },
        { name: "MACHINES", color: "#2B7FD9", spineX: 520, angle: -145, branchLength: 180, causes: [] },
        { name: "ENVIRONNEMENT", color: "#2B7FD9", spineX: 720, angle: -155, branchLength: 160, causes: [] },
        { name: "MANAGEMENT", color: "#2B7FD9", spineX: 900, angle: -155, branchLength: 160, causes: [] }
    ];
}
```

### Fichier 2 : `public/js/ishikawa-interactions.js` (Drag & Drop)

```javascript
// Variables de drag
let isDragging = false;
let draggedCause = null;
let draggedCauseCategory = null;
let draggedCategory = null;
let dragOffset = { x: 0, y: 0 };

// Event listeners
canvas.addEventListener('mousedown', handleMouseDown);
canvas.addEventListener('mousemove', handleMouseMove);
canvas.addEventListener('mouseup', handleMouseUp);
canvas.addEventListener('mouseleave', handleMouseUp);

function handleMouseDown(event) { /* copier du v3 */ }
function handleMouseMove(event) { /* copier du v3 */ }
function handleMouseUp() { /* copier du v3 */ }

// Sauvegarde en base de données
async function saveDiagram() {
    const data = {
        name: 'Mon diagramme',
        problem: problemText,
        categories: categories.map(cat => ({
            name: cat.name,
            color: cat.color,
            spineX: cat.spineX,
            angle: cat.angle,
            branchLength: cat.branchLength,
            causes: cat.causes.map(c => ({
                text: c.text || c,
                customPosition: c.customPosition
            }))
        }))
    };

    try {
        const response = await fetch('/ishikawa/api/diagram', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            alert('✅ Diagramme sauvegardé !');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur de sauvegarde');
    }
}
```

---

## 📝 Étape 6 : Mapper les Données Existantes

Si vous avez déjà des données dans votre format actuel, créez un script de migration :

```php
// src/Command/MigrateToCanvasCommand.php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateToCanvasCommand extends Command
{
    protected static $defaultName = 'app:migrate-to-canvas';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Logique de migration des données existantes
        // vers le format Ishikawa canvas
        
        return Command::SUCCESS;
    }
}
```

---

## 📝 Étape 7 : Routes API (Si besoin de sauvegarde)

Ajouter dans votre `IshikawaController.php` (déjà fourni) :

```php
#[Route('/api/diagram', name: 'api_ishikawa_save', methods: ['POST'])]
public function save(Request $request, EntityManagerInterface $em): JsonResponse
{
    // Voir le IshikawaController.php fourni pour l'implémentation complète
}
```

---

## 📝 Étape 8 : Adaptation du Design

### Ajuster les couleurs pour matcher votre design

Dans `ishikawa-canvas.css`, modifier les variables :

```css
:root {
    --primary: #2B7FD9;        /* Bleu de vos cartes */
    --success: #10b981;        /* Vert des boutons "Ajouter" */
    --card-bg: #F8F9FF;        /* Fond des cartes */
    /* ... */
}
```

### Adapter les catégories par défaut

Dans `ishikawa-canvas.js`, fonction `loadDefaultCategories()` :

```javascript
categories = [
    { name: "PERSONNEL", color: "#2B7FD9", causes: [...] },
    { name: "MATÉRIELS", color: "#2B7FD9", causes: [...] },
    { name: "MÉTHODES", color: "#2B7FD9", causes: [...] },
    { name: "MACHINES", color: "#2B7FD9", causes: [...] },
    { name: "ENVIRONNEMENT", color: "#2B7FD9", causes: [...] },
    { name: "MANAGEMENT", color: "#2B7FD9", causes: [...] },
    { name: "MESURE", color: "#2B7FD9", causes: [...] }
];
```

---

## 📝 Étape 9 : Testing

```bash
# 1. Vérifier les routes
php bin/console debug:router | grep ishikawa

# 2. Vérifier les assets
ls -la public/css/ishikawa-canvas.css
ls -la public/js/ishikawa-canvas.js
ls -la public/js/ishikawa-interactions.js

# 3. Lancer le serveur
symfony serve

# 4. Tester
# Ouvrir http://localhost:8000/ishikawa-canvas
# F12 pour voir la console
# Essayer de drag & drop une cause
```

---

## 🎯 Checklist Finale

### Backend
- [ ] Entités créées et migrations appliquées
- [ ] Controller créé avec routes
- [ ] API endpoints fonctionnels
- [ ] Sérialisation JSON correcte

### Frontend
- [ ] CSS extrait et placé dans `public/css/`
- [ ] JavaScript de dessin extrait
- [ ] JavaScript d'interactions extrait
- [ ] Template Twig créé
- [ ] Modales intégrées

### Fonctionnalités
- [ ] Canvas s'affiche correctement
- [ ] Catégories apparaissent
- [ ] Causes draggables fonctionnent
- [ ] Double-clic pour éditer
- [ ] Sauvegarde en BDD fonctionne
- [ ] Export PNG fonctionne

### Design
- [ ] Couleurs adaptées à votre charte
- [ ] Responsive sur mobile
- [ ] Animations fluides

---

## 🚨 Points d'Attention

### 1. Ordre de chargement des scripts
```twig
{# IMPORTANT : Ordre correct #}
<script src="{{ asset('js/ishikawa-canvas.js') }}"></script>      {# D'abord #}
<script src="{{ asset('js/ishikawa-interactions.js') }}"></script> {# Ensuite #}
```

### 2. Initialisation du Canvas
```javascript
// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('ishikawaCanvas')) {
        initIshikawaDiagram();
    }
});
```

### 3. Gestion des événements
```javascript
// Nettoyer les event listeners en cas de navigation SPA
function cleanupCanvas() {
    canvas.removeEventListener('mousedown', handleMouseDown);
    canvas.removeEventListener('mousemove', handleMouseMove);
    canvas.removeEventListener('mouseup', handleMouseUp);
}
```

---

## 📚 Fichiers de Référence

### Fichiers à utiliser comme base :
1. **`diagramme_5m_v3_debug.html`** ✅ 
   - Source complète pour extraction
   - CSS complet à copier
   - JavaScript complet à adapter

2. **`IshikawaController.php`** ✅
   - API REST complète
   - Sérialisation correcte
   - Gestion des entités

3. **Entités (Diagram, Category, Cause)** ✅
   - Structure de données
   - Relations Doctrine

---

## 🔄 Migration Incrémentale (Recommandé)

Si vous ne voulez pas tout changer d'un coup :

### Phase 1 : Coexistence
```
/ishikawa          → Votre page actuelle (cartes)
/ishikawa-canvas   → Nouvelle page (canvas)
```

### Phase 2 : A/B Testing
Ajouter un toggle pour basculer entre les deux :
```twig
{% if app.user.hasFeature('canvas_view') %}
    {# Nouvelle vue canvas #}
{% else %}
    {# Ancienne vue cartes #}
{% endif %}
```

### Phase 3 : Remplacement
Une fois validé, remplacer complètement.

---

## 💡 Conseils pour Cursor

Quand vous donnerez le prompt à Cursor, assurez-vous de :

1. **Fournir le contexte complet** :
   - Image de la page actuelle
   - Fichier HTML v3 debug
   - Structure de votre projet Symfony

2. **Être précis sur ce qui doit être gardé** :
   - Layout général de votre app
   - Système d'authentification
   - Navigation existante

3. **Spécifier les adaptations** :
   - Couleurs de votre charte graphique
   - Noms des catégories
   - Workflow utilisateur

---

## 🎯 Résumé des Étapes

1. ✅ Entités déjà créées
2. 📝 Créer/adapter le Controller
3. 🎨 Créer le template Twig
4. 📋 Extraire le CSS du v3 debug
5. ⚙️ Extraire le JavaScript (2 fichiers)
6. 🔗 Connecter l'API
7. 🎨 Adapter les couleurs
8. 🧪 Tester
9. 🚀 Déployer

---

**Temps estimé d'implémentation : 3-4 heures**

Prêt pour le prompt optimisé pour Cursor ? 🚀
