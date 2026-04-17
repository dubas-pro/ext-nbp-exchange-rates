<?php

/**
 * This file is part of the NBP Exchange Rates - EspoCRM extension.
 *
 * dubas s.c. - contact@dubas.pro
 * Copyright (C) 2022-2026 Arkadiy Asuratov, Emil Dubielecki
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\UseDeclarationSniff;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$extension = json_decode(file_get_contents(__DIR__ . '/extension.json'));
$authors = implode(', ', $extension->authors);
$releaseYear = (new DateTime($extension->releaseDate))->format('Y');
$currentYear = (new DateTime())->format('Y');

$copyrightYears = $releaseYear;
if ($currentYear > $releaseYear) {
    $copyrightYears .= '-' . $currentYear;
}

$header = "This file is part of the {$extension->name} - EspoCRM extension.

{$extension->author}
Copyright (C) {$copyrightYears} {$authors}

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.";

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/php_scripts',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()

    ->withRules([
        UseDeclarationSniff::class,
        NoUnusedImportsFixer::class,
        OrderedClassElementsFixer::class,
    ])

    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => $header,
        'comment_type' => HeaderCommentFixer::HEADER_PHPDOC,
        'location' => 'after_open',
        'separate' => 'both',
    ])

    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ])

    ->withPreparedSets(
        arrays: true,
        namespaces: true,
        spaces: true,
        docblocks: true,
        comments: true,
    )

    ->withSkip([
        NotOperatorWithSuccessorSpaceFixer::class,
        PhpdocNoEmptyReturnFixer::class,
    ])

    ;
