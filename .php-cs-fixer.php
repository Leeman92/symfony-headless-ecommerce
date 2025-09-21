<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('docker')
    ->exclude('migrations')
    ->notPath('src/Domain/Repository/RepositoryInterface.php')
    ->notPath('src/Kernel.php')
    ->notPath('public/index.php')
    ->notPath('config/bootstrap.php')
    ->notPath('tests/bootstrap.php');

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true) // needed because we include :risky sets below
    ->setRules([
        // Baseline rule sets
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // PHP 8.4 migration (non-risky exists)
        '@PHP84Migration' => true,

        // Preferences
        'yoda_style' => false,

        // Modern PHP & quality
        'declare_strict_types' => true,
        'phpdoc_to_property_type' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_return_type' => true,
        'modernize_types_casting' => true,
        'modernize_strpos' => true,
        'use_arrow_functions' => true,
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],

        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],

        // Imports
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],

        // PHPUnit & docs
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_summary' => false,
        'phpdoc_separation' => false,
    ])
    ->setFinder($finder);
