<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor/*')
    ->notPath('resources/*')
    ->notPath('database/*')
    ->notPath('storage/*')
    ->notPath('node_modules/*')
    ->in([
        __DIR__ . '/config',
        __DIR__ . '/src',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR2' => true,
        '@PhpCsFixer' => true,
    ])
    ->setFinder($finder);
