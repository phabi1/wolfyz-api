<?php

namespace App\Event\UseCase;
use App\Event\Token\TokenService;
use App\Core\UseCase\UseCaseInterface;

class GetCheckoutResultUseCase implements UseCaseInterface
{
    private $entityManager;
    private TokenService $tokenService;

    public function __construct($entityManager, $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenService = $tokenService;
    }

    public function execute(array $data = []): array
    {
        $checkoutId = $data['checkout_id'] ?? 0;
        $token = $data['token'] ?? '';

        if (!$checkoutId || !$token) {
            return ['valid' => false];
        }

        // Validate the token
        if (!$this->tokenService->validateToken($checkoutId, $token)) {
            return ['valid' => false];
        }

        // Fetch the checkout result based on the type and checkout ID
        $checkoutRepository = $this->entityManager->getRepository('wolf-events.checkout');
        $checkout = $checkoutRepository->find($checkoutId);

        if (!$checkout) {
            return ['valid' => false];
        }

        return [
            'valid' => true,
            'checkout' => $checkout,
        ];
    }
}