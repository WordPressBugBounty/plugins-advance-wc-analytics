<?php
/* Adding main view of plugin */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class AWCA_Admin_View extends AWCA_View
{
  /* adding tabs array */
  public function on_load()
  {
    $this->tabs = array(
      'auth' => __('Authentication', 'advance-wc-analytics'),
    );
    $auth_settings = get_option('awca_auth_settings');
    $awca_refresh_token_fail = get_option('awca_refresh_token_fail');
    if (($auth_settings) && ($awca_refresh_token_fail != 'yes')) {
      $this->tabs = array(
        'settings' => __('Settings', 'advance-wc-analytics'),
      );
      if (isset($auth_settings['property_id']) && (strpos($auth_settings['property_id'], 'G') !== false)) {
        $this->tabs = array_merge(array('dash' => __('Dashboard', 'advance-wc-analytics')), $this->tabs);
      }
      $this->tabs = array_merge($this->tabs, array('upgrade' => __('Upgrade to Pro', 'advance-wc-analytics')));
      $this->tabs = array_merge($this->tabs, array('unlink' => __('Un-Link Google Analytics', 'advance-wc-analytics')));
    }
  }

  /* rendering tabs for plugin settings */
  public function render()
  {
?>
    <div class="awca-row">
      <div class="awca-col s12 m12 l12 xl12">
        <div class="awca-col s12 m2 l2 xl2 center-align">
          <img class="responsive-img small-plugin-image" src="<?php echo AWCA_URL . 'assests/images/AWCA.png'; ?>">
          <?php if ($G_id = $this->get_tracking_id()) { ?>
            <span class="badge blue white-text center">
              <?php echo $G_id; ?>
            </span>
          <?php } ?>
        </div>
        <ul class="awca-col s12 m10 l10 xl8 collection menu-collection">
          <?php
          foreach ($this->get_tabs() as $tab => $name) {
            if (($tab != 'unlink') && ($tab != 'upgrade')) { ?>
              <a href="<?php echo esc_url($this->get_tab_url($tab)); ?>"
                class="collection-item <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <?php
                  if ($tab == 'auth') {
                    $auth_settings = get_option('awca_auth_settings');
                    $awca_refresh_token_fail = get_option('awca_refresh_token_fail');
                    if ($auth_settings && ($awca_refresh_token_fail != 'yes')) {
                      //if (get_option('awca_auth_settings')) {
                      echo '<i class="material-icons right">check_circle</i>';
                    } elseif ($auth_settings && ($awca_refresh_token_fail == 'yes')) {
                      //if (get_option('awca_auth_settings')) {
                      echo '<i class="material-icons right yellow-text">info</i>';
                    } else {
                      echo '<i class="material-icons right info">info</i>';
                    }
                  } elseif ($tab == 'upgrade') {
                    echo '<i class="material-icons right shopping_cart">shopping_cart</i>';
                  } else {
                    echo '<i class="material-icons right">check_circle</i>';
                  } ?>
                </li>
              </a>
            <?php } elseif ($tab == 'unlink') { ?>
              <a href="#modal1"
                class="collection-item modal-trigger <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <i class="material-icons right red-text">error</i>
                </li>
              </a>
            <?php } elseif ($tab == 'upgrade') { ?>
              <a href="<?php echo awca_fs()->get_upgrade_url(); ?>"
                class="collection-item <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <i class="material-icons right red-text">shopping_cart</i>
                </li>
              </a>
          <?php }
          }
          ?>
        </ul>
        <div class="awca-col s12 m12 l12 xl2">
        </div>
        <div id="modal1" class="modal">
          <div class="modal-content">
            <h5>
              <?php _e('Un-Link Google Analysis from website', 'advance-wc-analytics'); ?>
            </h5>
            <p>
              <?php _e('Are you sure you wish to un-link Google Analysis from Website?', 'advance-wc-analytics'); ?>
            </p>
          </div>
          <div class="modal-footer">
            <a class="modal-close waves-effect waves-light btn AWCA-un-link">
              <?php _e('Un-Link Google Analytics', 'advance-wc-analytics'); ?>
            </a>
            <a class="modal-close waves-effect waves-green btn AWCA-access-revoke">
              <?php _e('Un-Link and Remove All Settings', 'advance-wc-analytics'); ?>
            </a>
            <a class="modal-close waves-effect waves-green btn">
              <?php _e('Cancel', 'advance-wc-analytics'); ?>
            </a>
          </div>
        </div>
      </div>
      <div class="content-pad">
        <div class="awca-col s12 m12 l12 xl12 white main-content">
          <?php
          $current_tab = $this->get_current_tab();
          $tab_options = $this->view_options($current_tab);
          $this->view($current_tab, $tab_options);
          ?>
        </div>
      </div>
  <?php
  }
  /* getting tracking id */
  public function get_tracking_id()
  {
    if (get_option('awca_auth_settings')) {
      $auth_settings = get_option('awca_auth_settings');
      if (isset($auth_settings['property_id'])) {
        $property = $auth_settings['property_id'];
        $pieces = explode('|', $property);
        return $pieces[1];
      } else {
        if (isset($auth_settings['tracking_id'])) {
          return $auth_settings['tracking_id'];
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }
  /* suppling setting and some required values */
  private function view_options($tab)
  {
    $awca_settings = AWCA_Settings::get_instance();
    $awca_auth = AWCA_Auth::get_instance();
    switch ($tab) {
      case 'dash':
        return array(
          'defaults' => $awca_settings->init_awca_dash_defaults(),
          //'property_views' => $awca_auth->get_analytics_property_views(),
        );
      case 'dash__premium_only':
        return array(
          'defaults' => $awca_settings->init_awca_dash_defaults(),
          //'property_views' => $awca_auth->get_analytics_property_views(),
        );
      case 'auth':
        if (!empty($ga_properties = get_option('ga_properties'))) {
          return array(
            'ga_properties' => $ga_properties,
            'analytics_properties' => false,
            'analytics_g4_properties' => false,
            'defaults' => $awca_settings->init_awca_auth_defaults(),
          );
        } else {
          return array(
            //'analytics_properties' => $awca_auth->get_analytics_properties(),
            'analytics_g4_properties' => $awca_auth->get_analytics_g4_properties(),
            'defaults' => $awca_settings->init_awca_auth_defaults(),
          );
        }
      case 'settings':
        return array(
          'defaults' => $awca_settings->init_awca_track_defaults(),
        );
      case 'support':
        return array();
      default:
        return array();
    }
  }
}
