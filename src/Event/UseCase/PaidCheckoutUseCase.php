<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\Event\Entity\Repository\CheckoutRepository;
use App\Event\Entity\Repository\ParticipantRepositoryInterface;
use App\Event\Entity\Repository\EventRepository;
use App\Event\Model\CheckoutStatus;

class PaidCheckoutUseCase implements UseCaseInterface
{
    private $checkoutRepository;

    public function __construct(EntityManager $entityManager)
    {
        $checkoutRepository = $entityManager->getRepository('wolf-events.checkout');
        if (!($checkoutRepository instanceof CheckoutRepository)) {
            throw new \Exception("Checkout repository must be instance of CheckoutRepository");
        }
        $this->checkoutRepository = $checkoutRepository;
    }

    public function execute(array $params = [])
    {
        $checkoutId = $params['id'] ?? null;
        if (!$checkoutId) {
            throw new \Exception("Checkout ID is required");
        }

        $checkout = $this->checkoutRepository->find($checkoutId);
        if (!$checkout) {
            throw new \Exception("Checkout not found");
        }

        if ($checkout->status === CheckoutStatus::PAID) {
            return;
        }

        $data = [
            'status' => CheckoutStatus::PAID,
            'updated_at' => time(),
        ];

        if (isset($params['payment_id'])) {
            $data['billing_payment_id'] = $params['payment_id'];
        }

        $this->checkoutRepository->update($checkoutId, $data);
    }
}