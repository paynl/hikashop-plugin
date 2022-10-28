<?php
/*
* pay.nl plugin.
*/

# You need to extend from the hikashopPaymentPlugin class which already define lots of functions in order to simplify your work
class plgHikashoppaymentPaynl extends hikashopPaymentPlugin
{
    # List of the plugin's accepted currencies. The plugin won't appear on the checkout if the current currency is not in that list. You can remove that attribute if you want your payment plugin to display for all the currencies
    var $accepted_currencies = array(
        "AUD",
        "BGN",
        "CAD",
        "CHF",
        "CNY",
        "CZK",
        "DKK",
        "EEK",
        "EUR",
        "GBP",
        "HKD",
        "HRK",
        "HUF",
        "IDR",
        "ZAR",
        "SGD",
        "SKK",
        "INR",
        "ISK",
        "JPY",
        "KRW",
        "LTL",
        "LVL",
        "MXN",
        "MYR",
        "NOK",
        "NZD",
        "PHP",
        "PLN",
        "RON",
        "RUB",
        "SEK",
        "THB",
        "TRY",
        "USD"
    );
    var $multiple = true; # Multiple plugin configurations. It should usually be set to true
    var $name = 'paynl'; # Payment plugin name (the name of the PHP file)


    # The constructor is optional if you don't need to initialize some parameters of some fields of the configuration and not that it can also be done in the getPaymentDefaultValues function as you will see later on
    function __construct(&$subject, $config)
    {
        return parent::__construct($subject, $config);
    }


    # This function is called at the end of the checkout. That's the function which should display your payment gateway redirection form with the data from HikaShop
    function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        parent::onAfterOrderConfirm($order, $methods,
            $method_id); # This is a mandatory line in order to initialize the attributes of the payment method

