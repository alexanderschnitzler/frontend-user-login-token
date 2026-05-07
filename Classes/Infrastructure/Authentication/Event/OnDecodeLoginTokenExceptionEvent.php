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

namespace Schnitzler\FrontendUserLoginToken\Infrastructure\Authentication\Event;

final readonly class OnDecodeLoginTokenExceptionEvent
{
    public function __construct(public \Throwable $exception) {}
}
