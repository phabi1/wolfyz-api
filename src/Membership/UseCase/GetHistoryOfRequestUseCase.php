<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepository;
use App\Core\UseCase\UseCaseInterface;

class GetHistoryOfRequestUseCase implements UseCaseInterface
{
    private EntityRepository $requestLogRepository;

    function __construct(EntityManager $entityManager)
    {
        $this->requestLogRepository = $entityManager->getRepository('wolf-memberships.request_log');
    }

    public function execute(array $params = []): array
    {
        if (!isset($params['request_id'])) {
            return [];
        }

        $results = $this->requestLogRepository->find([
            'request_id' => ['eq' => $params['request_id']],
        ]);

        $userIds = array_unique(array_map(function ($item) {
            return $item->changed_by;
        }, $results));

        $users = array_reduce(get_users(['include' => $userIds]), function ($carry, $user) {
            $carry[$user->ID] = $user;
            return $carry;
        }, []);

        foreach ($results as $item) {
            $user = isset($users[$item->changed_by]) ? $users[$item->changed_by] : null;
            if ($user) {
                $item->changed_by = [
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'user_email' => $user->user_email,
                ];
            } else {
                $item->changed_by = null;
            }
        }

        return $results ?: [];
    }
}