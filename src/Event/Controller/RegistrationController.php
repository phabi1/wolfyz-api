<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\ApiController;
use App\File\Presign\PresignedUrlService;
use App\File\Service\FileService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends ApiController
{
    public function infoAction(Request $request)
    {
        $eventId = (int) $request->attributes->get('id');

        $response = $this->useCaseBus('wolf-events.get_event', ['id' => $eventId]);
        
        return $response;
    }

    public function checkSubscriptionAction(Request $request)
    {
        $data = $request->getPayload()->all();
        $res = $this->useCaseBus('wolf-events.check_subscription', [
            'event_id' => (int) $request->attributes->get('id'),
            'firstname' => $data['firstname'] ?? null,
            'lastname' => $data['lastname'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
        ]);

        return new JsonResponse($res);
    }

    public function registerAction(Request $request)
    {
        $eventId = (int) $request->attributes->get('id');
        $data = $request->getPayload()->all();

        $contact = $data['contact'] ?? [];

        $errors = [];

        if (empty($contact['firstname'])) {
            $errors['contact.firstname'] = 'required';
        }
        if (empty($contact['lastname'])) {
            $errors['contact.lastname'] = 'required';
        }
        if (empty($contact['email'])) {
            $errors['contact.email'] = 'required';
        }

        if (!empty($contact['email']) && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact.email'] = 'invalid';
        }

        $participants = $data['participants'] ?? [];
        if (empty($participants)) {
            $errors['participants'] = 'required';
        }

        foreach ($participants as $participantIndex => $participant) {
            if (empty($participant['firstname'])) {
                $errors['participants.' . $participantIndex . '.firstname'] = 'required';
            }
            if (empty($participant['lastname'])) {
                $errors['participants.' . $participantIndex . '.lastname'] = 'required';
            }

            if (empty($participant['ticket_id'])) {
                $errors['participants.' . $participantIndex . '.ticket_id'] = 'required';
            }

        }

        if (!empty($errors)) {
            return new JsonResponse(['error' => 'invalid_data', 'message' => $errors], 400);
        }

        try {
            $result = $this->useCaseBus('wolf-events.register_to_event', [
                'event_id' => $eventId,
                'contact' => $contact,
                'participants' => $participants,
                'message' => $data['message'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Inscription réussie',
                'payment_url' => $result['payment_url'] ?? null
            ];
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'registration_error', 'message' => $e->getMessage()], 400);
        }


    }

    public function uploadFileAction(Request $request)
    {
        $eventId = $request->attributes->get('id');
        $payload = $request->getPayload()->all();

        if (!isset($eventId)) {
           return new JsonResponse(['message' => 'Event ID is required'], 400);
        }

        if (empty($payload['file'])) {
            return new JsonResponse(['message' => 'No file URL uploaded'], 400);
        }

        if (empty($payload['mime_type'])) {
            return new JsonResponse(['message' => 'MIME type is required'], 400);
        }

        /**
         * @var FileService
         */
        $fileService = $this->getService('file');

        $path = $fileService->resolveAvailablePath('event/' . $eventId, (string) $payload['file']);

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->upload($path, $payload['mime_type']);
        return [
            'success' => true,
            'url' => $uri,
            'path' => $path,
        ];
    }

    public function removeFileAction(Request $request)
    {
        $eventId = $request->attributes->get('id');
        $payload = $request->getPayload()->all();
        $file = $payload['file'] ?? null;

        if (!isset($eventId)) {
           return new JsonResponse(['message' => 'Event ID is required'], 400);
        }

        if (!$file) {
            return new JsonResponse(['message' => 'No file URL provided'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->remove($file);

        return [
            'success' => true,
            'url' => $uri,
        ];
    }

    public function downloadFileAction(Request $request)
    {
        $eventId = $request->attributes->get('id');
        $payload = $request->getPayload()->all();
        $file = $payload['file'] ?? null;

        if (!isset($eventId)) {
           return new JsonResponse(['message' => 'Event ID is required'], 400);
        }

        if (!$file) {
            return new JsonResponse(['message' => 'No file URL provided'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->download($file);

        return [
            'success' => true,
            'url' => $uri,
        ];
    }
}