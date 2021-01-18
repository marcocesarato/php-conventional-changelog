<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
    ->exclude('vendor')
    ->name(__DIR__ . '/.changelog')
    ->name(__DIR__ . '/.php_cs')
    ->name(__DIR__. '/conventional-changelog')
    ->in(__DIR__ . '/src');


$config = new PhpCsFixer\Config();
return $config
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.php_cs.cache')
    ->setRules(array(
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'psr4' => true,
        // Custom rules
        'align_multiline_comment' => array('comment_type' => 'phpdocs_only'), // PSR-5
        'phpdoc_to_comment' => false,
        'array_indentation' => true,
        'array_syntax' => array('syntax' => 'short'),
        'cast_spaces' => array('space' => 'none'),
        'concat_space' => array('spacing' => 'one'),
        'compact_nullable_typehint' => true,
        'declare_equal_normalize' => array('space' => 'single'),
        'increment_style' => array('style' => 'post'),
        'list_syntax' => array('syntax' => 'long'),
        'no_short_echo_tag' => true,
        'phpdoc_align' => false,
        'phpdoc_no_empty_return' => false,
        'phpdoc_order' => true, // PSR-5
        'phpdoc_no_useless_inheritdoc' => false,
        'protected_to_private' => false,
        'yoda_style' => false,
        'method_argument_space' => array('on_multiline' => 'ensure_fully_multiline'),
        'ordered_imports' => array(
            'sort_algorithm' => 'alpha',
            'imports_order' => array('class', 'const', 'function')
        ),
    ))
    ->setFinder($finder);