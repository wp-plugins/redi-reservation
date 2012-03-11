var $j = jQuery.noConflict();
$j(function(){


    $j( "#startDate" ).datepicker();
    $j( "#endDate" ).datepicker();

    //    $j('#dateFrom').datetimepicker();
    //    $j('#dateTo').datetimepicker();
     
    $j('#place').change(function() {
        $j("#place option:selected").each(function () {
            $j("#place_hidden").val(($j(this).val()));
            var data = {
                action: 'redi-submit',
                get: 'getcategories',
                place_id: $j(this).val()
                
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(MyAjax.ajaxurl, data, function(response) {
                //alert('Got this from the server: ' + response);
                $j("#category_div").html(response);
                
            });
        }); 
    });
    
    $j('#category_div').on('change', '.category', function() {

        $j("#category option:selected").each(function () {
            var data = {
                action: 'redi-submit',
                get: 'services',
                startDate: $j('#startDate').val(),
                endDate: $j('#endDate').val(),
                startTime: $j('#startTime').val(),
                endTime: $j('#endTime').val(),
                //place_id: $j("#place_hidden").val(),
                category_id: $j(this).val()
                
            };
            
            jQuery.post(MyAjax.ajaxurl, data, function(response) {
                $j("#services_div").html(response);
            });
        });
    });
});