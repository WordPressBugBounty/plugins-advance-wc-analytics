<?php
/* controlling view of Authentication settings*/
if ( !defined( 'ABSPATH' ) ) {
    die;
}
/* intiating variables */
$errors = '';
$manual_tracking = false;

/* getting settings values*/
if (!get_option('awca_auth_settings')){
	$awca_auth_settings = $defaults;
}else{
	$awca_auth_settings = get_option('awca_auth_settings');
}

/* storing Authentication settings */
if (isset($_POST['awca_auth_submit']) && wp_verify_nonce( $_POST['awca_nonce_header'], 'awca_auth_submit' )){
  $awca_auth_settings_save = AWCA_Settings::get_instance()->parse_awca_auth_settings($_POST['awca_auth_settings']);
  if (isset($awca_auth_settings_save['agreement'])) {
    if(!empty($awca_auth_settings_save['property_id'])|| !empty($awca_auth_settings_save['tracking_id'])) {
      if(isset($awca_auth_settings_save['property_id'])){
        if(strpos((string)$awca_auth_settings_save['property_id'],'G') !== false){
            unset($awca_auth_settings_save['tracking_id']);
            unset($awca_auth_settings_save['manual_tracking']);
            update_option('awca_auth_settings',$awca_auth_settings_save);
            echo '<script>
                jQuery(document).ready(function(){
                   M.toast({html:"'.__('Setting Saved!','awca-text').'", classes: "rounded teal", displayLength:4000});
                   setTimeout(function(){window.location.reload(1);}, 1000);
                });
            </script>';
            $awca_auth_settings = $awca_auth_settings_save;
        }else{
          if($awca_auth_settings_save){
            unset($awca_auth_settings_save['tracking_id']);
            unset($awca_auth_settings_save['manual_tracking']);
            update_option('awca_auth_settings',$awca_auth_settings_save);
            echo '<script>
                jQuery(document).ready(function(){
                   M.toast({html: "'.__('Setting Saved!','awca-text').'", classes:"rounded teal", displayLength:4000});
                   setTimeout(function(){window.location.reload(1);}, 1000);
                });
            </script>';
            $awca_auth_settings = $awca_auth_settings_save;
          }else {
            $errors .= __('Please complete Google Analytics Website Linking Process!','awca-text').'<br>';
            $awca_auth_settings = $awca_auth_settings_save;
          }
        }
      }else{
        $manual_tracking = true;
        if(isset($awca_auth_settings_save['manual_tracking'])){
          if(strpos((string)$awca_auth_settings_save['tracking_id'],'G') !== false){
              update_option('awca_auth_settings',$awca_auth_settings_save);
              echo '<script>
                      jQuery(document).ready(function(){
                        M.toast({html:"'.__('Setting Saved!','awca-text').'", classes: "rounded teal", displayLength:4000});
                        setTimeout(function(){window.location.reload(1);}, 1000);
                      });
                  </script>';
              $awca_auth_settings = $awca_auth_settings_save;
          }else{
            update_option('awca_auth_settings',$awca_auth_settings_save);
            echo '<script>
                      jQuery(document).ready(function(){
                        M.toast({html:"'.__('Setting Saved!','awca-text').'", classes: "rounded teal", displayLength:4000});
                        setTimeout(function(){window.location.reload(1);}, 1000);
                      });
                  </script>';
            $awca_auth_settings = $awca_auth_settings_save;
          }
        }else{
          $errors .= __('Please confirm using manual tracking or use auto connect facility','awca-text').'<br>';
          if(get_option('awca_auth_settings')){
            delete_option('awca_auth_settings');
            $awca_auth_settings = $awca_auth_settings_save;
          }
          $awca_auth_settings = $awca_auth_settings_save;
        }
    }
  }else {
      $errors .= __('Please complete Google Analytics Website Linking Process!','awca-text').'<br>';
      if(get_option('awca_auth_settings')){
        delete_option('awca_auth_settings');
        $awca_auth_settings = $awca_auth_settings_save;
        header('Refresh:0');
      }
      $awca_auth_settings = $awca_auth_settings_save;
    }
  }else {
      $errors .= __('Please agree with privacy policy and terms of service of plugin!','awca-text').'<br>';
      $awca_auth_settings = $awca_auth_settings_save;
  }
}
else{
  if((($awca_auth_settings['tracking_id']??false) || ($awca_auth_settings['manual_tracking']??false)) && empty($awca_auth_settings['property_id'])){
    $manual_tracking = true;
  }
  if(!empty($analytics_properties)){
    $manual_tracking = false;
  }
}
/* display error messages */
if (strlen($errors)> 0){
    echo '<script>
            jQuery(document).ready(function(){
               M.toast({html: '.__('Please correct following Errors:','awca-text').', classes: \'rounded red\', displayLength:6000});
               M.toast({html: '.$errors.', classes: \'rounded red\', displayLength:8000});
            });
        </script>';
}
/* creating Authentication forms */
?>
<div class="awca-col s12 awca-options">
  <form action="" method="POST">
		<div class="awca-col s12 top-mar">
      <div class="awca-col m6 s12">
        <?php
        $auth_settings = get_option('awca_auth_settings');
        $awca_refresh_token_fail = get_option('awca_refresh_token_fail');
        if($auth_settings && ($awca_refresh_token_fail != 'yes')){?>
          <h5 class="left zero-mar"><?php _e('Authentication', 'awca-text');?><span class="new badge green" data-badge-caption="<?php esc_attr_e('completed', 'awca-text');?>"></span></h5>
        <?php }elseif($auth_settings && ($awca_refresh_token_fail == 'yes')){ ?>
           <h5 class="left zero-mar"><?php _e('Authentication', 'awca-text'); ?><span class="new badge yellow" data-badge-caption="<?php esc_attr_e('Reverification Required', 'awca-text');?>"></span></h5>
         <?php }else{ ?>
           <h5 class="left zero-mar"><?php _e('Authentication', 'awca-text'); ?><span class="new badge red" data-badge-caption="<?php esc_attr_e('uncomplete', 'awca-text');?>"></span></h5>
         <?php }
         ?>
      </div>
      <div class="awca-col m6 s12">
        <?php if ( awca_fs()->is_not_paying() && !(awca_fs()->is_trial())) { ?>
          <a class="waves-effect waves-light btn right upgrade-btn" style="margin-left:15px" href="<?php echo  awca_fs()->get_upgrade_url(); ?>"><?php _e('Upgrade to Pro!', 'awca-text'); ?></a>
        <?php } ?>
        <a class="waves-effect waves-light btn right" href="https://advancedwcanalytics.com/documentation" target="_blank"><i class="material-icons left">book</i><?php _e('Documentation', 'awca-text'); ?></a>
      </div>
		</div>
		<div class="clearfix"></div>
		<div class="divider top-mar"></div>
    <div class="progress">
      <div class="indeterminate"></div>
    </div>
    <div class="awca-row">
      <div class="awca-col s12 center-align">
        <?php if(!isset($awca_auth_settings['tracking_id']) || empty($awca_auth_settings['tracking_id'])){
          $class_box = '';
        }else{
          $class_box ='off';
        }?>
        <p class="top-mar-30 <?php echo $class_box;?>">
          <div class="switch">
            <label>
              <b><?php _e('Auto Connect Method(Recommended)', 'awca-text'); ?></b>
              <input <?php if(!($awca_auth_settings['property_id']??false) && get_option('awca_access_token')){ echo 'disabled';} ?> id="awca_auth_settings[manual_tracking]" class="check_manual" name="awca_auth_settings[manual_tracking]" value="yes" type="checkbox" <?php checked(isset($awca_auth_settings['manual_tracking']) && $awca_auth_settings['manual_tracking']); ?> >
              <span class="lever"></span>
              <b><?php _e('Manual Connect Method', 'awca-text'); ?></b>
            </label>
          </div>
        </p>
      </div>
      <?php 
      $selector = ((!empty($analytics_g4_properties) && is_array($analytics_g4_properties))||(!empty($ga_properties) && is_array($ga_properties))); ?>
      <div id="auto" class="awca-col s12">
        <?php if(!($awca_auth_settings['property_id']??false) && get_option('awca_access_token')){ //&& !get_option('awca_auth_settings')){?>
          <div class="notice notice-info top-mar">
            <p><b><?php _e('Please choose your property which you want to link with website and hit submit button.', 'awca-text'); ?></b></p>
          </div>
        <?php }elseif(!get_option('awca_access_token')){?>
          <div class="input-field awca-col s12 center-align auto-connect">
            <div class="notice notice-info top-mar left-align">
            <b><p class="left-align"> <?php _e('Please complete following requirements then click "Link your Google Analytics" button.', 'awca-text'); ?></p></b>
              <ol>
                <li> <?php _e('Need active Google Analytics account. If you not have one ', 'awca-text'); ?><a href="https://marketingplatform.google.com/about/analytics/"><?php _e('please sign-up here.', 'awca-text'); ?></a></li>
                <li> <?php _e('Need to have at least one active property for current website in Google Analytics Account. ', 'awca-text'); ?></li>
              </ol>  
            </div>
          </div>
          <div class="awca-row auto-connect">
            <div class="input-field awca-col s12 center-align">
                <a style="text-transform: capitalize;font-weight:600" class="waves-effect waves-light white black-text btn AWCA-authenticate top-mar-30"><img width="20px" class="material-icons left" style="margin-top:7px; margin-right:10px" alt="Google sign-in" src="<?php echo AWCA_URL . 'assests/images/google-icon.png'; ?>" /><?php echo $selector? 'Re-Link with your Google account' : 'Link your Google Analytics' ; ?></a>
                <span class="helper-text top-mar-10" data-error="wrong" data-success="right"><?php _e('Please link your Google Analyitcs Account for data collection.', 'awca-text'); ?></span>
            </div>
          </div>
          <div class="input-field awca-col s12 center-align">
            <?php if(!($awca_auth_settings['property_id']??false) && !($awca_auth_settings['tracking_id']??false) && !get_option('awca_auth_settings')){?>
              <div class="notice notice-info top-mar manual-connect">
                <p><b> <?php _e('Please Enter your property-id / Data stream measurement id to link with website and hit submit button. ', 'awca-text'); ?></b></p>
              </div>
            <?php } ?>
            <div class="awca-row manual-connect">
              <?php if(isset($awca_auth_settings['tracking_id']) && !empty($awca_auth_settings['tracking_id'])){
                 echo '<h6>'.$awca_auth_settings['tracking_id'].'</h6>';?>
                 <input placeholder="G-XXXXXXXX" id="awca_auth_settings[tracking_id]" name="awca_auth_settings[tracking_id]" type="text" value="<?php if(isset($awca_auth_settings['tracking_id']) && !empty($awca_auth_settings['tracking_id'])) {echo $awca_auth_settings['tracking_id'];} ?>" class="tracking-id off">
                 <div class="input-field awca-col m12 s12">
                   <!--<a class="waves-effect waves-light btn modal-trigger" href="#modal1">Un-link Google Analyitcs from website.</a>-->
                 </div>
                 <div id="modal1" class="modal">
                   <div class="modal-content">
                     <h5><?php _e('Un-Link Google Analysis from website', 'awca-text'); ?></h5>
                     <p><?php _e('Are you sure you wish to un-link Google Analysis from Website?', 'awca-text'); ?></p>
                   </div>
                   <div class="modal-footer">
                     <a class="modal-close waves-effect waves-light btn AWCA-un-link"><?php _e('Un-Link Google Analytics', 'awca-text'); ?></a>
                     <a class="modal-close waves-effect waves-green btn"><?php _e('Cancel', 'awca-text'); ?></a>
                   </div>
                 </div>
               <?php }else{ ?>
              <div class="awca-col s12 center-align">
                <div class="top-mar-30">
                  <div class="input-field awca-col m4 offset-m4 s12">
                    <input placeholder="G-XXXXXXXX" id="awca_auth_settings[tracking_id]" name="awca_auth_settings[tracking_id]" type="text" value="<?php if(isset($awca_auth_settings['tracking_id']) && !empty($awca_auth_settings['tracking_id'])) {echo $awca_auth_settings['tracking_id'];} ?>" class="tracking-id">
                    <span class="helper-text top-mar-10" data-error="wrong" data-success="right"><?php _e('Please put your Google Analytics Propety Data stream measurement id from your GA4 here.', 'awca-text');?></span>
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>
        <?php }
          if($selector && (get_option('awca_access_token'))) { ?>
        <div class="awca-row auto-connect">
          <div class="awca-col l8 offset-l2">
            <div class="awca-col s12 center-align">
              <?php if(isset($awca_auth_settings['property_id']) && !empty($awca_auth_settings['property_id'])){ ?>
                <h6><?php
                $awca_show_tracking_id = explode( '|', $awca_auth_settings['property_id']);
                echo $awca_show_tracking_id[1]; ?></h6>
                <input id="awca_auth_settings[property_id]" name="awca_auth_settings[property_id]" type="text" value="<?php if(isset($awca_auth_settings['property_id']) && !empty($awca_auth_settings['property_id'])) {echo $awca_auth_settings['property_id'];} ?>" class="property-id off">
              <?php }else{ ?>
              <div class="input-field awca-col m12 s12">
                <?php
                if(isset($analytics_g4_properties) && !empty($analytics_g4_properties)&& is_array($analytics_g4_properties)){
                  foreach ( $analytics_g4_properties as $account_summary ) {
                      if(isset($account_summary->propertySummaries)){
                        foreach ( $account_summary->propertySummaries as $property ) {
                          $api = AWCA_Auth::get_instance()->get_google_management_api();
                          //if($awca_data_streams = get_option('awca_data_streams')){
                            //$web_data_streams = $awca_data_streams;
                          //}else{
                            $web_data_streams = $api->get_web_data_streams($property->property);
                            //if(isset($web_data_streams->webDataStreams)&&!empty($web_data_streams->webDataStreams)){
                              //update_option('awca_data_streams',$web_data_streams);
                            //}
                          //}
                          if(isset($web_data_streams->dataStreams)&&!empty($web_data_streams->dataStreams)){
                            foreach ($web_data_streams->dataStreams as $web_data_stream){
                              $optgroup = $property->displayName;
                              if ( ! isset( $ga_properties[ $optgroup ] ) ) {
                                $ga_properties[ $optgroup ] = [];
                              }
                              if(isset($web_data_stream->webStreamData->measurementId) && !empty($web_data_stream->webStreamData->measurementId))
                              $ga_properties[ $optgroup ][ $web_data_stream->name . '|' . $web_data_stream->webStreamData->measurementId ] = sprintf( '%s (%s)', $web_data_stream->displayName, $web_data_stream->webStreamData->measurementId );
                              natcasesort( $ga_properties[ $optgroup ] );
                            }
                          }
                        }
                      }
                    }
                } 
                if(isset($analytics_properties) && !empty($analytics_properties)&& is_array($analytics_properties)){
                  foreach ( $analytics_properties as $account_summary ) {
          						if ( ! isset( $account_summary->kind, $account_summary->id, $account_summary->name, $account_summary->webProperties ) ) {
          							continue;
          						}
          						if ( 'analytics#accountSummary' !== $account_summary->kind ) {
          							continue;
          						}
          						foreach ( $account_summary->webProperties as $property ) {
          							if ( ! isset( $property->kind, $property->id, $property->name ) ) {
          								continue;
          							}
          							if ( 'analytics#webPropertySummary' !== $property->kind ) {
          								continue;
          							}
          							$optgroup = $account_summary->name;
          							if ( ! isset( $ga_properties[ $optgroup ] ) ) {
          								$ga_properties[ $optgroup ] = [];
          							}
          							$ga_properties[ $optgroup ][ $account_summary->id . '|' . $property->id ] = sprintf( '%s (%s)', $property->name, $property->id );
          							natcasesort( $ga_properties[ $optgroup ] );
          						}
          					}
                  }
                  
                  if(isset($ga_properties) && !empty($ga_properties)){
                    update_option('ga_properties',$ga_properties);
                  }
                   ?>
                  <select id="awca_auth_settings[property_id]" name="awca_auth_settings[property_id]" class="property-id">
                    <?php
                    foreach($ga_properties as $optgroup_title=>$optgroup_options){
                    ?>
                    <optgroup label="<?php echo $optgroup_title;?>">
                      <?php
                      foreach($optgroup_options as $optgroup_option_key=>$optgroup_option_value){
                        $selected =  $awca_auth_settings['property_id'] == $optgroup_option_key ? 'selected="selected"':'';
                        echo   '<option value="'.$optgroup_option_key.'" '.$selected.'>'.$optgroup_option_value.'</option>';
                      } ?>
                    </optgroup>
                  <?php } ?>
                </select>
                <span class="helper-text top-mar-10" data-error="wrong" data-success="right">Choose Property for which you want to track data.</span>
              </div>
            <?php } ?>
              <!--<div class="input-field awca-col m12 s12 center-align">
                <a class="waves-effect waves-light btn modal-trigger" href="#modal1">Un-link Google Analyitcs from website.</a>
              </div> -->
              <?php
              $ana_edit = $ana_readonly = false; 
              //delete_option('awca_granted_scopes');
              $scopes = get_option('awca_granted_scopes');
              if(isset($scopes) && !empty($scopes)){
                echo '<b>Granted scopes<br></b>';
                $i = 0;
                $scope_c = count($scopes);
                foreach($scopes as $scope){
                  echo $scope.'<br>';
                  if(stripos($scope,'analytics') !== false){
                    $i++;
                    if($i == $scope_c){
                      $ana = true;
                    }
                  }
                  if(stripos($scope,'analytics.edit') !== false){
                    $ana_edit = true;
                  }
                }
              } if(!($ana && $ana_edit)){ 
                if(!$ana){
                  echo '<b>analytics.readonly scope</b><br>';
                  echo 'this scope is important because otherwise dashboard and onsite reports will not work so please provide the access of this scope<br>'; ?>
                  <a style="text-transform: capitalize;font-weight:600" class="waves-effect waves-light white black-text btn AWCA-authenticate top-mar-30"><img width="20px" class="material-icons left" style="margin-top:7px; margin-right:10px" alt="Google sign-in" src="<?php echo AWCA_URL . 'assests/images/google-icon.png'; ?>" /><?php echo $selector? 'Re-Link with your Google account' : 'Link your Google Analytics' ; ?></a>
                  <h5 class="align-center"> Or </h4>
                  <div class="input-field awca-col m12 s12 center-align">
                      <a class="waves-effect waves-light btn modal-trigger" href="#modal1">Un-link Google Analyitcs from website.</a>
                  </div>
                 <?php 
                }else{
                  delete_option('awca_refresh_token_fail');
                }
                /*if(!$ana_edit){
                  echo '<b>analytics.edit scope</b><br>';
                  echo 'this scope is important which help to measurement secret key for some important information transfer to google analytics and als0 creating custom dimension creation which is used for form conversion tracking, author tracking, content tracking and many more purposes <br>'; 
                }
                echo '<br><b>please start relink process to provide all requested scopes for AWCA plugin</b><br>';
                //update_option('awca_refresh_token_fail','yes');
                //delete_option('awca_granted_scopes');*/
               }else{
                  delete_option('awca_refresh_token_fail');
                }
               ?>
            </div>
            <div id="modal1" class="modal">
              <div class="modal-content">
                <h5><?php _e('Un-Link Google Analysis from website', 'awca-text');?></h5>
                <p><?php _e('Are you sure you wish to un-link Google Analysis from Website?', 'awca-text');?></p>
              </div>
              <div class="modal-footer">
                <a class="modal-close waves-effect waves-light btn AWCA-un-link"><?php _e('Un-Link Google Analytics', 'awca-text'); ?></a>
                <a class="modal-close waves-effect waves-green btn AWCA-access-revoke"><?php _e('Remove Access for all My Websites', 'awca-text'); ?></a>
                <a class="modal-close waves-effect waves-green btn"><?php _e('Cancel', 'awca-text'); ?></a>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>
      </div>
    </div>
    <div class="awca-row">
      <div class="awca-col m8 offset-m2 center-align">
        <p>
          <label>
            <input type="checkbox" id="awca_auth_settings[agreement]" name="awca_auth_settings[agreement]" value="yes" <?php checked(isset($awca_auth_settings['agreement']) && $awca_auth_settings['agreement']); ?> />
            <span><?php _e('I agree with ', 'awca-text'); ?><a href="https://advancedwcanalytics.com/privacy-policy/" target="_blank"><?php _e('privacy policy', 'awca-text'); ?></a> <?php _e('and', 'awca-text'); ?> <a href="https://advancedwcanalytics.com/terms-of-service/" target="_blank"><?php _e('terms of service', 'awca-text'); ?></a><?php _e('of AWCA: Google Analytics for Wordpress.', 'awca-text'); ?></span>
          </label>
        </p>
      </div>
    </div>
		<div class="divider top-mar"></div>
  	<div class="awca-row center-align">
  		<button class="btn waves-effect waves-light top-mar"  type="submit" name="awca_auth_submit"><?php _e('Submit', 'awca-text'); ?></button>
  	</div>
    <?php wp_nonce_field( 'awca_auth_submit', 'awca_nonce_header' ); ?>
  </form>
</div>
<?php
