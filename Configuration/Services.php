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

use Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Service\AccessTokenAuthenticationService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure()
    ;

    $services->load('Schnitzler\\FrontendUserLoginToken\\', __DIR__ . '/../Classes/')->exclude([
        __DIR__ . '/../Classes/Domain/Repository/FrontendUserQueryConfiguration.php',
        __DIR__ . '/../Classes/Infrastructure/Authentication/LoginToken.php',
    ]);

    $services->set(AccessTokenAuthenticationService::class)
        ->public()
    ;
};
