<?php

namespace App\Membership\UseCase;

use App\Core\Di\Locator;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Helper\DateHelper;
use App\Core\Helper\NameHelper;
use App\Core\Helper\EmailHelper;
use App\Core\Helper\PhoneHelper;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Watchdog\WatchdogAwareInterface;
use App\Core\Watchdog\WatchdogAwareTrait;
use App\Membership\Helper\MemberHelper;

class SyncSubscriptionsUseCase implements UseCaseInterface, WatchdogAwareInterface
{
    use WatchdogAwareTrait;

    private EntityRepositoryInterface $memberRepository;

    private EntityRepositoryInterface $subscriptionRepository;

    private EntityRepositoryInterface $contactRepository;

    private $memberHelper;

    private DateHelper $dateHelper;

    private PhoneHelper $phoneHelper;

    private EmailHelper $emailHelper;

    private NameHelper $nameHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper, Locator $helpers)
    {
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        $this->contactRepository = $entityManager->getRepository('wolf-memberships.contact');
        $this->memberHelper = $memberHelper;
        $this->dateHelper = $helpers->get('date');
        $this->phoneHelper = $helpers->get('phone');
        $this->emailHelper = $helpers->get('email');
        $this->nameHelper = $helpers->get('name');
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
            'subscriptions' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'error' => 0,
            ],
            'contacts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'error' => 0,
            ],
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
            'gender' => 'Sexe',
            'nationality' => 'Nationalité de l\'adhérent',
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
            'phone' => 'Téléphone mobile',
            'medical_certificate' => 'Certificat médical (moins d\'1 an pour licence compétition) ou Attestation',
            'identity_photo' => 'Photo d\'identité (obligatoire pour les licences compétition) Format pris en charge dans rolskanet : jpeg, png, gif',
            'agree_image' => 'J’autorise le club, la fédération ou ses ligues/comités à exploiter toutes les photos et vidéos prises dans le cadre des activités fédérales pour des actions publicitaires ou promotionnelles, conformément à l’article L. 333-1 du Code du sport. Cette autor',
            'doctor' => 'Nom du médecin (non obligatoire si attestation)',
            'discipline' => 'Discipline',
            'license_1' => 'Licence moins 6 ans',
            'license_2' => 'Licence 6 - 12 ans',
            'license_3' => 'Licence 13 ans et plus',
        ];

        $header = fgetcsv($handle, 0, $separator);

        $rowIndex = 0;
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            $rowIndex++;
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

            if (!$existingSubscription) {
                $this->watchdogService->debug('Skipping subscription for campaign ' . $campaignId, ['member' => $member, 'data' => $data]);
                $log['subscriptions']['skipped']++;
                continue;
            }

            $subscriptionData = [];

            $licensePaid = $this->extractLicensePaid($data);

            $subscriptionFields = [
                'identity_photo' => $data['identity_photo'] ?? null,
                'medical_certificate' => $data['medical_certificate'] ?? null,
                'agree_exit' => $this->extractBoolean($data['agree_exit'] ?? null),
                'agree_image' => $this->extractBoolean($data['agree_image'] ?? null),
                'doctor' => $data['doctor'] ?? null,
                'discipline' => $data['discipline'] ?? null,
                'license_paid' => $licensePaid,
            ];

            $subscriptionData['fields'] = $subscriptionFields;

            try {
                $this->subscriptionRepository->update($existingSubscription->id, $subscriptionData);
            } catch (\Exception $e) {
                $this->watchdogService->error('Failed to update subscription ' . $existingSubscription->id, ['exception' => $e->getMessage()]);
                $log['subscriptions']['error']++;
                continue;
            }

            $contactLog = $this->syncContacts($existingSubscription, $data);
            foreach ($contactLog as $key => $value) {
                $log['contacts'][$key] += $value;
            }

            $log['subscriptions']['updated']++;
        }
        fclose($handle);
        return $log;
    }

    private function extractLicensePaid(array $data): bool
    {
        if (
            $this->extractBoolean($data['license_1'])
            || $this->extractBoolean($data['license_2'])
            || $this->extractBoolean($data['license_3'])
        ) {
            return true;
        }
        return false;
    }

    private function syncContacts($existingSubscription, array $data): array
    {
        $log = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'error' => 0,
        ];
        $oldContacts = $this->contactRepository->find(['subscription_id' => ['eq' => $existingSubscription->id]]);
        $contacts = $this->extractContacts($data);
        foreach ($contacts as $contact) {
            $found = null;
            // Check if the contact already exists in the old contacts list based on firstname and lastname
            foreach ($oldContacts as $oldContact) {
                $oldFirstname = $this->nameHelper->firstname($oldContact->firstname);
                $oldLastname = $this->nameHelper->lastname($oldContact->lastname);
                if ($oldFirstname === $contact['firstname'] && $oldLastname === $contact['lastname']) {
                    $found = $oldContact;
                    // Remove this contact from the old contacts list to avoid duplicate processing
                    $oldContacts = array_filter($oldContacts, fn($c) => $c->id !== $oldContact->id);
                    break;
                }
            }
            if (!$found) {
                $data = [
                    'lastname' => $contact['lastname'],
                    'firstname' => $contact['firstname'],
                    'phone' => $contact['phone'],
                    'email' => $contact['email'],
                    'owner' => $contact['owner'],
                    'subscription_id' => $existingSubscription->id
                ];
                $this->watchdogService->debug('Inserting new contact for subscription ' . $existingSubscription->id, ['contact' => $data]);
                try {
                    $this->contactRepository->insert($data);
                    $log['created']++;
                } catch (\Exception $e) {
                    $this->watchdogService->error('Failed to insert contact for subscription ' . $existingSubscription->id, ['exception' => $e->getMessage()]);
                    $log['error']++;
                }
            } else {
                $updateData = [];

                if ($oldContact->firstname !== $contact['firstname']) {
                    $updateData['firstname'] = $contact['firstname'];
                }
                if ($oldContact->lastname !== $contact['lastname']) {
                    $updateData['lastname'] = $contact['lastname'];
                }
                if ($oldContact->phone !== $contact['phone']) {
                    $updateData['phone'] = $contact['phone'];
                }
                if ($oldContact->email !== $contact['email']) {
                    $updateData['email'] = $contact['email'];
                }

                if (!empty($updateData)) {
                    try {
                        $this->contactRepository->update($oldContact->id, $updateData);
                        $log['updated']++;
                    } catch (\Exception $e) {
                        $this->watchdogService->error('Failed to update contact for subscription ' . $existingSubscription->id, ['exception' => $e->getMessage()]);
                        $log['error']++;
                    }
                } else {
                    $log['skipped']++;
                }
            }
        }

        // Delete any old contacts that were not found in the new contacts list
        foreach ($oldContacts as $oldContact) {
            $this->watchdogService->debug('Deleting old contact for subscription ' . $existingSubscription->id, ['contact' => $oldContact]);
            $this->contactRepository->delete($oldContact->id);
            $log['deleted']++;
        }
        return $log;
    }

    private function extractContacts(array &$data): array
    {
        $contacts = [];
        for ($i = 1; $i <= 2; $i++) {
            if (!empty($data["legal_guardian_lastName_$i"]) && !empty($data["legal_guardian_firstName_$i"])) {
                $contacts[] = [
                    'lastname' => $this->nameHelper->lastname($data["legal_guardian_lastName_$i"]),
                    'firstname' => $this->nameHelper->firstname($data["legal_guardian_firstName_$i"]),
                    'phone' => $this->phoneHelper->sanitize($data["legal_guardian_phone_$i"] ?? null),
                    'email' => $this->emailHelper->sanitize($data["legal_guardian_email_$i"] ?? null),
                    'owner' => false
                ];
            }
        }
        return $contacts;
    }

    private function extractBoolean(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        $value = trim($value);
        $value = strtolower($value);
        return $value === '1' || $value === 'true' || $value === 'yes' || $value === 'on' || $value === 'oui';
    }

    private function extractAddress(array &$data): array
    {

        $zipcode = $data['postal_code'] ?? null;
        if ($zipcode !== null) {
            $zipcode = str_pad($zipcode, 5, '0', STR_PAD_LEFT);
        }

        $number = $data['address_number'] ?? null;
        $line1 = $data['address_line_1'] ?? null;
        $line2 = $data['address_line_2'] ?? null;
        $streetType = $data['address_street_type'] ?? null;
        $city = $data['city'] ?? null;
        $country = $data['country'] ?? null;

        return [
            'line1' => $number . ' ' . $streetType . ' ' . $line1,
            'line2' => $line2,
            'zipcode' => $zipcode,
            'city' => $city,
            'country' => $country,
        ];
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

        $address = $this->extractAddress($data);

        $phone = $data['phone'] ?? null;
        if ($phone !== null) {
            // Sanitize the phone number if it's provided
            $phone = $this->phoneHelper->sanitize($phone);

            if (!$this->phoneHelper->isValid($phone)) {
                $phone = null;
            }
        }

        return $this->memberRepository->insert([
            'firstname' => $this->nameHelper->firstname($data['firstName']),
            'lastname' => $this->nameHelper->lastname($data['lastName']),
            'birthdate' => $birthdate,
            'address' => $address,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'gender' => $this->extractGender($data),
            'license_number' => $this->isValidLicenseNumber($data['licence'] ?? null) ? $data['licence'] : null,
            'hash' => $hash
        ]);
    }

    /**
     * Updates a member's information if necessary.
     * @param mixed $member
     * @param array $data
     * @return \stdClass Returns the member object after attempting an update. If no update was needed, returns the original member object.
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

        $gender = $this->extractGender($data);
        if ($gender !== null && $gender !== $member->gender) {
            $updateData['gender'] = $gender;
        }

        $phone = $data['phone'] ?? null;
        if ($phone !== null) {
            $phone = $this->phoneHelper->sanitize($phone);

            if (!$this->phoneHelper->isValid($phone)) {
                $phone = null;
            }
        }
        if ($phone !== $member->phone) {
            $updateData['phone'] = $phone;
        }

        if (isset($data['email']) && $data['email'] !== $member->email) {
            $updateData['email'] = $data['email'];
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