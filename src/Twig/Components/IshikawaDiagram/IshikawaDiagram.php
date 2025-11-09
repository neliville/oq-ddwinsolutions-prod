<?php

namespace App\Twig\Components\IshikawaDiagram;

use App\Entity\User;
use App\Entity\IshikawaAnalysis;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\IshikawaAnalysisRepository;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent('ishikawa_diagram')]
class IshikawaDiagram extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    // Les LiveProps sont l'état du composant
    // Ils sont automatiquement synchronisés entre le serveur et le client
    
    #[LiveProp(writable: true)]
    public string $problem = "Utilisez ce modèle d'analyse pour optimiser l'amélioration des processus et identifier les causes racines.";
    
    #[LiveProp(writable: true)]
    public array $categories = [];
    
    #[LiveProp]
    public ?int $diagramId = null;
    
    #[LiveProp]
    public bool $isAuthenticated = false;
    
    // État pour les modals (non synchronisé automatiquement)
    #[LiveProp(writable: true)]
    public ?int $editingCategoryId = null;
    
    #[LiveProp(writable: true)]
    public ?int $editingCauseCategoryId = null;
    
    #[LiveProp(writable: true)]
    public ?int $editingCauseIndex = null;
    
    #[LiveProp(writable: true)]
    public bool $showCategoryModal = false;
    
    #[LiveProp(writable: true)]
    public bool $showCauseModal = false;

    // Catégories disponibles prédéfinies
    private const AVAILABLE_CATEGORIES = ["MATÉRIELS", "MESURE", "MACHINES", "MÉTHODES", "ENVIRONNEMENT", "PERSONNEL", "MANAGEMENT"];

    public function __construct(
        private EntityManagerInterface $em,
        private IshikawaAnalysisRepository $diagramRepo,
        private RequestStack $requestStack
    ) {}

    /**
     * Cette méthode est appelée à chaque initialisation du composant
     * C'est ici qu'on charge les données depuis la BDD ou la session
     */
    public function mount(?int $id = null): void
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $this->isAuthenticated = $user !== null;
        
        if ($user && $id) {
            // Utilisateur connecté avec un ID : charger depuis la BDD
            $this->loadFromDatabase($id);
        } elseif ($user && !$id) {
            // Utilisateur connecté sans ID : créer un nouveau diagramme ou charger depuis la session
            $this->loadFromSession();
            if (empty($this->categories)) {
                $this->initializeDefaultCategories();
            }
        } else {
            // Utilisateur non connecté : charger depuis la session
            $this->loadFromSession();
            if (empty($this->categories)) {
                $this->initializeDefaultCategories();
            }
        }
        
        $this->diagramId = $id;
    }

    /**
     * Charge les données depuis la base de données
     */
    private function loadFromDatabase(int $id): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $diagram = $this->diagramRepo->find($id);
        
        if (!$diagram) {
            throw $this->createNotFoundException('Diagramme non trouvé');
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($diagram->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        $this->problem = $diagram->getProblem() ?? $this->problem;
        
        // Décoder les données JSON
        $data = json_decode($diagram->getData(), true);
        if ($data && isset($data['categories'])) {
            $this->categories = $data['categories'];
            // Normaliser les catégories pour s'assurer qu'elles ont les propriétés Canvas
            foreach ($this->categories as &$category) {
                if (!isset($category['spineX'])) {
                    $category['spineX'] = $category['position']['left'] ?? 200;
                    if (is_string($category['spineX'])) {
                        $category['spineX'] = (int) str_replace('%', '', $category['spineX']) * 10;
                    }
                }
                if (!isset($category['angle'])) {
                    $category['angle'] = $category['id'] % 2 === 0 ? -30 : 30;
                }
                if (!isset($category['branchLength'])) {
                    $category['branchLength'] = 150;
                }
            }
            unset($category);
        } else {
            $this->initializeDefaultCategories();
        }
    }

    /**
     * Charge les données depuis la session PHP (utilisateurs non connectés)
     */
    private function loadFromSession(): void
    {
        $session = $this->requestStack->getSession();
        $sessionData = $session->get('ishikawa_diagram', null);
        
        if ($sessionData) {
            $this->problem = $sessionData['problem'] ?? $this->problem;
            $this->categories = $sessionData['categories'] ?? [];
        }
    }

    /**
     * Initialise les catégories par défaut avec propriétés Canvas
     */
    private function initializeDefaultCategories(): void
    {
        $this->categories = [
            [
                'id' => 1,
                'name' => 'PERSONNEL',
                'causes' => ['Formation insuffisante', 'Fatigue du personnel', 'Manque de motivation'],
                'position' => ['left' => '15%', 'top' => '10%'],
                'spineX' => 200, // Position sur la spine (pixels depuis le début)
                'angle' => -45, // Angle en degrés (négatif = vers le haut)
                'branchLength' => 150 // Longueur de l'arête en pixels
            ],
            [
                'id' => 2,
                'name' => 'MATÉRIELS',
                'causes' => ['Qualité des matières premières', 'Spécifications non conformes', 'Stockage inadéquat'],
                'position' => ['left' => '35%', 'top' => '11%'],
                'spineX' => 400,
                'angle' => -30,
                'branchLength' => 150
            ],
            [
                'id' => 3,
                'name' => 'MESURE',
                'causes' => ['Instruments de mesure défaillants', 'Précision inadéquate'],
                'position' => ['left' => '55%', 'top' => '10%'],
                'spineX' => 600,
                'angle' => -20,
                'branchLength' => 150
            ],
            [
                'id' => 4,
                'name' => 'MACHINES',
                'causes' => ['Équipement défaillant', 'Maintenance préventive insuffisante', 'Usure prématurée'],
                'position' => ['left' => '15%', 'top' => '60%'],
                'spineX' => 200,
                'angle' => 45, // Positif = vers le bas
                'branchLength' => 150
            ],
            [
                'id' => 5,
                'name' => 'MÉTHODES',
                'causes' => ['Procédures inadéquates', 'Manque de standardisation', 'Instructions peu claires'],
                'position' => ['left' => '35%', 'top' => '60%'],
                'spineX' => 400,
                'angle' => 30,
                'branchLength' => 150
            ],
            [
                'id' => 6,
                'name' => 'ENVIRONNEMENT',
                'causes' => ['Conditions de travail', 'Température inadéquate', 'Éclairage insuffisant'],
                'position' => ['left' => '55%', 'top' => '65%'],
                'spineX' => 600,
                'angle' => 20,
                'branchLength' => 150
            ],
            [
                'id' => 7,
                'name' => 'MANAGEMENT',
                'causes' => ['Planification insuffisante', 'Communication défaillante', 'Ressources inadéquates'],
                'position' => ['left' => '75%', 'top' => '60%'],
                'spineX' => 800,
                'angle' => 15,
                'branchLength' => 150
            ],
        ];
    }

    /**
     * Cette méthode est appelée automatiquement après chaque modification d'une LiveProp
     */
    public function updatedProblem(): void
    {
        $this->persistData();
    }

    /**
     * Cette méthode est appelée automatiquement après chaque modification des catégories
     */
    public function updatedCategories(): void
    {
        // S'assurer que toutes les catégories ont les propriétés Canvas
        foreach ($this->categories as &$category) {
            if (!isset($category['spineX'])) {
                $canvasPosition = $this->calculateNextCanvasPosition();
                $category['spineX'] = $canvasPosition['spineX'];
                $category['angle'] = $canvasPosition['angle'];
                $category['branchLength'] = $canvasPosition['branchLength'];
            }
        }
        unset($category); // Libérer la référence
        
        $this->persistData();
    }

    /**
     * Sauvegarde les données au bon endroit selon le type d'utilisateur
     */
    private function persistData(): void
    {
        if ($this->isAuthenticated) {
            $this->saveToDatabase();
        } else {
            $this->saveToSession();
        }
    }

    /**
     * Sauvegarde en base de données pour les utilisateurs connectés
     */
    private function saveToDatabase(): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $diagram = null;
        
        if ($this->diagramId) {
            // Mise à jour d'un diagramme existant
            $diagram = $this->diagramRepo->find($this->diagramId);
            
            if (!$diagram || $diagram->getUser() !== $user) {
                return; // Sécurité : ne pas sauvegarder si pas le propriétaire
            }
        } else {
            // Création d'un nouveau diagramme
            $diagram = new IshikawaAnalysis();
            $diagram->setUser($user);
            $diagram->setTitle(substr($this->problem, 0, 100) ?: 'Diagramme Ishikawa');
        }
        
        // Préparer les données JSON
        $data = [
            'categories' => $this->categories
        ];
        
        $diagram->setProblem($this->problem);
        $diagram->setData(json_encode($data));
        $diagram->setUpdatedAt(new \DateTimeImmutable());
        
        $this->em->persist($diagram);
        $this->em->flush();
        
        // Si c'était une nouvelle création, mettre à jour l'ID
        if (!$this->diagramId) {
            $this->diagramId = $diagram->getId();
        }
    }

    /**
     * Sauvegarde en session pour les utilisateurs non connectés
     */
    private function saveToSession(): void
    {
        $session = $this->requestStack->getSession();
        
        $data = [
            'problem' => $this->problem,
            'categories' => $this->categories
        ];
        
        $session->set('ishikawa_diagram', $data);
    }

    /**
     * Action pour ouvrir le modal d'ajout de catégorie
     */
    #[LiveAction]
    public function openAddCategoryModal(): void
    {
        error_log('[ISHIKAWA DEBUG] openAddCategoryModal called');
        error_log('[ISHIKAWA DEBUG] Setting editingCategoryId to: null');
        error_log('[ISHIKAWA DEBUG] Setting showCategoryModal to: true');
        
        $this->editingCategoryId = null;
        $this->showCategoryModal = true;
        
        error_log('[ISHIKAWA DEBUG] After setting props - showCategoryModal: ' . ($this->showCategoryModal ? 'true' : 'false'));
        
        // Forcer le re-render du composant
        error_log('[ISHIKAWA DEBUG] Dispatching modal:open event');
        $this->dispatchBrowserEvent('modal:open', [
            'modalId' => 'categoryModal'
        ]);
        
        error_log('[ISHIKAWA DEBUG] openAddCategoryModal completed');
    }

    /**
     * Action pour ouvrir le modal d'édition de catégorie
     */
    #[LiveAction]
    public function openEditCategoryModal(#[LiveArg] ?string $categoryId = null): void
    {
        error_log(sprintf('[IshikawaDiagram] openEditCategoryModal called with categoryId: %s', $categoryId ?? 'null'));
        
        if ($categoryId === null) {
            $this->addFlash('error', 'Catégorie non spécifiée');
            error_log('[IshikawaDiagram] openEditCategoryModal: categoryId is null, returning');
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        $this->editingCategoryId = $categoryIdInt;
        $this->showCategoryModal = true;
        
        error_log(sprintf('[IshikawaDiagram] openEditCategoryModal: Set editingCategoryId=%d, showCategoryModal=true', $categoryIdInt));
        
        // Déclencher l'ouverture du modal via un événement browser après que Live Component ait mis à jour le DOM
        // Selon la doc Symfony UX Turbo : https://symfony.com/bundles/ux-turbo/current/index.html
        $this->dispatchBrowserEvent('modal:open', [
            'modalId' => 'categoryModal',
            'categoryId' => $categoryIdInt
        ]);
        
        error_log('[IshikawaDiagram] openEditCategoryModal: Dispatched modal:open event');
    }

    /**
     * Action pour fermer le modal de catégorie
     */
    #[LiveAction]
    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->editingCategoryId = null;
        $this->dispatchBrowserEvent('modal:close');
    }

    /**
     * Action pour ajouter une catégorie
     */
    #[LiveAction]
    public function addCategory(
        #[LiveArg] ?string $categorySelect = null,
        #[LiveArg] ?string $categoryName = null
    ): void
    {
        // Récupérer les données du formulaire
        $categorySelect = $categorySelect ?? '';
        $categoryNameInput = $categoryName ?? '';
        
        // Déterminer le nom de la catégorie
        if (!empty(trim($categoryNameInput))) {
            // Si un nom personnalisé est fourni, l'utiliser (priorité)
            $categoryName = strtoupper(trim($categoryNameInput));
        } elseif ($categorySelect === 'custom' && !empty(trim($categoryNameInput))) {
            // Si "custom" est sélectionné et un nom est fourni
            $categoryName = strtoupper(trim($categoryNameInput));
        } elseif (!empty(trim($categorySelect)) && $categorySelect !== 'custom') {
            // Si une catégorie prédéfinie est sélectionnée
            $categoryName = strtoupper(trim($categorySelect));
        } else {
            $this->addFlash('error', 'Veuillez saisir un nom de catégorie');
            return;
        }
        
        if (count($this->categories) >= 10) {
            $this->addFlash('error', 'Limite de 10 catégories atteinte');
            return;
        }
        
        // Vérifier les doublons
        foreach ($this->categories as $cat) {
            if ($cat['name'] === $categoryName) {
                $this->addFlash('error', 'Cette catégorie existe déjà');
                return;
            }
        }
        
        // Calculer la position pour la nouvelle catégorie
        $position = $this->calculateNextCategoryPosition();
        $canvasPosition = $this->calculateNextCanvasPosition();
        
        // Ajouter la catégorie
        $newCategory = [
            'id' => time() + count($this->categories), // ID basé sur le timestamp
            'name' => $categoryName,
            'causes' => [],
            'position' => $position,
            'spineX' => $canvasPosition['spineX'],
            'angle' => $canvasPosition['angle'],
            'branchLength' => $canvasPosition['branchLength']
        ];
        
        $this->categories[] = $newCategory;
        
        // Sauvegarder les données avant de fermer le modal
        $this->persistData();
        
        // Fermer le modal
        $this->showCategoryModal = false;
        $this->editingCategoryId = null;
        
        $this->addFlash('success', 'Catégorie ajoutée avec succès');
        // Déclencher la fermeture du modal via un événement browser
        $this->dispatchBrowserEvent('modal:close', [
            'modalId' => 'categoryModal'
        ]);
    }

    /**
     * Action pour modifier une catégorie
     */
    #[LiveAction]
    public function updateCategory(
        #[LiveArg] ?string $editingCategoryId = null,
        #[LiveArg] ?string $categorySelect = null,
        #[LiveArg] ?string $categoryName = null
    ): void
    {
        // Utiliser editingCategoryId passé en paramètre ou depuis la propriété
        // Si editingCategoryId n'est pas passé en paramètre, utiliser la propriété
        $categoryId = null;
        if ($editingCategoryId !== null && $editingCategoryId !== '') {
            $categoryId = (int) $editingCategoryId;
        } elseif ($this->editingCategoryId !== null) {
            $categoryId = $this->editingCategoryId;
        }
        
        if ($categoryId === null) {
            $this->addFlash('error', 'Aucune catégorie en cours d\'édition');
            error_log('[IshikawaDiagram] updateCategory: editingCategoryId is null');
            error_log(sprintf('[IshikawaDiagram] updateCategory: Received params - editingCategoryId=%s, categorySelect=%s, categoryName=%s', $editingCategoryId ?? 'null', $categorySelect ?? 'null', $categoryName ?? 'null'));
            error_log(sprintf('[IshikawaDiagram] updateCategory: Current editingCategoryId property=%s', $this->editingCategoryId ?? 'null'));
            return;
        }
        
        error_log(sprintf('[IshikawaDiagram] updateCategory called with categoryId=%d, categorySelect=%s, categoryName=%s', $categoryId, $categorySelect ?? 'null', $categoryName ?? 'null'));
        
        // Récupérer les données du formulaire
        $categorySelect = $categorySelect ?? '';
        $categoryNameInput = $categoryName ?? '';
        
        // Déterminer le nom de la catégorie
        if ($categorySelect === 'custom' && !empty(trim($categoryNameInput))) {
            $categoryName = strtoupper(trim($categoryNameInput));
        } elseif (!empty(trim($categorySelect)) && $categorySelect !== 'custom') {
            $categoryName = strtoupper(trim($categorySelect));
        } else {
            $this->addFlash('error', 'Veuillez saisir un nom de catégorie');
            return;
        }
        
        // Trouver et mettre à jour la catégorie
        foreach ($this->categories as &$category) {
            if ($category['id'] === $categoryId) {
                // Vérifier les doublons (sauf la catégorie en cours d'édition)
                foreach ($this->categories as $cat) {
                    if ($cat['id'] !== $categoryId && $cat['name'] === $categoryName) {
                        $this->addFlash('error', 'Cette catégorie existe déjà');
                        return;
                    }
                }
                
                $category['name'] = $categoryName;
                
                // Sauvegarder les données avant de fermer le modal
                $this->persistData();
                
                // Fermer le modal
                $this->showCategoryModal = false;
                $this->editingCategoryId = null;
                
                error_log('[IshikawaDiagram] updateCategory: Category updated, closing modal');
                $this->addFlash('success', 'Catégorie modifiée avec succès');
                // Déclencher la fermeture du modal via un événement browser
                $this->dispatchBrowserEvent('modal:close', [
                    'modalId' => 'categoryModal'
                ]);
                return;
            }
        }
        
        error_log(sprintf('[IshikawaDiagram] updateCategory: Category with id=%d not found', $categoryId));
        $this->addFlash('error', 'Catégorie non trouvée');
    }

    /**
     * Action pour supprimer une catégorie
     */
    #[LiveAction]
    public function deleteCategory(#[LiveArg] ?string $categoryId = null): void
    {
        if ($categoryId === null) {
            $this->addFlash('error', 'Catégorie non spécifiée');
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        
        if (count($this->categories) <= 1) {
            $this->addFlash('error', 'Au moins une catégorie est requise');
            return;
        }
        
        $this->categories = array_values(
            array_filter(
                $this->categories,
                fn($cat) => $cat['id'] !== $categoryIdInt
            )
        );
        
        $this->persistData();
        $this->addFlash('success', 'Catégorie supprimée avec succès');
    }

    /**
     * Action pour ouvrir le modal d'ajout de cause
     */
    #[LiveAction]
    public function openAddCauseModal(#[LiveArg] ?string $categoryId = null): void
    {
        if ($categoryId === null) {
            $this->addFlash('error', 'Catégorie non spécifiée');
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        $this->editingCauseCategoryId = $categoryIdInt;
        $this->editingCauseIndex = null;
        $this->showCauseModal = true;
        
        // Déclencher l'ouverture du modal via un événement browser
        $this->dispatchBrowserEvent('modal:open', [
            'modalId' => 'causeModal',
            'categoryId' => $categoryIdInt
        ]);
    }

    /**
     * Action pour ouvrir le modal d'édition de cause
     */
    #[LiveAction]
    public function openEditCauseModal(#[LiveArg] ?string $categoryId = null, #[LiveArg] ?string $causeIndex = null): void
    {
        if ($categoryId === null || $causeIndex === null) {
            $this->addFlash('error', 'Paramètres manquants');
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        $causeIndexInt = (int) $causeIndex;
        $this->editingCauseCategoryId = $categoryIdInt;
        $this->editingCauseIndex = $causeIndexInt;
        $this->showCauseModal = true;
        
        // Déclencher l'ouverture du modal via un événement browser
        $this->dispatchBrowserEvent('modal:open', [
            'modalId' => 'causeModal',
            'categoryId' => $categoryIdInt,
            'causeIndex' => $causeIndexInt
        ]);
    }

    /**
     * Action pour fermer le modal de cause
     */
    #[LiveAction]
    public function closeCauseModal(): void
    {
        $this->showCauseModal = false;
        $this->editingCauseCategoryId = null;
        $this->editingCauseIndex = null;
        $this->dispatchBrowserEvent('modal:close');
    }

    /**
     * Action pour ajouter une cause à une catégorie
     */
    #[LiveAction]
    public function addCause(#[LiveArg] ?string $causeName = null): void
    {
        if (!$this->editingCauseCategoryId) {
            $this->addFlash('error', 'Catégorie non spécifiée');
            return;
        }
        
        $causeName = $causeName ?? '';
        
        if (empty(trim($causeName))) {
            $this->addFlash('error', 'Veuillez saisir une description de cause');
            return;
        }
        
        foreach ($this->categories as &$category) {
            if ($category['id'] === $this->editingCauseCategoryId) {
                $category['causes'][] = trim($causeName);
                $this->showCauseModal = false;
                $this->editingCauseCategoryId = null;
                $this->editingCauseIndex = null;
                
                $this->dispatchBrowserEvent('modal:close');
                $this->addFlash('success', 'Cause ajoutée avec succès');
                return;
            }
        }
        
        $this->addFlash('error', 'Catégorie non trouvée');
    }

    /**
     * Action pour mettre à jour une cause
     */
    #[LiveAction]
    public function updateCause(#[LiveArg] ?string $causeName = null): void
    {
        if (!$this->editingCauseCategoryId || $this->editingCauseIndex === null) {
            $this->addFlash('error', 'Cause non spécifiée');
            return;
        }
        
        $causeName = $causeName ?? '';
        
        if (empty(trim($causeName))) {
            $this->addFlash('error', 'Veuillez saisir une description de cause');
            return;
        }
        
        foreach ($this->categories as &$category) {
            if ($category['id'] === $this->editingCauseCategoryId) {
                if (isset($category['causes'][$this->editingCauseIndex])) {
                    $category['causes'][$this->editingCauseIndex] = trim($causeName);
                    $this->showCauseModal = false;
                    $this->editingCauseCategoryId = null;
                    $this->editingCauseIndex = null;
                    
                    $this->dispatchBrowserEvent('modal:close');
                    $this->addFlash('success', 'Cause modifiée avec succès');
                    return;
                }
            }
        }
        
        $this->addFlash('error', 'Cause non trouvée');
    }

    /**
     * Action pour supprimer une cause
     */
    #[LiveAction]
    public function deleteCause(#[LiveArg] ?string $categoryId = null, #[LiveArg] ?string $causeIndex = null): void
    {
        if ($categoryId === null || $causeIndex === null) {
            $this->addFlash('error', 'Paramètres manquants');
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        $causeIndexInt = (int) $causeIndex;
        
        foreach ($this->categories as &$category) {
            if ($category['id'] === $categoryIdInt) {
                if (isset($category['causes'][$causeIndexInt])) {
                    array_splice($category['causes'], $causeIndexInt, 1);
                    $this->persistData();
                    $this->addFlash('success', 'Cause supprimée avec succès');
                    return;
                }
            }
        }
        
        $this->addFlash('error', 'Cause non trouvée');
    }

    /**
     * Action pour réorganiser l'ordre des catégories
     */
    #[LiveAction]
    public function reorderCategories(#[LiveArg] ?array $order = null): void
    {
        if ($order === null || !is_array($order) || empty($order)) {
            return;
        }
        
        // Créer un tableau associatif pour faciliter la recherche
        $orderedCategories = [];
        $existingCategories = [];
        
        // Créer un index des catégories existantes par ID
        foreach ($this->categories as $category) {
            $existingCategories[$category['id']] = $category;
        }
        
        // Réorganiser selon le nouvel ordre
        foreach ($order as $categoryIdStr) {
            $categoryId = (int) $categoryIdStr;
            if (isset($existingCategories[$categoryId])) {
                $orderedCategories[] = $existingCategories[$categoryId];
            }
        }
        
        // Ajouter les catégories qui n'étaient pas dans l'ordre (ne devrait pas arriver)
        foreach ($existingCategories as $categoryId => $category) {
            if (!in_array($categoryId, array_map('intval', $order))) {
                $orderedCategories[] = $category;
            }
        }
        
        $this->categories = $orderedCategories;
        $this->persistData();
    }

    /**
     * Action pour mettre à jour la position d'une catégorie (drag & drop)
     */
    #[LiveAction]
    public function updateCategoryPosition(#[LiveArg] ?string $categoryId = null, #[LiveArg] ?string $left = null, #[LiveArg] ?string $top = null, #[LiveArg] ?int $spineX = null, #[LiveArg] ?float $angle = null, #[LiveArg] ?int $branchLength = null): void
    {
        if ($categoryId === null) {
            return;
        }
        
        $categoryIdInt = (int) $categoryId;
        
        foreach ($this->categories as &$category) {
            if ($category['id'] === $categoryIdInt) {
                // Mettre à jour la position legacy si fournie
                if ($left !== null && $top !== null) {
                    $category['position'] = [
                        'left' => $left,
                        'top' => $top
                    ];
                }
                
                // Mettre à jour les propriétés Canvas si fournies
                if ($spineX !== null) {
                    $category['spineX'] = $spineX;
                }
                if ($angle !== null) {
                    $category['angle'] = $angle;
                }
                if ($branchLength !== null) {
                    $category['branchLength'] = $branchLength;
                }
                
                // Sauvegarder silencieusement (pas de flash message pour ne pas polluer l'interface)
                $this->persistData();
                return;
            }
        }
    }

    /**
     * Action pour réinitialiser toutes les causes
     */
    #[LiveAction]
    public function resetAllCauses(): void
    {
        foreach ($this->categories as &$category) {
            $category['causes'] = [];
        }
        
        $this->addFlash('success', 'Toutes les causes ont été supprimées');
    }

    /**
     * Action pour sauvegarder (utilisé par le bouton Sauvegarder)
     */
    #[LiveAction]
    public function save(): void
    {
        $this->persistData();
        $this->addFlash('success', 'Diagramme sauvegardé avec succès');
    }

    /**
     * Action pour mettre à jour le problème (utilisé par l'input)
     */
    #[LiveAction]
    public function updateProblem(#[LiveArg] string $problem): void
    {
        $this->problem = $problem;
        $this->persistData();
    }

    /**
     * Action pour basculer entre catégorie prédéfinie et personnalisée
     */
    #[LiveAction]
    public function toggleCategoryType(#[LiveArg] string $selectedCategory): void
    {
        // Cette action sera gérée côté client via JavaScript
        // Mais on peut mettre à jour isCustom si nécessaire
    }

    /**
     * Calcule la position pour une nouvelle catégorie (legacy pour compatibilité)
     */
    private function calculateNextCategoryPosition(): array
    {
        $defaultPositions = [
            ['left' => '15%', 'top' => '10%'],
            ['left' => '35%', 'top' => '11%'],
            ['left' => '55%', 'top' => '10%'],
            ['left' => '15%', 'top' => '60%'],
            ['left' => '35%', 'top' => '60%'],
            ['left' => '55%', 'top' => '65%'],
            ['left' => '75%', 'top' => '60%'],
            ['left' => '25%', 'top' => '35%'],
            ['left' => '45%', 'top' => '35%'],
            ['left' => '65%', 'top' => '35%'],
        ];
        
        $index = count($this->categories);
        
        return $defaultPositions[$index % count($defaultPositions)];
    }

    /**
     * Calcule la position Canvas pour une nouvelle catégorie
     */
    private function calculateNextCanvasPosition(): array
    {
        $canvasPositions = [
            ['spineX' => 200, 'angle' => -45, 'branchLength' => 150],
            ['spineX' => 400, 'angle' => -30, 'branchLength' => 150],
            ['spineX' => 600, 'angle' => -20, 'branchLength' => 150],
            ['spineX' => 200, 'angle' => 45, 'branchLength' => 150],
            ['spineX' => 400, 'angle' => 30, 'branchLength' => 150],
            ['spineX' => 600, 'angle' => 20, 'branchLength' => 150],
            ['spineX' => 800, 'angle' => 15, 'branchLength' => 150],
            ['spineX' => 300, 'angle' => -35, 'branchLength' => 150],
            ['spineX' => 500, 'angle' => 35, 'branchLength' => 150],
            ['spineX' => 700, 'angle' => -25, 'branchLength' => 150],
        ];
        
        $index = count($this->categories);
        
        return $canvasPositions[$index % count($canvasPositions)];
    }

    /**
     * Getters pour le template
     */
    public function getAvailableStandardCategories(): array
    {
        $used = array_column($this->categories, 'name');
        
        // Retourner les catégories disponibles qui ne sont pas utilisées
        // OU la catégorie en cours d'édition
        $available = [];
        foreach (self::AVAILABLE_CATEGORIES as $cat) {
            if (!in_array($cat, $used) || 
                ($this->editingCategoryId && $this->getEditingCategory() && $this->getEditingCategory()['name'] === $cat)) {
                $available[] = $cat;
            }
        }
        
        return $available;
    }

    /**
     * Récupère la catégorie en cours d'édition
     */
    public function getEditingCategory(): ?array
    {
        if (!$this->editingCategoryId) {
            return null;
        }
        
        foreach ($this->categories as $category) {
            if ($category['id'] === $this->editingCategoryId) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Récupère la cause en cours d'édition
     */
    public function getEditingCause(): ?string
    {
        if (!$this->editingCauseCategoryId || $this->editingCauseIndex === null) {
            return null;
        }
        
        foreach ($this->categories as $category) {
            if ($category['id'] === $this->editingCauseCategoryId) {
                if (isset($category['causes'][$this->editingCauseIndex])) {
                    return $category['causes'][$this->editingCauseIndex];
                }
            }
        }
        
        return null;
    }

    public function canAddMoreCategories(): bool
    {
        return count($this->categories) < 10;
    }

    /**
     * Récupère la couleur d'une catégorie
     */
    public function getCategoryColor(string $categoryName): string
    {
        $colors = [
            'PERSONNEL' => '#dc3545',
            'MATÉRIELS' => '#20c997',
            'MESURE' => '#0dcaf0',
            'MACHINES' => '#ffc107',
            'MÉTHODES' => '#6f42c1',
            'ENVIRONNEMENT' => '#198754',
            'MANAGEMENT' => '#fd7e14'
        ];
        
        return $colors[$categoryName] ?? '#6c757d';
    }
}

