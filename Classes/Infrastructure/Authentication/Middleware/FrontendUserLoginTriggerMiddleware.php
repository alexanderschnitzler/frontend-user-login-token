<?php

declare(strict_types=1);

/*
 * This file is part of the "Frontend User Login Token" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2026-2026 Alexander Schnitzler <git@alexanderschnitzler.de>, Schnitzler Softwarelösungen
 */

namespace Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class FrontendUserLoginTriggerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly HashService $hashService) {}

    /**
     * This middleware is responsible for triggering frontend user login based on
     * an access token. It checks if the request contains a 'login-token' query
     * parameter and if so, sets the security aspect to trigger the login process.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/') {
            return $handler->handle($request);
        }

        if (null === ($loginToken = $request->getQueryParams()['login-token'] ?? null)) {
            return $handler->handle($request);
        }

        if (null === ($hmac = $request->getQueryParams()['hmac'] ?? null)) {
            return $handler->handle($request);
        }

        if ($this->hashService->validateHmac($loginToken, 'frontend_user_login_token', $hmac) === false) {
            return $handler->handle($request);
        }

        // The AbstractUserAuthentication goes into active login mode if status=login is sent
        // and no existing session is found.
        // Since we want to login whenever a login token is provided, we trigger a logoff
        // every time a login token is used.

        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);
        $frontendUser->logoff();

        // If we are in active login mode, the AbstractUserAuthentication checks for the request token scope.
        // The request token acts as a kind of CSRF token to prevent side loaded login attempts.
        // We need to fake an existing request token to trigger the login process.
        // To prevent punching a whole into this security measure, we only do this if the request token is properly signed.
        // See hmac check further above.

        // todo: think about using BeforeRequestTokenProcessedEvent to set the request token

        /** @var SecurityAspect $securityAspect */
        $securityAspect = GeneralUtility::makeInstance(SecurityAspect::class);
        $securityAspect->setReceivedRequestToken(new RequestToken('core/user-auth/fe', new \DateTimeImmutable()));

        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('security', $securityAspect);

        return $handler->handle($request);
    }
}
