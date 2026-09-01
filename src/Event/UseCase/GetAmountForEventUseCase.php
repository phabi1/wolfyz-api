<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\Event\Entity\Repository\CheckoutRepositoryInterface;

class GetAmountForEventUseCase implements UseCaseInterface
{

    private CheckoutRepositoryInterface $checkoutRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->checkoutRepository = $entityManager->getRepository('wolf-events.checkout');
    }

    function execute(array $data = [])
    {
        $eventId = $data['event_id'];
        return $this->checkoutRepository->getTotalAmountForEvent($eventId);
    }
}