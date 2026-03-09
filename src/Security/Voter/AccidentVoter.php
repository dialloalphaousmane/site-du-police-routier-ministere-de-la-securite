<?php

namespace App\Security\Voter;

use App\Entity\Accident;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AccidentVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [AuthorizationVoter::VIEW, AuthorizationVoter::EDIT, AuthorizationVoter::DELETE, AuthorizationVoter::CREATE], true)) {
            return false;
        }

        return $subject instanceof Accident;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Accident $accident */
        $accident = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_DIRECTION_GENERALE', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles(), true)) {
            return $accident->getRegion()?->getId() !== null && $accident->getRegion()?->getId() === $user->getRegion()?->getId();
        }

        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles(), true)) {
            return $accident->getBrigade()?->getId() !== null && $accident->getBrigade()?->getId() === $user->getBrigade()?->getId();
        }

        if (in_array('ROLE_AGENT', $user->getRoles(), true)) {
            if ($attribute === AuthorizationVoter::VIEW || $attribute === AuthorizationVoter::CREATE) {
                return $accident->getCreatedBy()?->getId() === $user->getId();
            }

            if ($attribute === AuthorizationVoter::EDIT) {
                return $accident->getCreatedBy()?->getId() === $user->getId() && $accident->getStatus() === Accident::STATUS_EN_COURS;
            }

            if ($attribute === AuthorizationVoter::DELETE) {
                return false;
            }
        }

        return false;
    }
}
