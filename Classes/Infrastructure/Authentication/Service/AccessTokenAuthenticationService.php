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

namespace Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Service;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schnitzler\FrontendUserLoginToken\Domain\Repository\FrontendUserQueryConfiguration;
use Schnitzler\FrontendUserLoginToken\Domain\Repository\FrontendUserRepository;
use Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Event\OnDecodeLoginTokenExceptionEvent;
use Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Event\OnDecodeLoginTokenExpiredExceptionEvent;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;

class AccessTokenAuthenticationService extends AbstractAuthenticationService
{
    private bool $serviceIsResponsibleForAuthentication = false;

    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Find a user (eg. look up the user record in database when a login is sent).
     *
     * @return array<string, mixed>|false User array or FALSE
     */
    public function getUser(): array|false
    {
        // As long as we don't have a login token, this service is not responsible for authentication
        $this->serviceIsResponsibleForAuthentication = false;

        $request = $this->authInfo['request'] ?? null;

        if ($request instanceof ServerRequestInterface === false) {
            $this->logger?->error('Request is not a ServerRequestInterface');

            return false;
        }

        if ('' === $loginToken = (string)($request->getQueryParams()['login-token'] ?? null)) {
            // This case will only happen on regular logins that somehow failed
            // This service is then asked to fetch a user which is not possible
            return false;
        }

        // Now that we have a login token, this service is responsible for authentication
        $this->serviceIsResponsibleForAuthentication = true;

        try {
            $payload = JWT::decode($loginToken, new Key($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256'));
        } catch (ExpiredException $e) {
            // An event is dispatched here to provide the possibility to throw
            // an ImmediateResponseException or to handle this exception otherwise.
            $this->eventDispatcher->dispatch(new OnDecodeLoginTokenExpiredExceptionEvent($e));

            $this->writelog(
                SystemLogType::LOGIN,
                SystemLogLoginAction::ATTEMPT,
                SystemLogErrorClassification::SECURITY_NOTICE,
                null,
                'Login-attempt from ###IP###, login token expired',
                ['uid' => $e->getPayload()->uid],
            );

            return false;
        } catch (\Throwable $e) {
            // An event is dispatched here to provide the possibility to throw
            // an ImmediateResponseException or to handle this exception otherwise.
            $this->eventDispatcher->dispatch(new OnDecodeLoginTokenExceptionEvent($e));

            $this->writelog(
                SystemLogType::LOGIN,
                SystemLogLoginAction::ATTEMPT,
                SystemLogErrorClassification::SECURITY_NOTICE,
                null,
                sprintf('Login-attempt from ###IP###, login token decoding failed: %s', $e->getMessage()),
                $e->getTrace(),
            );

            return false;
        }

        $frontendUserQueryConfiguration = new FrontendUserQueryConfiguration(
            table: $this->authInfo['db_user']['table'] ?? 'fe_users',
            userIdColumn: $this->authInfo['db_user']['userid_column'] ?? 'uid',
            enableClause: $this->authInfo['db_user']['enable_clause'] ?? null,
        );

        if (null === $user = $this->frontendUserRepository->findByUid($payload->uid, $frontendUserQueryConfiguration)) {
            return false;
        }

        return $user;
    }

    /**
     * Authenticate a user: Check submitted user credentials against stored hashed password.
     *
     * Returns one of the following status codes:
     *  >= 200: User authenticated successfully. No more checking is needed by other auth services.
     *  >= 100: User not authenticated; this service is not responsible. Other auth services will be asked.
     *  > 0:    User authenticated successfully. Other auth services will still be asked.
     *  <= 0:   Authentication failed, no more checking needed by other auth services.
     *
     * @param array<string, mixed> $user User data
     *
     * @return int Authentication status code, one of 0, 100, 200
     */
    public function authUser(array $user): int
    {
        if ($this->serviceIsResponsibleForAuthentication === false) {
            return 100;
        }

        $uid = $user[$this->authInfo['db_user']['userid_column'] ?? 'uid'];

        $this->writelog(
            SystemLogType::LOGIN,
            SystemLogLoginAction::LOGIN,
            SystemLogErrorClassification::MESSAGE,
            null,
            sprintf('Authentication successful for uid \'%d\'', $uid),
            [],
        );

        return 200;
    }
}
