<?php

namespace App\Command;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Entity\Tag;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:import-blog-articles',
    description: 'Importe les articles de blog depuis les fichiers Markdown',
)]
final class ImportBlogArticlesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
        private readonly SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importation des articles de blog');

        // Définir les articles à importer
        $articles = [
            [
                'file' => __DIR__ . '/../../blog/amelioration-continue/introduction-amelioration-continue.md',
                'category_slug' => 'amelioration-continue',
                'category_name' => 'Amélioration continue',
                'category_color' => 'success',
                'category_icon' => 'trending-up',
                'category_order' => 1,
                'title' => 'Introduction à l\'amélioration continue',
                'slug' => 'introduction-amelioration-continue',
                'excerpt' => 'Découvrez les principes fondamentaux de l\'amélioration continue et comment l\'implémenter dans votre organisation pour optimiser vos processus.',
                'readTime' => '8 min',
                'featured' => true,
                'tags' => ['amélioration continue', 'kaizen', 'lean', 'qualité'],
                'publishedAt' => '2024-01-15',
            ],
            [
                'file' => __DIR__ . '/../../blog/amelioration-continue/cycle-pdca-pratique.md',
                'category_slug' => 'amelioration-continue',
                'category_name' => 'Amélioration continue',
                'category_color' => 'success',
                'category_icon' => 'trending-up',
                'category_order' => 1,
                'title' => 'Le cycle PDCA en pratique',
                'slug' => 'cycle-pdca-pratique',
                'excerpt' => 'Guide pratique pour appliquer le cycle Plan-Do-Check-Act dans vos projets d\'amélioration continue avec des exemples concrets.',
                'readTime' => '6 min',
                'featured' => false,
                'tags' => ['PDCA', 'amélioration', 'méthode'],
                'publishedAt' => '2024-01-18',
            ],
            [
                'file' => __DIR__ . '/../../blog/amelioration-continue/iso-9001-2026.md',
                'category_slug' => 'amelioration-continue',
                'category_name' => 'Amélioration continue',
                'category_color' => 'success',
                'category_icon' => 'trending-up',
                'category_order' => 1,
                'title' => 'ISO 9001:2026 : Ce qui va changer pour votre PME (et comment vous préparer dès maintenant)',
                'slug' => 'iso-9001-2026',
                'excerpt' => 'La norme ISO 9001:2026 sera publiée en octobre 2026 avec des changements modérés mais significatifs pour les PME. Découvrez ce qui change et comment anticiper ces évolutions.',
                'readTime' => '12 min',
                'featured' => true,
                'tags' => ['ISO 9001', 'certification', 'qualité', 'PME', 'norme'],
                'publishedAt' => '2024-01-20',
            ],
            [
                'file' => __DIR__ . '/../../blog/methodologie/5-pourquoi-bonnes-pratiques.md',
                'category_slug' => 'methodologie',
                'category_name' => 'Méthodologie',
                'category_color' => 'primary',
                'category_icon' => 'book-open',
                'category_order' => 2,
                'title' => 'Les 5 Pourquoi : bonnes pratiques',
                'slug' => '5-pourquoi-bonnes-pratiques',
                'excerpt' => 'La méthode des 5 Pourquoi est un outil simple et puissant pour identifier la véritable cause d\'un problème. Découvrez les bonnes pratiques pour l\'utiliser efficacement.',
                'readTime' => '5 min',
                'featured' => false,
                'tags' => ['5 pourquoi', 'méthode', 'analyse des causes', 'Toyota'],
                'publishedAt' => '2024-01-22',
            ],
            [
                'file' => __DIR__ . '/../../blog/methodologie/ishikawa-guide-complet.md',
                'category_slug' => 'methodologie',
                'category_name' => 'Méthodologie',
                'category_color' => 'primary',
                'category_icon' => 'book-open',
                'category_order' => 2,
                'title' => 'Guide complet du diagramme d\'Ishikawa',
                'slug' => 'ishikawa-guide-complet',
                'excerpt' => 'Le diagramme d\'Ishikawa, aussi appelé diagramme en arête de poisson, est un outil visuel puissant pour explorer les causes racines d\'un problème.',
                'readTime' => '7 min',
                'featured' => true,
                'tags' => ['Ishikawa', 'diagramme', 'analyse des causes', 'fishbone'],
                'publishedAt' => '2024-01-25',
            ],
        ];

        $imported = 0;
        $skipped = 0;

        foreach ($articles as $articleData) {
            // Vérifier si l'article existe déjà
            $existingPost = $this->entityManager->getRepository(BlogPost::class)
                ->findOneBy(['slug' => $articleData['slug']]);

            if ($existingPost) {
                $io->warning("L'article '{$articleData['title']}' existe déjà. Ignoré.");
                $skipped++;
                continue;
            }

            // Lire le contenu du fichier Markdown
            if (!file_exists($articleData['file'])) {
                $io->error("Le fichier '{$articleData['file']}' n'existe pas. Ignoré.");
                $skipped++;
                continue;
            }

            $content = file_get_contents($articleData['file']);
            
            // S'assurer que le contenu est en UTF-8
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            }

            // Créer ou récupérer la catégorie
            $category = $this->categoryRepository->findOneBy(['slug' => $articleData['category_slug']]);
            if (!$category) {
                $category = new Category();
                $category->setName($articleData['category_name']);
                $category->setSlug($articleData['category_slug']);
                $category->setColor($articleData['category_color']);
                $category->setIcon($articleData['category_icon']);
                $category->setOrder($articleData['category_order']);
                $category->setDescription('Articles sur ' . strtolower($articleData['category_name']));

                $this->entityManager->persist($category);
                $this->entityManager->flush();
                $io->info("Catégorie '{$articleData['category_name']}' créée.");
            }

            // Créer l'article
            $post = new BlogPost();
            $post->setTitle($articleData['title']);
            $post->setSlug($articleData['slug']);
            $post->setExcerpt($articleData['excerpt']);
            $post->setContent($content);
            $post->setReadTime($articleData['readTime']);
            $post->setFeatured($articleData['featured']);
            $post->setCategory($category);
            $post->setPublishedAt(new \DateTimeImmutable($articleData['publishedAt']));
            $post->setCreatedAt(new \DateTimeImmutable($articleData['publishedAt']));
            $post->setUpdatedAt(new \DateTimeImmutable($articleData['publishedAt']));

            // Créer ou associer les tags
            foreach ($articleData['tags'] as $tagName) {
                $tagSlug = strtolower($this->slugger->slug($tagName)->toString());
                $tag = $this->tagRepository->findOneBy(['slug' => $tagSlug]);

                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $tag->setSlug($tagSlug);
                    $this->entityManager->persist($tag);
                    $this->entityManager->flush();
                    $io->info("Tag '{$tagName}' créé.");
                }

                $post->addTag($tag);
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $imported++;
            $io->success("Article '{$articleData['title']}' importé avec succès.");
        }

        $io->newLine();
        $io->success("Importation terminée : {$imported} article(s) importé(s), {$skipped} article(s) ignoré(s).");

        return Command::SUCCESS;
    }
}

