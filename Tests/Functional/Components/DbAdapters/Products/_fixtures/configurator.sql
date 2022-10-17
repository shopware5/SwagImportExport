INSERT INTO s_article_configurator_groups (id, name, description, position) VALUES (100, 'Set', null, 3);

INSERT INTO s_article_configurator_sets (id, name, public, type) VALUES (100, 'Set-SW10002.3', 0, 1);

INSERT INTO s_articles (id, supplierID, name, description, description_long, shippingtime, datum, active, taxID, pseudosales, topseller, metaTitle, keywords, changetime, pricegroupID, pricegroupActive, filtergroupID, laststock, crossbundlelook, notification, template, mode, main_detail_id, available_from, available_to, configurator_set_id) VALUES
(110, 2, 'Test product', '', '', null, '2012-08-15', 1, 1, 20, 0, null, '', '2012-08-30 16:57:00', 1, 0, 1, 0, 0, 0, '', 0, 125, null, null);

INSERT INTO s_articles_details (id, articleID, ordernumber, suppliernumber, kind, additionaltext, sales, active, instock, stockmin, laststock, weight, position, width, height, length, ean, unitID, purchasesteps, maxpurchase, minpurchase, purchaseunit, referenceunit, packunit, releasedate, shippingfree, shippingtime, purchaseprice) VALUES
 (110, 110, 'foobar', '', 1, '', 0, 1, 25, 0, 0, 0.000, 0, null, null, null, null, 1, null, null, 1, 0.7000, 1.000, 'Foos', '2012-06-13', 0, '', 0);
