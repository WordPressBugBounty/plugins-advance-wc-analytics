<?php
/* controlling dashboard related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class AWCA_Google_Report_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_message;
  protected $raw_response_body;
  protected $response;

  public function __construct($access_token)
  {
    $this->request_uri = 'https://analyticsreporting.googleapis.com/v4/reports:batchGet';
    $this->request_headers['authorization'] = sprintf('Bearer %s', is_string($access_token) ? $access_token : '');
    $this->request_headers['Content-Type'] = 'application/json';
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
      error_log($e->getMessage(), 0);
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
        if (isset($report->data->rows) && !empty($report->data->rows)) {
          foreach ($report->data->rows as $object) {
            if (isset($object->dimensions[0]) && !empty($object->dimensions[0])) {
              $data[$i][$object->dimensions[0]] = $object->metrics[0]->values[0];
            } else {
              $array_name = 'awca_dash_stats_data_' . $tab_id;
              $stats_array = array_keys(AWCA_Settings::get_instance()->$array_name);
              $k = 0;
              foreach ($object->metrics as $metric) {
                $j = 0;
                foreach ($metric->values as $value) {
                  $data[$i][$stats_array[$j]][$k] = round($value, 3);
                  $j++;
                }
                $k++;
              }
            }
          }
        } elseif (isset($report->data->totals) && !empty($report->data->totals) && ($i == 0)) {
          $k = 0;
          foreach ($report->data->totals as $object) {
            $array_name = 'awca_dash_stats_data_' . $tab_id;
            $stats_array = array_keys(AWCA_Settings::get_instance()->$array_name);
            $j = 0;
            foreach ($stats_array as $stat) {
              $data[$i][$stat][$k] = round($object->values[$j], 2);
              $j++;
            }
            $k++;
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
    $array_name = 'awca_dash_stats_data_' . $tab_id;
    $metrics = '"metrics": [';
    $metrics_loop_array = AWCA_Settings::get_instance()->$array_name;
    foreach ($metrics_loop_array as $stat_name => $stat) {
      $metrics .= '{"expression": "ga:' . $stat_name . '"},';
    }
    $metrics .= ']';
    return $metrics;
  }

  public function get_dashboard_data($view_id, $start_date, $end_date, $tab_id)
  {
    $report_request = '{';
    $array_name = 'awca_report_request_' . $tab_id;
    $request_loop_array = AWCA_Settings::get_instance()->$array_name;
    foreach ($request_loop_array as $report_name => $report_parameters) {
      $start = strtotime($start_date);
      $end = strtotime($end_date);
      $days_between = ceil(abs($end - $start) / 86400) + 1;
      $cmp_start_date = date_format(date_sub(date_create($start_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
      $cmp_end_date = date_format(date_sub(date_create($end_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
      if ($report_name == 'stats') {
        $metrics = $this->get_metrics_data($tab_id);
        $report_request .= '"reportRequests":
                  {"viewId":"' . $view_id . '",
                    "dateRanges": [
                      {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
                      {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
                    ],
                    ' . $metrics . ',
                      "includeEmptyRows": true,
                  },';
      } elseif ($report_name == 'dateViseVisitors') {
        $report_request .= '"reportRequests":
          {"viewId":"' . $view_id . '",
            "dateRanges": [
              {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
              {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
            ],
            "metrics": [
                {"expression": "ga:' . $report_parameters[0] . '"},
            ],
            "dimensions" :[
                {"name": "ga:' . $report_parameters[1] . '"},
            ],
            "orderBys" :[
                {"fieldName":"ga:' . $report_parameters[2] . '",
                "sortOrder": "ASCENDING",
              }
            ],
            "includeEmptyRows": true,
          },';
      } else {
        $report_request .= '"reportRequests":
          {"viewId":"' . $view_id . '",
            "dateRanges": [
              {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
              {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
            ],
            "metrics": [
                {"expression": "ga:' . $report_parameters[0] . '"},
            ],
            "dimensions" :[
                {"name": "ga:' . $report_parameters[1] . '"},
            ],
            "orderBys" :[
                {"fieldName":"ga:' . $report_parameters[2] . '",
                 "sortOrder": "DESCENDING",
               }
            ],
            "pageSize" :10,
          },';
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
    $response_headers = wp_remote_retrieve_headers($response);
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