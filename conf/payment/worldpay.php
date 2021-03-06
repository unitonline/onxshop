<?php
/**
 * Worldpay configuration
 *
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 * 
 */

// TODO: limit form WorldPay servers 155.136.16.0/24
// or add passsword

// value 100 for test mode, value 0 for production environment
define('ECOMMERCE_TRANSACTION_WORLDPAY_TESTMODE', 100);
define('ECOMMERCE_TRANSACTION_WORLDPAY_URL', 'https://secure-test.wp3.rbsworldpay.com/wcc/purchase');
//define('ECOMMERCE_TRANSACTION_WORLDPAY_URL', 'https://select.worldpay.com/wcc/purchase');
define('ECOMMERCE_TRANSACTION_WORLDPAY_INSID', 0);
define('ECOMMERCE_TRANSACTION_WORLDPAY_DESCRIPTION', "Payment for goods from the {$GLOBALS['onxshop_conf']['global']['title']} shop.");
define('ECOMMERCE_TRANSACTION_MAIL_TO', $GLOBALS['onxshop_conf']['global']['admin_email']);
define('ECOMMERCE_TRANSACTION_MAIL_TONAME', $GLOBALS['onxshop_conf']['global']['admin_email_name']);

