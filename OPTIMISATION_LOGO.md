# Instructions pour optimiser le logo

## Problème identifié
Le fichier `/public/img/logo.png` fait actuellement **1178 KiB (1.15 MB)** alors qu'il est affiché en **40x40px**, ce qui cause :
- Un mauvais score LCP (Largest Contentful Paint)
- Un temps de chargement excessif
- Une consommation de bande passante inutile

## Solutions recommandées

### Option 1 : Créer une version optimisée (RECOMMANDÉ)
1. Ouvrir le logo original dans un éditeur d'images (Photoshop, GIMP, etc.)
2. Redimensionner à **80x80px** (2x pour les écrans Retina) ou **40x40px** (1x)
3. Exporter en **WebP** avec compression 80-85%
4. Créer également une version PNG de fallback (40x40px, optimisée)
5. Sauvegarder comme :
   - `public/img/logo.webp` (version WebP)
   - `public/img/logo.png` (version PNG optimisée, ~5-10 KiB max)

### Option 2 : Utiliser un outil en ligne
- [Squoosh.app](https://squoosh.app/) - Outil Google pour optimiser les images
- [TinyPNG](https://tinypng.com/) - Compression PNG/WebP
- [ImageOptim](https://imageoptim.com/) - Pour Mac

### Option 3 : Utiliser des images responsives
Mettre à jour les templates pour utiliser `<picture>` avec plusieurs sources :

```html
<picture>
    <source srcset="{{ asset('img/logo.webp') }}" type="image/webp">
    <img src="{{ asset('img/logo.png') }}" alt="OUTILS-QUALITÉ" width="40" height="40" loading="eager" fetchpriority="high">
</picture>
```

## Objectif
Réduire la taille du logo de **1178 KiB à moins de 10 KiB** (réduction de 99%+)

## Vérification
Après optimisation, vérifier avec :
- Google PageSpeed Insights
- Chrome DevTools (Network tab)
- La taille du fichier doit être < 10 KiB

