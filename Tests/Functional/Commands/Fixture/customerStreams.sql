INSERT INTO `s_customer_streams` (`id`, `name`, `conditions`, `description`, `freeze_up`, `static`)
VALUES (:customerStreamId, 'Test', '{\"Shopware\\\\Bundle\\\\CustomerSearchBundle\\\\Condition\\\\IsCustomerSinceCondition\":{\"operator\":\">=\",\"customerSince\":\"2000-01-01T00:00:00\"}}', '', NULL, 0);
