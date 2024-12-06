<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('Resources')
    ->exclude('Documentation')
    ->exclude('lib')
    ->in(__DIR__)
;

$config = new \PhpCsFixer\Config();

return $config
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'phpdoc_align' => false,
        'no_superfluous_phpdoc_tags' => false,
        'fully_qualified_strict_types' => false,
        'php_unit_method_casing' => false,
        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            // no support for "arguments" and "parameters" as we need support for PHP 7.4
            'elements' => [
                'array_destructuring',
                'arrays',
                'match',
            ],
        ],
    ])
    ->setLineEnding("\n");
