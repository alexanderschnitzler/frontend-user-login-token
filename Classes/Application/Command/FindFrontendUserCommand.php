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

namespace Schnitzler\FrontendUserLoginToken\Application\Command;

use Doctrine\DBAL\Result;
use Firebase\JWT\JWT;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\MathUtility;

#[AsCommand('schnitzler:frontend-user-login-token:find-frontend-user', description: 'Find frontend user by uid or name')]
class FindFrontendUserCommand extends Command
{
    private readonly Connection $connection;

    public function __construct(ConnectionPool $connectionPool, private readonly HashService $hashService)
    {
        $this->connection = $connectionPool->getConnectionForTable('fe_users');

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user', InputArgument::OPTIONAL, 'The user uid or name', '*');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $userArgument = $input->getArgument('user');
        $uidOrName = MathUtility::canBeInterpretedAsInteger($userArgument)
            ? (int)$userArgument
            : (string)$userArgument;

        $users = $this->getFindOrganizationUserQuery($uidOrName)->fetchAllAssociative();

        if (false === $expiresAt = \DateTimeImmutable::createFromFormat('U', (string)time())) {
            throw new \RuntimeException('Could not create DateTimeImmutable');
        }

        $expiresAt->add(new \DateInterval('PT1H'));

        $users = array_map(function (array $row) use ($expiresAt) {
            $accessToken = JWT::encode([
                'uid' => $row['uid'],
                'exp' => $expiresAt->getTimestamp(),
            ], $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256');

            $row['url'] = sprintf(
                '/?logintype=login&login-token=%s&hmac=%s',
                $accessToken,
                $this->hashService->hmac($accessToken, 'frontend_user_login_token'),
            );

            $row['expiresAt'] = $expiresAt->format(\DateTimeInterface::ATOM);

            return $row;
        }, $users);

        $regularUserTable = $style->createTable();
        $regularUserTable->setHeaders(['uid', 'username', 'firstname', 'lastname', 'url', 'expires at']);

        foreach ($users as $user) {
            $regularUserTable->addRow($user);
        }

        if ($users === []) {
            $style->warning('No users found');
        } else {
            $style->section('Users');
            $regularUserTable->render();
        }

        return Command::SUCCESS;
    }

    private function getFindOrganizationUserQuery(int|string $uidOrName): Result
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $query = $queryBuilder
            ->select('u.uid', 'u.username', 'u.first_name', 'u.last_name')
            ->from('fe_users', 'u')
            ->orderBy('u.uid')
        ;

        if ($uidOrName === '*') {
            return $query->executeQuery();
        }

        $query->andWhere(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->like('u.username', $this->connection->quote('%' . $this->connection->escapeLikeWildcards((string)$uidOrName) . '%')),
                $queryBuilder->expr()->like('u.first_name', $this->connection->quote('%' . $this->connection->escapeLikeWildcards((string)$uidOrName) . '%')),
                $queryBuilder->expr()->like('u.last_name', $this->connection->quote('%' . $this->connection->escapeLikeWildcards((string)$uidOrName) . '%')),
            ),
        );

        if (is_int($uidOrName)) {
            $query->orWhere(
                $queryBuilder->expr()->eq('u.uid', $uidOrName),
            );
        }

        return $query->executeQuery();
    }
}