        # Here we can do some checks on the options of the payment method and make sure that every required parameter is set and otherwise display an error message to the user
        if (empty($this->payment_params->service_id)) # The plugin can only work if those parameters are configured on the website's backend
        {
            $this->app->enqueueMessage('You have to configure a Service ID for the Pay.nl plugin payment first : check your plugin\'s parameters, on your website backend',
                'error');
            # Enqueued messages will appear to the user, as Joomla's error messages
            return false;
        } elseif (empty($this->payment_params->token_api)) {
            $this->app->enqueueMessage('You have to configure an api token for the Pay.nl plugin payment first : check your plugin\'s parameters, on your website backend',
                'error');
            return false;
        } elseif (empty($this->payment_params->option_id)) {
            $this->app->enqueueMessage('You have to configure a payment option for the Pay.nl plugin payment first : check your plugin\'s parameters',
                'error');
            return false;
        } else {
            if (!class_exists('Pay_Api_Start')) {
                require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/Api.php');
                require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/api/Start.php');
                require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/Exception.php');
                require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/api/Exception.php');
            }
            if (!class_exists('Pay_Helper')) {
                require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/Helper.php');
            }

            if ($this->currency->currency_locale['int_frac_digits'] > 2) {
                $this->currency->currency_locale['int_frac_digits'] = 2;
            }
            $notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
            $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&user=true&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&order_id=' . $order->order_id . $this->url_itemid;


            # Enduser data
            $addressBT = $this->splitAddress($order->cart->billing_address->address_street . ' ' . $order->cart->billing_address->address_street2);
            $addressST = $this->splitAddress($order->cart->shipping_address->address_street . ' ' . $order->cart->shipping_address->address_street2);
            $lang = JFactory::getLanguage();
            $enduser = array(
                'initials' => substr($order->cart->billing_address->address_firstname, 0, 1),
                'lastName' => $order->cart->billing_address->address_lastname,
                'language' => substr($lang->getTag(),0,2),
                'emailAddress' => $order->customer->email,
                'invoiceAddress' => array(
                    'streetName' => $addressBT[0],
                    'streetNumber' => $addressBT[1],
                    'city' => $order->cart->billing_address->address_city,
                    'zipCode' => $order->cart->billing_address->address_post_code,
                    'countryCode' => $order->cart->billing_address->address_country->zone_code_2
                ),
                'address' => array(
                    'initials' => substr($order->cart->shipping_address->address_firstname, 0, 1),
                    'lastName' => $order->cart->shipping_address->address_firstname,
                    'streetName' => $addressST[0],
                    'streetNumber' => $addressST[1],
                    'city' => $order->cart->shipping_address->address_city,
                    'zipCode' => $order->cart->shipping_address->address_post_code,
                    'countryCode' => $order->cart->shipping_address->address_country->zone_code_2
                )
            );

            $paynlService = new Pay_Api_Start();
            $paynlService->setServiceId($this->payment_params->service_id);
            $paynlService->setApiToken($this->payment_params->token_api);
            $paynlService->setPaymentOptionId($this->payment_params->option_id);
            $paynlService->setAmount(round($order->cart->full_total->prices[0]->price_value_with_tax, 2) * 100);
            $paynlService->setDescription(JText::_('INVOICE') . ': ' . $order->order_number);
            $paynlService->setCurrency($this->currency->currency_code);
            $paynlService->setExchangeUrl($notify_url);
            $paynlService->setFinishUrl($return_url);
            $paynlService->setExtra1($order->order_id);
            $paynlService->setExtra2($order->order_number);
            $paynlService->setEnduser($enduser);
            $paynlService->setObject('hikashop 3.2.6');

            # Add items

            foreach ($order->cart->products as $product) {
                $amount = round($product->order_product_total_price * 100);
                if ($amount != 0) {
                    $price = $product->order_product_total_price;
                    $tax = $product->order_product_tax;
                    $taxClass = Pay_Helper::calculateTaxClass($price, $tax);

                    $paynlService->addProduct($product->order_product_id, $product->order_product_name, $amount,
                        $product->order_product_quantity, $taxClass);
                }
            }
            # Shipment
            if (!empty($order->order_shipping_price) && $order->order_shipping_price != 0) {
                $taxClass = Pay_Helper::calculateTaxClass($order->order_shipping_price, $order->order_shipping_tax);

                $paynlService->addProduct('shipment', $order->order_shipping_method,
                    round($order->order_shipping_price * 100), 1, $taxClass);
            }

            # Coupon
            if (!empty($order->order_discount_price) && $order->order_discount_price != 0) {
                $taxClass = Pay_Helper::calculateTaxClass($order->order_discount_price, $order->order_discount_tax);
                $paynlService->addProduct('discount', $order->order_discount_code,
                    round($order->order_discount_price * -100), 1);
            }

            # Payment
            if (!empty($order->order_payment_price) && $order->order_payment_price != 0) {
                $paynlService->addProduct('payment', $order->order_payment_method,
                    round($order->order_payment_price, (int)$this->currency->currency_locale['int_frac_digits']) * 100,
                    1);
            }


            try {
                $result = $paynlService->doRequest();
            } catch (Exception $ex) {
                die($ex);
            }
            $paynlUrl = $result['transaction']['paymentURL'];
            $this->saveTransaction(array(
                'transaction_id' => $result['transaction']['transactionId'],
                'option_id' => $this->payment_params->option_id,
                'amount' => round($order->cart->full_total->prices[0]->price_value_with_tax, 2) * 100,
                'order_id' => $order->order_id,
                'status' => 'PENDING'
            ));
            $this->app->redirect($paynlUrl);


            return true;
        }
    }


    # To set the specific configuration (back end) default values (see $pluginConfig array)
    function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = 'Pay.nl';
        $element->payment_description = 'Safe and fast payment method';
        $element->payment_images = '';
        $element->payment_params->address_type = "billing";
        $element->payment_params->notification = 1;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
    }


    # After submitting the platform payment form, this is where the website will receive the response information from the payment gateway servers and then validate or not the order
    function onPaymentNotification(&$statuses)
    {
        # We first create a filtered array from the parameters received
        $vars = array();
        $filter = JFilterInput::getInstance();
        foreach ($_REQUEST as $key => $value) {
            $key = $filter->clean($key);
            $value = hikaInput::get()->getString($key);
            $vars[$key] = $value;
        }

        $isExchange = !isset($vars['user']);


        if (!$isExchange) {
            $receivedOrderId = (int)$vars['order_id'];
            $transactionId = $vars['orderId'];
        } else {
            $receivedOrderId = (int)$vars['extra1'];
            $transactionId = $vars['order_id'];
        }

        $dbOrder = $this->getOrder($receivedOrderId);
        $this->loadPaymentParams($dbOrder);
        if (empty($this->payment_params)) {
            return false;
        }
        $this->loadOrderData($dbOrder);
        $order_id = $dbOrder->order_id;

        $success_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;
        $cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order_id . $this->url_itemid;

        $url = HIKASHOP_LIVE . 'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id=' . $order_id;
        $order_text = "\r\n" . JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE', $dbOrder->order_number,
                HIKASHOP_LIVE);
        $order_text .= "\r\n" . str_replace('<br/>', "\r\n", JText::sprintf('ACCESS_ORDER_WITH_LINK', $url));

        $order_status = $this->checkStatus($transactionId);
        $custom_state = $this->getCustomState($order_status);
        $history = new stdClass();
        $history->notified = 0;
        $history->amount = @$vars['amount'];
        $history->data = ob_get_clean();


        # Internal status paid so I will change anything
        if ($this->isPaid($dbOrder->order_id)) {
            $message = 'TRUE| message: transaction already paid, orderId:' . $dbOrder->order_id . ',  hikashop_order_status:' . $dbOrder->order_status . ', api_current_state: ' . $order_status .
                ',  status_canceled: ' . $this->payment_params->invalid_status .
                ',  status_pending: ' . $this->payment_params->pending_status .
                ',  status_success: ' . $this->payment_params->verified_status;
            if ($isExchange) {
                die($message);
            } else {
                $this->app->redirect($success_url);
                return true;
            }
        }# End internal status paid

        # Internal status not paid and received a paid notification
        if ($order_status == 'PAID' && !$this->isPaid($order_id)) {
            # Change internal status and send notification email
            $email = new stdClass();
            $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Paynl', $custom_state,
                $dbOrder->order_number);
            $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Paynl',
                    $custom_state)) . ' ' . JText::sprintf('ORDER_STATUS_CHANGED',
                    $custom_state) . "\r\n\r\n" . $order_text;
            $history->notified = 1;
            $this->modifyOrder($order_id, $custom_state, $history, $email);
			$this->updateTransaction($dbOrder->order_id, $order_status);

            if ($isExchange) {
                $message = 'TRUE| message: transaction paid, orderId:' . $dbOrder->order_id . ',  hikashop_order_status:' . $dbOrder->order_status . ', api_current_state: ' . $order_status .
                    ',  status_canceled: ' . $this->payment_params->invalid_status .
                    ',  status_pending: ' . $this->payment_params->pending_status .
                    ',  status_success: ' . $this->payment_params->verified_status;
                die($message);
                return true;
            } else {
                $this->app->redirect($success_url);
                return true;
            }
        }
        # Internal status not paid and received a cancelled notification
        if (!$this->isPaid($dbOrder->order_id) && $order_status == 'CANCEL') {
            # Change status and send email
            $email = new stdClass();
            $email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER', 'Paynl', $custom_state,
                $dbOrder->order_number);
            $email->body = str_replace('<br/>', "\r\n", JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER', 'Paynl',
                    $custom_state)) . ' ' . JText::sprintf('ORDER_STATUS_CHANGED',
                    $custom_state) . "\r\n\r\n" . $order_text;
            if($isExchange){
                $this->modifyOrder($order_id, $custom_state, true, $email);
                $this->updateTransaction($dbOrder->order_id, $order_status);
            }
            if (!$isExchange) {
                $this->app->enqueueMessage('Transaction Failed');
                $this->app->redirect($cancel_url);
                return false;
            } else {
                $message = 'TRUE| message: transaction cancelled, orderId:' . $dbOrder->order_id . ',  hikashop_order_status:' . $dbOrder->order_status . ', api_current_state: ' . $order_status .
                    ',  status_canceled: ' . $this->payment_params->invalid_status .
                    ',  status_pending: ' . $this->payment_params->pending_status .
                    ',  status_success: ' . $this->payment_params->verified_status;
                die($message);
            }

        }# END internal status not paid and received a cancelled notification
        # Received a pending request and internally not confirmed
        if (!$this->isPaid($dbOrder->order_id) && $order_status == 'PENDING') {
            if ($isExchange) {
                $message = 'TRUE| message: transaction pending, orderId:' . $dbOrder->order_id . ',  hikashop_order_status:' . $dbOrder->order_status . ', api_current_state: ' . $order_status .
                    ',  status_canceled: ' . $this->payment_params->invalid_status .
                    ',  status_pending: ' . $this->payment_params->pending_status .
                    ',  status_success: ' . $this->payment_params->verified_status;
                $this->updateTransaction($dbOrder->order_id, $order_status);
                die($message);
            } else {
                $this->app->redirect($success_url);
                return true;
            }
        }

    }


    private function splitAddress($strAddress)
    {
        $strAddress = trim($strAddress);
        $a = preg_split('/([0-9]+)/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);
        $strStreetName = trim(array_shift($a));
        $strStreetNumber = trim(implode('', $a));

        if (empty($strStreetName)) { # American address notation
            $a = preg_split('/([a-zA-Z]{2,})/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);

            $strStreetNumber = trim(implode('', $a));
            $strStreetName = trim(array_shift($a));
        }

        return array($strStreetName, $strStreetNumber);
    }

    private function checkStatus($order_id)
    {
        if (!class_exists('Pay_Api_Info')) {
            require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/Api.php');
            require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/api/Info.php');
            require(JPATH_SITE . '/plugins/hikashoppayment/paynl/paynl/Helper.php');
        }
        $payApiInfo = new Pay_Api_Info();
        $payApiInfo->setApiToken($this->payment_params->token_api);
        $payApiInfo->setServiceId($this->payment_params->service_id);
        $payApiInfo->setTransactionId($order_id);
        try {
            $result = $payApiInfo->doRequest();
        } catch (Exception $ex) {
            vmError($ex->getMessage());
        }


        $state = Pay_Helper::getStateText($result['paymentDetails']['state']);
        return $state;
    }


    private function saveTransaction($data)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $columns = array('transaction_id', 'option_id', 'amount', 'order_id', 'status');
        $values = array(
            $db->quote($data['transaction_id']),
            $db->quote($data['option_id']),
            $db->quote(strval($data['amount'] * 100)),
            $db->quote($data['order_id']),
            $db->quote($data['status'])
        );
        $query
            ->insert($db->quoteName('#__paynl_transactions'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
        $db->setQuery($query);
        $result = $db->execute();
        return $result;
    }

    private function updateTransaction($orderId, $status)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $date = new DateTime();

        $fields = array(
            $db->quoteName('status') . ' = ' . $db->quote($status),
            $db->quoteName('last_update') . ' = ' . $db->quote($date->format('Y-m-d H:i:s')),
        );

        $conditions = array(
            $db->quoteName('order_id') . ' = ' . $db->quote($orderId)
        );

        $query->update($db->quoteName('#__paynl_transactions'))->set($fields)->where($conditions);

        $db->setQuery($query);
        $result = $db->execute();
        return $result;
    }

    private function isPaid($orderId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $conditions = array(
            $db->quoteName('order_id') . " = " . $db->quote($orderId),
            $db->quoteName('status') . " = " . $db->quote('PAID'),
        );

        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__paynl_transactions'));
        $query->where($conditions);

        $db->setQuery($query);
        $count = $db->loadResult();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function getCustomState($state)
    {
        switch ($state) {
            case 'PAID':
                $vmstate = $this->payment_params->verified_status;
                break;
            case 'CANCEL':
                $vmstate = $this->payment_params->invalid_status;
                break;
            case 'PENDING':
                $vmstate = $this->payment_params->pending_status;
                break;

        }
        return $vmstate;
    }


}
