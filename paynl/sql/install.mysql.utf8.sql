CREATE TABLE IF NOT EXISTS `#__paynl_transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` varchar(50) NOT NULL,
        `option_id` int(11) NOT NULL,
        `amount` int(11) NOT NULL,
        `order_id` int(11) NOT NULL,
        `status` varchar(10) NOT NULL DEFAULT 'PENDING',
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_update` timestamp ,
        `start_data` timestamp,
        PRIMARY KEY (`id`)
      ) ENGINE=myisam AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;