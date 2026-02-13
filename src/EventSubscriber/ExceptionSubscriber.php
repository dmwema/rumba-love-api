<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle JSON API requests
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $statusCode = 500;
        $message = 'Internal server error';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $message = $exception->getMessage();
        } elseif ($exception instanceof \RuntimeException) {
            $statusCode = 500;
            $message = $exception->getMessage();
        }

        // Log the error in production
        if ($statusCode >= 500) {
            error_log(sprintf(
                '[%s] %s: %s in %s:%d',
                date('Y-m-d H:i:s'),
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        $response = new JsonResponse([
            'error' => $message,
            'code' => $statusCode,
            'timestamp' => time()
        ], $statusCode);

        $event->setResponse($response);
    }
}