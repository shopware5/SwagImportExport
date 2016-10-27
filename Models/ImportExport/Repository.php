<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware ImportExport Plugin
 *
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2014, shopware AG (http://www.shopware.de)
 */

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;

class Repository extends ModelRepository
{
    /**
     * Returns a query builder object to get all profiles.
     *
     * @param array $filterBy
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getProfilesListQuery(array $filterBy = array(), array $orderBy = array(), $limit = null, $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('p');
        $builder->select(
            array(
                'p.id as id',
                'p.type as type',
                'p.name as name',
                'p.tree as tree',
                'p.default as default'
            )
        );

        if (!empty($filterBy)) {
            $builder->addFilter($filterBy);
        }

        if (!empty($orderBy)) {
            $builder->addOrderBy($orderBy);
        }

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        return $builder;
    }

    /**
     * Returns a query builder object to get all sessions.
     *
     * @param array $filterBy
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getSessionsListQuery(array $filterBy = array(), array $orderBy = array(), $limit = null, $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('s');

        $builder->select(
            array(
                's.id as id',
                'p.id as profileId',
                'p.name as profileName',
                's.type as type',
                's.position as position',
                's.totalCount as totalCount',
                's.userName as username',
                's.fileName as fileName',
                's.format as format',
                's.fileSize as fileSize',
                's.state as state',
                's.createdAt as createdAt',
            )
        );

        $builder->join('s.profile', 'p');

        if (!empty($filterBy)) {
            $builder->addFilter($filterBy);
        }

        if (!empty($orderBy)) {
            $builder->addOrderBy($orderBy);
        }

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        return $builder;
    }

    /**
     * Returns a query builder object to get all expressions.
     *
     * @param array $filterBy
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getExpressionsListQuery(array $filterBy = array(), array $orderBy = array(), $limit = null, $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('e');

        $builder->select(
            array(
                'e.id as id',
                'p.id as profileId',
                'e.variable as variable',
                'e.exportConversion as exportConversion',
                'e.importConversion as importConversion',
            )
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

    /**
     * Returns a query builder object to get all logs.
     *
     * @param array $filterBy
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getLogListQuery(array $filterBy = array(), array $orderBy = array(), $limit = null, $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('l');

        $builder->select(
            array(
                'l.id as id',
                'l.message as message',
                'l.state as state',
                'l.createdAt as logDate'
            )
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
