<?php
/*
Plugin Name: Recurly Plugin
Plugin URI: http://www.logiccoding.com/software-products/wordpress-recurly-plans
Description: A recurly plugin that shows all recurly plans on front end.
             This has simple admin page for API setup
Author: Meet Thosar
Author URI:http://www.logiccoding.com/
Version: 1.1
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

/*
GENERAL NOTES

 * PHP short tags ( e.g. <?= ?> ) are not used as per the advice from PHP.net
 * No database implementation
 * IMPORTANT: Menu is visible to anyone who has 'read' capability, so that means subscribers
              See: http://codex.wordpress.org/Roles_and_Capabilities for information on appropriate settings for different users

*/

// Make sure that no info is exposed if file is called directly -- Idea taken from Akismet plugin
if ( !function_exists( 'add_action' ) ) {
	echo "This page cannot be called directly.";
	exit;
}

// Define some useful constants that can be used by functions
if ( ! defined( 'WP_CONTENT_URL' ) ) {	
	if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
	define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
}
if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( basename(dirname(__FILE__)) == 'plugins' )
	define("RECURLY_DIR",'');
else define("RECURLY_DIR" , basename(dirname(__FILE__)) . '/');
define("RECURLY_PATH", WP_PLUGIN_URL . "/" . RECURLY_DIR);

/* Add new menu */
add_action('admin_menu', 'recurly_add_pages');
// http://codex.wordpress.org/Function_Reference/add_action

// Create table on creation//
$db_name = $wpdb->prefix . 'recur_settings';
 
// function to create the DB / Options / Defaults					
function recurly_plugin_options_install() {
   	global $wpdb;
  	//global $db_name;
 	$db_name = $wpdb->prefix . 'recur_settings';
	// create the ECPT metabox database table
	if($wpdb->get_var("show tables like '$db_name'") != $db_name) 
	{
		$sql = "CREATE TABLE " . $db_name . " (
		`id` int(9) NOT NULL AUTO_INCREMENT,
		`subdomain` varchar(200) NOT NULL,
		`api_key` varchar(300) NOT NULL,
		`pri_key` varchar(300) NOT NULL,
		
		UNIQUE KEY id (id)
		);";
 
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
 
}
// run the install scripts upon plugin activation
register_activation_hook(__FILE__,'recurly_plugin_options_install');
/*

******** BEGIN PLUGIN FUNCTIONS ********

*/


// function for: 
function recurly_add_pages() {

  // anyone can see the menu for the Recurly Plugin
  add_menu_page('Recurly Overview','Recurly Plugin', 'read', 'recurly_overview', 'recurly_overview', RECURLY_PATH.'images/b_status.png');
  // http://codex.wordpress.org/Function_Reference/add_menu_page

  // this is just a brief introduction
  add_submenu_page('recurly_overview', 'Overview for the Recurly Plugin', 'Overview', 'read', 'recurly_overview', 'recurly_intro');
  // http://codex.wordpress.org/Function_Reference/add_submenu_page

}

