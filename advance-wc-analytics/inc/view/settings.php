<?php

/* controlling view of dashboard*/
if ( !defined( 'ABSPATH' ) ) {
    die;
}
/* intiating variables */
$errors = '';
/* getting dashboard settings value */
if ( !get_option( 'awca_settings' ) ) {
    $awca_settings = $defaults;
    update_option( 'awca_settings', $defaults );
} else {
    $awca_settings = get_option( 'awca_settings' );
}
/* storing Event settings */
if ( isset( $_POST['awca_event_settings'] ) && wp_verify_nonce( $_POST['awca_nonce_header'], 'awca_event_submit' ) ) {
    $awca_event_settings_save = AWCA_Settings::get_instance()->parse_awca_bool_settings( $_POST['awca_event_settings'] );
    if ( $awca_event_settings_save ) {
        update_option( 'awca_event_settings', $awca_event_settings_save );
        echo '<script>
        jQuery(document).ready(function(){
           M.toast({html: \'' . __( 'Setting Saved!', 'awca-text' ) . '\', classes: \'rounded teal\', displayLength:4000});
        });
    </script>';
        $awca_event_settings = $awca_event_settings_save;
    } else {
        $errors .= __( 'Error while saving data!', 'awca-text' ) . '<br>';
        $awca_event_settings = $awca_event_settings_save;
    }
}
/* storing dashboard settings */
if ( isset( $_POST['awca_dashboard_settings'] ) && wp_verify_nonce( $_POST['awca_nonce_header'], 'awca_dashboard_submit' ) ) {
    $awca_dashboard_settings_save = AWCA_Settings::get_instance()->parse_awca_bool_settings( $_POST['awca_dashboard_settings'] );
    if ( $awca_dashboard_settings_save ) {
        update_option( 'awca_dashboard_settings', $awca_dashboard_settings_save );
        echo '<script>
        jQuery(document).ready(function(){
           M.toast({html: \'' . __( 'Setting Saved!', 'awca-text' ) . '\', classes: \'rounded teal\', displayLength:4000});
        });
    </script>';
        $awca_dashboard_settings = $awca_dashboard_settings_save;
    } else {
        $errors .= __( 'Error while saving data!', 'awca-text' ) . '<br>';
        $awca_dashboard_settings = $awca_dashboard_settings_save;
    }
}
/* saving tracking value on successful submission */
if ( isset( $_POST['awca_track_settings'] ) && wp_verify_nonce( $_POST['awca_nonce_header'], 'awca_track_submit' ) ) {
    $awca_track_settings_save = AWCA_Settings::get_instance()->parse_awca_bool_settings( $_POST['awca_track_settings'] );
    if ( $awca_track_settings_save ) {
        update_option( 'awca_track_settings', $awca_track_settings_save );
        echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:\'' . __( 'Setting Saved!', 'awca-text' ) . '\', classes: \'rounded teal\', displayLength:4000});
            });
        </script>';
        $awca_track_settings = $awca_track_settings_save;
    } else {
        $errors .= __( 'Error while saving data!', 'awca-text' ) . '<br>';
        $awca_track_settings = $awca_track_settings_save;
    }
}
if ( isset( $_POST['awca_advance_submit'] ) && wp_verify_nonce( $_POST['awca_nonce_header'], 'awca_advance_submit' ) ) {
    if ( !empty( $_POST['awca_advance_settings'] ) ) {
        //if (isset($_POST['awca_advance_settings']['google_measuremnt']) && isset($_POST['awca_advance_settings']['google_measuremnt'])) {
        if ( !empty( $_POST['awca_advance_settings']['google_measurement_api'] ) ) {
            $google_measurement_api = str_replace( ' ', '', $_POST['awca_advance_settings']['google_measurement_api'] );
            update_option( 'measurement_key', $google_measurement_api );
        } else {
            delete_option( 'measurement_key' );
        }
        //}
        if ( isset( $_POST['awca_advance_settings']['facebook_pixel_code'] ) && isset( $_POST['awca_advance_settings']['facebook_pixel'] ) ) {
            if ( empty( $_POST['awca_advance_settings']['facebook_pixel_code'] ) ) {
                $errors .= __( 'Please supply proper Facebook Pixel code!', 'awca-text' ) . '<br>';
            }
        }
        if ( isset( $_POST['awca_advance_settings']['google_adword_code'] ) && isset( $_POST['awca_advance_settings']['google_adword'] ) ) {
            if ( empty( $_POST['awca_advance_settings']['google_adword_code'] ) ) {
                $errors .= __( 'Please supply proper Google Adword code!', 'awca-text' ) . '<br>';
            }
            if ( !isset( $_POST['awca_advance_settings']['google_adword_label'] ) || empty( $_POST['awca_advance_settings']['google_adword_label'] ) ) {
                $errors .= __( 'Please supply proper Google Adword Label!', 'awca-text' ) . '<br>';
            }
        }
        if ( empty( $errors ) ) {
            $awca_advance_settings_save = AWCA_Settings::get_instance()->parse_awca_advance_settings( $_POST['awca_advance_settings'] );
            if ( $awca_advance_settings_save ) {
                update_option( 'awca_advance_settings', $awca_advance_settings_save );
                echo '<script>
              jQuery(document).ready(function(){
                 M.toast({html: \'' . __( 'Setting Saved!', 'awca-text' ) . '\', classes: \'rounded teal\', displayLength:4000});
              });
          </script>';
                $awca_advance_settings = $_POST['awca_advance_settings'];
            } else {
                $errors .= __( 'Error while saving data! May be data is not in proper format. Please correct Data formats.', 'awca-text' ) . '<br>';
                $awca_advance_settings = $_POST['awca_advance_settings'];
            }
        } else {
            $awca_advance_settings = $_POST['awca_advance_settings'];
        }
    } else {
        $errors .= __( 'there is nothing new to save', 'awca-text' );
    }
}
/* displaying errors */
if ( strlen( $errors ) > 0 ) {
    echo '<script>
            jQuery(document).ready(function(){
               M.toast({html: \'' . __( 'Please correct following Errors:', 'awca-text' ) . '\', classes: \'rounded red\', displayLength:6000});
               M.toast({html: \'' . $errors . '\', classes: \'rounded red\', displayLength:8000});
            });
        </script>';
}
?>

<div class="awca-col s12 awca-options">
  <div class="awca-col s12 top-mar">
    <div class="awca-col m6 s12">
      <h5 class="left zero-mar">Settings</h5>
    </div>
    <div class="awca-col m6 s12">
      <?php 
if ( awca_fs()->is_not_paying() && !awca_fs()->is_trial() ) {
    ?>
        <a class="waves-effect waves-light btn right upgrade-btn" style="margin-left:15px"
          href="<?php 
    echo awca_fs()->get_upgrade_url();
    ?>"><?php 
    _e( 'Upgrade to Pro!', 'awca-text' );
    ?></a>
      <?php 
}
?>
      <a class="waves-effect waves-light btn right" href="https://advancedwcanalytics.com/documentation/" target="_blank"><i
          class="material-icons left">book</i>
        <?php 
_e( 'Documentation', 'awca-text' );
?>
      </a>
    </div>
  </div>
  <div class="clearfix"></div>
  <div class="divider top-mar" style="margin-bottom:20px"></div>
  <div class="awca-row">
    <ul class="tabs">
      <li class="tab awca-col m3 s3"><a id="set-tracking-tab" href="#set-tracking">
          <?php 
_e( 'Tracking Settings', 'awca-text' );
?>
        </a></li>
      <li class="tab awca-col m3 s3"><a id="set-events-tab" href="#set-events">
          <?php 
_e( 'Events Settings', 'awca-text' );
?>
        </a></li>
      <li class="tab awca-col m3 s3"><a id="set-advanced-tab" href="#set-advanced">
          <?php 
_e( 'Advanced Integrations', 'awca-text' );
?>
        </a></li>
        <?php 
?>
    </ul>
  </div>
  <div id="set-tracking" class="awca-col s12"></div>
  <div id="set-events" class="awca-col s12"></div>
  <div id="set-advanced" class="awca-col s12"></div>
  <?php 
?>
  <div class="clearfix"></div>
</div>
<?php 
?>
<?php