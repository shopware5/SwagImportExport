<?php
declare(strict_types=1);
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
 * @extends ModelRepository<Logger>
 */
class LoggerRepository extends ModelRepository
{
    /**
     * Returns a query builder object to get all logs.
     *
     * @param array<string, string>|array<array{property: string, value: mixed, expression?: string}> $filterBy
     * @param array<array{property: string, direction: string}>                                       $orderBy
     */
    public function getLogListQuery(array $filterBy = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $builder = $this->createQueryBuilder('l');

        $builder->select(
            [
                'l.id as id',
                'l.message as message',
                'l.state as errorState',
                'l.createdAt as logDate',
            ]
        );

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
