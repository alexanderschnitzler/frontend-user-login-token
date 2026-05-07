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

use Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Middleware\FrontendUserLoginTriggerMiddleware;

return [
    'frontend' => [
        'schnitzler/frontend-user-login-token/login-token-trigger' => [
            'target' => FrontendUserLoginTriggerMiddleware::class,
            'before' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
