<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

$com_info = [
             'menu_name'   => lang('Unitpay', 'payment_method_unitpay'), // Menu name
             'description' => lang('Метод оплаты Unitpay', 'payment_method_unitpay'), // Module Description
             'admin_type'  => 'window', // Open admin class in new window or not. Possible values window/inside
             'window_type' => 'xhr', // Load method. Possible values xhr/iframe
             'w'           => 600, // Window width
             'h'           => 550, // Window height
             'author'      => 'dev@unitpay.ru', // Author info
             'icon_class'  => 'icon-barcode',
            ];

/* End of file module_info.php */