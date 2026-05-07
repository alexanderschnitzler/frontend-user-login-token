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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

final readonly class FrontendUserQueryConfiguration
{
    public function __construct(
        public string $table,
        public string $userIdColumn,
        public CompositeExpression|null $enableClause = null,
    ) {}
}
