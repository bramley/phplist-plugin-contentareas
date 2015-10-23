<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->exclude('phplist')
    ->exclude('tests')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::NONE_LEVEL)
    ->fixers(array(
        'psr0', 'encoding', 'braces', 'elseif', 'space', 'function_declaration', 'indentation', 'line_after_namespace',
        'linefeed', 'lowercase_constants', 'lowercase_keywords', 'method_argument_space', 'multiple_use', 'parenthesis', 'php_closing_tag',
        'single_line_after_imports', 'trailing_spaces', 'visibility'
    ))
    ->finder($finder)
;
