<?php

namespace App\Controller\Parameter;

use App\Service\PermissionService;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractCrudController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ValidatorInterface $validator,
        protected LoggerInterface $logger,
        protected PermissionService $permissionService,
        protected SecurityService $securityService
    ) {}

    abstract protected function getEntityClass(): string;
    abstract protected function getRepository();
    abstract protected function canView(): bool;
    abstract protected function canEdit(): bool;
    abstract protected function canDelete(): bool;
    abstract protected function validateData(array $data): void;
    abstract protected function createEntity(array $data): object;
    abstract protected function updateEntity(object $entity, array $data): void;
    abstract protected function getEntityName(): string;
    abstract protected function getTemplatePrefix(): string;

    protected function index(): Response
    {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        if (!$this->canView()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $entities = $this->getRepository()->findAll();
        $canEdit = $this->canEdit();
        $canDelete = $this->canDelete();

        return $this->render($this->getTemplatePrefix() . '/index.html.twig', [
            'entities' => $entities,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$currentUser) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->canEdit()) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = $this->getRequestData($request);
            
            // Valider les données
            $this->validateData($data);
            
            // Créer l'entité
            $entity = $this->createEntity($data);
            
            // Persister l'entité
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => $this->getEntityName() . ' ajouté avec succès',
                'id' => method_exists($entity, 'getId') ? $entity->getId() : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout d\'un ' . $this->getEntityName(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout : ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$currentUser) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->canEdit()) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $entity = $this->getRepository()->find($id);
            
            if (!$entity) {
                return $this->json([
                    'success' => false,
                    'message' => $this->getEntityName() . ' non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $data = $this->getRequestData($request);
            
            // Valider les données
            $this->validateData($data);
            
            // Mettre à jour l'entité
            $this->updateEntity($entity, $data);
            
            // Persister les changements
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => $this->getEntityName() . ' mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la modification d\'un ' . $this->getEntityName(), [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$currentUser) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->canDelete()) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $entity = $this->getRepository()->find($id);
            
            if (!$entity) {
                return $this->json([
                    'success' => false,
                    'message' => $this->getEntityName() . ' non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => $this->getEntityName() . ' supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression d\'un ' . $this->getEntityName(), [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    protected function getRequestData(Request $request): array
    {
        return $request->request->all();
    }
} 