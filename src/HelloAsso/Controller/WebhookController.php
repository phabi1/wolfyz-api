<?php

namespace App\HelloAsso\Controller;

use App\Core\Mvc\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class WebhookController extends AbstractController
{
    public function getHistoryRepository()
    {
        return $this->getService('entity.manager')->getRepository('wolf-helloasso.history');
    }

    public function getWebhookBus()
    {
        return $this->getService('wolf-helloasso.webhook_bus');
    }

    public function handleAction(Request $request)
    {
        //Check valid ip
        $valid_ips = ['51.138.206.200']; // Replace with actual IP addresses
        $request_ip = $_SERVER['REMOTE_ADDR'];
        if (!in_array($request_ip, $valid_ips)) {
            return new JsonResponse(['status' => 'forbidden'], 403);
        }

        $payload = $request->getPayload()->all();

        if (empty($payload['eventType'])) {
            return new JsonResponse(['status' => 'bad_request', 'message' => 'Missing event field'], 400);
        }

        $historyRepository = $this->getHistoryRepository();

        $eventId = $this->generateEventId($payload);

        if ($historyRepository->count(['event_id' => ['eq' => $eventId]]) > 0) {
            return new JsonResponse(['status' => 'duplicate'], 200);
        }

        $historyRepository->insert([
            'event_id' => $eventId,
            'event_type' => $payload['eventType'],
            'event_data' => $payload['data'] ?? [],
            'created_at' => time(),
        ]);

        $eventName = $this->mapEventToEventName($payload);

        if ($eventName) {
            $webhookBus = $this->getWebhookBus();
            $webhookBus->execute($eventName, $payload);
        }

        return new JsonResponse(['status' => 'success'], 200);
    }

    private function mapEventToEventName(array $payload): ?string
    {
        // Map the eventType from the payload to the corresponding event name
        $eventType = $payload['eventType'] ?? '';

        switch ($eventType) {
            case 'Order':
                return 'receive_order';
            case 'Payment':
                return 'receive_payment';
            // Add more cases as needed for different event types
            default:
                return null; // Fallback to the original event type if no mapping is found
        }
    }

    private function generateEventId(array $payload): string
    {
        // Generate a unique event ID based on the payload data
        return md5(json_encode($payload));
    }
}