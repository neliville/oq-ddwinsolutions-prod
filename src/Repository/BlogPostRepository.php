<?php

namespace App\Repository;

use App\Entity\BlogPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogPost>
 */
class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPost::class);
    }

    public function findMostViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('bp')
            ->where('bp.publishedAt IS NOT NULL')
            ->orderBy('bp.views', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByFilter(string $filter = 'all', int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->leftJoin('bp.category', 'c')
            ->addSelect('c')
            ->leftJoin('bp.tags', 't')
            ->addSelect('t')
            ->orderBy('bp.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        switch ($filter) {
            case 'published':
                $qb->where('bp.publishedAt IS NOT NULL');
                break;
            case 'draft':
                $qb->where('bp.publishedAt IS NULL');
                break;
            case 'featured':
                $qb->where('bp.featured = true');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilter(string $filter = 'all'): int
    {
        $qb = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id)');

        switch ($filter) {
            case 'published':
                $qb->where('bp.publishedAt IS NOT NULL');
                break;
            case 'draft':
                $qb->where('bp.publishedAt IS NULL');
                break;
            case 'featured':
                $qb->where('bp.featured = true');
                break;
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function findOneByCategoryAndSlug(string $categorySlug, string $slug): ?BlogPost
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.category', 'c')
            ->addSelect('c')
            ->leftJoin('bp.tags', 't')
            ->addSelect('t')
            ->where('c.slug = :categorySlug')
            ->andWhere('bp.slug = :slug')
            ->andWhere('bp.publishedAt IS NOT NULL')
            ->andWhere('bp.publishedAt <= :now')
            ->setParameter('categorySlug', $categorySlug)
            ->setParameter('slug', $slug)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublishedByCategory(?string $categorySlug = null, int $page = 1, int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->leftJoin('bp.category', 'c')
            ->addSelect('c')
            ->leftJoin('bp.tags', 't')
            ->addSelect('t')
            ->where('bp.publishedAt IS NOT NULL')
            ->andWhere('bp.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('bp.publishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($categorySlug) {
            $qb->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        return $qb->getQuery()->getResult();
    }

    public function countPublishedByCategory(?string $categorySlug = null): int
    {
        $qb = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id)')
            ->where('bp.publishedAt IS NOT NULL')
            ->andWhere('bp.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable());

        if ($categorySlug) {
            $qb->join('bp.category', 'c')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function findRelatedPosts(BlogPost $post, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->leftJoin('bp.category', 'c')
            ->addSelect('c')
            ->leftJoin('bp.tags', 't')
            ->addSelect('t')
            ->where('bp.publishedAt IS NOT NULL')
            ->andWhere('bp.publishedAt <= :now')
            ->andWhere('bp.id != :postId')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('postId', $post->getId())
            ->orderBy('bp.publishedAt', 'DESC')
            ->setMaxResults($limit);

        // Prioriser les articles de la même catégorie ou avec des tags communs
        if ($post->getCategory()) {
            $qb->andWhere('bp.category = :category')
                ->setParameter('category', $post->getCategory());
        }

        $related = $qb->getQuery()->getResult();

        // Si pas assez de résultats de la même catégorie, compléter avec d'autres
        if (count($related) < $limit) {
            $qb2 = $this->createQueryBuilder('bp')
                ->leftJoin('bp.category', 'c')
                ->addSelect('c')
                ->leftJoin('bp.tags', 't')
                ->addSelect('t')
                ->where('bp.publishedAt IS NOT NULL')
                ->andWhere('bp.publishedAt <= :now')
                ->andWhere('bp.id != :postId')
                ->setParameter('now', new \DateTimeImmutable())
                ->setParameter('postId', $post->getId())
                ->orderBy('bp.publishedAt', 'DESC')
                ->setMaxResults($limit);

            if ($post->getCategory()) {
                $qb2->andWhere('bp.category != :category')
                    ->setParameter('category', $post->getCategory());
            }

            $more = $qb2->getQuery()->getResult();
            $related = array_merge($related, $more);
        }

        return array_slice($related, 0, $limit);
    }
}

