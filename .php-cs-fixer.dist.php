<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/public')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;