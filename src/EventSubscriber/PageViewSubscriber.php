<?php

namespace App\EventSubscriber;

use App\Service\Analytics\PageViewLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PageViewSubscriber implements EventSubscriberInterface
{
    private const IGNORED_ROUTE_PREFIXES = [
        '_profiler',
        '_wdt',
        '_debug',
        'analytics_track_export',
    ];

    public function __construct(private readonly PageViewLogger $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!\in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $route = $request->attributes->get('_route');
        if (!$route) {
            return;
        }

        foreach (self::IGNORED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return;
            }
        }

        $this->logger->log($request);
    }
}
