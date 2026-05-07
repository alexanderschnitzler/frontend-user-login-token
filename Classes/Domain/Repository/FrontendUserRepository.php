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

namespace Schnitzler\FrontendUserLoginToken\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

final readonly class FrontendUserRepository
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * @return array<string,int|float|string>|null
     */
    public function findByUid(int $uid, FrontendUserQueryConfiguration $configuration): array|null
    {
        $connection = $this->connectionPool->getConnectionForTable($configuration->table);

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from($configuration->table)
            ->where(
                $queryBuilder->expr()->eq($configuration->userIdColumn, $uid),
            )
        ;

        if ($configuration->enableClause instanceof CompositeExpression) {
            $queryBuilder->andWhere($configuration->enableClause);
        }

        $queryBuilder->setMaxResults(1);

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        $row = is_array($row) ? $row : [];

        return $row !== [] ? $row : null;
    }
}
