<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class LoginHoneypotListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only check on Main Request and Login POST
        if (
            !$event->isMainRequest()
            || $request->attributes->get('_route') !== 'app_login'
            || !$request->isMethod('POST')
        ) {
            return;
        }

        // Honeypot check
        if ($request->request->get('_hp_login')) {
            // Stop additional processing, return 400 or just pretend it failed
            // Returning a JSON error for now, or could emulate a bad credentials redirect
            $event->setResponse(new JsonResponse(['error' => 'Invalid request'], 400));
        }
    }
}
