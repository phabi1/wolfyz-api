<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Helper\DateHelper;
use App\Membership\Helper\MemberHelper;
use App\Membership\Model\LicenseType;

class ConvertRequestToSubscriptionsUseCase implements UseCaseInterface
{
    private $subscriptionRepository;

    private EntityRepositoryInterface $requestRepository;

    private $contactRepository;

    private $memberRepository;

    private $sessionRepository;

    private $memberHelper;

    private $dateHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper, DateHelper $dateHelper)
    {
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        $this->contactRepository = $entityManager->getRepository('wolf-memberships.contact');
        $this->sessionRepository = $entityManager->getRepository('wolf-memberships.session');
        $this->memberHelper = $memberHelper;
        $this->dateHelper = $dateHelper;
    }

    public function execute(array $params = [])
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID parameter is required');
        }

        $requestId = $params['request_id'] ?? null;
        if (!$requestId) {
            throw new \InvalidArgumentException('Request ID parameter is required');
        }

        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \Exception('Request not found.');
        }

        $log = [
            'created' => 0,
            'skipped' => 0,
        ];

        foreach ($request->data->participants as $data) {
            $birthdate = $this->extractBirthdate($data);
            $hash = $this->memberHelper->generateHash($data->firstname, $data->lastname, $birthdate);
            $existsingMember = $this->memberRepository->findOne([
                'hash' => ['eq' => $hash],
            ]);


            if ($existsingMember) {
                $member = $this->updateMember($existsingMember, $data);
            } else {
                $member = $this->createMember($data);
            }

            $existingSubscription = $this->subscriptionRepository->findOne([
                'member_id' => ['eq' => $member->id],
                'campaign_id' => ['eq' => $campaignId],
            ]);

            if ($existingSubscription) {
                $log['skipped']++;
                continue;
            }

            if (!isset($data->license_type) || !LicenseType::isValidType($data->license_type)) {
                $log['skipped']++;
                continue;
            }

            $subscribedAt = time();

            $allowedFields = ['medical_cerificate', 'identity_photo', 'health_questionnaire', 'agree_exit', 'agree_photo'];
            $fields = [];
            foreach ($allowedFields as $key) {
                if (isset($data->$key)) {
                    $fields[$key] = $data->$key;
                }
            }

            $subscriptionData = [
                'subscribed_at' => $subscribedAt,
                'license_type' => $data->license_type,
                'fields' => $fields,
                'member_id' => $member->id,
                'request_id' => $request->id,
                'campaign_id' => $campaignId,
            ];

            $subscription = $this->subscriptionRepository->insert($subscriptionData);

            $contactData = $this->extractContacts($data);
            foreach ($contactData as $contact) {
                $this->contactRepository->insert([
                    'firstname' => $contact['firstname'],
                    'lastname' => $contact['lastname'],
                    'phone' => $contact['phone'] ?? null,
                    'email' => $contact['email'] ?? null,
                    'subscription_id' => $subscription->id,
                ]);
            }

            if (!empty($data->lesson_id)) {
                $this->sessionRepository->insert([
                    'lesson_id' => $data->lesson_id,
                    'subscription_id' => $subscription->id,
                    'member_id' => $member->id,
                    'campaign_id' => $campaignId,
                ]);
            }

            $log['created']++;
        }
        return $log;
    }

    private function extractGender(array &$data): ?string
    {
        if (!isset($data['gender'])) {
            return null;
        }
        return ($data['gender'] === 'F') ? 'female' : 'male';
    }

    private function extractAddress(\stdClass $data): array
    {
        if (!isset($data->address)) {
            return [
                'line_1' => null,
                'line_2' => null,
                'postal_code' => null,
                'city' => null,
                'country' => null,
            ];
        }

        $address = $data->address;

        $zipcode = $address->zipcode ?? null;
        if ($zipcode !== null) {
            $zipcode = str_pad($zipcode, 5, '0', STR_PAD_LEFT);
        }

        return [
            'line_1' => $address->line1 ?? null,
            'line_2' => $address->line2 ?? null,
            'postal_code' => $zipcode,
            'city' => $address->city ?? null,
            'country' => $address->country ?? null,
        ];
    }

    /**
     * Extracts contact information from the data array.
     * @param \stdClass $data
     * @return array
     */
    private function extractContacts(\stdClass $data): array
    {
        $contacts = [];
        for ($i = 1; $i <= 2; $i++) {
            if (property_exists($data, "tutor$i")) {
                $tutor = $data->{"tutor$i"};
                if (!empty($tutor->lastname) && !empty($tutor->firstname)) {
                    $contacts[] = [
                        'lastname' => $tutor->lastname,
                        'firstname' => $tutor->firstname,
                        'phone' => $tutor->phone ?? null,
                        'email' => $tutor->email ?? null,
                    ];
                }
            }
        }
        return $contacts;
    }

    /**
     * Creates a new member in the database.
     * @param \stdClass $data
     */
    private function createMember(\stdClass $data)
    {
        $birthdate = $this->extractBirthdate($data);
        $hash = $this->memberHelper->generateHash($data->firstname, $data->lastname, $birthdate);

        return $this->memberRepository->insert([
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'birthdate' => $birthdate,
            'gender' => $this->extractGender($data),
            'address' => $this->extractAddress($data),
            'email' => $data->email ?? null,
            'phone' => $data->phone ?? null,
            'license_number' => $this->isValidLicenseNumber($data->licence ?? null) ? $data->licence : null,
            'hash' => $hash
        ]);
    }

    /**
     * Updates a member's information if necessary.
     * @param mixed $member
     * @param \stdClass $data
     * @return \stdClass Returns the member object after attempting an update. If no update was needed, returns the original member object.
     */
    private function updateMember($member, \stdClass $data)
    {
        $updateData = [];

        if (
            isset($data->licence)
            && $this->isValidLicenseNumber($data->licence)
            && $data->licence !== $member->license_number
        ) {
            $updateData['license_number'] = $data->licence;
        }

        $gender = $this->extractGender($data);
        if ($gender !== null && $gender !== $member->gender) {
            $updateData['gender'] = $gender;
        }

        if (isset($data->phone) && $data->phone !== $member->phone) {
            $updateData['phone'] = $data->phone;
        }

        if (isset($data->email) && $data->email !== $member->email) {
            $updateData['email'] = $data->email;
        }

        $address = $this->extractAddress($data);
        if ($address !== $member->address) {
            $updateData['address'] = $address;
        }

        if (empty($updateData)) {
            return $member;
        }

        return $this->memberRepository->update($member->id, $updateData);
    }

    /**
     * Validates the license number.
     * @param string|null $licenseNumber
     * @return bool
     */
    private function isValidLicenseNumber(?string $licenseNumber): bool
    {
        if ($licenseNumber === null) {
            return false;
        }
        // Implement your validation logic here (e.g., regex check)
        return preg_match('/^[0-9]+$/', $licenseNumber);
    }

    /**
     * Extracts the birthdate from the data object and converts it to a timestamp.
     * @param \stdClass $data
     * @return int|null Returns the timestamp of the birthdate or null if not set
     */
    private function extractBirthdate(\stdClass $data): ?int
    {
        if (isset($data->birthdate)) {
            $birthdate = strtotime($data->birthdate);
            return $birthdate;
        }
        return null;
    }
}