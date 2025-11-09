<?php

namespace App\Controller\Admin;

use App\Entity\CmsPage;
use App\Form\CmsPageType;
use App\Repository\CmsPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cms', name: 'app_admin_cms_')]
#[IsGranted('ROLE_ADMIN')]
class CmsController extends AbstractController
{
    private const CMS_PAGES = [
        'privacy-policy' => [
            'title' => 'Politique de confidentialité',
            'breadcrumb' => 'Politique de confidentialité',
        ],
        'terms-of-use' => [
            'title' => 'Conditions d\'utilisation',
            'breadcrumb' => 'Conditions d\'utilisation',
        ],
        'legal-notice' => [
            'title' => 'Mentions légales',
            'breadcrumb' => 'Mentions légales',
        ],
    ];

    public function __construct(
        private readonly CmsPageRepository $cmsPageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/cms/index.html.twig', [
            'pages' => self::CMS_PAGES,
        ]);
    }

    #[Route('/{slug}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(string $slug, Request $request): Response
    {
        if (!isset(self::CMS_PAGES[$slug])) {
            throw $this->createNotFoundException();
        }

        $pageConfig = self::CMS_PAGES[$slug];

        $cmsPage = $this->cmsPageRepository->findOneBySlug($slug);
        if (!$cmsPage instanceof CmsPage) {
            $cmsPage = (new CmsPage())
                ->setSlug($slug)
                ->setTitle($pageConfig['title']);
            $this->entityManager->persist($cmsPage);
        }

        $form = $this->createForm(CmsPageType::class, $cmsPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cmsPage->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('La page "%s" a été enregistrée.', $cmsPage->getTitle()));

            return $this->redirectToRoute('app_admin_cms_edit', ['slug' => $slug]);
        }

        return $this->render('admin/cms/edit.html.twig', [
            'form' => $form->createView(),
            'cmsPage' => $cmsPage,
            'slug' => $slug,
            'pageConfig' => $pageConfig,
        ]);
    }
}
