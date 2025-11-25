<?php

namespace App\Service;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service pour optimiser et générer les variantes d'images via LiipImagineBundle
 */
class ImageOptimizerService
{
    public function __construct(
        private readonly FilterService $filterService,
        private readonly CacheManager $cacheManager,
    ) {
    }

    /**
     * Génère toutes les variantes nécessaires pour une image de blog
     * 
     * @param string $imagePath Chemin relatif depuis public/ (ex: "uploads/blog/image.jpg")
     * @return array URLs des variantes générées
     */
    public function generateBlogImageVariants(string $imagePath): array
    {
        $variants = [];
        $filters = ['cover_webp', 'cover_jpeg', 'card_desktop', 'card_mobile'];

        foreach ($filters as $filter) {
            try {
                // Génère la variante et retourne l'URL
                $url = $this->filterService->getUrlOfFilteredImage($imagePath, $filter);
                $variants[$filter] = $url;
            } catch (\Exception $e) {
                // En cas d'erreur, on continue avec les autres filtres
                $variants[$filter] = null;
            }
        }

        return $variants;
    }

    /**
     * Génère une variante spécifique
     */
    public function generateVariant(string $imagePath, string $filter): ?string
    {
        try {
            return $this->filterService->getUrlOfFilteredImage($imagePath, $filter);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Supprime toutes les variantes en cache pour une image
     */
    public function removeVariants(string $imagePath): void
    {
        $filters = ['cover_webp', 'cover_jpeg', 'card_desktop', 'card_mobile'];
        
        foreach ($filters as $filter) {
            try {
                $this->cacheManager->remove($imagePath, $filter);
            } catch (\Exception $e) {
                // Ignorer les erreurs de suppression
            }
        }
    }
}

