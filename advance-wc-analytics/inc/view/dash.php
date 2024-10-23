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
             M.toast({html: "'. __('Setting Saved!', 'awca-text') .'", classes: "rounded teal", displayLength:4000});
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
               M.toast({html:" '. __('Please correct following Errors:', 'awca-text') .'", classes: "rounded red", displayLength:6000});
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
          } ?>><?php _e('Yesterday', 'awca-text'); ?></option>
          <option value="Last 7 days" <?php if (isset($awca_dash_settings['report_frame'])) {
            echo $awca_dash_settings['report_frame'] == 'Last 7 days' ? 'selected="selected"' : '';
          } ?>><?php _e('Last 7 days', 'awca-text'); ?></option>
          <option value="Last 30 days" <?php if (isset($awca_dash_settings['report_frame'])) {
            echo $awca_dash_settings['report_frame'] == 'Last 30 days' ? 'selected="selected"' : '';
          } ?>><?php _e('Last 30 days', 'awca-text'); ?></option>
        </select>
        <label>
          <?php _e('Select View', 'awca-text'); ?>Date Range
        </label>
      </div>
      <div class="awca-col m5 s12">
        <div class="awca-col m6 l-bord from">
          <label>
            <?php _e('From', 'awca-text'); ?>
          </label>
          <input type="text" name="awca_dash_settings[report_from]" class="datepicker" id="from"
            value="<?php if (isset($awca_dash_settings['report_from'])) {
              echo $awca_dash_settings['report_from'];
            } ?>">
        </div>
        <div class="awca-col m6 l-bord to">
          <label>
            <?php _e('To', 'awca-text'); ?>
          </label>
          <input type="text" name="awca_dash_settings[report_to]" class="datepicker" id="to"
            value="<?php if (isset($awca_dash_settings['report_to'])) {
              echo $awca_dash_settings['report_to'];
            } ?>">
        </div>
      </div>
      <div class="awca-col m1 s12">
        <button class="btn waves-effect waves-light top-mar" type="submit" name="awca_dash_submit" value="submit">
          <?php _e('Go', 'awca-text'); ?>
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
          <span><?php _e('Dashboard', 'awca-text'); ?></span>
        </a></li>
        <li class="tab awca-col m1 s4"><a id="audience-pro-tab" href="#audience-pro">
        <span><?php _e('Audience', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
      <li class="tab awca-col m1 s4"><a id="acquisition-pro-tab" href="#acquisition-pro">
      <span><?php _e('Acquisition', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
      <li class="tab awca-col m1 s4"><a id="behavior-pro-tab" href="#behavior-pro">
      <span><?php _e('Behavior', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
      <?php
      if (class_exists('WooCommerce')) {
        ?>
        <li class="tab awca-col m1 s4"><a id="conversion-pro-tab" href="#conversion-pro">
        <span><?php _e('Conversion', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
          </a></li>
      <?php } ?>
      <li class="tab awca-col m1 s4"><a id="googleAds-pro-tab" href="#googleAds-pro">
      <span><?php _e('Google Ads', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
      <li class="tab awca-col m1 s4"><a id="googleAdsense-pro-tab" href="#googleAdsense-pro">
      <span><?php _e('Google Adsense', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>  
      <li class="tab awca-col m1 s4"><a id="audience-pro-tab" href="#upgrade-pro">
      <span><?php _e('Upgrade', 'awca-text'); ?></span><i class="material-icons awca_pro_icon info">info</i>
        </a></li>
    </ul>
  </div>
  <div id="dash" class="awca-col s12"></div>
  <div id="audience-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/audience.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Overview Report</p>
              <p class="awca-info-description">This report shows the number of users and how many new users visited the website for a specific date over the period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/country.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Country Based Users Report</p>
              <p class="awca-info-description">This report categorizes users into different countries based on their location for a specific period of time.</p>
            </div> 
          </div> 
        </div>
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/translation.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Language Based Users Report</p>
              <p class="awca-info-description">This report categorizes users based on their browser language for a specific period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/responsive.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Device Based Users Report</p>
              <p class="awca-info-description">This is extra description.This report categories users based of their device category for specific period of time.</p>
            </div> 
          </div> 
        </div>  
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="acquisition-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/user.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Users Based on Channels</p>
              <p class="awca-info-description">This report shows an analysis of which channel contributed the most traffic to the website for a specified period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/website.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Users Based on Source</p>
              <p class="awca-info-description">This report classifies users based on source/medium by using users who reached the website for a specific period of time.</p>
            </div> 
          </div> 
        </div>
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/stretch.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Users Based on Screen Sizes</p>
              <p class="awca-info-description">This report classify different screen sizes users were using for browsing website over period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/analysis.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Users Based on Medium</p>
              <p class="awca-info-description">This report classify users based on medium by using users reached website for specific period of time.</p>
            </div> 
          </div> 
        </div>  
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="behavior-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/pageview.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Page Performance Report</p>
              <p class="awca-info-description">This report shows which pages have the most visitors over a period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/stop-watch.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Avg. Time Spend of Page</p>
              <p class="awca-info-description">The total amount of time (in seconds) your website page was in the foreground of users' devices for a specified time period.</p>
            </div> 
          </div> 
        </div>
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/age-group.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Users based on Age-Group</p>
              <p class="awca-info-description">This is the division of traffic based on age groups over a period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/gender.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Gender Based User Report</p>
              <p class="awca-info-description">Gender-based division of traffic over a period of time.</p>
            </div> 
          </div> 
        </div>  
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="conversion-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/revenue.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Product Base Revenue Report</p>
              <p class="awca-info-description">This report details revenue generated from individual products, highlighting sales performance and financial contributions.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/fintech.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Source Base Revenue Report</p>
              <p class="awca-info-description">This report details income origins, showing revenue generated from various sources, aiding in financial analysis and strategy.</p>
            </div> 
          </div> 
        </div>
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/profit-up.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Device base conversion share</p>
              <p class="awca-info-description">This report shows a device category breakdown with revenue generated by each different device category over a period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/map.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">State/Region Base Revenue Report</p>
              <p class="awca-info-description">This report shows which state or region contributed most to total revenue generation by putting them in order based on revenue for a specified period of time.</p>
            </div> 
          </div> 
        </div>  
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="googleAds-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/cost-per-click.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Ad Group Cost Report.</p>
              <p class="awca-info-description">This report shows ad costs based on different ad groups over a period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/arrow.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Ad Group Success Report</p>
              <p class="awca-info-description">This report shows the contribution of ad groups to generating website traffic for a specified period of time.</p>
            </div> 
          </div> 
        </div>
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/google.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Ad Search Query Reports</p>
              <p class="awca-info-description">This report shows which search query triggered maximum ad clicks for a specified period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/megaphone.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Ad Distribution Network Performance Report(Ad Clicks)</p>
              <p class="awca-info-description">This report shows ad clicks based on different ad slots for a specific period of time.</p>
            </div> 
          </div> 
        </div>  
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="googleAdsense-pro" class="awca-col s12">
    <div class="awca-col l12 m12 s12">
        <div class="awca-row">
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/pay-per-click.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Adsense Revenue Based on PageTitle</p>
              <p class="awca-info-description">This report shows ad revenue based on page for a specified period of time.</p>
            </div> 
          </div> 
          <div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
            <div class="awca-col s4 m3 l2 xl2">
              <img class="awca-info-img" src="<?php echo AWCA_URL;?>assests/images/pay-per-click-1.png">
            </div>
            <div class="awca-col s8 m9 l10 xl10">
              <p class="awca-info-title">Ad Clicks Based on PageTitle</p>
              <p class="awca-info-description">This report shows which page received maximum ad clicks over the period of time.</p>
            </div> 
          </div> 
      </div>
    </div>
    <div class="center-align top-mar-30">
      <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
    </div>
  </div>
  <div id="upgrade-pro" class="awca-col s12">
    <div class="awca-row">
      <div class="awca-col s12 m12 l12 awca-flex">
        <?php 
        $features = AWCA_Settings::get_instance()->awca_features_list;
          foreach ($features as $image=>$feature ){
            $pro = $feature[2]?'<sup>pro</sup>':'';
            echo '<div class="awca-col s12 m6 l6 xl6 valign-wrapper awca-info-box">
              <div class="awca-col s4 m3 l2 xl2">
                <img class="awca-info-img" src="'.AWCA_URL.'assests/images/AWCA.png">
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
        <?php _e('Please upgrade to unlock reports and stats associated with audience.', 'awca-text'); ?>
      </h5>
      <div class="center-align top-mar-30">
        <a class="waves-effect waves-light btn" href="https://advancedwcanalytics.com/pricing/"><?php _e('Upgrade Now!', 'awca-text'); ?></a>
      </div>
    </div>      
  </div>
  <div class="clearfix"></div>
</div>
<?php