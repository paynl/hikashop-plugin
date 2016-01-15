<?php
/*
* The HTML of the backend specifics configuration can be listed here. You don't need that file if you use the $pluginConfig attribute of the main PHP file of the plugin. But if you need custom type of fields which are not supported by the system, like we do for some plugins, you can use this file instead.
*/
?>
<script type="text/javascript">
  function getPaymentProfiles(serviceId,apiToken){
    var serviceId = jQuery('[name="data[payment][payment_params][service_id]"]');
    var apiToken = jQuery('[name="data[payment][payment_params][token_api]"]');
    var optionId = jQuery('[name="data[payment][payment_params][option_id]"]');
    var selectedOptionId = "<?php echo @$this->element->payment_params->option_id; ?>";
    
    if(serviceId.val() != '' && apiToken.val() != ''){
        optionId.html('<option>Loading...</option>');
        jQuery.ajax({
            url: 'https://rest-api.pay.nl/v4/Transaction/getServicePaymentOptions/jsonp/?token='+apiToken.val()+'&serviceId='+serviceId.val(),
            dataType: 'jsonp',
            success: function(data){
                if(data.request.result == 1){                  
                    var options = "";
                    jQuery.each(data.paymentProfiles, function(key, profile){
                        options += "<option value='"+profile.id+"'>"+profile.name+"</option>";
                    });
                    optionId.html(options);
                    optionId.val(selectedOptionId);
                    optionId.trigger("liszt:updated");  
                } else {console.log(data.request.result);
                    optionId.html('<option>Please check ApiToken and serviceId</option>');
                    optionId.trigger("liszt:updated");
                    alert('Error: '+data.request.errorMessage);
                }
            }
        });
    } 
}
jQuery(document).ready(function(){
    getPaymentProfiles();
	var serviceId = jQuery('[name="data[payment][payment_params][service_id]"]');
    var apiToken = jQuery('[name="data[payment][payment_params][token_api]"]');
    
    serviceId.change(function(evt,params){getPaymentProfiles();});
    apiToken.change(function(evt,params){getPaymentProfiles();});

});
</script>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][token_api]">
			<?php echo JText::_( 'PAYNL_TOKEN_API' ); ?>
		</label>
	</td>
	<td>
		<input type="text" name="data[payment][payment_params][token_api]" value="<?php echo @$this->element->payment_params->token_api; ?>" />
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][service_id]">
			<?php echo JText::_( 'PAYNL_SERVICE_ID' ); ?>
		</label>
	</td>
	<td>
		<input type="text" name="data[payment][payment_params][service_id]" value="<?php echo @$this->element->payment_params->service_id; ?>" />
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][option_id]">
			<?php echo JText::_( 'PAYNL_PAYMENT_OPTION_ID' ); ?>
		</label>
	</td>
		<td>
		<select name="data[payment][payment_params][option_id]">
			
		</select>
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][invalid_status]"><?php
			echo JText::_('INVALID_STATUS');
		?></label>
	</td>
	<td><?php
		echo $this->data['order_statuses']->display("data[payment][payment_params][invalid_status]", @$this->element->payment_params->invalid_status);
	?></td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][pending_status]"><?php
			echo JText::_('PENDING_STATUS');
		?></label>
	</td>
	<td><?php
		echo $this->data['order_statuses']->display("data[payment][payment_params][pending_status]", @$this->element->payment_params->pending_status);
	?></td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][verified_status]"><?php
			echo JText::_('VERIFIED_STATUS');
		?></label>
	</td>
	<td><?php
		echo $this->data['order_statuses']->display("data[payment][payment_params][verified_status]", @$this->element->payment_params->verified_status);
	?></td>
</tr>

