<?php
/*
Plugin Name: Integrate Launch Effect & ExactTarget
Plugin URI: http://acumenholdings.com/
Description: Integrates LE and ET and makes them work together. I call it LEET. Yeah, I call it that.
Version: 0.4
Author: Brian Sage
Author URI: http://twitter.com/briansage


Changelog:

v0.4
 - Added multi-email referral email form to success page.
 - IE still sucks. Balls. 

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
          global $wpdb;
          global $li_stats_table;

          /* // Unused
          $user_code = $code;
          // Check for returning user
          if(filter_var($email, FILTER_VALIDATE_EMAIL)) :
            $li_stats_table = $wpdb->prefix . "launcheffect";

            function fknQuery($query, $type) {
              global $wpdb;
              $result = $wpdb->$type( $query );
              return $result;
            }

            $count = fknQuery(fknQuery("SELECT COUNT(*) FROM $li_stats_table WHERE email = '$email'", 'prepare'), 'get_var');

            if ($count > 0) :
              $stats = fknQuery("SELECT * FROM $li_stats_table WHERE email = '$email' ORDER BY time DESC", 'get_results');
              foreach ($stats as $stat) {
                $user_code = $stat->code;
              }
            endif;
          endif;

          $et_post_arr = array(
            'user_code'      => $user_code
          );
          */

          if (function_exists('curl_init') && ($is_launcheffect)):

            function do_et_post($et_post_arr){
              $et_url = 'http://cl.exct.net/subscribe.aspx?';
              $et_date = date('m-j-y');
              $et_post_defaults = array(
                'SubAction'     => 'sub_add_update',
                'thx'           => 'http://countryoutfitter.com/',
                'err'           => 'http://countryoutfitter.com/500',
                'type'          => 'HTML',
                'submit_date'    => $et_date
              );
              $et_post_arr = array_merge($et_post_defaults, $et_post_arr);
              $et_get_str .= http_build_query($et_post_arr,'','&');
              //echo '<pre>$et_get_str == '.$et_url.$et_get_str."</pre>\n";

              // open connection
              $ch = curl_init();

              // Set cURL params
              curl_setopt($ch, CURLOPT_URL, $et_url.$et_get_str);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

              // execute post
              $et_result = curl_exec($ch);

              // close connection
              curl_close($ch);
            }

            
            // Submit a subscription
            if(!array_key_exists('is_referral', $_POST)):

              $et_MID = get_option('LEET_exacttarget_mid');
              $et_LID = get_option('LEET_exacttarget_lid');
              $post_arr = array(
                'lid'   => $et_LID,
                'mid'   => $et_MID
              );
              foreach($_POST as $post_key => $post_value):
                switch ($post_key){
                  case 'utm_campaign':
                  //case 'utm_content':
                  //case 'utm_medium':
                  //case 'utm_source':
                  //case 'code':
                    $post_arr[$post_key] = $post_value;
                    break;
                  case 'email':
                    $post_arr['Email Address'] = $post_value;
                    $post_arr['Subscriber Key'] = $post_value;
                    break;
                }
              endforeach;
              do_et_post($post_arr);


            // Submit a referral
            else:
              $return_arr = array();

              foreach($_POST as $post_key => $post_value):
                if (strpos($post_key, 'referral-email-') !== false && filter_var($post_value, FILTER_VALIDATE_EMAIL)):

                  $return_arr[$post_key] = $post_value;

                  $et_ref_MID = get_option('LEET_exacttarget_referral_mid');
                  $et_ref_LID = get_option('LEET_exacttarget_referral_lid');
                  $ref_post_arr = array(
                    'lid'            => $et_ref_LID,
                    'mid'            => $et_ref_MID,
                    'Email Address'  => $post_value,
                    'Subscriber Key' => 'nobody@acumenholdings.com'
                  );
                  foreach($_POST as $ref_post_key => $ref_post_value):
                    switch ($ref_post_key){
                      case 'utm_campaign':
                      //case 'utm_content':
                      //case 'utm_medium':
                      //case 'utm_source':
                      //case 'code':
                      case 'referred_by':
                        $ref_post_arr[$ref_post_key] = $ref_post_value;
                        break;
                    }
                  endforeach;
                  do_et_post($ref_post_arr);

                endif;
              endforeach;

            endif;

            if(array_key_exists('is_referral',$_POST) && $_POST['is_referral']=='1'):

              // If referral form, stop the presses.
              echo json_encode($return_arr);

              die('');
            endif;

          else:
            die('ERROR: cURL not installed!');
          endif;

        endif;

      endif;
    }


    function wp_head_action(){
      $output = <<<HTML
        <style type="text/css">
        .referral-email-fields li {
          clear: both;
          margin-bottom: 6px;
        }
        #signup a.css3button {
          float: none;
          margin: 0 auto;
          padding: 10px 30px;
          height: auto;
          width: auto;
          color: #000 !important;
        }
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

        #success-content {
          text-align: center;
        }
        #signup input#submit-button,
        #referral-submit-button {
          border: none;
          border-radius: 2px;
          width:auto;
          font-size: 1.1em;
          height:33px;
          margin:0;
          padding:0px 15px;
          color: #fff;
          font-weight: bold;
          text-transform: uppercase;
          cursor: pointer;
          box-shadow: none;
          -moz-box-shadow: none;
          -webkit-box-shadow: none;
          font-family: Helvetica, Arial, sans-serif !important;
          background-color:#f7af3b;
          filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd942', endColorstr='#c26823');
          background: -moz-linear-gradient(
            top,
            #ffd942 0%,
            #c26823);
          background: -webkit-gradient(
            linear, left top, left bottom, 
            from(#ffd942),
            to(#c26823));
          background: -webkit-linear-gradient(
            top,
            #ffd942 0%,
            #c26823);
          background: linear-gradient(
            top,
            #ffd942 0%,
            #c26823);
          -moz-box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          -webkit-box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          box-shadow:
            0px 1px 3px rgba(000,000,000,0.5),
            inset 0px 0px 2px rgba(255,255,255,1);
          font-family: Helvetica,Arial,sans-serif !important;
          font-size: 1.1em;
          font-weight: bold;
          height: 36px;
          text-transform: uppercase;
          text-shadow: 0 -1px 0 #666;
          width: auto;

          -moz-transition: background-color 0.2s linear;
          -webkit-transition: background-color 0.2s linear;
          -o-transition: background-color 0.2s linear;
          transition: background-color 0.2s linear 0s;
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
            // Track Analytics Goal
            var pv = ("countryoutfiters.com" + window.location.pathname + "/success/").replace('//','/');
            try{
              _gaq.push(['_trackPageview', pv ]);
            } catch(err) {};
          });

          jQuery('#success').submit(function(){
            // Track Analytics Goal
            var pv = ("countryoutfitters.com" + window.location.pathname + "/share/").replace('//','/');
            try{
              _gaq.push(['_trackPageview', pv ]);
            } catch(err) {};
          });
        </script>
HTML;

      if(get_option('LEET_exacttarget_referral_enabled') && (get_option('LEET_exacttarget_referral_lid') != get_option('LEET_exacttarget_lid'))):

        $et_ref_LID = get_option('LEET_exacttarget_referral_lid');
        $et_ref_MID = get_option('LEET_exacttarget_referral_mid');
        $et_ref_date = date('m-j-y');
        $template_url = get_bloginfo('template_url');

        $utm_campaign_output = (array_key_exists('utm_campaign',$_GET)) ? '<input type="hidden" name="utm_campaign" value="'.$_GET['utm_campaign'].'">' : '';

        $output .= <<<HTML
          <ul id="referral-form-layout">
            <li class="first">
              <label for="email" style="visibility: visible; ">SHARE WITH FRIENDS</label>
              <div class="clear"></div>
              {$utm_campaign_output}
              <input type="hidden" id="referral-referred_by" name="referred_by" value="undefined">
              <input type="hidden" id="referral-is_referral" name="is_referral" value="1">

              <div class="ajax_notices" style="display:none; height:42px; position:relative;">
                <p class="notice_submitting" style="display:none; position:absolute;">Sharing link... <img src="{$template_url}/im/ajax-loader.gif"></p>
                <p class="notice_success" style="display:none; position:absolute;">Link shared! <a href="http://www.countryoutfitter.com/codes/FBFAMILY10?redirect_to=/cowboy-boots&signup=new" style="text-decoration:underline;">Start shopping with 10% off now!</a></p>
              </div>

              <div style="float:left; width:316px;">
                <input type="text" id="referral-email-01" name="referral-email-01" style="width: 293px;" placeholder="Email 1 (required)" required="required" data-rel="referral-email">

                <div class="clear" style="height:6px;"></div>

                <input type="text" id="referral-email-02" name="referral-email-02" style="width: 293px;" placeholder="Email 2" data-rel="referral-email">

                <div class="clear" style="height:6px;"></div>

                <input type="text" id="referral-email-03" name="referral-email-03" style="width: 293px;" placeholder="Email 3" data-rel="referral-email">

                <div class="clear" style="height:6px;"></div>
              </div>

              <div style="float:left;">
                <input type="submit" name="submit" value="SUBMIT" id="referral-submit-button" style="width:100px;">
              </div>

              <div class="clear"></div>

            </li>
          </ul>
          <script type="text/javascript">
            jQuery('#referral-form-layout').appendTo('form#success');
          </script>

          <script type="text/javascript">
            var Searchmonger = {
              find: function(search_term,url_Str){
                var win_search, win_search_split;
                try {
                  var search_arr = (typeof url_Str == 'undefined') ? Searchmonger.parse(url_Str) : url_Str;
                  if (typeof search_arr[search_term] != 'undefined') {
                    return search_arr[search_term];
                  } else {
                    return '';
                  }
                } catch(err) {
                  if(typeof window.console != 'undefined')
                    console.log('blarp!');
                }
              },

              parse: function(url_Str) {
                var win_search, win_search_split;
                var search_assarr = {};
                try {
                  win_search = (typeof url_Str == 'undefined') ? window.location.search : url_Str;
                  if(typeof win_search.length != 'undefined')
                    win_search_split = win_search.replace('?','').split('&');
                  for (i=0; i<win_search_split.length; i++){
                    if(win_search_split[i].indexOf('=')>0)
                      search_assarr[ win_search_split[i].split('=')[0] ] = win_search_split[i].split('=')[1];
                  }
                  return search_assarr;
                } catch(err) {
                  if(typeof window.console != 'undefined')
                    console.log('blarp!');
                }
              }
            }

            // Handle referral form submit
            jQuery('form#success').submit(function(e){

              e.preventDefault();

              // Append referred_by email input
              jQuery(this).find('#referral-referred_by').val( jQuery('form#form input#email').val() );

              referral_post_data = jQuery("form#success").serialize();
              referral_post_url = jQuery('#templateURL').attr('value') + "/post.php";

              jQuery('form#success .ajax_notices .notice_success').fadeOut(0,function(){
                jQuery('form#success .ajax_notices').slideDown(function(){
                  jQuery('form#success .ajax_notices .notice_submitting').fadeIn(function(){
                    jQuery.ajax({
                      type: "POST",
                      url: referral_post_url,
                      cache: false,
                      data: referral_post_data,
                      dataType: "json",
                      success: function(data, textStatus, jqXHR){
                        jQuery('form#success .ajax_notices .notice_submitting').fadeOut(function(){
                          jQuery('form#success .ajax_notices .notice_success').fadeIn(function(){
                            jQuery('#referral-email-01,#referral-email-02,#referral-email-03').val('');
                            jQuery('#referral-email-01')[0].focus();
                          });
                        });
                      }
                    });
                  });
                });
              });

            });


            jQuery(function(){
              // Preserve campaign URL information in all forms
              var search_assarr = Searchmonger.parse();
              for(key in search_assarr) {
                jQuery('form').append('<input type="hidden" name="'+key+'" value="'+Searchmonger.find(key)+'" data-rel="extended">');
              }

              // Preserve whitelist campaign URL information in links
              for(key in search_assarr) {
                switch (key) {
                  case 'utm_campaign':
                  case 'utm_content':
                  case 'utm_medium':
                  case 'utm_source':
                    jQuery('a[href*="'+window.location.hostname.replace(/www\.|\.com|\-fb\.local|\.local/,'')+'"]').each(function(){
                      if(jQuery(this).attr('href').indexOf('?') == -1){
                        jQuery(this).attr('href', jQuery(this).attr('href')+'?');
                      }
                      jQuery(this).attr('href', jQuery(this).attr('href')+'&'+key+'='+Searchmonger.find(key));
                      // console.log(this);
                    });
                    break;
                }
              }
            });

          </script>
HTML;
      endif;

      echo $output;
    }


    function LEET_admin_menu () {
      global $LEET;
      if ( count($_POST) > 0 && isset($_POST['LEET_settings'])):
          
        // Setup ExactTarget Settings Form
        $options = array(
          'exacttarget_mid',
          'exacttarget_lid',
          'exacttarget_referral_enabled',
          'exacttarget_referral_mid',
          'exacttarget_referral_lid'
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
                
                <h3>ExactTarget <i>Signup</i> Campaign</h3>
                <p>These are the list IDs needed for the <strong>primary squeeze page signup action</strong>.</p>
                
                <div class="form-field">
                  <label for="exacttarget_mid">MID</label>
                  <input name="exacttarget_mid" type="text" id="exacttarget_mid" value="<?php echo get_option('LEET_exacttarget_mid'); ?>" />
                  <p>The ExactTarget <b>Business Unit ID</b>.</p>
                  <p>Usually, an 8-digit number in peranthesis, next to &quot;Welcome, Company Name&quot; in the upper-right of the dashboard.</p>
                </div>

                <div class="form-field">
                  <label for="exacttarget_lid">LID</label>
                  <input name="exacttarget_lid" type="text" id="exacttarget_lid" value="<?php echo get_option('LEET_exacttarget_lid'); ?>" />
                  <p>The ExactTarget <b>List ID</b>.</p>
                </div>

              </div>
            </div>
          </div>
        </div>

        <div id="col-container">
          <div id="col-left">
            <div class="col-wrap">
              <div class="form-wrap">
                
                <h3>ExactTarget <i>Referral</i> Campaign</h3>
                <p>Used to handle <strong>email referral lists. Not subscribers.</strong></p>
                
                <table class="form-table">
                  <tbody>
                  <tr>
                    <th scope="row">Referral Form</th>
                    <td><label for="exacttarget_referral_enabled"><input type="checkbox" name="exacttarget_referral_enabled" id="exacttarget_referral_enabled" value="1"<?php checked( 1 == get_option('LEET_exacttarget_referral_enabled' )); ?> class="tog"/> enable.</label></td>
                  </tr>
                  <tr>
                  </tbody>
                </table>

                <div class="form-field">
                  <label for="exacttarget_referral_mid">MID</label>
                  <input name="exacttarget_referral_mid" type="text" id="exacttarget_referral_mid" value="<?php echo get_option('LEET_exacttarget_referral_mid'); ?>" />
                  <p>The ExactTarget <b>Business Unit ID</b>.</p>
                  <p>May be the same as MID above.</p>
                </div>

                <div class="form-field">
                  <label for="exacttarget_referral_lid">LID</label>
                  <input name="exacttarget_referral_lid" type="text" id="exacttarget_referral_lid" value="<?php echo get_option('LEET_exacttarget_referral_lid'); ?>" />
                  <p>The ExactTarget <b>List ID</b>.</p>
                  <p><strong>Must be different</strong> from Subscriber LID above.</p>
                </div>
                
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
