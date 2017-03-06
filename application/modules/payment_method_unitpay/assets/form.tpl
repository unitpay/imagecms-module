<form id="paidForm" name="pay" method="POST" action="https://unitpay.ru/pay/{echo $data['public_key']}">
    <input type="hidden" name="account" value="{echo $data['account']}" />
    <input type='hidden' name='description' value="{echo $data['description']}" />
    <input type="hidden" name="sum" value="{echo $data['sum']}" />
    <input type="hidden" name="currency" value="{echo $data['currency']}" />
    <div class='btn-cart btn-cart-p'>
        <input type='submit' value='{lang('Оплатить','payment_method_unitpay')}'>
    </div>
</form>