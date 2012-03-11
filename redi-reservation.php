<?php
/*
  Plugin Name: ReDi Reservation
  Plugin URI: http://reservation.eu/eng/reservation-wordpress-plugin/
  Description: ReDi Reservation plugin allows you to manage reservations for your business. This plugin can help places suuch restaurnats, bars, saunas, foto studios, billiards, bowlings and so on to receive reservations from clients online. Your clients will be able to see available space at specified time, and if it's available, client is able to make a reservation.
  Version: 0.1
  Author: Aleksei Prokopov
  Author URI: http://reservation.eu/

 */

define("REDIAPI", "http://provider.reservationdiary.eu/eng/api/");
define('REDI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REDI_DEBUG', true);

if (!class_exists('ReDiReservation'))
{

    class ReDiReservation
    {

        var $version = '0.0.1';

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
                    'key' => '',
                );
                update_option($this->optionsName, $options);
            }
            $this->options = $options;
        }

        /**
         * Include scripts used by Watermark RELOADED
         */
        public function content()
        {
            $content = '<form name="redi" method="post">';
            if ($_POST['submit'])
            {

                if ($_POST['Name'] != "" && $_POST['Phone'] != "" && $_POST['Email'] != "")
                {

                    $services = '';
                    foreach ((array) $_POST['service'] as $service)
                    {
                        $services .='&id=' . $service;
                    }

                    if (!(isset($_SESSION['redi_data_send']) && $_SESSION['redi_data_send'] + 30/* TODO change to 5 min */ > time()))
                    {
                        $_SESSION['redi_data_send'] = time();

                        $content.= $this->get(
                                'reserve', '&name=' . urlencode($_POST['Name']) .
                                '&phone=' . urlencode($_POST['Phone']) .
                                '&email=' . urlencode($_POST['Email']) .
                                $services .
                                '&startDate=' . urlencode($_POST['startDate'] . ' ' . $_POST['startTime']) .
                                '&endDate=' . urlencode($_POST['endDate'] . ' ' . $_POST['endTime'])
                        );
                    }
                    else
                        $content.= '<b style="color:red">Data allready send.</b>';
                } else
                {
                    $content.= '<b style="color:red">Not all required fields are set.</b>';
                }
                $content.='<br/>';
            }

            date_default_timezone_set('Europe/Minsk');

            $places = $this->get('places');
            $content .= $this->getplaces($places);
            $content .= '<div id="category_div">';
            $first_place = $places[0]->ID;
            $categories = $this->get('categories/' . $first_place);
            $content .= $this->getcategories($categories);

            $startDate = date('Y-m-d', strtotime('+48 hour'));
            $startTime = date('G:i');

            $endDate = date('Y-m-d', strtotime('+48 hours 30 minutes'));
            $endTime = date('G:i', strtotime('+30 minutes'));

            $first_category = $categories[0]->ID;
            $services = $this->get(
                    'services/' . $first_category, '&startDate=' . urlencode($startDate . ' ' . $startTime) .
                    '&endDate=' . urlencode($endDate . ' ' . $endTime)
            );

            $content .= '</div>';
            $content .= '<br/><input type="text" value="' . $startDate . '" name="startDate" id="startDate"/> <input id="startTime" type="text" value="' . $startTime . '" name="startTime"/><br/>';
            $content .= '<input type="text" value="' . $endDate . '" name="endDate" id="endDate"/> <input id="endTime" type="text" value="' . $endTime . '" name="endTime"/>';
            $content .= $this->getservices($services);
            $content .= $this->user_info_form();
            return $content . '</form>';
        }

        public function user_info_form()
        {
            //phone
            $content = '<div>
            <div>
                <label for="Name">Name</label> <span class="required">*</span><br>
                <input type="text" value="" name="Name" id="Name">
                <span id="Name_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Phone">Phone</label> <span class="required">*</span><br>
                <input type="text" value="" name="Phone" id="Phone">
                <span id="Phone_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Email">Email</label> <span class="required">*</span><br>
                <input type="text" value="" name="Email" id="Email">
                <span id="Email_validationMessage" class="field-validation-valid"></span>
            </div>
            <div>
                <label for="Comments">Comment</label><br>
                <textarea rows="2" name="Comments" id="Comments" cols="20"></textarea>
            </div>
            <div style="display: none;">
                <input type="submit" id="Action" name="Action" value="book">
            </div>
            <div style="margin-top: 30px; margin-bottom: 30px;">
                    <input id="submit" type="submit" value="make a reservation" name="submit">
            </div>
        </div>';
            return $content;
        }

        public function getservices($services)
        {
            $content = '<div id="services_div"><table>';
            foreach ((array) $services as $service)
            {
                $content .='<tr class="service_status_' . $service->Status . '">' .
                        '<td><input type="checkbox" ' . ($service->Status = 'service_status_NON_WORKING_TIME' ? 'disabled="disabled"' : '') . ' name="service[]" value="' . $service->ID . '" /></td>' .
                        '<td>' . $service->Name . '</td><td>' . $service->Comments . '</td></tr>';
            }
            $content .= '</table></div>';

            return $content;
        }

        public function getcategories($categories)
        {
            $content = '<select id="category" name="category" class="category">';
            foreach ((array) $categories as $category)
            {
                $content .='<option value="' . $category->ID . '">' . $category->Name . '</option>';
            }
            $content.='</select>';

            return $content;
        }

        public function get($func, $params="")
        {
            $url = REDIAPI . $func . '?apikey=' . $this->options['key'] . $params;

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
                    echo $url . '<br/>';
                    echo $e->getMessage();
                }
            }
            restore_error_handler();

            if ($json)
                return json_decode($json);
        }

        public function put($func, $params)
        {
            $url = REDIAPI . $func . '?apikey=' . $this->options['key'] . $params;

            $json = @file_get_contents($url, 0, null, null);
            if ($json)
                return json_decode($json);
        }

        public function getplaces($places)
        {
            $content = '<select name="place" id="place">';
            foreach ((array) $places as $place)
            {
                $content .='<option value="' . $place->ID . '">' . $place->Name . '</option>';
            }
            $content.='</select>';

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
            wp_register_style('redistyle', REDI_PLUGIN_URL . 'redi.css');

            wp_enqueue_style('redistyle');
            wp_register_script('redi', REDI_PLUGIN_URL . 'redi.js', array('jquery'));
            wp_enqueue_script('redi');
            wp_enqueue_style('jquery-ui-datepicker', get_bloginfo('template_directory') . '/jquery-ui-datepicker/jquery-ui-1.8.16.custom.min.js', array('jquery'));

            wp_localize_script('redi', 'MyAjax', array(
                // URL to wp-admin/admin-ajax.php to process the request
                'ajaxurl' => admin_url('admin-ajax.php')
                    )
            );
//            wp_register_style('timepicker-addon-style', REDI_PLUGIN_URL . '/lib/datetimepicker/css/jquery-ui-timepicker-addon.css');
//            wp_enqueue_style('timepicker-addon-style');
            //           wp_register_script('timepicker-addon-js', REDI_PLUGIN_URL . 'lib/datetimepicker/js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-slider'));
            //           wp_enqueue_script('timepicker-addon-js');

            wp_register_style('jquery_ui', plugins_url('styles/jquery-ui-1.8.2.custom.css', __FILE__));
            wp_enqueue_style('jquery_ui');
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

        function admin_init()
        {

            //add_menu_page("Redi Reservation", "Redi Reservation", null, "redi_orders", 'redi_options', PLUGIN_URL . "/img/ico16x16.png");
            //add_submenu_page("redi_orders", "Settings", "Setinngs", null, "redi_settings", 'redi_options');
        }

        function redi_options()
        {
            echo '<div class="wrap">';

            echo '</div>';
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
                    echo $this->getcategories($this->get('categories/' . intval($_POST['place_id'])));
                    break;

                case 'services':
                    $category_id = $_POST['category_id'];
                    $startDate = $_POST['startDate'];
                    $startTime = $_POST['startTime'];

                    $endDate = $_POST['endDate'];
                    $endTime = $_POST['endTime'];
                    $services = $this->get(
                            'services/' . $category_id, '&startDate=' . urlencode($startDate . ' ' . $startTime) .
                            '&endDate=' . urlencode($endDate . ' ' . $endTime)
                    );
                    echo $this->getservices($services);
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

                $this->options['key'] = $_POST['key'];

                $this->save_admin_options();

                echo '<div class="updated"><p>' . __('Success! Your changes were successfully saved!', $this->localizationDomain) . '</p></div>';
            }
            ?>

            <div class="wrap">
                <div class="icon32" id="icon-options-general"><br/></div>
                <h2>Redi Reservation</h2>

                <p>To optain API key plase register at <a href="http://www.reservationdiary.eu/ProviderHome/Register.aspx">http://www.reservationdiary.eu/ProviderHome/Register.aspx</a></p>
                <form method="post" id="wp_paginate_options">

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Redi Api KEY</th>
                            <td><input name="key" type="text" id="key" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['key'])); ?>"/>
                                <span class="description">API key</span></td>
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