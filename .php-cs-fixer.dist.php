<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

// 以 @PSR12 为基础；对与项目现状风格冲突的规则显式关闭。
return (new Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
            'operators' => [
                '=>' => 'align_single_space_minimal_by_scope',
            ],
        ],
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_unused_imports' => true,
        'ordered_class_elements' => ['order' => ['use_trait', 'case', 'constant', 'property', 'construct']],
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_line_span' => false,
    ])
    ->setFinder($finder);
