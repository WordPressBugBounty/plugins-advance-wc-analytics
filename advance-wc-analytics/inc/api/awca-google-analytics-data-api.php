<?php
/* controlling google Management API related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class AWCA_Google_Analytics_Data_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_headers;
  protected $response_message;
  protected $raw_response_body;
  protected $response;
  protected $property_id;

  public function __construct($access_token)
  {
    $this->property_id = $this->get_property_id();
    $this->request_uri = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $this->property_id . ':batchRunReports';
    $this->request_headers['authorization'] = sprintf('Bearer %s', is_string($access_token) ? $access_token : '');
    $this->request_headers['Content-Type'] = 'application/json';
  }

  /* getting property id */
  public function get_property_id()
  {
    if (get_option('awca_auth_settings')) {
      $auth_settings = get_option('awca_auth_settings');
      if (isset($auth_settings['property_id'])) {
        $property = $auth_settings['property_id'];
        $pieces = explode('|', $property);
        $property_id_text = $pieces[0];
        $text_pieces = explode('/', $property_id_text);
        if (isset($text_pieces[1]) && !empty($text_pieces[1])) {
          return $text_pieces[1];
        } else {
          return 0;
        }
      } else {
        return 0;
      }
    } else {
      return 0;
    }
  }

  /* get require stat data */
  public function get_require_stat_data($body, $tab_id)
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $response = wp_remote_post($this->request_uri, $this->get_request_args($body));
      if (!empty($response) && is_array($response)) {
        if (isset($response['response']['code']) && ((int) $response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) {
        break;
      }
      $i++;
    }
    try {
      $response = $this->handle_response($response);
    } catch (Exception $e) {
      $error_message = $e->getMessage();
      if(stripos($error_message,'unauthorized') !== false){
        $process_status = get_option('awca_refresh_token_fail');
			  if(empty($process_status)){
				  update_option('awca_refresh_token_fail','retry');
			  }else{
				  update_option('awca_refresh_token_fail','yes');
          delete_option('awca_granted_scopes');
			  }
      }
      if(stripos($error_message,'forbidden') !== false){
        $process_status = get_option('awca_refresh_token_fail');
			  if(empty($process_status)){
				  update_option('awca_refresh_token_fail','retry');
			  }else{
				  update_option('awca_refresh_token_fail','yes');
          delete_option('awca_granted_scopes');
			  }
      }
      $response = false;
    }
    $i = 0;
    if (isset($response->reports) && !empty($response->reports)) {
      foreach ($response->reports as $report) {
        if (isset($report->rows) && !empty($report->rows)) {
          foreach ($report->rows as $object) {
            if (isset($object->dimensionValues[0]) && !empty($object->dimensionValues[0])) {
              $date_range_count = count($object->dimensionValues) - 1;
              if($date_range_count > 0){
                $f= array(0,1);
                foreach ($f as $f_value){
                  $test = 'gawp_date_range_'.$f_value;
                  if (strpos($object->dimensionValues[$date_range_count]->value,$test) !== false) {
                    $data[$i][$object->dimensionValues[0]->value][$f_value] = $object->metricValues;
                  }
                }
              }else{
                $test = 'gawp_date_range_';
                if (strpos($object->dimensionValues[$date_range_count]->value,$test) !== false) {
                  $k = (int) filter_var($object->dimensionValues[0]->value, FILTER_SANITIZE_NUMBER_INT);
                  $array_name = 'awca_dash_stats_data_ga4_' . $tab_id;
                  $stats_array_ga4 = array_keys(AWCA_Settings::get_instance()->$array_name);
                  $j = 0;
                  foreach ($object->metricValues as $key) {
                    foreach ($key as $key2 => $value) {
                      $data[$i][$stats_array_ga4[$j]][$k] = round($value, 2);
                    }
                    $j++;
                  }
                } else {
                  $data[$i][$object->dimensionValues[0]->value] = $object->metricValues;
                }
              }
            } else {
              $array_name = 'awca_dash_stats_data_ga4_' . $tab_id;
              $stats_array_ga4 = array_keys(AWCA_Settings::get_instance()->$array_name);
              $j = 0;
              foreach ($object->metricValues as $key => $key2) {
                foreach ($key2 as $name => $value) {
                  $data[$i][$stats_array_ga4[$j]] = round($value, 2);
                }
                $j++;
              }
            }
          }
        } else {
          $data[$i] = false;
        }
        $i++;
      }
    } else {
      $data = false;
    }
    return $data;
  }

  public function get_metrics_data($tab_id)
  {
    $array_name = 'awca_dash_stats_data_ga4_' . $tab_id;
    $metrics = '"metrics": [';
    $metrics_loop_array = AWCA_Settings::get_instance()->$array_name;
    foreach ($metrics_loop_array as $stat_name => $stat) {
      $metrics .= '{"name": "' . $stat_name . '"},';
    }
    $metrics .= ']';
    return $metrics;
  }
  public function get_array_metrics_data($metrics_array)
  {
    $metrics = '"metrics": [';
    foreach ($metrics_array as $stat_name) {
      $metrics .= '{"name": "' . $stat_name . '"},';
    }
    $metrics .= ']';
    return $metrics;
  }

  public function get_dashboard_data($start_date, $end_date, $tab_id)
  {
    $report_request = '{';
    $array_name = 'awca_report_request_ga4_' . $tab_id;
    $request_loop_array = AWCA_Settings::get_instance()->$array_name;
    foreach ($request_loop_array as $report_name => $report_parameters) {
      if ($report_name == 'stats') {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $days_between = ceil(abs($end - $start) / 86400) + 1;
        $cmp_start_date = date_format(date_sub(date_create($start_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
        $cmp_end_date = date_format(date_sub(date_create($end_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
        $metrics = $this->get_metrics_data($tab_id);
        $report_request .= '"requests":[
                  {"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '","name":"gawp_date_range_0"},{"startDate":"' . $cmp_start_date . '","endDate":"' . $cmp_end_date . '","name":"gawp_date_range_1"}],
                    ' . $metrics . ',
                      "keepEmptyRows": true
                  },
                ],';
      } elseif ($report_name == 'purchaseJourney') {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $days_between = ceil(abs($end - $start) / 86400) + 1;
        $cmp_start_date = date_format(date_sub(date_create($start_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
        $cmp_end_date = date_format(date_sub(date_create($end_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $report_request .= '"requests":[
                  {"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '","name":"gawp_date_range_0"},{"startDate":"' . $cmp_start_date . '","endDate":"' . $cmp_end_date . '","name":"gawp_date_range_1"}],
                    ' . $metrics . ',
                    "dimensions" :[
                      {"name": "' . $report_parameters[1] . '"},
                    ],
                    "keepEmptyRows": true
                  },
                ],';
      } elseif ($report_name == 'dateViseVisitors') {
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $report_request .= '"requests":[
            {"dateRanges":{"startDate":"' . $start_date . '","endDate":"' . $end_date . '"},
            ' . $metrics . ',
            "dimensions" :[
                {"name": "' . $report_parameters[1] . '"},
            ],
            "orderBys":[
              {
              "dimension":{
                "dimensionName":"date"
              },
              }
            ],
            "keepEmptyRows":true,
          },
        ],';
      }elseif ($report_name == 'overallProductPerformance') {
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $report_request .= '"requests":[
            {"dateRanges":{"startDate":"' . $start_date . '","endDate":"' . $end_date . '"},
            ' . $metrics . ',
            "dimensions" :[
                {"name": "' . $report_parameters[1] . '"},
            ],
            "orderBys" :[{
              "desc": true,
              "metric": {
                "metricName": "' . $report_parameters[2] . '"
              },
            }],
            "keepEmptyRows":true,
          },
        ],';
      }else {
        $report_request .= '"requests":[
            {"dateRanges":{"startDate":"' . $start_date . '","endDate":"' . $end_date . '"},
            "metrics": [
                {"name": "' . $report_parameters[0] . '"},
            ],
            "dimensions" :[
                {"name": "' . $report_parameters[1] . '"},
            ],
            "orderBys" :[{
              "desc": true,
              "metric": {
                "metricName": "' . $report_parameters[2] . '"
              },
               }],
            "limit" :10,
          },
        ],';
      }
    }
    $report_request .= '}';
    $require_data = $this->get_require_stat_data($report_request, $tab_id);
    return $require_data;
  }

  /* getting request args for api requests */
  protected function get_request_args($body)
  {

    $args = array(
      'method' => 'POST',
      'timeout' => MINUTE_IN_SECONDS,
      'redirection' => 0,
      'httpversion' => '1.0',
      'sslverify' => true,
      'user-agent' => $this->get_request_user_agent(),
      'headers' => $this->request_headers,
      'body' => $body,
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

  /* handle response received from api request */
  protected function handle_response($response)
  {
    if (is_wp_error($response)) {
      throw new Exception($response->get_error_message());
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

  /* resetting response variables */
  protected function reset_response()
  {
    $this->response_code = null;
    $this->response_message = null;
    $this->raw_response_body = null;
    $this->response = null;
  }
}
