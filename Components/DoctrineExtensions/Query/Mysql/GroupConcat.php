<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Complete support of GROUP_CONCAT in Doctrine2
// -------------------------------------------------
// Credit : http://sysmagazine.com/posts/181666/

/**
 * DoctrineExtensions Mysql Function Pack
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace DoctrineExtensions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Full support for:
 *
 * GROUP_CONCAT([DISTINCT] expr [,expr ...]
 *              [ORDER BY {unsigned_integer | col_name | expr}
 *                  [ASC | DESC] [,col_name ...]]
 *              [SEPARATOR str_val])
 */
class GroupConcat extends FunctionNode
{
    /**
     * @var bool
     */
    public $isDistinct = false;

    /**
     * @var array<PathExpression>
     */
    public $pathExp;

    public $separator;

    /**
     * @var OrderByClause
     */
    public $orderBy;

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_DISTINCT)) {
            $parser->match(Lexer::T_DISTINCT);

            $this->isDistinct = true;
        }

        // first Path Expression is mandatory
        $this->pathExp = [];
        $this->pathExp[] = $parser->SingleValuedPathExpression();

        while ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $stringPrimary = $parser->StringPrimary();
            if (!$stringPrimary instanceof PathExpression) {
                continue;
            }
            $this->pathExp[] = $stringPrimary;
        }

        if ($lexer->isNextToken(Lexer::T_ORDER)) {
            $this->orderBy = $parser->OrderByClause();
        }

        if ($lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            if (\strtolower($lexer->lookahead['value']) !== 'separator') {
                $parser->syntaxError('separator');
            }
            $parser->match(Lexer::T_IDENTIFIER);

            $this->separator = $parser->StringPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $result = 'GROUP_CONCAT(' . ($this->isDistinct ? 'DISTINCT ' : '');

        $fields = [];

        foreach ($this->pathExp as $pathExp) {
            $fields[] = $pathExp->dispatch($sqlWalker);
        }

        $result .= \sprintf('%s', \implode(', ', $fields));

        if ($this->orderBy) {
            $result .= ' ' . $sqlWalker->walkOrderByClause($this->orderBy);
        }

        if ($this->separator) {
            $result .= ' SEPARATOR ' . $sqlWalker->walkStringPrimary($this->separator);
        }

        $result .= ')';

        return $result;
    }
}
