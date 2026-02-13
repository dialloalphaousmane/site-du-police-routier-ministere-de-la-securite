<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AuditService
{
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_VIEW = 'VIEW';
    public const ACTION_EXPORT = 'EXPORT';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function log(
        string $action,
        string $entity,
        ?string $entityId = null,
        ?array $changes = null,
        ?string $description = null
    ): AuditLog {
        $user = $this->security->getUser();

        $auditLog = new AuditLog();
        $auditLog->setUser($user?->getUserIdentifier() ?? 'Anonymous');
        $auditLog->setAction($action);
        $auditLog->setEntity($entity);
        $auditLog->setEntityId($entityId);
        $auditLog->setChanges($changes);
        $auditLog->setDescription($description);
        $auditLog->setIpAddress($this->getClientIp());
        $auditLog->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);
        $auditLog->setCreatedAt(new \DateTime());

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }

    public function logCreate(string $entity, ?string $entityId = null, ?string $description = null): void
    {
        $this->log(self::ACTION_CREATE, $entity, $entityId, null, $description);
    }

    public function logUpdate(
        string $entity,
        ?string $entityId = null,
        array $changes = [],
        ?string $description = null
    ): void {
        $this->log(self::ACTION_UPDATE, $entity, $entityId, $changes, $description);
    }

    public function logDelete(string $entity, ?string $entityId = null, ?string $description = null): void
    {
        $this->log(self::ACTION_DELETE, $entity, $entityId, null, $description);
    }

    public function logLogin(User $user): void
    {
        $this->log(self::ACTION_LOGIN, 'User', (string)$user->getId(), null, 'Connexion utilisateur');
    }

    public function logLogout(User $user): void
    {
        $this->log(self::ACTION_LOGOUT, 'User', (string)$user->getId(), null, 'Déconnexion utilisateur');
    }

    public function logExport(string $type, int $count, ?string $description = null): void
    {
        $this->log(
            self::ACTION_EXPORT,
            'Export',
            null,
            ['type' => $type, 'count' => $count],
            $description ?? "Export de $count $type"
        );
    }

    private function getClientIp(): ?string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        return $ip;
    }
}
