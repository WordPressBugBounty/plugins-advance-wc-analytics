<?php
/* controlling google Management API related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class AWCA_Google_Management_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_headers;
  protected $response_message;
  protected $raw_response_body;
  protected $response;

  public function __construct($access_token)
  {
    $this->request_uri = 'https://www.googleapis.com/analytics/v3/management';
    $this->request_headers['authorization'] = sprintf('Bearer %s', is_string($access_token) ? $access_token : '');
    $this->request_headers['Content-Type'] = 'application/json';
  }

  /* listing account summarries */
  public function list_account_summaries($account_summaries)
  {
    $list_account_summaries = array();
    if (isset($account_summaries->items)) {
      $list_account_summaries = (array) $account_summaries->items;
    }
    return $list_account_summaries;
  }

  /* list all views for webproperty */
  public function list_views($views)
  {
    $profiles = array();
    if (isset($views->items)) {
      $profiles = (array) $views->items;
    }
    return $profiles;
  }

  /* get  account summaries */
  public function get_account_summaries()
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request('https://www.googleapis.com/analytics/v3/management/accountSummaries', $this->get_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
      $this->response = false;
    }
    return $this->response;
  }

  /* get g4 account summaries */
  public function get_g4_account_summaries()
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request('https://analyticsadmin.googleapis.com/v1alpha/accountSummaries', $this->get_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
      $this->response = false;
    }
    return $this->response;
  }
  /* get measurement protocall for test */
  public function get_measurement_protocall($property_name){
    $this->reset_response();
    $measurement_protocall_name = "AWCA_secret_Key";
    $i = 0;
    while(1){
      $this->response = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1beta/{$property_name}/measurementProtocolSecrets", $this->get_custom_protocall_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }
   /* create custom dimension for test */
   public function create_measurement_protocall($property_name){
    $this->reset_response();
    $i = 0;
    while(1){
      $this->response = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1alpha/{$property_name}/measurementProtocolSecrets", $this->custom_protocall_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }
  /* create custom dimension for test */
  public function create_custom_dimensions($property_name){
    $this->reset_response();
    $custom_dimensions_array = AWCA_Settings::get_instance()->awca_custom_dimensions;
    foreach($custom_dimensions_array as $key => $custom_dimesion){
      $custom_dimension_generated = get_option('custom_dimension_generated');
      if(stripos($custom_dimension_generated,$key)=== false){
        $i = 0;
        while(1){
          $this->response = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1alpha/{$property_name}/customDimensions", $this->custom_dimension_request_args($custom_dimesion));
          if (!empty($this->response) && is_array($this->response)) {
            if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
              if(!empty($custom_dimension_generated)){
                $custom_dimension_generated .= $key;
                update_option('custom_dimension_generated',$custom_dimension_generated);
              }else{
                update_option('custom_dimension_generated',$key);
              }
              break;
            }
          }
          if ($i > 1) {
            break;
          }
          $i++;
        }
      }
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }
  /* get data webstreams */
  public function get_web_data_streams($property_name)
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1alpha/{$property_name}/dataStreams", $this->get_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }

  /* get  property views summaries */
  public function get_property_views($account_id, $property_id)
  {
    $this->reset_response();
    if (isset($property_id) && !empty($property_id)) {
      $i = 0;
      while (1) {
        $this->response = wp_safe_remote_request("https://www.googleapis.com/analytics/v3/management/accounts/{$account_id}/webproperties/{$property_id}/profiles?fields=items(id,name,internalWebPropertyId)", $this->get_request_args());
        if (!empty($this->response) && is_array($this->response)) {
          if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
            break;
          }
        }
        if ($i > 1) {
          break;
        }
        $i++;
      }
      try {
        $this->response = $this->handle_response($this->response);
      } catch (Exception $e) {
        error_log($e->getMessage(), 0);
      }
      return $this->response;
    }
    return;
  }

  /* handle response received from api request */
  protected function handle_response($response)
  {
    if (is_wp_error($response)) {
      throw new Exception($this->response->get_error_message());
    }
    $this->response_code = wp_remote_retrieve_response_code($response);
    $this->response_message = wp_remote_retrieve_response_message($response);
    $this->raw_response_body = wp_remote_retrieve_body($response);
    $this->response_headers = wp_remote_retrieve_headers($response);
    if ($this->response_code == 200) {
      $this->response = json_decode($this->raw_response_body);
    } else {
      $this->response = '';
      throw new Exception($this->response_message);
    }
    return $this->response;
  }
/* getting request args for api requests */
protected function custom_dimension_request_args($body)
  { 
  $body = '{
    "displayName": "'.$body[0].'",
    "scope": "'.$body[1].'",
    "description": "'.$body[2].'",
    "parameterName": "'.$body[3].'",
    "disallowAdsPersonalization": false,
  }';
  $args = array(
    'method' => 'POST',
    'timeout' => MINUTE_IN_SECONDS,
    'redirection' => 0,
    'httpversion' => '1.0',
    'sslverify' => true,
    'user-agent' => $this->get_request_user_agent(),
    'headers' => $this->request_headers,
    'body' => $body,
    'cookies' => array(),
  );
  return $args;
}
/* getting request args for api requests */
protected function get_custom_protocall_request_args()
{ 
  $args = array(
    'method' => 'GET',
    'timeout' => MINUTE_IN_SECONDS,
    'redirection' => 0,
    'httpversion' => '1.0',
    'sslverify' => true,
    'user-agent' => $this->get_request_user_agent(),
    'headers' => $this->request_headers,
    'cookies' => array(),
  );
  return $args;
}
/* getting request args for api requests */
protected function custom_protocall_request_args()
{ $body = '{
  "displayName": "AWCA_secret_Key",
}';
  $args = array(
    'method' => 'POST',
    'timeout' => MINUTE_IN_SECONDS,
    'redirection' => 0,
    'httpversion' => '1.0',
    'sslverify' => true,
    'user-agent' => $this->get_request_user_agent(),
    'headers' => $this->request_headers,
    'body' => $body,
    'cookies' => array(),
  );
  return $args;
}
  /* getting request args for api requests */
  protected function get_request_args()
  {
    $args = array(
      'method' => 'GET',
      'timeout' => MINUTE_IN_SECONDS,
      'redirection' => 0,
      'httpversion' => '1.0',
      'sslverify' => true,
      'blocking' => true,
      'user-agent' => $this->get_request_user_agent(),
      'headers' => $this->request_headers,
      'body' => '',
      'cookies' => array(),
    );
    return $args;
  }

  /* request user agent */
  protected function get_request_user_agent()
  {
    if (function_exists($this->awca_get_user_agent())) {
      $user_agent = htmlentities($this->awca_get_user_agent(), ENT_QUOTES, 'UTF-8');
    } else {
      $user_agent = sprintf('%s/%s (WordPress/%s)', 'AWCA', AWCA_VERSION, $GLOBALS['wp_version']);
    }
    return $user_agent;
  }
  /* getting user agent from server */
  public function awca_get_user_agent()
  {
    return isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
  }
  /* get views settings info */
  public function get_profiles($account_id, $property_id)
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request("https://www.googleapis.com/analytics/v3/management/accounts/{$account_id}/webproperties/{$property_id}/profiles?fields=items(id,internalWebPropertyId,name,websiteUrl,currency,timezone,eCommerceTracking,enhancedECommerceTracking)", $this->get_request_args());
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }

  /* resetting response variables */
  protected function reset_response()
  {
    $this->response_code = null;
    $this->response_message = null;
    $this->raw_response_body = null;
    $this->response = null;
  }
}
