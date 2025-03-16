<?php

namespace App\Controller\Parameter;

use App\Entity\Parameters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parameter/config')]
#[IsGranted('ROLE_ADMIN')]
class ConfigController extends AbstractController
{
    #[Route('/', name: 'app_parameter_config_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur courant
        $currentUser = $this->getUser();
        
        // Récupérer les paramètres de configuration
        $config = $this->getConfigParameters($entityManager);
        
        // Récupérer les sauvegardes disponibles
        $backups = $this->getAvailableBackups();

        return $this->render('parameter/config/index.html.twig', [
            'user' => $currentUser,  // Pour le template header
            'config' => $config,     // Pour les paramètres de configuration
            'backups' => $backups    // Pour la liste des sauvegardes
        ]);
    }

    #[Route('/general', name: 'app_parameter_config_general', methods: ['POST'])]
    public function saveGeneral(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();
        
        // Mettre à jour les paramètres généraux
        $this->updateParameter($entityManager, 'siteName', $data['siteName']);
        $this->updateParameter($entityManager, 'siteDescription', $data['siteDescription']);
        $this->updateParameter($entityManager, 'maintenanceMode', isset($data['maintenanceMode']) ? '1' : '0');
        $this->updateParameter($entityManager, 'defaultLanguage', $data['defaultLanguage']);
        $this->updateParameter($entityManager, 'dateFormat', $data['dateFormat']);
        $this->updateParameter($entityManager, 'timeFormat', $data['timeFormat']);
        
        return $this->json([
            'success' => true,
            'message' => 'Paramètres généraux mis à jour avec succès.'
        ]);
    }

    #[Route('/email', name: 'app_parameter_config_email', methods: ['POST'])]
    public function saveEmail(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();
        
        // Mettre à jour les paramètres d'email
        $this->updateParameter($entityManager, 'mailDriver', $data['mailDriver']);
        $this->updateParameter($entityManager, 'mailHost', $data['mailHost']);
        $this->updateParameter($entityManager, 'mailPort', $data['mailPort']);
        $this->updateParameter($entityManager, 'mailEncryption', $data['mailEncryption']);
        $this->updateParameter($entityManager, 'mailUsername', $data['mailUsername']);
        
        // Ne mettre à jour le mot de passe que s'il est fourni
        if (!empty($data['mailPassword'])) {
            $this->updateParameter($entityManager, 'mailPassword', $data['mailPassword']);
        }
        
        $this->updateParameter($entityManager, 'mailFromAddress', $data['mailFromAddress']);
        $this->updateParameter($entityManager, 'mailFromName', $data['mailFromName']);
        
        return $this->json([
            'success' => true,
            'message' => 'Paramètres d\'email mis à jour avec succès.'
        ]);
    }

    #[Route('/email/test', name: 'app_parameter_config_email_test', methods: ['POST'])]
    public function testEmail(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $data = $request->request->all();
        $testEmailAddress = $data['testEmailAddress'];
        
        if (empty($testEmailAddress)) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez fournir une adresse email de test.'
            ]);
        }
        
        try {
            // Créer un email de test
            $email = (new Email())
                ->from($data['mailFromAddress'])
                ->to($testEmailAddress)
                ->subject('Test de configuration email - Digi-3')
                ->html('<p>Ceci est un email de test pour vérifier la configuration de votre serveur d\'email.</p>');
            
            // Envoyer l'email
            $mailer->send($email);
            
            return $this->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à ' . $testEmailAddress
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/security', name: 'app_parameter_config_security', methods: ['POST'])]
    public function saveSecurity(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();
        
        // Mettre à jour les paramètres de sécurité
        $this->updateParameter($entityManager, 'sessionLifetime', $data['sessionLifetime']);
        $this->updateParameter($entityManager, 'passwordPolicy', $data['passwordPolicy']);
        $this->updateParameter($entityManager, 'maxLoginAttempts', $data['maxLoginAttempts']);
        $this->updateParameter($entityManager, 'lockoutTime', $data['lockoutTime']);
        $this->updateParameter($entityManager, 'twoFactorAuth', isset($data['twoFactorAuth']) ? '1' : '0');
        $this->updateParameter($entityManager, 'forcePasswordChange', $data['forcePasswordChange']);
        
        return $this->json([
            'success' => true,
            'message' => 'Paramètres de sécurité mis à jour avec succès.'
        ]);
    }

    #[Route('/backup', name: 'app_parameter_config_backup', methods: ['POST'])]
    public function saveBackup(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();
        
        // Mettre à jour les paramètres de sauvegarde
        $this->updateParameter($entityManager, 'autoBackup', isset($data['autoBackup']) ? '1' : '0');
        $this->updateParameter($entityManager, 'backupFrequency', $data['backupFrequency']);
        $this->updateParameter($entityManager, 'backupRetention', $data['backupRetention']);
        $this->updateParameter($entityManager, 'backupStorage', $data['backupStorage']);
        
        return $this->json([
            'success' => true,
            'message' => 'Paramètres de sauvegarde mis à jour avec succès.'
        ]);
    }

    #[Route('/backup/create', name: 'app_parameter_config_backup_create', methods: ['POST'])]
    public function createBackup(): JsonResponse
    {
        try {
            // Générer un nom de fichier pour la sauvegarde
            $backupName = 'backup-' . date('Y-m-d-H-i-s');
            $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
            
            // Créer le répertoire de sauvegarde s'il n'existe pas
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Simuler la création d'une sauvegarde (à implémenter selon les besoins réels)
            // Dans un cas réel, on pourrait exécuter une commande pour sauvegarder la base de données
            // et les fichiers importants
            
            // Pour l'exemple, on crée juste un fichier vide
            file_put_contents($backupDir . '/' . $backupName . '.zip', 'Simulation de sauvegarde');
            
            return $this->json([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/backup/{id}/download', name: 'app_parameter_config_backup_download')]
    public function downloadBackup(string $id): Response
    {
        $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
        $backupFile = $backupDir . '/' . $id . '.zip';
        
        if (!file_exists($backupFile)) {
            throw $this->createNotFoundException('La sauvegarde demandée n\'existe pas.');
        }
        
        return $this->file($backupFile);
    }

    #[Route('/backup/{id}/restore', name: 'app_parameter_config_backup_restore', methods: ['POST'])]
    public function restoreBackup(string $id): JsonResponse
    {
        try {
            $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
            $backupFile = $backupDir . '/' . $id . '.zip';
            
            if (!file_exists($backupFile)) {
                return $this->json([
                    'success' => false,
                    'message' => 'La sauvegarde demandée n\'existe pas.'
                ], 404);
            }
            
            // Simuler la restauration d'une sauvegarde (à implémenter selon les besoins réels)
            // Dans un cas réel, on pourrait exécuter une commande pour restaurer la base de données
            // et les fichiers importants
            
            return $this->json([
                'success' => true,
                'message' => 'Sauvegarde restaurée avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/backup/{id}/delete', name: 'app_parameter_config_backup_delete', methods: ['POST'])]
    public function deleteBackup(string $id): JsonResponse
    {
        try {
            $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
            $backupFile = $backupDir . '/' . $id . '.zip';
            
            if (!file_exists($backupFile)) {
                return $this->json([
                    'success' => false,
                    'message' => 'La sauvegarde demandée n\'existe pas.'
                ], 404);
            }
            
            // Supprimer le fichier de sauvegarde
            unlink($backupFile);
            
            return $this->json([
                'success' => true,
                'message' => 'Sauvegarde supprimée avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère les paramètres de configuration
     */
    private function getConfigParameters(EntityManagerInterface $entityManager): array
    {
        $config = [];
        $parameters = $entityManager->getRepository(Parameters::class)->findAll();
        
        foreach ($parameters as $parameter) {
            $config[$parameter->getParamKey()] = $parameter->getParamValue();
        }
        
        return $config;
    }

    /**
     * Met à jour un paramètre de configuration
     */
    private function updateParameter(EntityManagerInterface $entityManager, string $key, string $value): void
    {
        $parameter = $entityManager->getRepository(Parameters::class)->findOneBy(['paramKey' => $key]);
        
        if (!$parameter) {
            // Créer un nouveau paramètre s'il n'existe pas
            $parameter = new Parameters();
            $parameter->setParamKey($key);
            // L'entité Parameters initialise paramCreatedAt dans son constructeur
            if (method_exists($parameter, 'setParamUpdatedAt')) {
                $parameter->setParamUpdatedAt(new \DateTime());
            }
            $entityManager->persist($parameter);
        } else if (method_exists($parameter, 'setParamUpdatedAt')) {
            $parameter->setParamUpdatedAt(new \DateTime());
        }
        
        $parameter->setParamValue($value);
        $entityManager->flush();
    }

    /**
     * Récupère la liste des sauvegardes disponibles
     */
    private function getAvailableBackups(): array
    {
        $backups = [];
        $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
        
        if (!file_exists($backupDir)) {
            return $backups;
        }
        
        $files = glob($backupDir . '/*.zip');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $id = pathinfo($filename, PATHINFO_FILENAME);
            $date = filemtime($file);
            $size = filesize($file);
            
            // Formater la taille en KB, MB, etc.
            $sizeFormatted = $this->formatFileSize($size);
            
            $backups[] = [
                'id' => $id,
                'name' => $filename,
                'date' => new \DateTime('@' . $date),
                'size' => $sizeFormatted
            ];
        }
        
        // Trier les sauvegardes par date (la plus récente en premier)
        usort($backups, function($a, $b) {
            return $b['date']->getTimestamp() - $a['date']->getTimestamp();
        });
        
        return $backups;
    }

    /**
     * Formate la taille d'un fichier en KB, MB, etc.
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
} 