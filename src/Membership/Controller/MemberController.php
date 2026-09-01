<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\EntityController;
use App\Core\UseCase\UseCaseBus;
use App\Membership\Entity\Service\MemberEntityService;

class MemberController extends EntityController
{
    private $useCaseBus;

    protected $entityName = 'wolf-memberships.member';

    public function __construct(UseCaseBus $useCaseBus, MemberEntityService $entityService)
    {
        $this->entityService = $entityService;
        $this->entityService->setEntityName($this->entityName);

        $this->useCaseBus = $useCaseBus;
    }

    public function existsAction($request)
    {
        $lastname = $request->get_param('lastname');
        if (!$lastname) {
            return new \WP_Error('lastname_required', 'Lastname parameter is required', ['status' => 400]);
        }

        $firstname = $request->get_param('firstname');
        if (!$firstname) {
            return new \WP_Error('firstname_required', 'Firstname parameter is required', ['status' => 400]);
        }

        $birthdate = $request->get_param('birthdate');
        if (!$birthdate) {
            return new \WP_Error('birthdate_required', 'Birthdate parameter is required', ['status' => 400]);
        }

        // Check if valid format for birthdate
        if (!\DateTime::createFromFormat('Y-m-d', $birthdate)) {
            return new \WP_Error('invalid_birthdate', 'Birthdate must be in YYYY-MM-DD format', ['status' => 400]);
        }

        $suggestions = !!$request->get_param('suggestions');

        $result = $this->useCaseBus->execute('wolf-memberships.exists_member', [
            'lastname' => $lastname,
            'firstname' => $firstname,
            'birthdate' => $birthdate,
            'suggestions' => $suggestions,
            'minScore' => $request->get_param('score_min') ?? 0,
        ]);

        return [
            'exists' => $result['exists'],
            'id' => $result['id'] ?? null,
            'suggestions' => $result['suggestions'] ?? []
        ];
    }

    public function hashAction($request)
    {
        global $wpdb;
        $memberHelper = $this->getService('wolf-memberships.helper.member');

       $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wolf_memberships_member");

        foreach ($members as $member) {
            $hash = $memberHelper->generateHash($member->firstname, $member->lastname, $member->birthdate);
            $wpdb->update(
                "{$wpdb->prefix}wolf_memberships_member",
                ['hash' => $hash],
                ['id' => $member->id],
            );
        }

        return [
            'success' => true
        ];
    }

    public function importAction($request)
    {
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            throw new \WP_Error('file_not_provided', 'No file provided for import', ['status' => 400]);
        }

        $log = $this->useCaseBus->execute('wolf-memberships.import_members', [
            'file' => $files['file']['tmp_name']
        ]);

        return [
            'success' => true,
            'log' => $log
        ];
    }
}