<?php

/**
 * Shopware 4.2
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
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
        $builder = $this->createQueryBuilder('p');
        $builder->select(array(
            'p.id as id',
            'p.type as type',
            'p.name as name',
            'p.tree as tree',
        ));
        
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
        $builder = $this->createQueryBuilder('s');
       
        $builder->select(array(
            's.id as id',
            'p.id as profileId',
            's.type as type',
            's.position as position',
            's.totalCount as totalCount',
            's.userName as username',
            's.fileName as fileName',
            's.format as format',
            's.state as state',
            's.createdAt as createdAt',
        ));
        
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
        $builder = $this->createQueryBuilder('e');
       
        $builder->select(array(
            'e.id as id',
            'p.id as profileId',
            'e.variable as variable',
            'e.exportConversion as exportConversion',
            'e.importConversion as importConversion',
        ));
        
        $builder->join('e.profile', 'p');
        
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
        $builder = $this->createQueryBuilder('l');
       
        $builder->select(array(
            'l.id as id',
            'l.message as message',
            'l.state as state',
            'l.createdAt as logDate'
        ));
        
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
    
}
