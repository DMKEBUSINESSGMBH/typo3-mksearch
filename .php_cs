<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('Resources')
    ->exclude('Documentation')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
    ])
    ->setLineEnding("\n")
;

