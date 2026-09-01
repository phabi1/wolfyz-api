<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\Membership\Entity\Repository\MemberEntityRepository;
use App\Membership\Helper\MemberHelper;

class ExistsMemberUseCase implements UseCaseInterface
{
    private MemberEntityRepository $memberRepository;

    private MemberHelper $memberHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper)
    {
        $memberRepository = $entityManager->getRepository('wolf-memberships.member');
        if (!$memberRepository instanceof MemberEntityRepository) {
            throw new \RuntimeException('Expected repository of type MemberEntityRepository');
        }
        $this->memberRepository = $memberRepository;
        $this->memberHelper = $memberHelper;
    }

    public function execute(array $params = [])
    {
        $lastname = $params['lastname'] ?? null;
        $firstname = $params['firstname'] ?? null;
        $birthdate = $params['birthdate'] ?? null;

        if (!$lastname || !$firstname || !$birthdate) {
            throw new \InvalidArgumentException('Lastname, firstname and birthdate are required');
        }

        $hash = $this->memberHelper->generateHash($firstname, $lastname, $birthdate);

        $id = $this->memberRepository->existsHash($hash);
        $exists = $id !== null;

        $suggestions = [];
        if (!$exists && $params['suggestions'] ?? false) {
            $suggestions = $this->memberRepository->findSuggestions($lastname, $firstname, $birthdate, $params['minScore'] ?? 0);
        }

        return ['exists' => $exists, 'id' => $id, 'suggestions' => $suggestions];
    }
}