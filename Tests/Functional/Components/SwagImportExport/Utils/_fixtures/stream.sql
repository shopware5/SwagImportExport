INSERT INTO `s_product_streams` (`id`, `name`, `conditions`, `type`, `sorting`, `description`, `sorting_id`)
VALUES (1, 'TestStream',
        '{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Condition\\\\ProductAttributeCondition|attr3\":{\"field\":\"attr3\",\"operator\":\"<\",\"value\":\"3\"},\"Shopware\\\\Bundle\\\\SearchBundle\\\\Condition\\\\PriceCondition\":{\"minPrice\":0.20000000000000001,\"maxPrice\":null}}',
        1, '[]', '', NULL);
