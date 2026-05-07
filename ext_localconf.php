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

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'lectiopro_data',
    'auth',
    Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Service\AccessTokenAuthenticationService::class,
    [
        'title' => 'Login token authentication',
        'description' => 'Authenticate with login token',
        'subtype' => 'getUserFE,authUserFE',
        'available' => 1,
        'priority' => 100,
        'quality' => 100,
        'os' => '',
        'exec' => '',
        'className' => Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Service\AccessTokenAuthenticationService::class,
    ],
);