function add_query_vars_filter( $vars ){
  $vars[] = "plan_code";
  return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

add_shortcode('plans', 'plans_shortcode');

function plans_shortcode($atts) {
	extract( shortcode_atts( array(
		'type' => 'all',
		'codes' => '',
	), $atts ) );
	
	$plan_code = get_query_var('plan_code');
	
	if($plan_code != '') {
		show_subscriptionform($plan_code);
	}else {
		echo do_shortcode(get_plans($type, $codes));	
	}	
}

function get_plans($type, $codes='') {
	global $wpdb;
	$rs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix . "recur_settings LIMIT 1") ;
	if(!empty($rs)){
		foreach($rs as $set) {
			$subdo = $set->subdomain;
			$api_key = $set->api_key;
			$pri_key = $set->pri_key;
		}
		require_once('lib/recurly.php');
 
		// Required for the API
		Recurly_Client::$subdomain = $subdo;
		Recurly_Client::$apiKey = $api_key;
		Recurly_js::$privateKey = $pri_key;
		 
		$recurObj = new Recurly_PlanList;
		$plans = $recurObj->getPlanXML();	
		$xml = new SimpleXMLElement($plans->body);
		//echo '<pre>';print_r($xml);exit;
		$plans = json_decode(json_encode((array) $xml),true);
		$plans = $plans['plan'];
		//echo '<pre>';print_r($plans);exit;
		$plansArr = array();
		$codeArr = array();
		if($codes != '') {
			$codeArr = explode(',',$codes);
		}
		
		if(is_array($plans[0])) {
			foreach($plans as $plan) { 
				if(($type == 'days' && $plan['plan_interval_unit'] == 'days') 
				|| ($type == 'months' && $plan['plan_interval_unit'] == 'months' && $plan['plan_interval_length'] < 12)
				|| ($type == 'yearly' && $plan['plan_interval_unit'] == 'months' && $plan['plan_interval_length'] >= 12)
				|| ($type == 'all')) {
					
				if($codes == '' || in_array($plan['plan_code'],$codeArr)) {
						$plansArr[] = array(
						'plan_code' => $plan['plan_code'],
						'name' => $plan['name'],
						'description' => $plan['description'],
						'plan_interval_length' => $plan['plan_interval_length'],
						'plan_interval_unit' => $plan['plan_interval_unit'],
						'unit_amount_in_cents' => $plan['unit_amount_in_cents']['USD']/100,
						'setup_fee_in_cents' => $plan['setup_fee_in_cents']['USD'],
						);	
					} 
				}
				
			}
		}else {
		if(($type == 'days' && $plans['plan_interval_unit'] == 'days') 
				|| ($type == 'months' && $plans['plan_interval_unit'] == 'months' && $plans['plan_interval_length'] < 12)
				|| ($type == 'yearly' && $plans['plan_interval_unit'] == 'months' && $plans['plan_interval_length'] >= 12)
				|| ($type == 'all')) {
				if($codes == '' || in_array($plan['plan_code'],$codeArr)) {
					$plansArr[] = array(
					'plan_code' => $plans['plan_code'],
					'name' => $plans['name'],
					'description' => $plans['description'],
					'plan_interval_length' => $plans['plan_interval_length'],
					'plan_interval_unit' => $plans['plan_interval_unit'],
					'unit_amount_in_cents' => $plans['unit_amount_in_cents']['USD']/100,
					'setup_fee_in_cents' => $plans['setup_fee_in_cents']['USD'],
					);	
				}
			}
		}
			
		
		$i=0;
		$num_plans = count($plansArr);
		$shortcode = "<style>
		.main_div{width:100%;} 
		.sub_div{width:24%;float:left;text-align:center;color:#FFF;}
		.heading{width:100%;background-color:#25262C;padding:14px 0; border-radius: 7px 7px 0 0;}
		.bottom{width:100%;background-color:#25262C;padding:3px 0; border-radius: 0 0 7px 7px;}
		.row_red{background-color:#F45F51;padding:14px 0;}
		.row_grey{background-color:#A9B4BA;padding:14px 0;}
		.sub_div a{font-size:1em;color:#FFF;}
		</style>";
		
		$shortcode .= "<div class='main_div'>";
		$shortcode .= "<div class='heading'>";
		$shortcode .= "<div class='sub_div'>Title</div>";
		$shortcode .= "<div class='sub_div'>Price</div>";
		$shortcode .= "<div class='sub_div'>Description</div>";
		$shortcode .= "<div class='sub_div'>Link</div>";
		$shortcode .= "<div style='clear:both;'></div>";
		$shortcode .= "</div>";
		$i = 0;
		foreach($plansArr as $plan) {
			if($i%2 == 0) $cls = "row_red";else $cls = "row_grey";
			
			$shortcode .= "<div class='".$cls."'>";
			$shortcode .= "<div class='sub_div'>".$plan['name']."</div>";
			$shortcode .= "<div class='sub_div'>$".$plan['unit_amount_in_cents']."</div>";
			$shortcode .= "<div class='sub_div'>for ".$plan['plan_interval_length'].' '.$plan['plan_interval_unit']."</div>";
			$shortcode .= "<div class='sub_div'><a href='".get_page_link();
			if(strpos(get_page_link(), '?'))
			$shortcode .= "&plan_code=".$plan['plan_code']."'><strong>Choose</strong></a></div>";
			else 
			$shortcode .= "?plan_code=".$plan['plan_code']."'><strong>Choose</strong></a></div>";
			
			$shortcode .= "<div style='clear:both;'></div>";
			$shortcode .= "</div>";
			$i++;	
			}
		$shortcode .= "<div class='bottom'></div>";	
		$shortcode .= "</div>";
		return $shortcode;
		//return $plansArr;		
	}else {
		return 'No Plans found';
	}	
}

function show_subscriptionform($plan_code) { 
	global $wpdb;
	$rs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix . "recur_settings LIMIT 1") ;
	if(!empty($rs)){
		foreach($rs as $set) {
			$subdo = $set->subdomain;
			$api_key = $set->api_key;
			$pri_key = $set->pri_key;
		}
		require_once('lib/recurly.php');
		 
		// Required for the API
		Recurly_Client::$subdomain = $subdo;
		Recurly_Client::$apiKey = $api_key;
		Recurly_js::$privateKey = $pri_key;
		 
		$signature = Recurly_js::sign(array(
		  'account'=>array(
		    'account_code'=>'my-account-code'
		  ),
		  'subscription' => array(
		    'plan_code' => $plan_code,
		  )
		));
		echo '<div id="recurly-form"></div>';
		?>
			<link href="<?php echo site_url();?>/wp-content/plugins/recurly-plans/recurlyjs/themes/default/recurly.css" rel="stylesheet">
		    <script src="<?php echo site_url();?>/wp-content/plugins/recurly-plans/recurlyjs/js/recurly.js"></script>
		    <script>
		    jQuery(document).ready(function($){
		      Recurly.config({
		        subdomain: '<?php echo $subdo;?>'
		        , currency: 'USD' // GBP | CAD | EUR, etc...
		      });

		      Recurly.buildSubscriptionForm({
		        target: '#recurly-form',
		        planCode: '<?php echo $plan_code;?>',
		        successURL: '<?php echo get_permalink();?>',
		        signature: '<?php echo $signature;?>',
		      });

		    });
		    </script>
		<?php
	}else {
		echo 'No Plan Found';
	}
}

function recurly_overview() {
	global $wpdb;
	if(isset($_POST['saveSet'])) {
		$subd = $_POST['subdomain'];
		$api_key = $_POST['api_key'];
		$pri_key = $_POST['pri_key'];
		
		$rs = $wpdb->get_results("SELECT id FROM ".$wpdb->prefix . "recur_settings LIMIT 1") ;
		if(empty($rs)) {
			$sql = "INSERT INTO ".$wpdb->prefix . "recur_settings (subdomain,api_key,pri_key) 
					VALUES('".$subd."','".$api_key."','".$pri_key."')";
		}else {
			$sql = "UPDATE ".$wpdb->prefix . "recur_settings SET subdomain = '".$subd."',
					api_key = '".$api_key."',pri_key = '".$pri_key."'";
		}
		$insrtRs = $wpdb->query($sql);
	}
	$rs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix . "recur_settings LIMIT 1") ;
	if(!empty($rs)){
		foreach($rs as $set) {
		$subdomain = $set->subdomain;
		$api_key = $set->api_key;
		$pri_key = $set->pri_key;
		}
	}
?>
<div class="wrap"><h2>Recurly Plugin Settings</h2>
	<form method="POST" id="recurly_set" name="recurly_set">
		<p>
			<label>Recurly Subdomain</label><br />
			<input type="text" value="<?php echo $subdomain; ?>" name="subdomain" size="40" />
		</p>
		<p>
			<label>API Key</label><br />
			<input type="text" value="<?php echo $api_key; ?>" name="api_key" size="40" />
		</p>
		<p>
			<label>Private Key</label><br />
			<input type="text" value="<?php echo $pri_key; ?>" name="pri_key" size="40" />
		</p>
		<p>
			<input type="submit" name="saveSet" id="saveSet" value="Update"/>
		</p>
	</form>
</div>

<?php
exit;
}

?>