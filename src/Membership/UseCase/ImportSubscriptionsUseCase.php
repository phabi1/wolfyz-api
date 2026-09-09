<?php

namespace App\Membership\UseCase;

use App\Core\Di\Locator;
use App\Core\Entity\EntityManager;
use App\Core\Helper\DateHelper;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Watchdog\WatchdogAwareInterface;
use App\Core\Watchdog\WatchdogAwareTrait;
use App\Membership\Helper\MemberHelper;
use App\Membership\Model\LicenseType;

class ImportSubscriptionsUseCase implements UseCaseInterface, WatchdogAwareInterface
{
    use WatchdogAwareTrait;

    private $subscriptionRepository;

    private $contactRepository;

    private $memberRepository;

    private $sessionRepository;

    private $memberHelper;

    private $dateHelper;

    private $nameHelper;

    private $phoneHelper;
    private $emailHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper, Locator $helpers)
    {
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        $this->contactRepository = $entityManager->getRepository('wolf-memberships.contact');
        $this->sessionRepository = $entityManager->getRepository('wolf-memberships.session');
        $this->memberHelper = $memberHelper;
        $this->dateHelper = $helpers->get('date');
        $this->nameHelper = $helpers->get('name');
        $this->phoneHelper = $helpers->get('phone');
        $this->emailHelper = $helpers->get('email');
    }

    public function execute(array $params = [])
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID parameter is required');
        }
        $file = $params['file'] ?? null;
        if (!$file) {
            throw new \InvalidArgumentException('File parameter is required');
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not open file for reading');
        }

        $log = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $separator = isset($params['separator']) ? $params['separator'] : ',';

        $orders = [];

        $fields = isset($params['fields']) ? $params['fields'] : [
            'order_id' => 'Référence commande',
            'subscribed_at' => 'Date de la commande',
            'firstName' => 'Prénom adhérent',
            'lastName' => 'Nom adhérent',
            'birthdate' => 'Date de naissance de l\'adhérent',
            'lesson' => 'Tarif',
            'license_type' => 'Type de licence',
            'licence' => 'Numéro de licence',
            'legal_guardian_lastName_1' => 'Nom du tuteur légal 1 (obligatoire si adhérent mineur)',
            'legal_guardian_firstName_1' => 'Prénom du tuteur légal 1 (obligatoire si adhérent mineur)',
            'legal_guardian_phone_1' => 'Téléphone du tuteur légal 1 (obligatoire si adhérent mineur)',
            'legal_guardian_lastName_2' => 'Nom du tuteur légal 2 (obligatoire si adhérent mineur)',
            'legal_guardian_firstName_2' => 'Prénom du tuteur légal 2 (obligatoire si adhérent mineur)',
            'legal_guardian_phone_2' => 'Téléphone du tuteur légal 2 (obligatoire si adhérent mineur)',
            'address_number' => 'Numéro de voie',
            'address_street_type' => 'Type de voie',
            'address_line_1' => 'Nom de la voie',
            'address_line_2' => 'Complément',
            'postal_code' => 'Code postal',
            'city' => 'Commune',
            'country' => 'Pays',
            'payer_firstname' => 'Prénom payeur',
            'payer_lastname' => 'Nom payeur',
            'payer_email' => 'Email payeur',
        ];

        $header = fgetcsv($handle, 0, $separator);
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            // Transform the row into an associative array using the header
            $data = $this->transformRowWithHeader($fields, $header, $row);

            $birthdate = $this->extractBirthdate($data);
            $hash = $this->memberHelper->generateHash($data['firstName'], $data['lastName'], $birthdate);
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

            if (!isset($data['license_type']) || !LicenseType::isValidType($data['license_type'])) {
                $log['skipped']++;
                continue;
            }

            $subscribedAt = null;
            try {
                $subscribedAt = isset($data['subscribed_at']) && !empty($data['subscribed_at'])
                    ? $this->dateHelper->convertToTimestamp($data['subscribed_at'], DateHelper::FORMAT_DMYHI)
                    : time();
            } catch (\Exception $e) {
                $subscribedAt = time();
            }

            $subscriptionData = [
                'subscribed_at' => $subscribedAt,
                'license_type' => $data['license_type'],
                'member_id' => $member->id,
                'campaign_id' => $campaignId,
            ];

            try {
                $subscription = $this->subscriptionRepository->insert($subscriptionData);
            } catch (\Exception $e) {
                $this->watchdogService->error('Failed to insert subscription for member ' . $member->id, ['exception' => $e->getMessage()]);
                $log['skipped']++;
                continue;
            }

            $contactData = $this->extractContacts($data);
            foreach ($contactData as $contact) {
                try {
                    $this->contactRepository->insert([
                        'firstname' => $contact['firstName'],
                        'lastname' => $contact['lastName'],
                        'phone' => $contact['phone'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'owner' => $contact['owner'] ?? false,
                        'subscription_id' => $subscription->id,
                    ]);
                } catch (\Exception $e) {
                    $this->watchdogService->error('Failed to insert contact for subscription ' . $subscription->id, ['exception' => $e->getMessage()]);
                    $log['skipped']++;
                    continue;
                }
            }


            if (!empty($data['lesson'])) {
                try {
                    $this->sessionRepository->insert([
                        'lesson_id' => $data['lesson'],
                        'subscription_id' => $subscription->id,
                        'member_id' => $member->id,
                        'campaign_id' => $campaignId,
                    ]);
                } catch (\Exception $e) {
                    $this->watchdogService->error('Failed to insert session for subscription ' . $subscription->id, ['exception' => $e->getMessage()]);
                    $log['skipped']++;
                    continue;
                }
            }

            $log['created']++;
        }
        fclose($handle);
        return $log;
    }

    private function extractAddress(array &$data): array
    {

        $zipcode = $data['postal_code'] ?? null;
        if ($zipcode !== null) {
            $zipcode = str_pad($zipcode, 5, '0', STR_PAD_LEFT);
        }

        return [
            'number' => $data['address_number'] ?? null,
            'street_type' => $data['address_street_type'] ?? null,
            'line_1' => $data['address_line_1'] ?? null,
            'line_2' => $data['address_line_2'] ?? null,
            'postal_code' => $zipcode,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
        ];
    }

    /**
     * Extracts contact information from the data array.
     * @param array $data
     * @return array
     */
    private function extractContacts(array &$data): array
    {
        $contacts = [];
        for ($i = 1; $i <= 2; $i++) {
            if (!empty($data["legal_guardian_lastName_$i"]) && !empty($data["legal_guardian_firstName_$i"])) {
                $contacts[] = [
                    'lastName' => $data["legal_guardian_lastName_$i"],
                    'firstName' => $data["legal_guardian_firstName_$i"],
                    'phone' => $data["legal_guardian_phone_$i"] ?? null,
                    'email' => $data["legal_guardian_email_$i"] ?? null,
                    'owner' => false
                ];
            }
        }
        return $contacts;
    }

    /**
     * Transforms a CSV row into an associative array using the provided header mapping.
     * @param array $fields
     * @param array $row
     * @return array
     */
    private function transformRowWithHeader(array $fields, array $header, array $row)
    {
        $data = [];
        $values = array_combine($header, $row);
        foreach ($fields as $key => $fieldName) {
            if (isset($values[$fieldName])) {
                $data[$key] = $values[$fieldName];
            }
        }
        return $data;
    }

    private function extractGender(array &$data): ?string
    {
        if (!isset($data['gender'])) {
            return null;
        }
        return ($data['gender'] === 'F') ? 'female' : 'male';
    }

    /**
     * Creates a new member in the database.
     * @param array $data
     */
    private function createMember(array $data)
    {
        $birthdate = $this->extractBirthdate($data);
        $hash = $this->memberHelper->generateHash($data['firstName'], $data['lastName'], $birthdate);

        return $this->memberRepository->insert([
            'firstname' => $this->nameHelper->firstname($data['firstName']),
            'lastname' => $this->nameHelper->lastname($data['lastName']),
            'birthdate' => $birthdate,
            'address' => $this->extractAddress($data),
            'phone' => $this->phoneHelper->sanitize($data['phone'] ?? null),
            'email' => $this->emailHelper->sanitize($data['email'] ?? null),
            'gender' => $this->extractGender($data),
            'license_number' => $this->isValidLicenseNumber($data['licence'] ?? null) ? $data['licence'] : null,
            'hash' => $hash
        ]);
    }

    /**
     * Updates a member's information if necessary.
     * @param mixed $member
     * @param array $data
     * @return \stdClass Returns member if the member was updated, otherwise returns the original member
     */
    private function updateMember($member, array $data)
    {
        $updateData = [];

        $firstname = $this->nameHelper->firstname($data['firstName']);
        $lastname = $this->nameHelper->lastname($data['lastName']);

        if ($firstname !== $member->firstname) {
            $updateData['firstname'] = $firstname;
        }
        if ($lastname !== $member->lastname) {
            $updateData['lastname'] = $lastname;
        }

        if (
            isset($data['licence'])
            && $this->isValidLicenseNumber($data['licence'])
            && $data['licence'] !== $member->license_number
        ) {
            $updateData['license_number'] = $data['licence'];
        }
        if (isset($data['phone']) && $data['phone'] !== $member->phone) {
            $updateData['phone'] = $this->phoneHelper->sanitize($data['phone'] ?? null);
        }

        if (isset($data['email']) && $data['email'] !== $member->email) {
            $updateData['email'] = $this->emailHelper->sanitize($data['email'] ?? null);
        }

        $gender = $this->extractGender($data);
        if ($gender !== null && $gender !== $member->gender) {
            $updateData['gender'] = $gender;
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

    private function extractBirthdate(array &$data): ?int
    {
        if (isset($data['birthdate'])) {
            list($day, $month, $year) = explode('/', $data['birthdate']);
            $birthdate = strtotime("$year-$month-$day");
            return $birthdate;
        }
        return null;
    }
}