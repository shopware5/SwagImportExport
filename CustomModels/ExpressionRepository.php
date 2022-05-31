<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\CustomModels;

use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;

/**
 * @extends ModelRepository<Expression>
 */
class ExpressionRepository extends ModelRepository
{
    /**
     * Returns a query builder object to get all expressions.
     *
     * @param array<string, string>|array<array{property: string, value: mixed, expression?: string}> $filterBy
     * @param array<array{property: string, direction: string}>                                       $orderBy
     *
     * @return QueryBuilder
     */
    public function getExpressionsListQuery(array $filterBy = [], array $orderBy = [], ?int $limit = null, ?int $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('e');

        $builder->select(
            [
                'e.id as id',
                'p.id as profileId',
                'e.variable as variable',
                'e.exportConversion as exportConversion',
                'e.importConversion as importConversion',
            ]
        );

        $builder->join('e.profile', 'p');

        if (!empty($filterBy)) {
            $builder->addFilter($filterBy);
        }

        if (!empty($orderBy)) {
            $builder->addOrderBy($orderBy);
        }

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)->setMaxResults($limit);
        }

        return $builder;
    }
}
