<?php

/*
 * This file is part of the "Frontend User Login Token" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2026-2026 Alexander Schnitzler <git@alexanderschnitzler.de>, Schnitzler Softwarelösungen
 */

/** @phpstan-var array<string, mixed> $EM_CONF */
/** @phpstan-var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontend User Login Tokens',
    'description' => 'A TYPO3 extension that generates frontend user login tokens',
    'category' => 'fe',
    'author' => 'Alexander Schnitzler',
    'author_email' => 'git@alexanderschnitzler.de',
    'author_company' => 'Schnitzler Softwarelösungen',
    'state' => 'stable',
    'version' => '13.4.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '13.4.0-14.3.99',
        ],
    ],
];
