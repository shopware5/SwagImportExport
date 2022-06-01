<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessParenthesisFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessStrlenFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceBeforeStatementFixer;
use PhpCsFixerCustomFixers\Fixers;

$finder = PhpCsFixer\Finder::create()
//    ->in(__DIR__ . '/Commands')
    ->in(__DIR__ . '/Components/Converter')
    ->in(__DIR__ . '/Components/DataManagers')
    ->in(__DIR__ . '/Components/DataType')
    ->in(__DIR__ . '/Components/DbAdapters')
    ->in(__DIR__ . '/Components/Exception')
    ->in(__DIR__ . '/Components/Factories')
    ->in(__DIR__ . '/Components/FileIO')
    ->in(__DIR__ . '/Components/Logger')
    ->in(__DIR__ . '/Components/Profile')
    ->in(__DIR__ . '/Components/Service')
    ->in(__DIR__ . '/Components/Session')
    ->in(__DIR__ . '/Components/Utils')

    //    ->in(__DIR__ . '/Components/Transformers')
//    ->in(__DIR__ . '/Components/Validators')
//      ->in(__DIR__ . '/Components')
//    ->in(__DIR__ . '/Controllers')
//    ->in(__DIR__ . '/CustomModels')
//    ->in(__DIR__ . '/Setup')
//    ->in(__DIR__ . '/Subscribers')
//    ->in(__DIR__ . '/Tests')

;

$header = <<<EOF
(c) shopware AG <info@shopware.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new Config())
    ->registerCustomFixers(new Fixers())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,

        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => false,
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        'concat_space' => ['spacing' => 'one'],
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['copyright', 'category']],
        'header_comment' => ['header' => $header, 'separate' => 'bottom', 'comment_type' => 'PHPDoc'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'native_constant_invocation' => true,
        'native_function_invocation' => ['scope' => 'all', 'strict' => false],
        'no_superfluous_phpdoc_tags' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'operator_linebreak' => ['only_booleans' => true],
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'phpdoc_var_annotation_correct_order' => true,
        'php_unit_test_case_static_method_calls' => true,
        'single_line_throw' => false,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'phpdoc_to_return_type' => true,
        'void_return' => true,

        NoSuperfluousConcatenationFixer::name() => true,
        NoUselessCommentFixer::name() => true,
        NoUselessStrlenFixer::name() => true,
        NoUselessParenthesisFixer::name() => true,
        PhpdocParamTypeFixer::name() => true,
        SingleSpaceAfterStatementFixer::name() => true,
        SingleSpaceBeforeStatementFixer::name() => true,
    ])->setFinder($finder);
