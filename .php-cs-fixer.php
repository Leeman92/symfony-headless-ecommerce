<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('docker')
    ->exclude('migrations')
    ->notPath('src/Kernel.php')
    ->notPath('public/index.php')
    ->notPath('config/bootstrap.php')
    ->notPath('tests/bootstrap.php')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP84Migration' => true,
        '@PHP84Migration:risky' => true,
        
        // Disable Yoda style (we don't want it)
        'yoda_style' => false,
        
        // PHP 8.4 specific features
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        
        // Enhanced type declarations
        'phpdoc_to_property_type' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_return_type' => true,
        
        // Constructor property promotion (PHP 8.0+)
        'constructor_promotion' => true,
        
        // Modern PHP features
        'modernize_types_casting' => true,
        'modernize_strpos' => true,
        'use_arrow_functions' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        
        // Code quality
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
        
        // Symfony specific
        'symfony_container_xml_path' => false,
        'php_unit_method_casing' => ['case' => 'camel_case'],
        
        // Documentation
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_summary' => false,
        'phpdoc_separation' => false,
    ])
    ->setFinder($finder)
;