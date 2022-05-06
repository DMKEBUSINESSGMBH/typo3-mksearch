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
        '@Symfony' => true,
        'phpdoc_align' => false,
        'no_superfluous_phpdoc_tags' => false,
        'fully_qualified_strict_types' => false,
        'php_unit_method_casing' => false,
    ])
    ->setLineEnding("\n");
