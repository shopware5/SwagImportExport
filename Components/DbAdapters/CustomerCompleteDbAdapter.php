<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Order;
use SwagImportExport\Components\Utils\DbAdapterHelper;

class CustomerCompleteDbAdapter extends CustomerDbAdapter
{
    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::CUSTOMER_COMPLETE_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultColumns(): array
    {
        return $this->getCustomerColumns();
    }

    public function getCustomerColumns(): array
    {
        return [
            'customer',
            'attribute',
        ];
    }

    public function getBuilder(array $columns, array $ids): QueryBuilder
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from(Customer::class, 'customer')
            ->leftJoin('customer.attribute', 'attribute')
            ->groupBy('customer.id')
            ->where('customer.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $query = $this->manager->getConnection()->createQueryBuilder();
        $query->select(['customer.id']);
        $query->from('s_user', 'customer');

        if ($start) {
            $query->setFirstResult($start);
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        if (\array_key_exists('customerId', $filter)) {
            $query->andWhere('customer.id = :customerId');
            $query->setParameter('customerId', $filter['customerId']);
        }

        $ids = $query->execute()->fetchFirstColumn();

        return \array_map('\intval', $ids);
    }

    public function read(array $ids, array $columns): array
    {
        foreach ($columns as $key => $value) {
            if ($value === 'unhashedPassword') {
                unset($columns[$key]);
            }
        }

        $query = $this->getBuilder($columns, $ids)->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $customers = $this->manager->createPaginator($query)->getIterator()->getArrayCopy();

        $customerIds = \array_column($customers, 'id');
        $addresses = $this->getAddresses($customerIds);
        $orders = $this->getCustomerOrders($customerIds);

        $newsletterRecipients = $this->getNewsletterRecipients($customerIds);

        foreach ($customers as &$customer) {
            $customer['newsletter'] = false;
            if (isset($newsletterRecipients[$customer['id']])) {
                $customer['newsletter'] = true;
            }
            if ($addresses[$customer['id']]) {
                $customer['addresses'] = DbAdapterHelper::decodeHtmlEntities($addresses[$customer['id']]);
            }
            if (isset($orders[$customer['id']])) {
                $customer['orders'] = DbAdapterHelper::decodeHtmlEntities($orders[$customer['id']]);
            }
            if (\array_key_exists('attribute', $customer)) {
                unset($customer['attribute']['id'], $customer['attribute']['customerId']);
            }
        }
        unset($customer);

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($customers);

        return $result;
    }

    /**
     * @param array<int> $ids
     *
     * @return array<string, mixed>
     */
    private function getAddresses(array $ids): array
    {
        return $this->manager->getConnection()->createQueryBuilder()
            ->select('address.user_id, address.*', 'attribute.*')
            ->from('s_user_addresses', 'address')
            ->leftJoin('address', 's_user_addresses_attributes', 'attribute', 'address.id = attribute.address_id')
            ->where('address.user_id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_GROUP);
    }

    /**
     * @param array<int> $ids
     *
     * @return array<string|int, array<int, mixed>>
     */
    private function getCustomerOrders(array $ids): array
    {
        $orders = $this->manager->createQueryBuilder()->from(Order::class, 'o')
            ->select([
                'o',
                'attr',
                'partial payment.{id, name, description}',
                'paymentStatus',
                'orderStatus',
                'details',
                'detailAttr',
                'billingAddress',
                'shippingAddress',
            ])
            ->leftJoin('o.details', 'details')
            ->leftJoin('details.attribute', 'detailAttr')
            ->leftJoin('o.billing', 'billingAddress')
            ->leftJoin('o.shipping', 'shippingAddress')
            ->leftJoin('o.payment', 'payment')
            ->leftJoin('o.paymentStatus', 'paymentStatus')
            ->leftJoin('o.orderStatus', 'orderStatus')
            ->leftJoin('o.attribute', 'attr')
            ->where('o.customerId IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();

        $indexedOrders = [];
        foreach ($orders as $order) {
            $order['orderTime'] = $order['orderTime']->format('Y-m-d H:i:s');
            foreach ($order['details'] as &$detail) {
                $releaseDate = $detail['releaseDate'];

                if (!$releaseDate instanceof \DateTime) {
                    $releaseDate = new \DateTime('1970-01-01');
                }

                $detail['releaseDate'] = $releaseDate->format('Y-m-d H:i:s');
            }
            unset($detail);
            if (!\array_key_exists($order['customerId'], $indexedOrders)) {
                $indexedOrders[$order['customerId']] = [];
            }
            $indexedOrders[$order['customerId']][] = $order;
        }

        return $indexedOrders;
    }

    /**
     * @param array<int> $ids
     *
     * @return array<string, mixed>
     */
    private function getNewsletterRecipients(array $ids): array
    {
        return $this->manager->getConnection()->createQueryBuilder()
            ->select('customer.id, mailaddress.email')
            ->from('s_campaigns_mailaddresses', 'mailaddress')
            ->innerJoin('mailaddress', 's_user', 'customer', 'customer.email = mailaddress.email')
            ->where('customer.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }
}
