<?php
/* controlling view of dashboard*/
if (!defined('ABSPATH')) {
  die;
}

/* intiating variables */
$errors = '';

/* getting dashboard settings value */
if (!get_option('awca_dash_settings')) {
  $awca_dash_settings = $defaults;
  update_option('awca_dash_settings', $defaults);
} else {
  $awca_dash_settings = get_option('awca_dash_settings');
}

/* saving dashboard settings on successful submission */
if (isset($_POST['awca_dash_submit']) && wp_verify_nonce($_POST['awca_nonce_header'], 'awca_dash_submit')) {
  if (!empty($_POST['awca_dash_settings'])) {
    $awca_dash_settings_save = AWCA_Settings::get_instance()->parse_awca_dash_settings($_POST['awca_dash_settings']);
    if ($awca_dash_settings_save) {
      if ($awca_dash_settings_save['report_frame'] == 'Yesterday') {
        $awca_dash_settings_save['report_to'] = date('Y-m-d', strtotime('-1 day'));
        $awca_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-1 day'));
      } elseif ($awca_dash_settings_save['report_frame'] == 'Last 7 days') {
        $awca_dash_settings_save['report_to'] = date('Y-m-d', strtotime('-1 day'));
        $awca_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-8 day'));
      } else {
        $awca_dash_settings_save['report_to'] = date('Y-m-d', strtotime('-1 day'));
        $awca_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-31 day'));
      }
      update_option('awca_dash_settings', $awca_dash_settings_save);
      echo '<script>
          jQuery(document).ready(function(){
             M.toast({html: "'. __('Setting Saved!', 'advance-wc-analytics') .'", classes: "rounded teal", displayLength:4000});
          });
      </script>';
      $awca_dash_settings = $awca_dash_settings_save;
    } else {
      $errors .= 'Error while saving data!<br>';
      $awca_dash_settings = $awca_dash_settings_save;
    }
  }
}

/* displaying errors */
if (strlen($errors) > 0) {
  echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:" '. __('Please correct following Errors:', 'advance-wc-analytics') .'", classes: "rounded red", displayLength:6000});
               M.toast({html:" '. $errors . '", classes: "rounded red", displayLength:8000});
            });
        </script>';
}
?>
<div class="awca-col s12 awca-options">
  <div class="awca-col s12 top-mar">
    <form action="" method="POST">
      <div class="awca-col m3 s12 input-field">
        <select name="awca_dash_settings[report_frame]" id="report_frame">
          <option value="Yesterday" <?php if (isset($awca_dash_settings['report_frame'])) {
            echo $awca_dash_settings['report_frame'] == 'Yesterday' ? 'selected="selected"' : '';
          } ?>><?php _e('Yesterday', 'advance-wc-analytics'); ?></option>
          <option value="Last 7 days" <?php if (isset($awca_dash_settings['report_frame'])) {
            echo $awca_dash_settings['report_frame'] == 'Last 7 days' ? 'selected="selected"' : '';
          } ?>><?php _e('Last 7 days', 'advance-wc-analytics'); ?></option>
          <option value="Last 30 days" <?php if (isset($awca_dash_settings['report_frame'])) {
            echo $awca_dash_settings['report_frame'] == 'Last 30 days' ? 'selected="selected"' : '';
          } ?>><?php _e('Last 30 days', 'advance-wc-analytics'); ?></option>
        </select>
        <label>
          <?php _e('Select View', 'advance-wc-analytics'); ?>Date Range
        </label>
      </div>
      <div class="awca-col m5 s12">
        <div class="awca-col m6 l-bord from">
          <label>
            <?php _e('From', 'advance-wc-analytics'); ?>
          </label>
          <input type="text" name="awca_dash_settings[report_from]" class="datepicker" id="from"
            value="<?php if (isset($awca_dash_settings['report_from'])) {
              echo $awca_dash_settings['report_from'];
            } ?>">
        </div>
        <div class="awca-col m6 l-bord to">
          <label>
            <?php _e('To', 'advance-wc-analytics'); ?>
          </label>
          <input type="text" name="awca_dash_settings[report_to]" class="datepicker" id="to"
            value="<?php if (isset($awca_dash_settings['report_to'])) {
              echo $awca_dash_settings['report_to'];
            } ?>">
        </div>
      </div>
      <div class="awca-col m1 s12">
        <button class="btn waves-effect waves-light top-mar" type="submit" name="awca_dash_submit" value="submit">
          <?php _e('Go', 'advance-wc-analytics'); ?>
        </button>
      </div>
      <?php wp_nonce_field('awca_dash_submit', 'awca_nonce_header'); ?>
    </form>
  </div>
  <div class="clearfix"></div>
  <div class="divider top-mar-20" style="margin-bottom:20px"></div>
  <div class="awca-row">
    <ul class="tabs">
      <li class="tab awca-col m1 s4"><a id="dash-tab" href="#dash">
          <span><?php _e('Dashboard', 'advance-wc-analytics'); ?></span>
        </a></li>
      <li class="tab awca-col m1 s4"><a id="audience-pro-tab" href="#upgrade-pro">
      <span><?php _e('Upgrade', 'advance-wc-analytics'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
    </ul>
  </div>
  <div id="dash" class="awca-col s12"></div>
  <div id="upgrade-pro" class="awca-col s12">
    <div class="awca-row">
      <div class="awca-col s12 m12 l12 awca-flex">
        <?php 
        $features = AWCA_Settings::get_instance()->awca_features_list;
          foreach ($features as $image=>$feature ){
            $pro = $feature[2]?'<sup>pro</sup>':'';
            echo '<div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
              <div class="awca-col s4 m3 l2 xl2">
                <img class="awca-info-img" src="'.AWCA_URL.'assests/images/'.$feature[3].'.png">
              </div>
              <div class="awca-col s8 m9 l10 xl10">
                <p class="awca-info-title">'.$feature[0].' '.$pro.'</p>
                <p class="awca-info-description">'.$feature[1].'</p>
              </div> 
            </div>'; 
          }
        ?>
      </div>
      <div class="awca-col s12 m12 l12"></div>   
      <h5 class="center-align">
        <?php _e('Please upgrade to unlock reports and stats associated with audience.', 'advance-wc-analytics'); ?>
      </h5>
      <div class="center-align top-mar-30">
        <a class="waves-effect waves-light btn" href="<?php echo awca_fs()->get_upgrade_url();?>"><?php _e('Upgrade Now!', 'advance-wc-analytics'); ?></a>
      </div>
    </div>      
  </div>
  <div class="clearfix"></div>
</div>
<?php
