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

namespace Schnitzler\FrontendUserLoginToken\Tests\Functional\Infrastructure\Authentication\Service;

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;
use Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Service\AccessTokenAuthenticationService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AccessTokenAuthenticationServiceTest extends FunctionalTestCase
{
    private const ENCRYPTION_KEY = 'aTestEncryptionKeyThatIsLongEnough';
    private const TOKEN_USER_UID = 1;
    private const OTHER_USER_UID = 2;

    protected array $testExtensionsToLoad = ['schnitzler/frontend-user-login-token'];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = self::ENCRYPTION_KEY;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUsers.csv');
    }

    /**
     * A login token that does not decode must not make this service claim responsibility for
     * the user record core resolves afterwards. Core's own auth service looks that record up by
     * the posted username alone, so answering 200 here would authenticate any user without a
     * password, which is the authentication bypass this test guards against.
     */
    #[Test]
    public function authUserDoesNotAuthenticateWhenTheTokenIsInvalid(): void
    {
        $subject = $this->createSubject('not-a-token');

        self::assertFalse($subject->getUser());
        self::assertSame(100, $subject->authUser($this->userRecord(self::OTHER_USER_UID)));
    }

    /**
     * Same bypass as above, reached through an expired token instead of an undecodable one.
     * Expiry is handled in its own catch block in getUser(), so it needs its own test.
     */
    #[Test]
    public function authUserDoesNotAuthenticateWhenTheTokenIsExpired(): void
    {
        $subject = $this->createSubject($this->createToken(self::TOKEN_USER_UID, time() - 10));

        self::assertFalse($subject->getUser());
        self::assertSame(100, $subject->authUser($this->userRecord(self::OTHER_USER_UID)));
    }

    /**
     * A valid token identifies exactly one user. When core asks this service to authenticate a
     * different user record, the token says nothing about that user, so the service must defer.
     */
    #[Test]
    public function authUserDoesNotAuthenticateAUserTheTokenWasNotIssuedFor(): void
    {
        $subject = $this->createSubject($this->createToken(self::TOKEN_USER_UID, time() + 3600));

        self::assertSame('token-user', $subject->getUser()['username'] ?? null);
        self::assertSame(100, $subject->authUser($this->userRecord(self::OTHER_USER_UID)));
    }

    /**
     * The one case that is supposed to log someone in: a valid, unexpired token, and the user
     * record core hands back is the one the token was issued for.
     */
    #[Test]
    public function authUserAuthenticatesTheUserTheTokenWasIssuedFor(): void
    {
        $subject = $this->createSubject($this->createToken(self::TOKEN_USER_UID, time() + 3600));

        self::assertSame('token-user', $subject->getUser()['username'] ?? null);
        self::assertSame(200, $subject->authUser($this->userRecord(self::TOKEN_USER_UID)));
    }

    private function createSubject(string $loginToken): AccessTokenAuthenticationService
    {
        $subject = $this->get(AccessTokenAuthenticationService::class);

        $subject->authInfo = [
            'request' => (new ServerRequest('https://example.com/'))->withQueryParams(['login-token' => $loginToken]),
            'db_user' => ['table' => 'fe_users', 'userid_column' => 'uid'],
        ];

        return $subject;
    }

    private function createToken(int $uid, int $expiresAt): string
    {
        return JWT::encode(['uid' => $uid, 'exp' => $expiresAt], self::ENCRYPTION_KEY, 'HS256');
    }

    /**
     * The record core would hand to authUser(), read from the database like core's own auth service does.
     *
     * @return array<string, mixed>
     */
    private function userRecord(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('fe_users');
        $record = $queryBuilder->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($record);

        return $record;
    }
}
