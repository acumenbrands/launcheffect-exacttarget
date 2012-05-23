<?php
/*
Plugin Name: Integrate Launch Effect & ExactTarget
Plugin URI: http://acumenholdings.com/
Description: Integrates LE and ET and makes them work together. I call it LEET. Yeah, I call it that.
Version: 0.2
Author: Brian Sage
Author URI: http://twitter.com/briansage
*/


if (!class_exists("LEET")) {
  class LEET {

    function LEET() { // constructor
    }

    function plugin_loaded_action() {
      if ($_SERVER['REQUEST_METHOD'] == 'POST' && !filter_var($email, FILTER_VALIDATE_EMAIL)):
        global $LEET;
        global $_POST;
        extract($_POST);

        $et_MID = (get_option('LEET_exacttarget_mid') ? get_option('LEET_exacttarget_mid') : '10404059');
        $et_LID = (get_option('LEET_exacttarget_lid') ? get_option('LEET_exacttarget_lid') : '29998414');

        $et_URL = 'http://cl.exct.net/subscribe.aspx?lid='.$et_LID.'&';
        $et_date = date('m-j-y');
        $et_post_arr = array(
          'SubAction'     => 'sub_add_update',
          'thx'           => 'http://countryoutfitter.com/',
          'err'           => 'http://countryoutfitter.com/500',
          'type'          => 'HTML',
          'MID'           => $et_MID,
          'submit_date'   => $et_date,
          'Email Address' => $email
        );

        // url-encode the data
        $et_get_str = $et_URL;
        foreach($et_post_arr as $post_key=>$post_value):
          $et_get_str .= urlencode($post_key).'='.urlencode($post_value).'&';
        endforeach;
        rtrim($et_get_str,'&');

        if (function_exists('curl_init')):
          // open connection
          //echo '<pre>$et_get_str == '.$et_get_str.'</pre>';
          $ch = curl_init();

          // Set cURL params
          curl_setopt($ch, CURLOPT_URL, $et_get_str);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

          // execute post
          $et_result = curl_exec($ch); //echo '<pre>$et_result == '.$et_result.'</pre>';

          // close connection
          curl_close($ch);
        else:
          die('ERROR: cURL not installed!');
        endif;

      endif;
    }



    function LEET_admin_menu () {
      global $LEET;
      if ( count($_POST) > 0 && isset($_POST['LEET_settings'])):
          
        // Setup ExactTarget Settings Form
        
        $options = array(
          'exacttarget_lid',
          'exacttarget_mid'
        );
        foreach ( $options as $opt ){
          delete_option ( 'LEET_'.$opt, $_POST[$opt] );
          add_option ( 'LEET_'.$opt, $_POST[$opt] );  
        }

      endif;
      add_menu_page('ExactTarget Settings', 'ExactTarget', 'manage_options', 'leet_settings', null, plugins_url('et_favicon.png', __FILE__));
      add_submenu_page('leet_settings', 'ExactTarget Settings', 'ExactTarget Settings', 'manage_options', 'leet_settings', array(&$LEET,'LEET_admin_settings'));

    }

    function LEET_admin_settings() {
    ?>

    <div class="wrap">
      <h2>Launch Effect ExactTarget Settings</h2>
      
      <form method="post" action="">

        <div id="col-container">
          <div id="col-left">
            <div class="col-wrap">
              <div class="form-wrap">

                
                <hr>
                
                <h3>ExactTarget Campaign</h3>
                
                <div class="form-field">
                  <label for="exacttarget_mid">MID</label>
                  <input name="exacttarget_mid" type="text" id="exacttarget_mid" value="<?php echo get_option('LEET_exacttarget_mid'); ?>" />
                  <p>Enter the ExactTarget <b>Business Unit ID</b>.</p>
                  <p>Usually, an 8-digit number in peranthesis, next to &quot;Welcome, Company Name&quot; in the upper-right of the dashboard.</p>
                </div>

                <div class="form-field">
                  <label for="exacttarget_lid">LID</label>
                  <input name="exacttarget_lid" type="text" id="exacttarget_lid" value="<?php echo get_option('LEET_exacttarget_lid'); ?>" />
                  <p>Enter the ExactTarget <b>List ID</b>.</p>
                </div>

                <hr>                
                
                <p class="submit">
                  <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
                  <input type="hidden" name="LEET_settings" value="save" style="display:none;" />
                </p>
            
              </div>
            </div>
          </div>
        </div>
        
      </form>
    </div>
          
          
    <?php
    }

    function LEET_warning() {
      echo "
      <div id='LEET-warning' class='updated fade'><p><strong>".__('ExactTarget Settings are almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter an ExactTarget MID and LID</a> for it to work.'), "admin.php?page=leet_settings")."</p></div>
      ";
    }

  }
}




if (class_exists("LEET")) {
  $LEET = new LEET();
}

if (isset($LEET)) :
  add_action( 'plugins_loaded', array(&$LEET,'plugin_loaded_action') );
  add_action( 'admin_menu', array(&$LEET,'LEET_admin_menu') );
  
  // Warnings
  if (!get_option('LEET_exacttarget_mid') && !get_option('LEET_exacttarget_lid') && !isset($_POST['submit'])):
    add_action('admin_notices', array(&$LEET,'LEET_warning') );
  ENDIF;

endif;
