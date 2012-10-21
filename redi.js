var $j = jQuery.noConflict();

function parseTime(timeString)
{
  if (timeString == '') return null;
  var d = new Date();
  var time = timeString.match(/(\d+)(:(\d\d))?\s*(p?)/i);

  d.setHours( parseInt(time[1],10) + ( ( parseInt(time[1],10) < 12 && time[4] ) ? 12 : 0) );
  d.setMinutes( parseInt(time[3],10) || 0 );
  d.setSeconds(0, 0);
  return d;
}

function update_services()
{
    var data = {
        action: 'redi-submit',
        get: 'services',
        startDate: $j('#startDate').val(),
        endDate: $j('#endDate').val(),
        startTime: $j('#startTime').val(),
        endTime: $j('#endTime').val(),
        category_id: $j('#category_id').val(),
        place_id: $j("#place_id").val()
    };
            
    jQuery.post(MyAjax.ajaxurl, data, function(response) {

	if(response.Status =="ERROR")
		$j("#services_div").html('<div class="redi_validation_error">'+response.Message+'</div>'+response.data);
	else
        $j("#services_div").html(response.data);
    }, "json");
}

$j(function(){

    $j('#startTime').timepicker(
    {
        stepMinute: 15,
        onClose:  function(dateText, inst)
        {
			timestr = $j('#startTime').val();
			
			time = parseTime(timestr);
			time.setMinutes(time.getMinutes() + 30);
			
			leadingHours = "";
			leadingMins = "";
			
			if (time.getHours() < 10)
			{
				leadingHours = "0";
			}
			
			if (time.getMinutes() < 10)
			{
				leadingMins = "0";
			}
			
			$j('#endTime').val(leadingHours + time.getHours() + ':' + leadingMins + time.getMinutes());

			update_services();
	}
    });
    $j('#endTime').timepicker(
    {
        stepMinute: 15,
        onClose:  function(dateText, inst)
        {
            update_services();
	}

    });
    
    $j( "#startDate" ).datepicker({
        dateFormat: 'yy-mm-dd', 
        onSelect: function(dateText, inst){
			$j('#endDate').val($j('#startDate').val());
            update_services();
        } 
    });
    $j( "#endDate" ).datepicker({
        dateFormat: 'yy-mm-dd',
        onSelect: function(dateText, inst){
            update_services();
        }
    });
     
    $j('#place').change(function() {
        $j("#place option:selected").each(function () {
            $j("#place_id").val(($j(this).val()));
            var data = {
                action: 'redi-submit',
                get: 'getcategories',
                place_id: $j(this).val()
                
            };
            $j('#place_id').val($j(this).val());
            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(MyAjax.ajaxurl, data, function(response) {
                //alert('Got this from the server: ' + response);
                $j("#category_div").html(response);
                //update services
                $j("#category option:selected").each(function () {
                    $j('#category_id').val($j(this).val());
                    update_services();
                });
            });
        }); 
    });
    
    $j('#category_div').on('change', '.category', function() {

        $j("#category option:selected").each(function () {
            $j('#category_id').val($j(this).val());
            update_services();
        });
    });
    
});

$j.ajaxSetup({
  beforeSend: function() {
     $('#loader').show();
     $('#services_div').hide();
  },
  complete: function(){
     $('#loader').hide();
     $('#services_div').slideDown('slow');
  },
  success: function() {}
});