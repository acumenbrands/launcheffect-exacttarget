<?php
/*
Plugin Name: Integrate Launch Effect & ExactTarget
Plugin URI: http://acumenholdings.com/
Description: Integrates LE and ET and makes them work together. I call it LEET. Yeah, I call it that.
Version: 0.3.1
Author: Brian Sage
Author URI: http://twitter.com/briansage


Changelog:

v0.3
 - Made LEET a little smarter, as to restrict email subscriptions for just ANY post.
 - Played keep-away from admin, wp-includes, and user uploads.
 - Added a few custom styles for content we like to use.

v0.2
 - Made it work by intercepting $_POST. Publicly accessible Github repo created.

v0.1
 - Tried using AJAX. IE sucks.

*/



if (!class_exists("LEET")) {
  class LEET {

    function LEET() { // constructor
    }

    function plugin_loaded_action() {
      if (
        ($_SERVER['REQUEST_METHOD'] == 'POST') &&
        !strstr($_SERVER['HTTP_REFERER'], '/wp-admin') && // No admin stuff
        !strstr($_SERVER['REQUEST_URI'], '/wp-includes') && // No core stuff
        !strstr($_SERVER['REQUEST_URI'], '/upload') // No user-uploaded stuff
        ):
        //print_r($_SERVER);

        // Submit to something like Launch Effect
        if (
          strstr($_SERVER['REQUEST_URI'], '/wp-content') &&
          strstr($_SERVER['REQUEST_URI'], '/themes') &&
          strstr($_SERVER['REQUEST_URI'], '/post.php')
          ):
          $is_launcheffect = true;
        endif;

        extract($_POST);

        if($is_launcheffect):
          global $LEET;
          global $_POST;

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
          foreach($_POST as $post_key=>$post_value):
            $et_get_str .= urlencode($post_key).'='.urlencode($post_value).'&';
          endforeach;
          rtrim($et_get_str,'&');

          if (function_exists('curl_init') && ($is_launcheffect)):
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

        else:
          die('ERROR: Launch Effect not installed!');
        endif;;

      endif;
    }


    function wp_head_action(){
      $output = <<<HTML
        <style type="text/css">
        .referral-email-fields li {
          clear: both;
          margin-bottom: 6px;
        }
        input.styled-submit-button,
        .css3button {
          font-family: Arial, Helvetica, sans-serif;
          font-size: 14px;
          cursor:pointer;
          color: #222;
          height: 40px;
          width: 80px;
          background-color:#ffd942;
          filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd942', endColorstr='#c26823');
          background: -moz-linear-gradient(
            top,
            #ffd942 0%,
            #c26823);
          background: -webkit-gradient(
            linear, left top, left bottom, 
            from(#ffd942),
            to(#c26823));
          -moz-border-radius: 10px;
          -webkit-border-radius: 10px;
          border-radius: 10px;
          border: 1px solid #8c590e;
          -moz-box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          -webkit-box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          text-shadow:
            0px -1px 0px rgba(000,000,000,0.4),
            0px 1px 0px rgba(255,255,255,0.3);
          float:left;
          margin: -2px 0 -2px 10px;
        }
        #signup a.css3button {
          float: none;
          margin: 0 auto;
          padding: 10px 30px;
          height: auto;
          width: auto;
          color: #000 !important;
        }

        #success-content {
          text-align: center;
        }
        .social-container {
          margin-bottom: 10px;
        }
        </style>
HTML;
      echo $output;
    }


    function wp_footer_action(){
      $output = "";
      $output .= <<<HTML
        <script type="text/javascript">
          jQuery('#form').submit(function(){
            var pv = (window.location.pathname + "/success/").replace('//','/');
            //alert('track '+pageView);
            try{
              _gaq.push(['_trackPageview', pv ]);
            } catch(err) {};
          });
        </script>
HTML;

      echo $output;
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
      echo "<div id='LEET-warning' class='updated fade'><p><strong>ExactTarget Settings are almost ready.</strong> ".sprintf('You must <a href="%1$s">enter an ExactTarget MID and LID</a> for it to work.', "admin.php?page=leet_settings")."</p></div>";
    }

    function LEET_curl_warning() {
      echo "<div id='LEET-curl-warning' class='updated fade'><p><strong>cURL PHP is not installed on this server. ExactTarget will not work without cURL library installed.</strong></p></div>";
    }

  }
}




if (class_exists("LEET")) {
  $LEET = new LEET();
}

if (isset($LEET)) :
  add_action( 'plugins_loaded', array(&$LEET,'plugin_loaded_action') );
  add_action( 'wp_head', array(&$LEET,'wp_head_action') );
  add_action( 'wp_footer', array(&$LEET,'wp_footer_action') );
  add_action( 'admin_menu', array(&$LEET,'LEET_admin_menu') );
  
  // Warnings
  if (!get_option('LEET_exacttarget_mid') && !get_option('LEET_exacttarget_lid') && !isset($_POST['submit'])):
    add_action('admin_notices', array(&$LEET,'LEET_warning') );
  endif;

  if (!function_exists('curl_init')):
    add_action('admin_notices', array(&$LEET,'LEET_curl_warning') );
  endif;

endif;
