<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');


class payment_method_unitpay extends MY_Controller
{

    public $paymentMethod;

    public $moduleName = 'payment_method_unitpay';

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('payment_method_unitpay');
    }

    public function index() {
        lang('unitpay', 'payment_method_unitpay');
    }

    /**
     * Вытягивает данные способа оплаты
     * @param str $key
     * @return array
     */
    private function getPaymentSettings($key) {
        $ci = &get_instance();
        $value = $ci->db->where('name', $key)
            ->get('shop_settings');
        if ($value) {
            $value = $value->row()->value;
        } else {
            show_error($ci->db->_error_message());
        }
        return unserialize($value);
    }

    /**
     * Вызывается при редактировании способов оплатыв админке
     * @param integer $id ид метода оплаты
     * @param string $payName название payment_method_liqpay
     * @return string
     */
    public function getAdminForm($id, $payName = null) {
        if (!$this->dx_auth->is_admin()) {
            redirect('/');
            exit;
        }

        $nameMethod = $payName ? $payName : $this->paymentMethod->getPaymentSystemName();
        $key = $id . '_' . $nameMethod;
        $data = $this->getPaymentSettings($key);

        $data = array_merge($data,

            [
                'public_key_label'  =>  lang('PUBLIC KEY', 'payment_method_unitpay'),
                'secret_key_label'  =>  lang('SECRET KEY', 'payment_method_unitpay'),
                'merchant_setting_label'  =>  lang('Merchant settings', 'payment_method_unitpay'),
                'callback_url'  =>  site_url('payment_method_unitpay/payment_method_unitpay/callback'),
            ]
            );

        /*$codeTpl = \CMSFactory\assetManager::create()
                ->setData('data', $data)
                ->fetchTemplate('adminForm');

        return $codeTpl;*/

        return '           
             <div class="control-group">
                <label class="control-label" for="inputRecCount">' . lang('PUBLIC KEY', 'payment_method_unitpay') . ':</label>
                <div class="controls">
                  <input type="text" name="payment_method_unitpay[public_key]" value="' . $data['public_key'] . '"  />
                </div>
            </div>          
            <div class="control-group">
                <label class="control-label" for="inputRecCount">' . lang('SECRET KEY', 'payment_method_unitpay') . ':</label>
                <div class="controls">
                 <input type="text" name="payment_method_unitpay[secret_key]" value="' . $data['secret_key'] . '"  />
                </div>
            </div>
            
            <div class="control-group">
                <label class="control-label" for="inputRecCount">' . lang('Merchant settings', 'payment_method_unitpay') . ':</label>
                <div class="controls" style="width:100%;">
                    <b>Pay URL:</b> ' . site_url('payment_method_unitpay/payment_method_unitpay/callback') . '<br/>
                    <span class="help-block">' . lang('The method of sending data for all requests: GET', 'main') . '</span>
                </div>
            </div>
   
        ';

    }

    /**
     * Формирование кнопки оплаты
     * @param obj $param Данные о заказе
     * @return str
     */
    public function getForm($param) {
        $payment_method_id = $param->getPaymentMethod();
        $key = $payment_method_id . '_' . $this->moduleName;
        $paySettings = $this->getPaymentSettings($key);

        $descr = 'OrderId: ' . $param->id . '; Key: ' . $param->getKey();
        $price = $param->getDeliveryPrice() ? ($param->getTotalPrice() + $param->getDeliveryPrice()) : $param->getTotalPrice();
        $code = \Currency\Currency::create()->getMainCurrency()->getCode();

        $price = number_format(str_replace(',', '.', $price), 2, '.', '');

        $data['account'] = $param->id;
        $data['currency'] = $code;
        $data['description'] = $descr;
        $data['sum'] = $price;
        $data['public_key'] = $paySettings['public_key'];

        /*$codeTpl = \CMSFactory\assetManager::create()
                ->setData('data', $data)
                ->fetchTemplate('form');

        return $codeTpl;*/

        return '
        
        <form id="paidForm" name="pay" method="GET" action="https://unitpay.ru/pay/' . $data['public_key'] . '">
            <input type="hidden" name="account" value="' . $data['account'] . '" />
            <input type="hidden" name="description" value="' . $data['description'] . '" />
            <input type="hidden" name="sum" value="' . $data['sum'] . '" />
            <input type="hidden" name="currency" value="' . $data['currency'] . '" />
            <div class="btn-cart btn-cart-p">
                <input type="submit" value="' . lang('Оплатить','payment_method_unitpay') . '">
            </div>
        </form>
        
        ';

    }

    /**
     * Save settings
     *
     * @return bool|string
     */
    public function saveSettings(SPaymentMethods $paymentMethod) {
        $saveKey = $paymentMethod->getId() . '_' . $this->moduleName;
        \ShopCore::app()->SSettings->set($saveKey, serialize($_POST['payment_method_unitpay']));

        return true;
    }

    public function autoload() {

    }

    public function _install() {
        $ci = &get_instance();

        $result = $ci->db->where('name', $this->moduleName)
            ->update('components', ['enabled' => '1']);
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }
    }

    public function _deinstall() {
        $ci = &get_instance();

        $result = $ci->db->where('payment_system_name', $this->moduleName)
            ->update(
                'shop_payment_methods',
                [
                 'active'              => '0',
                 'payment_system_name' => '0',
                ]
            );
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }

        $result = $ci->db->like('name', $this->moduleName)
            ->delete('shop_settings');
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }
    }

    public function callback()
    {

        $data = $_GET;
        $method = '';
        $params = array();
        if ((isset($data['params'])) && (isset($data['method'])) && (isset($data['params']['signature']))) {
            $params = $data['params'];
            $method = $data['method'];
            $signature = $params['signature'];

            $ci = &get_instance();

            $order_id = $params['account'];
            $userOrder = $ci->db->where('id', $order_id)
                ->get('shop_orders');
            if ($userOrder) {
                $userOrder = $userOrder->row();
                $key = $userOrder->payment_method . '_' . $this->moduleName;
                $paySettings = $this->getPaymentSettings($key);
                $secret_key = $paySettings['secret_key'];
            } else {
                $secret_key = '';
            }

            if (empty($signature)) {
                $status_sign = false;
            } else {
                $status_sign = $this->verifySignature($params, $method, $secret_key);
            }
        } else {
            $status_sign = false;
        }
//        $status_sign = true;
        if ($status_sign) {
            switch ($method) {
                case 'check':
                    $result = $this->check($params);
                    break;
                case 'pay':
                    $result = $this->pay($params);
                    break;
                case 'error':
                    $result = $this->error($params);
                    break;
                default:
                    $result = array('error' =>
                        array('message' => 'неверный метод')
                    );
                    break;
            }
        } else {
            $result = array('error' =>
                array('message' => 'неверная сигнатура')
            );
        }
        $this->hardReturnJson($result);
    }
    function check( $params )
    {
        $ci = &get_instance();

        $order_id = $params['account'];
        $userOrder = $ci->db->where('id', $order_id)
            ->get('shop_orders');

        if (!$userOrder) {
            $result = array('error' =>
                array('message' => 'не совпадает сумма заказа')
            );
        } else {
            $userOrder = $userOrder->row();
            $total = $userOrder->delivery_price + $userOrder->total_price;
            $price = number_format(str_replace(',', '.', $total), 2, '.', '');
            $ISOCode = SCurrenciesQuery::create()->filterByIsDefault(true)->findOne()->getCode();
            if ($ISOCode == 'RUR') {
                $ISOCode = 'RUB';
            }

            if ((float)$total != (float)$params['orderSum']) {
                $result = array('error' =>
                    array('message' => 'не совпадает сумма заказа')
                );
            }elseif ($ISOCode != $params['orderCurrency']) {
                $result = array('error' =>
                    array('message' => 'не совпадает валюта заказа')
                );
            }
            else{
                $result = array('result' =>
                    array('message' => 'Запрос успешно обработан')
                );
            }
        }

        return $result;
    }
    function pay( $params )
    {
        $ci = &get_instance();

        $order_id = $params['account'];
        $userOrder = $ci->db->where('id', $order_id)
            ->get('shop_orders');

        if (!$userOrder) {
            $result = array('error' =>
                array('message' => 'не совпадает сумма заказа')
            );
        } else {
            $userOrder = $userOrder->row();
            $total = $userOrder->delivery_price + $userOrder->total_price;
            $ISOCode = SCurrenciesQuery::create()->filterByIsDefault(true)->findOne()->getCode();
            if ($ISOCode == 'RUR') {
                $ISOCode = 'RUB';
            }

            if ((float)$total != (float)$params['orderSum']) {
                $result = array('error' =>
                    array('message' => 'не совпадает сумма заказа')
                );
            }elseif ($ISOCode != $params['orderCurrency']) {
                $result = array('error' =>
                    array('message' => 'не совпадает валюта заказа')
                );
            }
            else{

                //устанавливаем в статус "оплачено" и добавляем к сумме на аккаунте, почему-то без доставки
                $amount = $ci->db->select('amout')
                    ->get_where('users', ['id' => $userOrder->user_id]);

                $amount = $amount->row()->amout;
                $amount += $userOrder->total_price;

                $ci->db->where('id', $order_id)
                    ->update('shop_orders', ['paid' => '1', 'date_updated' => time()]);

                $ci->db
                    ->where('id', $userOrder->user_id)
                    ->limit(1)
                    ->update(
                        'users',
                        [
                            'amout' => str_replace(',', '.', $amount),
                        ]
                    );

                $result = array('result' =>
                    array('message' => 'Запрос успешно обработан')
                );
            }
        }

        return $result;
    }
    function error( $params )
    {
        $result = array('result' =>
            array('message' => 'Запрос успешно обработан')
        );
        return $result;
    }
    function getSignature($method, array $params, $secretKey)
    {
        ksort($params);
        unset($params['sign']);
        unset($params['signature']);
        array_push($params, $secretKey);
        array_unshift($params, $method);
        return hash('sha256', join('{up}', $params));
    }
    function verifySignature($params, $method, $secret)
    {
        return $params['signature'] == $this->getSignature($method, $params, $secret);
    }
    function hardReturnJson( $arr )
    {
        header('Content-Type: application/json');
        $result = json_encode($arr);
        die($result);
    }

}