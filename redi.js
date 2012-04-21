var $j = jQuery.noConflict();
function udpate_services()
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
        $j("#services_div").html(response);
    });
}

function update_categories()
{
    var data = {
        action: 'redi-submit',
        get: 'services',
        startDate: $j('#startDate').val(),
        endDate: $j('#endDate').val(),
        startTime: $j('#startTime').val(),
        endTime: $j('#endTime').val(),
        category_id: $j("#category_id").val()
    };
            
    jQuery.post(MyAjax.ajaxurl, data, function(response) {
        $j("#services_div").html(response);
    });
}

$j(function(){

    $j('#startTime').timepicker(
    {
        stepMinute: 15,
        onClose:  function(dateText, inst)
        {
            udpate_services();
	}
    });
    $j('#endTime').timepicker(
    {
        stepMinute: 15,
        onClose:  function(dateText, inst)
        {
            udpate_services();
	}

    });
    
    $j( "#startDate" ).datepicker({
        dateFormat: 'yy-mm-dd', 
        onSelect: function(dateText, inst){
            udpate_services();
        } 
    });
    $j( "#endDate" ).datepicker({
        dateFormat: 'yy-mm-dd',
        onSelect: function(dateText, inst){
            udpate_services();
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
                    update_categories();
                });
            });
        }); 
    });
    
    $j('#category_div').on('change', '.category', function() {

        $j("#category option:selected").each(function () {
            $j('#category_id').val($j(this).val());
            update_categories();
        });
    });
    
});