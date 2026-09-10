<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\EntityController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MemberController extends EntityController
{
    protected $entityName = 'wolf-memberships.member';

    public function existsAction(Request $request)
    {
        $lastname = $request->query->get('lastname');
        if (!$lastname) {
            return new JsonResponse(['error' => 'lastname_required', 'message' => 'Lastname parameter is required'], 400);
        }

        $firstname = $request->query->get('firstname');
        if (!$firstname) {
            return new JsonResponse(['error' => 'firstname_required', 'message' => 'Firstname parameter is required'], 400);
        }

        $birthdate = $request->query->get('birthdate');
        if (!$birthdate) {
            return new JsonResponse(['error' => 'birthdate_required', 'message' => 'Birthdate parameter is required'], 400);
        }

        // Check if valid format for birthdate
        if (!\DateTime::createFromFormat('Y-m-d', $birthdate)) {
            return new JsonResponse(['error' => 'invalid_birthdate', 'message' => 'Birthdate must be in YYYY-MM-DD format'], 400);
        }

        $suggestions = !!$request->query->get('suggestions');

        $result = $this->useCaseBus('wolf-memberships.exists_member', [
            'lastname' => $lastname,
            'firstname' => $firstname,
            'birthdate' => $birthdate,
            'suggestions' => $suggestions,
            'minScore' => $request->query->get('score_min') ?? 0,
        ]);

        return [
            'exists' => $result['exists'],
            'id' => $result['id'] ?? null,
            'member' => $result['member'] ?? null,
            'suggestions' => $result['suggestions'] ?? []
        ];
    }

    public function importAction(Request $request)
    {
        $files = $request->files->all();
        if (empty($files['file'])) {
            return new JsonResponse(['error' => 'file_not_provided', 'message' => 'No file provided for import'], 400);
        }

        $log = $this->useCaseBus('wolf-memberships.import_members', [
            'file' => $files['file']['tmp_name']
        ]);

        return [
            'success' => true,
            'log' => $log
        ];
    }
}