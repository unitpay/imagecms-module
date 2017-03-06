<div class="control-group">
    <label class="control-label" for="inputRecCount">{echo $data['public_key_label']}:</label>
    <div class="controls">
        <input type="text" name="payment_method_unitpay[public_key]" value="{echo $data['public_key']}"  />
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{echo $data['secret_key_label']}:</label>
    <div class="controls">
        <input type="text" name="payment_method_unitpay[secret_key]" value="{echo $data['secret_key']}"  />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="inputRecCount">{echo $data['merchant_setting_label']}:</label>
    <div class="controls" style="width:100%;">
        <b>Pay URL:</b> {echo $data['callback_url']}<br/>
    </div>
</div>