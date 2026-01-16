<?php

namespace App\Controller\Admin;

use App\Service\NotificationService;
use App\Repository\ConfigurationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notification')]
#[IsGranted('ROLE_ADMIN')]
class NotificationController extends AbstractController
{
    private $notificationService;
    private $configRepository;
    private $userRepository;
    private $entityManager;

    public function __construct(
        NotificationService $notificationService,
        ConfigurationRepository $configRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationService = $notificationService;
        $this->configRepository = $configRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_admin_notification_index', methods: ['GET'])]
    public function index(): Response
    {
        // Récupérer les configurations de notification
        $emailEnabled = $this->configRepository->findByCle('email_notifications_enabled');
        $rapportValidation = $this->configRepository->findByCle('notification_rapport_validation');
        $nouveauControle = $this->configRepository->findByCle('notification_nouveau_controle');
        $seuilAlerte = $this->configRepository->findByCle('notification_seuil_alerte');
        
        // Récupérer les utilisateurs pour les tests
        $users = $this->userRepository->findBy(['isActive' => true]);
        
        return $this->render('admin/notification/index.html.twig', [
            'email_enabled' => $emailEnabled ? $emailEnabled->getValeur() === 'true' : false,
            'rapport_validation' => $rapportValidation ? $rapportValidation->getValeur() === 'true' : false,
            'nouveau_controle' => $nouveauControle ? $nouveauControle->getValeur() === 'true' : false,
            'seuil_alerte' => $seuilAlerte ? $seuilAlerte->getValeur() : '10',
            'users' => $users
        ]);
    }

    #[Route('/test', name: 'app_admin_notification_test', methods: ['POST'])]
    public function testNotification(Request $request): JsonResponse
    {
        $type = $request->request->get('type');
        $userId = $request->request->get('user');
        
        $user = null;
        if ($userId) {
            $user = $this->userRepository->find($userId);
        }
        
        $success = false;
        $message = '';
        
        switch ($type) {
            case 'welcome':
                if ($user) {
                    $success = $this->notificationService->sendWelcomeNotification($user);
                    $message = $success ? 'Notification de bienvenue envoyée avec succès' : 'Erreur lors de l\'envoi de la notification';
                }
                break;
                
            case 'seuil_alerte':
                $statistiques = [
                    'infractions' => 15,
                    'controls' => 25
                ];
                $success = $this->notificationService->sendSeuilAlerteNotification($statistiques);
                $message = $success ? 'Alerte de seuil envoyée avec succès' : 'Erreur lors de l\'envoi de l\'alerte';
                break;
                
            case 'maintenance':
                $success = $this->notificationService->sendMaintenanceNotification('Test de maintenance - Le système sera en maintenance de 14h à 16h');
                $message = $success ? 'Notification de maintenance envoyée avec succès' : 'Erreur lors de l\'envoi de la notification';
                break;
                
            case 'backup':
                $success = $this->notificationService->sendBackupNotification('Test de sauvegarde - Sauvegarde complétée avec succès', true);
                $message = $success ? 'Notification de sauvegarde envoyée avec succès' : 'Erreur lors de l\'envoi de la notification';
                break;
                
            default:
                $data = [
                    'message' => 'Test de notification générale',
                    'date' => date('d/m/Y H:i')
                ];
                $success = $this->notificationService->sendNotification('notification', $data, $user);
                $message = $success ? 'Notification générale envoyée avec succès' : 'Erreur lors de l\'envoi de la notification';
                break;
        }
        
        return new JsonResponse([
            'success' => $success,
            'message' => $message
        ]);
    }

    #[Route('/config', name: 'app_admin_notification_config', methods: ['POST'])]
    public function updateConfig(Request $request): JsonResponse
    {
        $emailEnabled = $request->request->get('email_enabled');
        $rapportValidation = $request->request->get('rapport_validation');
        $nouveauControle = $request->request->get('nouveau_controle');
        $seuilAlerte = $request->request->get('seuil_alerte');
        
        // Mettre à jour les configurations
        $configs = [
            'email_notifications_enabled' => $emailEnabled,
            'notification_rapport_validation' => $rapportValidation,
            'notification_nouveau_controle' => $nouveauControle,
            'notification_seuil_alerte' => $seuilAlerte
        ];
        
        foreach ($configs as $cle => $valeur) {
            $config = $this->configRepository->findByCle($cle);
            if ($config) {
                $config->setValeur($valeur ? 'true' : 'false');
                $config->setUpdatedBy($this->getUser());
                $this->entityManager->flush();
            }
        }
        
        $this->addFlash('success', 'Configuration des notifications mise à jour avec succès !');
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Configuration mise à jour'
        ]);
    }

    #[Route('/send', name: 'app_admin_notification_send', methods: ['POST'])]
    public function sendCustomNotification(Request $request): JsonResponse
    {
        $recipient = $request->request->get('recipient');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');
        
        $user = null;
        if ($recipient && $recipient !== 'all') {
            $user = $this->userRepository->find($recipient);
        }
        
        $data = [
            'message' => $message,
            'subject' => $subject,
            'date' => date('d/m/Y H:i')
        ];
        
        $success = $this->notificationService->sendNotification('notification', $data, $user);
        
        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Notification envoyée avec succès' : 'Erreur lors de l\'envoi'
        ]);
    }

    #[Route('/status', name: 'app_admin_notification_status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        // Vérifier si le service de notification est fonctionnel
        $emailConfig = $this->configRepository->findByCle('email_notifications_enabled');
        $isEmailEnabled = $emailConfig ? $emailConfig->getValeur() === 'true' : false;
        
        // Test de configuration SMTP (simulé)
        $smtpConfig = [
            'configured' => true,
            'host' => 'localhost',
            'port' => 25,
            'encryption' => 'none'
        ];
        
        return new JsonResponse([
            'email_enabled' => $isEmailEnabled,
            'smtp_config' => $smtpConfig,
            'service_status' => 'operational',
            'last_check' => date('d/m/Y H:i:s')
        ]);
    }
}
