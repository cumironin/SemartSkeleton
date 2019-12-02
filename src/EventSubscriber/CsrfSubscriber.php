<?php

declare(strict_types=1);

namespace KejawenLab\Semart\Skeleton\EventSubscriber;

use KejawenLab\Semart\Skeleton\Security\Service\CsrfTokenService;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventSubscriberInterface;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@gmail.com>
 */
class CsrfSubscriber implements EventSubscriberInterface
{
    private $csrfTokenProvider;

    public function __construct(CsrfTokenService $csrfTokenProvider)
    {
        $this->csrfTokenProvider = $csrfTokenProvider;
    }

    public function validate()
    {
        if (!$this->csrfTokenProvider->validate()) {
            throw new AccessDeniedException();
        }
    }

    public function injectToken(ResponseEvent $event)
    {
        $event->setResponse($this->csrfTokenProvider->apply($event->getResponse()));
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'validate',
            ResponseEvent::class => 'injectToken',
        ];
    }
}
