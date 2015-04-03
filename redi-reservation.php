<?php
/*
  Plugin Name: ReDi Reservation
  Plugin URI: http://reservationdiary.eu/eng/reservation-wordpress-plugin/
  Description: ReDi Reservation plugin allows you to manage reservations for your business. This plugin can help places such restaurants, bars, saunas, photo studios, billiards, bowlings, yachts and so on to receive reservations from clients online. Your clients will be able to see available space at specified time, and if it's available, client is able to make a reservation. To activate: Create new page and place {redi} in page content.
  Version: 14.1122
  Author: reservationdiary.eu
  Author URI: http://reservationdiary.eu/
 */

define('REDIAPI', 'http://provider.reservationdiary.eu/'.str_replace('_', '-', get_locale()).'/api2/');
define('REDI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REDI_DEBUG', false);

if (!class_exists('ReDiReservation'))
{

    class ReDiReservation
    {

        var $version = '14.1122';

        /**
         * @var string The options string name for this plugin
         */
        var $optionsName = 'wp_redi_options';
        var $options = array();

        function ReDiReservation()
        {
            $this->__construct();
        }

        //TODO: better version check
        function plugin_get_version()
        {
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_version = $plugin_data['Version'];
            return $plugin_version;
        }

        /**
         * Retrieves the plugin options from the database.
         * @return array
         */
        function get_options()
        {
            if (!$options = get_option($this->optionsName))
            {
                $options = array(
                    'key' => '35cfa9ba-8633-46d3-896c-32591c0e0cfc',
                );
                update_option($this->optionsName, $options);
            }
            $this->options = $options;
        }

        public function content()
        {
            $post_success = false;
			$content = '';
			if(trim($this->options['key']) == '35cfa9ba-8633-46d3-896c-32591c0e0cfc')
			{
				$content ='<div class="redi_validation_error">This is a demo account. <br/>'.
				'You need to register to obtain your API key at <br/>'.
				'<a href="http://www.reservationdiary.eu/ProviderHome/Register.aspx">http://www.reservationdiary.eu/ProviderHome/Register.aspx</a><br/>'.
				'<br/><br/>When you receive API key by email, please set it in admin panel of redi plugin.<br/></div>';
			}
            $content .= '<!--{version:"'.$this->version.'"}--><form name="redi" method="post">';
            if ($_POST['submit'])
            {
                if ($_POST['Name'] != '' && $_POST['Phone'] != '' && $_POST['Email'] != '')
                {

                    $services = '';
                    foreach ((array) $_POST['service'] as $service)
                    {
                        $services .='&serviceid='.$service;
                    }

                    if (!(isset($_SESSION['redi_data_send']) && $_SESSION['redi_data_send'] + 30/* TODO change to 5 min */ > time()))
                    {
                        $_SESSION['redi_data_send'] = time();

                        $return_json = $this->get(
                                'reserve', '?name='.urlencode($_POST['Name']).
                                '&phone='.urlencode($_POST['Phone']).
                                '&email='.urlencode($_POST['Email']).
                                '&comments='.urlencode($_POST['Comments']).
                                $services.
                                '&startDate='.urlencode($_POST['startDate'].' '.$_POST['startTime']).
                                '&endDate='.urlencode($_POST['endDate'].' '.$_POST['endTime'])
                        );


                        $content.='<p class="redi_status_'.strtolower($return_json->Status).'">'.$return_json->Message.'</p>';
                        if($return_json->Status == 'SUCCESS')
                        {
                            $print_url = 'http://reservationdiary.eu/eng/Client/DiscountTicket/Print/'.$return_json->RefID;
                            $content .='<div onclick="window.open(\''.$print_url.'\')" class="redi_button redi_button-icon redi_button-print">
                            <span class="redi_icon-print"></span>
                            Print reservation ticket
                            </div><br/>';
                            $post_success = true;
                        }
                    }
                    else
                        $content.= '<b class="redi_validation_error">Data already send.</b>';
                } else
                {
                    $content.= '<b class="redi_validation_error">Not all required fields are set.</b>';
                }
                $content.='<br/>';
            }

            if (!$post_success)
            {
                date_default_timezone_set('Europe/Minsk');

                $places = $this->get('places');
                $content .= $this->getPlaces($places);

	            $first_place = $places[0]->ID;
	            $content .= '<input type="hidden" id="place_id" value="'.$first_place.'"/>';

                $content .= '<div id="category_div">';
                $categories = $this->get('categories', '?placeid='.$first_place);
                $content .= $this->getcategories($categories);

                $startDate = date('Y-m-d', strtotime('+48 hour'));
                $startTime = date('G:00');

                $endDate = date('Y-m-d', strtotime('+48 hours 30 minutes'));
                $endTime = date('G:30');

                $first_category = $categories[0]->ID;

                $services = $this->get(
                        'services', '?categoryid='.$first_category.'&startDate='.urlencode($startDate.' '.$startTime).
                        '&endDate='.urlencode($endDate.' '.$endTime)
                );

                $content .= '</div>';
                $content .= '<br/><label for="startDate">Date and time: </label><br/>';
                $content .= '<input type="text" value="'.$startDate.'" name="startDate" id="startDate"/> <input id="startTime" type="text" value="'.$startTime.'" name="startTime"/><br/>';
                $content .= '<input type="text" value="'.$endDate.'" name="endDate" id="endDate"/> <input id="endTime" type="text" value="'.$endTime.'" name="endTime"/>';
                $content .= '<br/><br/><label for="services_div">Services: </label><br/>';
                $content .= $this->getservices($services, $first_category);
				$content .= $this->getLegend();
                $content .= $this->user_info_form();
            }
            return $content.'</form>';
        }

        public function user_info_form()
        {
            //phone
            $content = '<div>
            <div>
                <label for="Name">Name</label> <span class="redi_required">*</span><br>
                <input type="text" value="" name="Name" id="Name">
                <span id="Name_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Phone">Phone</label> <span class="redi_required">*</span><br>
                <input type="text" value="" name="Phone" id="Phone">
                <span id="Phone_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Email">Email</label> <span class="redi_required">*</span><br>
                <input type="text" value="" name="Email" id="Email">
                <span id="Email_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Comments">Comment</label><br>
                <textarea rows="2" name="Comments" id="Comments" cols="20"></textarea>
            </div>
            <div style="margin-top: 30px; margin-bottom: 30px;">
                    <input id="submit" type="submit" value="make a reservation" name="submit">
            </div>
        </div>';
            return $content;
        }

        public function getLegend()
		{
			$content = '            
			<div class="table-info">
                <div class="span-15">
					<div class="">
                        <div class="span-1 orange" style="width: 19px; height: 17px;">
                        </div>
                        <div class="span-2" style="white-space: nowrap">reserved</div>
                                                
						<div class="span-1 grey" style="width: 19px; height: 17px;">
                        </div>
                        <div class="span-2" style="white-space: nowrap">closed</div>
                        
						<div class="span-1 selected_non_working" style="width: 19px; height: 17px;">
                        </div>
                        <div class="span-2" style="white-space: nowrap">unavailable</div>
                    </div>
				</div>
			</div>';
            return $content;
		}

        public function getservices($services, $first_category)
        {
            $content = '';
            $content .='<div style="padding-left:300px; display:none; height:50px;" id="loader"><div id="loader_image"></div></div>';
            $content .= '<div id="services_div">';

            $content .= '<input type="hidden" id="category_id" value="'.$first_category.'"/>';
            $content .='<table>';
			foreach ((array)$services as $service)
			{
				$content .='<tr class="service_status_'.$service->Status.'">'.
						'<td><input type="checkbox" '.
						(in_array($service->Status, array(
							'NON_WORKING_TIME',
							'RESERVED',
							'MIXED',
							'OUT_OF_ORDER',
							'UNKNOWN'
						)) ? 'disabled="disabled"' : '').' name="service[]" value="'.$service->ID.'" /></td>'.
						'<td>'.$service->Name.'</td><td>'.$service->Comments.'</td></tr>';
			}

            $content .= '</table></div>';

            return $content;
        }

        public function getcategories($categories)
        {
			if(is_array($categories) && count($categories) > 1)
			{
				$content = '<label for="category">Category: </label><br/>
				<select id="category" name="category" class="category">';
				foreach ( $categories as $category)
				{
					$content .='<option value="'.$category->ID.'">'.$category->Name.'</option>';
				}
				$content.='</select>';
			}

            return $content;
        }

        public function get($func, $params='')
        {
            $url = REDIAPI.$func.'/'.trim($this->options['key']).$params;

            set_error_handler(
                    create_function(
                            '$severity, $message, $file, $line', 'throw new ErrorException($message, $severity, $severity, $file, $line);'
                    )
            );

            try
            {				
                $json = @file_get_contents($url, 0, null, null);
            } catch (Exception $e)
            {
                if (REDI_DEBUG)
                {
                    echo 'debug:on<br/>';
                    echo $url.'<br/>';
                    echo $e->getMessage();
                }
            }
            restore_error_handler();

            if ($json)
                return json_decode($json);
        }

        public function put($func, $params)
        {
            $url = REDIAPI.$func.'?apikey='.trim($this->options['key']).$params;

            $json = @file_get_contents($url, 0, null, null);
            if ($json)
                return json_decode($json);
        }

        public function getPlaces($places)
        {
			if(is_array($places) && count($places) > 1)
			{
				$content = '<br/><label for="place">Place: </label><br/><select name="place" id="place">';
				foreach ( $places as $place)
				{
					$content .='<option value="'.$place->ID.'">'.$place->Name.'</option>';
				}
				$content.='</select>';
			}

            return $content;
        }

        function init_sessions()
        {
            if (!session_id())
            {
                session_start();
            }
        }

        public function __construct()
        {
            //Initialize the options
            $this->get_options();
            //Actions
            add_action('init', array(&$this, 'init_sessions'));
            add_action('admin_menu', array(&$this, 'redi_admin_menu_link'));
            add_filter('the_content', array(&$this, 'redi_filter'));
            wp_register_style('redistyle', REDI_PLUGIN_URL.'redi.css');

            wp_enqueue_style('redistyle');

            wp_enqueue_script('timepicker-addon-js');

            wp_register_style('jquery_ui', null, array('jquery')); //plugins_url('styles/jquery-ui-1.8.2.custom.css')
            wp_enqueue_style('jquery_ui');

            wp_enqueue_script('jquery-ui-datepicker');


            wp_register_script('redi', REDI_PLUGIN_URL.'redi.js', array('jquery'));
            wp_enqueue_script('redi');

            wp_register_style('jquery-ui-custom-style', REDI_PLUGIN_URL.'/css/custom-theme/jquery-ui-1.8.18.custom.css');
            wp_enqueue_style('jquery-ui-custom-style');
            wp_localize_script('redi', 'MyAjax', array(
                // URL to wp-admin/admin-ajax.php to process the request
                'ajaxurl' => admin_url('admin-ajax.php')
                    )
            );
            wp_register_script('datetimepicker', REDI_PLUGIN_URL.'/lib/datetimepicker/js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-core', 'jquery-ui-slider', 'jquery-ui-datepicker'));
            wp_enqueue_script('datetimepicker');

            add_action('wp_ajax_nopriv_redi-submit', array(&$this, 'redi_ajax'));
            add_action('wp_ajax_redi-submit', array(&$this, 'redi_ajax'));
        }

        function redi_filter($content)
        {
            if (strpos($content, '{redi}') === false)
            {
                return $content;
            } else
            {
                $code = $this->content(false);
                $content = str_replace('{redi}', $code, $content);
                return $content;
            }
        }

        /**
         * @desc Adds the options subpanel
         */
        function redi_admin_menu_link()
        {
            add_options_page('Redi Reservation', 'Redi Reservation', 10, basename(__FILE__), array(&$this, 'redi_admin_options_page'));
        }

        function redi_ajax()
        {
            switch ($_POST['get'])
            {
                case 'getcategories':
                    echo $this->getcategories($this->get('categories', '?placeid='.intval($_POST['place_id'])));
                    break;

                case 'services':
                    $category_id = $_POST['category_id'];
                    $startDate = $_POST['startDate'];
                    $startTime = $_POST['startTime'];

                    $endDate = $_POST['endDate'];
                    $endTime = $_POST['endTime'];
                    $services = $this->get(
                            'services', '?categoryid='.$category_id.'&startDate='.urlencode($startDate.' '.$startTime).
                            '&endDate='.urlencode($endDate.' '.$endTime)
                    );
					if($services->Status == 'ERROR')
					{
						$services->data = '<input type="hidden" id="category_id" value="'.$category_id.'"/>';
						echo json_encode($services);
					}
					else
                    echo json_encode (array('data' => $this->getservices($services, $category_id)));
                    break;
            }

            die(); // this is required to return a proper result
        }

        /**
         * Saves the admin options to the database.
         */
        function save_admin_options()
        {
            return update_option($this->optionsName, $this->options);
        }

        /**
         * Adds settings/options page
         */
        function redi_admin_options_page()
        {
            if (isset($_POST['wp_redi_save']))
            {

                $this->options['key'] = trim($_POST['key']);

                $this->save_admin_options();

                echo '<div class="updated"><p>'.__('Your changes were successfully saved!', $this->localizationDomain).'</p></div>';
            }
            ?>

            <div class="wrap">
                <div class="icon32" id="icon-options-general"><br/></div>
                <h2>Redi Reservation</h2>


                <p>By default DEMO key is provided, so you can test reservation functionality without registration.</p>
                <p>To optain you own API Key please register at <a href="http://www.reservationdiary.eu/ProviderHome/Register.aspx" target="_blank">Reservation Diary</a><br/>
                    After registration, you will receive API Key by email. Copy API Key into Redi Api Key field and click on Save Changes button.</p><br />
					<div style="border-bottom-left-radius: 3px;
    border-radius: 5px;
    border-style: solid;
    border-width: 1px;
  background-color: #FFFBCC;
    border-color: #E6DB55;
    color: #555555;
 padding:5px">
					<b>We need your feeback!</b> Please feel free to contact us at <a href="mailto:info@reservationdiary.eu">info@reservationdiary.eu</a> and provide us what you need.
					</div>
<form method="post" id="wp_paginate_options">

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">API Key</th>
                            <td><input name="key" type="text" id="key" size="40" value="<?php echo stripslashes(htmlspecialchars(trim($this->options['key']))); ?>"/>
                                <span class="description">API Key to access reservation functionality</span></td>
                        </tr>

                    </table>
                    <p class="submit">
                        <input type="submit" value="Save Changes" name="wp_redi_save" class="button-primary" />
                    </p>
                </form>

            </div>

            <?php
        }

    }

}
$reDiReservation = new ReDiReservation();
