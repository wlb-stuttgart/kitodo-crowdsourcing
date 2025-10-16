<?php
declare(strict_types=1);

namespace Wlb\Crowdsourcing\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\CMS\Core\Session\UserSessionManager;

/*
 * FIXME Workaround for the issue that the sf_register session is not initialized correctly inside the sf_register extension.
 */
class SessionInitMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionName = 'evoweb-sfregister-session';
        $sessionKey = 'dummyKey';

        $userSessionManager = UserSessionManager::create('FE');
        $session = $userSessionManager->createFromRequestOrAnonymous(
            $request,
            $sessionName,
        );

        $session->set($sessionKey, serialize([]));
        $userSessionManager->updateSession($session);

        if (!$userSessionManager->isSessionPersisted($session)) {
            $userSessionManager->fixateAnonymousSession($session);
            $setCookieService = SetCookieService::create($sessionName, 'FE');
            $normalizedParams = NormalizedParams::createFromRequest($request);
            $cookie = $setCookieService->setSessionCookie($session, $normalizedParams);
            return $handler->handle($request)->withAddedHeader('Set-Cookie', (string)$cookie);
        }

        return $handler->handle($request);
    }
}