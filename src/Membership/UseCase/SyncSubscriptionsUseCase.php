<?php

namespace App\Membership\UseCase;

use App\Core\Di\Locator;
use App\Core\Entity\EntityManager;
use App\Core\Helper\DateHelper;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Validator\PhoneValidator;
use App\Membership\Helper\MemberHelper;

class SyncSubscriptionsUseCase implements UseCaseInterface
{
    private $memberRepository;

    private $subscriptionRepository;

    private $memberHelper;

    private $dateHelper;

    private $phoneHelper;

    private $emailHelper;

    private $nameHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper, Locator $helpers)
    {
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
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
            'gender' => 'Sexe',
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
            'certificat_medical' => 'Certificat médical (moins d\'1 an pour licence competition) ou Attestation',
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
                $log['skipped']++;
                continue;
            }

            $subscriptionData = [];

            $subscriptionFields = [
                'medical_certificate' => $data['certificat_medical'] ?? null,
                'agree_exit' => $this->extractBoolean($data['agree_exit'] ?? null),
                'agree_image' => $this->extractBoolean($data['agree_image'] ?? null),
            ];

            $subscriptionData['fields'] = $subscriptionFields;

            try {
                $this->subscriptionRepository->update($existingSubscription->id, $subscriptionData);
            } catch (\Exception $e) {
                var_dump($e);
                $log['skipped']++;
                continue;
            }

            $log['updated']++;
        }
        fclose($handle);
        return $log;
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