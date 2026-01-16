<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Agent;
use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Amende;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AuthorizationVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE])) {
            return false;
        }

        return $subject instanceof Agent 
            || $subject instanceof Controle 
            || $subject instanceof Infraction 
            || $subject instanceof Amende;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin can do everything
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Direction générale can do everything
        if (in_array('ROLE_DIRECTION_GENERALE', $user->getRoles())) {
            return true;
        }

        // Direction régionale can manage their region
        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles())) {
            return $this->canAccessRegionalData($subject, $user);
        }

        // Chef de brigade can manage their brigade
        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles())) {
            return $this->canAccessBrigadeData($subject, $user);
        }

        // Agent can only view and create
        if (in_array('ROLE_AGENT', $user->getRoles())) {
            return $attribute === self::VIEW || $attribute === self::CREATE;
        }

        return false;
    }

    private function canAccessRegionalData(mixed $subject, User $user): bool
    {
        if ($subject instanceof Agent) {
            return $subject->getRegion()?->getId() === $user->getRegion()?->getId();
        }

        if ($subject instanceof Controle) {
            return $subject->getBrigade()?->getRegion()?->getId() === $user->getRegion()?->getId();
        }

        if ($subject instanceof Infraction) {
            $controle = $subject->getControle();
            return $controle?->getBrigade()?->getRegion()?->getId() === $user->getRegion()?->getId();
        }

        if ($subject instanceof Amende) {
            $infraction = $subject->getInfraction();
            $controle = $infraction?->getControle();
            return $controle?->getBrigade()?->getRegion()?->getId() === $user->getRegion()?->getId();
        }

        return false;
    }

    private function canAccessBrigadeData(mixed $subject, User $user): bool
    {
        if ($subject instanceof Agent) {
            return $subject->getBrigade()?->getId() === $user->getBrigade()?->getId();
        }

        if ($subject instanceof Controle) {
            return $subject->getBrigade()?->getId() === $user->getBrigade()?->getId();
        }

        if ($subject instanceof Infraction) {
            $controle = $subject->getControle();
            return $controle?->getBrigade()?->getId() === $user->getBrigade()?->getId();
        }

        if ($subject instanceof Amende) {
            $infraction = $subject->getInfraction();
            $controle = $infraction?->getControle();
            return $controle?->getBrigade()?->getId() === $user->getBrigade()?->getId();
        }

        return false;
    }
}
