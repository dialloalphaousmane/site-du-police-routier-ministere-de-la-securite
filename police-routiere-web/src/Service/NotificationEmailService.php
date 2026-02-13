<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {}

    public function notifyNewControl(User $user, string $controlData): void
    {
        $email = (new Email())
            ->from('noreply@police-routiere.gn')
            ->to($user->getEmail())
            ->subject('Nouveau contrôle enregistré')
            ->htmlTemplate('emails/new_control.html.twig')
            ->context(['user' => $user, 'controlData' => $controlData]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't fail
        }
    }

    public function notifyInfractionDetected(User $chef, string $infractionData): void
    {
        $email = (new Email())
            ->from('noreply@police-routiere.gn')
            ->to($chef->getEmail())
            ->subject('Nouvelle infraction détectée')
            ->htmlTemplate('emails/new_infraction.html.twig')
            ->context(['chef' => $chef, 'infractionData' => $infractionData]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't fail
        }
    }

    public function notifyAmendeIssued(User $user, string $amendeInfo): void
    {
        $email = (new Email())
            ->from('noreply@police-routiere.gn')
            ->to($user->getEmail())
            ->subject('Amende émise')
            ->htmlTemplate('emails/amende_issued.html.twig')
            ->context(['user' => $user, 'amendeInfo' => $amendeInfo]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't fail
        }
    }

    public function notifyAmendePaid(User $user, string $amendeNumber): void
    {
        $email = (new Email())
            ->from('noreply@police-routiere.gn')
            ->to($user->getEmail())
            ->subject('Amende payée - Confirmation')
            ->htmlTemplate('emails/amende_paid.html.twig')
            ->context(['user' => $user, 'amendeNumber' => $amendeNumber]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't fail
        }
    }

    public function notifyDailyReport(User $regionDirector, array $reportData): void
    {
        $email = (new Email())
            ->from('noreply@police-routiere.gn')
            ->to($regionDirector->getEmail())
            ->subject('Rapport quotidien - ' . date('d/m/Y'))
            ->htmlTemplate('emails/daily_report.html.twig')
            ->context(['director' => $regionDirector, 'report' => $reportData]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't fail
        }
    }
}
