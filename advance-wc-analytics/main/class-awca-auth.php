<?php
/* Adding Authentication functionality */
if (!defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
class AWCA_Auth
{
	/* initiating variables */
	const PROXY_URL = 'https://google.woocommerceanalytics.com/';
	private $management_api;
	private $report_api;
	private static $instance = null;
	private $transient_value;

	public static function get_instance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct()
	{
		add_action('init', array($this, 'awca_authenticate'));
		add_action('wp_ajax_web_awca_un_link', array($this, 'web_un_link'));
		add_action('wp_ajax_nopriv_web_awca_un_link', array($this, 'web_un_link'));
		add_action('wp_ajax_web_awca_tab_update', array($this, 'tab_update'));
		add_action('wp_ajax_nopriv_web_awca_tab_update', array($this, 'tab_update'));
		add_action('wp_ajax_web_awca_revoke_access', array($this, 'web_revoke_access'));
		add_action('wp_ajax_nopriv_web_awca_revoke_access', array($this, 'web_revoke_access'));
		add_action('admin_enqueue_scripts', array($this, 'load_local_script'));
		add_action('plugins_loaded', array($this, 'new_update_settings'));
		add_action('wp_dashboard_setup', array($this, 'awca_dashboard_widget'));
		add_action('template_redirect', array($this, 'awca_edit_action_scope'));
	}
	/* custom action */
	function awca_edit_action_scope()
	{
		$granted_scopes = get_option('awca_granted_scopes');
		if (!empty($granted_scopes) && is_array($granted_scopes)) {
			foreach ($granted_scopes as $scope) {
				if (stripos($scope, 'analytics.edit') !== false) {
					$m_process_status = get_option('measurement_key_process');
					if (stripos($m_process_status, 'completed') === false) {
						$this->awca_get_mesurement_key();
					}
					$c_process_status = get_option('custom_dimension_process');
					if (stripos($c_process_status, 'completed') === false) {
						$this->awca_create_custom_dimension();
					}
				} else {
				}
			}
		}
	}
	/* creating dashboard widget */
	function awca_dashboard_widget()
	{
		global $wp_meta_boxes;
		if ($auth_settings = get_option('awca_auth_settings')) {
			$awca_refresh_token_fail = get_option('awca_refresh_token_fail');
			if (isset($auth_settings['property_id']) && ($awca_refresh_token_fail != 'yes')) {
				$this->transient_value = $this->awca_required_dashboard_data();
				if ((strpos($auth_settings['property_id'], 'UA') !== false)) {
					$dash_data_widget = AWCA_Settings::get_instance()->awca_dash_data_widget;
				} else {
					$dash_data_widget = AWCA_Settings::get_instance()->awca_dash_data_ga4_widget;
				}
				$i = 0;
				foreach ($dash_data_widget as $widget_title => $widget_data) {
					$i++;
					wp_add_dashboard_widget('awca_status_widget' . $i, $widget_title, array($this, 'awca_dashboard_help'), '', $widget_data, '', 'high');
				}
			}
		} else {
			wp_add_dashboard_widget('awca_status_widget_6', 'AWCA Setup', array($this, 'awca_dashboard_help6'));
		}
	}
	function awca_required_dashboard_data()
	{
		$awca_dash_settings = $this->get_current_dash_settings();
		$property_id = $this->get_ga_property_id();
		$dash_end = date('Y-m-d', strtotime('-1 day'));
		$dash_start = date('Y-m-d', strtotime('-30 day'));
		$tab_id = 'dash';
		if (!empty($awca_dash_settings['report_view'])) {
			$new_api = $this->get_google_report_api();
			$transient_name = $this->awca_create_transient_name($awca_dash_settings['report_view'], $property_id, $dash_start, $dash_end, $tab_id);
			$transient_value = get_transient($transient_name);
			if ($transient_value == false) {
				$transient_value = $new_api->get_dashboard_data($awca_dash_settings['report_view'], $dash_start, $dash_end, $tab_id);
				if (isset($transient_value) && is_array($transient_value)) {
					set_transient($transient_name, $transient_value, 3600);
				}
			}
		} else {
			$new_api = $this->get_google_analytics_data_api();
			$transient_name = $this->awca_create_transient_name(false, $property_id, $dash_start, $dash_end, $tab_id);
			$transient_value = get_transient($transient_name);
			if ($transient_value == false) {
				$transient_value = $new_api->get_dashboard_data($dash_start, $dash_end, $tab_id);
				if (isset($transient_value) && is_array($transient_value)) {
					set_transient($transient_name, $transient_value, 3600);
				}
			}
		}
		return $transient_value;
	}
	function awca_create_transient_name($view_id, $property_id, $start_date, $end_date, $tab_id)
	{
		if ($view_id) {
			$transient_name = md5(serialize(array($view_id, $property_id, $start_date, $end_date, $tab_id)));
		} else {
			$transient_name = md5(serialize(array($property_id, $start_date, $end_date, $tab_id)));
		}
		return $transient_name;
	}
	function awca_dashboard_help($var, $widget_data_array)
	{
		$widget_data = $widget_data_array['args'];
		if ($widget_data['1'] == 'line') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_line_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $widget_data['2'], $widget_data['3'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'bar') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_bar_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $widget_data['2'], $widget_data['3'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'doughnut') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_doughnut_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'stats') {
			$this->publish_stat_data_2($this->transient_value[$widget_data['4']]);
		}
		echo '<hr><b>Data Period:</b> Last 30 Days';
	}

	function awca_dashboard_help6()
	{
		if (is_multisite() && is_network_admin()) {
			$button_url = network_admin_url('admin.php?page=awca_pro_plugin_options');
		} else {
			$button_url = admin_url('admin.php?page=awca_pro_plugin_options');
		}
		?>
		<div class="awca-row valign-wrapper">
			<div class="awca-col s4">
				<img class="responsive-img small-plugin-image" src="<?php echo AWCA_URL . 'assests/images/AWCA.png'; ?>">
			</div>
			<div class="awca-col s8">
				<p><?php _e('You\'re almost there! Once you complete AWCA setup you start receiving different facts and reports from
					Google Analytics for Website Here.', 'awca-text'); ?> </p>
				<a href="<?php echo $button_url; ?>"
					class="button button-primary"><?php _e('Complete Setup', 'awca-text'); ?></a>
			</div>
		</div>
		<?php
	}
	/* Getting  Measurement Secret Key for GA4 Property */
	public function awca_get_mesurement_key()
	{
		$account_id = $this->get_ga_account_id();
		if (!empty($account_id) && (stripos($account_id, 'data') != false)) {
			$api = $this->get_google_management_api();
			$responses = $api->get_measurement_protocall($account_id);
			if (!empty($responses)) {
				$ga4_match = false;
				foreach ($responses->measurementProtocolSecrets as $response) {
					if ($response->displayName == 'AWCA_secret_Key') {
						$ga4_match = true;
						update_option('measurement_key', $response->secretValue);
						update_option('measurement_key_process', 'completed');
						break;
					}
				}
				if (!$ga4_match) {
					$this->awca_create_mesurement_key();
				}
			} else {
				$this->awca_create_mesurement_key();
			}
		}
	}
	/* Creating Measurement Secret Key for GA4 Property */
	public function awca_create_mesurement_key()
	{
		$account_id = $this->get_ga_account_id();
		if (!empty($account_id) && (stripos($account_id, 'data') != false)) {
			$api = $this->get_google_management_api();
			$response = $api->create_measurement_protocall($account_id);
			if (!empty($response->secretValue)) {
				update_option('measurement_key', $response->secretValue);
				update_option('measurement_key_process', 'completed');
			} else {
				$process_status = get_option('measurement_key_process');
				if (empty($process_status)) {
					update_option('measurement_key_process', 'retry');
				} else {
					update_option('measurement_key_process', 'completed_with_error');
				}
			}
		}
	}
	/* Creating Custom Dimensions for GA4 Property */
	public function awca_create_custom_dimension()
	{
		$account_id = $this->get_ga_account_id();
		$pieces = explode('/', $account_id);
		$property_id = $pieces[0] . '/' . $pieces[1];
		if (!empty($property_id) && (stripos($property_id, 'properties') !== false)) {
			$api = $this->get_google_management_api();
			$response = $api->create_custom_dimensions($property_id);
			$custom_dimension_generated = get_option('custom_dimension_generated');
			$custom_dimension_generated = (int) $custom_dimension_generated;
			if ($custom_dimension_generated > 1233) {
				update_option('custom_dimension_process', 'completed');
			} else {
				$process_status = get_option('custom_dimension_process');
				if (empty($process_status)) {
					update_option('custom_dimension_process', 'retry');
				} else {
					update_option('custom_dimension_process', 'completed_with_error');
				}
			}
		}
	}
	/* Update plugin settings for latest update */
	public function new_update_settings()
	{
		$awca_settings = AWCA_Settings::get_instance();
		$defaults_track = $awca_settings->init_awca_track_defaults();
		if (!get_option('awca_track_settings')) {
			update_option('awca_track_settings', $defaults_track);
		}
		$defaults_event = $awca_settings->init_awca_event_defaults();
		if (!get_option('awca_event_settings')) {
			update_option('awca_event_settings', $defaults_event);
		}
	}

	/* Un-link Google Analytics Account from Website */
	public function web_un_link()
	{
		check_ajax_referer('awca-un-link', 'security');
		delete_option('awca_access_token');
		delete_option('awca_refresh_token');
		delete_option('awca_auth_settings');
		delete_option('awca_granted_scopes');
		delete_option('measurement_key');
		delete_option('measurement_key_process');
		delete_option('custom_dimension_process');
		delete_option('dimension_key');
		delete_option('awca_refresh_token_fail');
		delete_option('ga_properties');
		wp_die();
	}
	/* getting current values of settings */
	public function get_current_dash_settings()
	{
		$awca_dash_settings = get_option('awca_dash_settings');
		if (empty($awca_dash_settings)) {
			$awca_dash_settings = $awca_settings->init_awca_dash_defaults();
		}
		/*$property_views = $this->get_analytics_property_views();
					if (is_array($property_views) && !empty($property_views)) {
						if (!isset($awca_dash_settings['report_view']) || !in_array($awca_dash_settings['report_view'], $property_views)) {
							$awca_dash_settings['report_view'] = $property_views[0]->id;
						}
					} else {
						$awca_dash_settings['report_view'] = false;
					}*/
		if ($awca_dash_settings['report_frame'] == 'Yesterday') {
			$awca_dash_settings['report_to'] = date('Y-m-d', strtotime('-1 day'));
			$awca_dash_settings['report_from'] = date('Y-m-d', strtotime('-1 day'));
		} elseif ($awca_dash_settings['report_frame'] == 'Last 7 days') {
			$awca_dash_settings['report_to'] = date('Y-m-d', strtotime('-1 day'));
			$awca_dash_settings['report_from'] = date('Y-m-d', strtotime('-7 day'));
		} elseif ($awca_dash_settings['report_frame'] == 'Current Year') {
			$awca_dash_settings['report_to'] = date('Y-m-d', strtotime('now'));
			$awca_dash_settings['report_from'] = date('Y') . '-01-01';
		} elseif ($awca_dash_settings['report_frame'] == 'Custom Range') {
			if (isset($awca_dash_settings['report_to']) && isset($awca_dash_settings['report_from'])) {
			} else {
				$awca_dash_settings['report_to'] = date('Y-m-d', strtotime('-1 day'));
				$awca_dash_settings['report_from'] = date('Y-m-d', strtotime('-30 day'));
			}
		} else {
			$awca_dash_settings['report_to'] = date('Y-m-d', strtotime('-1 day'));
			$awca_dash_settings['report_from'] = date('Y-m-d', strtotime('-30 day'));
		}
		return $awca_dash_settings;
	}

	/* publishing stat data on dashboard */
	public function publish_stat_data($stats_data, $stats_array, $currency_symbol)
	{
		if (is_array($stats_data) && !empty($stats_data)) {
			echo '<div class="awca-row">';
			foreach ($stats_data as $stat_name => $stat) {
				$stat[0] = round($stat[0], 4);
				if (!empty($stats_array[$stat_name][2])) {
					if ($stats_array[$stat_name][3]) {
						if ($stats_array[$stat_name][2] == 'money') {
							$stat_value = $currency_symbol . $stat[0];
						} else {
							$stat_value = $stats_array[$stat_name][2] . $stat[0];
						}
					} else {
						if ($stats_array[$stat_name][2] == '100%') {
							$stat_value = $stat[0] * 100 . '%';
						} else {
							$stat_value = $stat[0] . $stats_array[$stat_name][2];
						}
					}
				} else {
					$stat_value = $stat[0];
				}
				$comp_percentage = $this->stat_percentage_cal($stat[1], $stat[0]);
				$per_icon = $this->comp_icon_style_color($comp_percentage, $stats_array[$stat_name][4]);
				echo '<div class="awca-col xl3 l4 m6 s12">
									<div class="quick_stats">
										<div class="stat_icon valign-wrapper">
											<i class="small material-icons teal-text">' . $stats_array[$stat_name][0] . '</i>
											<p class="right right-align per-info">' . $comp_percentage . ' % </p>
										</div>
										<div class="valign-wrapper">
											<div>
												<div class="stat_value">
													<p>' . $stat_value . '</p>
												</div>
												<div class="stat_title">
													<p>' . $stats_array[$stat_name][1] . '</p>
												</div>
											</div>
											<div style="order: 2;margin-left: auto;">
												<i class="medium material-icons ' . $per_icon['color'] . '">' . $per_icon['style'] . '</i>
											</div>
										</div>	
									</div>
								</div>';
			}
			echo '</div>';
			echo '<div class="awca-row awca-flex">';
		}
	}

	/* publishing stat data on dashboard */
	public function publish_stat_data_2($stats_data)
	{
		global $woocommerce;
		if (class_exists('WooCommerce')) {
			$currency_symbol = get_woocommerce_currency_symbol();
		} else {
			$currency_symbol = '$';
		}
		if (empty($currency_symbol)) {
			$currency_symbol = '$';
		}
		$awca_dash_settings = $this->get_current_dash_settings();
		$tab_id = 'dash';
		if (!empty($awca_dash_settings['report_view'])) {
			$array_name = 'awca_dash_stats_data_' . $tab_id;
			$stats_array = AWCA_Settings::get_instance()->$array_name;
		} else {
			$array_name = 'awca_dash_stats_data_ga4_' . $tab_id;
			$stats_array = AWCA_Settings::get_instance()->$array_name;
		}
		if (is_array($stats_data) && !empty($stats_data)) {
			echo '<div class="awca-row">';
			foreach ($stats_data as $stat_name => $stat) {
				$stat[0] = round($stat[0], 4);
				if (!empty($stats_array[$stat_name][2])) {
					if ($stats_array[$stat_name][3]) {
						if ($stats_array[$stat_name][2] == 'money') {
							$stat_value = $currency_symbol . $stat[0];
						} else {
							$stat_value = $stats_array[$stat_name][2] . $stat[0];
						}
					} else {
						if ($stats_array[$stat_name][2] == '100%') {
							$stat_value = $stat[0] * 100 . '%';
						} else {
							$stat_value = $stat[0] . $stats_array[$stat_name][2];
						}
					}
				} else {
					$stat_value = $stat[0];
				}
				$comp_percentage = $this->stat_percentage_cal($stat[1], $stat[0]);
				$per_icon = $this->comp_icon_style_color($comp_percentage, $stats_array[$stat_name][4]);
				echo '<div class="awca-col s6">
									<div class="">
										<div class="valign-wrapper">
											<div>
												<div class="stat_value_2">
													<p>' . $stat_value . '</p>
												</div>
												<div class="stat_title">
													<p>' . $stats_array[$stat_name][1] . '</p>
												</div>
											</div>
											
										</div>
										<div class="stat_icon valign-wrapper">
											<p class="left left-align per-info-2">' . $comp_percentage . ' % </p>
											<i class="small material-icons ' . $per_icon['color'] . '">' . $per_icon['style'] . '</i>
										</div>	
									</div>
								</div>';
			}
			echo '</div>';
		}
	}

	/* providing compare icons and colors */
	public function comp_icon_style_color($percentage, $type)
	{
		if (is_float($percentage)) {
			if ($percentage == 0) {
				$per_icon['style'] = "trending_flat";
				$per_icon['color'] = "teal-text";
			} elseif ($percentage > 0) {
				$per_icon['style'] = "trending_up";
				if ($type) {
					$per_icon['color'] = "green-text";
				} else {
					$per_icon['color'] = "red-text";
				}
			} elseif ($percentage < 0) {
				$per_icon['style'] = "trending_down";
				if ($type) {
					$per_icon['color'] = "red-text";
				} else {
					$per_icon['color'] = "green-text";
				}
			}
		} else {
			if ($percentage == '--') {
				$per_icon['style'] = "trending_flat";
				$per_icon['color'] = "teal-text";
			} else {
				$per_icon['style'] = "trending_up";
				if ($type) {
					$per_icon['color'] = "green-text";
				} else {
					$per_icon['color'] = "red-text";
				}
			}
		}
		return $per_icon;
	}

	/* calculating percentage for stats */
	public function stat_percentage_cal($stat_old, $stat_new)
	{
		if ($stat_old == 0) {
			if ($stat_new > 0) {
				return "∞";
			} else {
				return "--";
			}
		} else {
			return round(((($stat_new - $stat_old) / $stat_old) * 100), 2);
		}
	}
	/* publishing simple table */
	public function publish_simple_table($location, $title, $xtitle, $ytitle, $chart_data, $description)
	{
		?>
		<div class="awca-box">
			<p class="awca-box-title">
				<?php echo $title; ?>
			</p>
			<p class="awca-box-title right"><i class="small material-icons teal-text">info</i></p>
			<p class="awca-box-description">
				<?php echo $description; ?>
			</p>
			<div id="<?php echo $location; ?>">
				<table>
					<thead>
						<tr>
							<th>Index</th>
							<th>
								<?php echo $xtitle; ?>
							</th>
							<?php if (is_array($ytitle)) {
								foreach ($ytitle as $title) {
									echo '<th>' . $title . '</th>';
								}
							} else {
								echo '<th>' . $ytitle . '</th>';
							} ?>
						</tr>
					</thead>
					<tbody>
						<?php
						$h = 1;
						foreach ($chart_data as $x => $y) {
							if (is_array($y)) {
								echo '<tr><td><span class="new badge white-text awca-badge" data-badge-caption="">' . $h . '</span></td><td>' . $x . '</td>';
								$i = 0;
								$j = 0;
								foreach ($y as $key => $element) {
									if (is_array($element)) {
										if ($i < 1) {
											foreach ($element as $key2 => $object) {
												echo '<td>' . round($y[$i][$j]->value, 2) . ' <span class="journey-per-info">' . $this->publish_compare_stats($y[$i][$j]->value, $y[$i + 1][$j]->value) . '</span></td>';
												$j++;
											}
											$j = 0;
										}
										$i++;
									} elseif (is_object($element)) {
										echo '<td>' . round($element->value, 2) . '</td>';
									}
									$i++;
								}
								echo '</tr>';
							} else {
								echo '<tr><td>' . $h . '</td><td>' . $x . '</td><td>' . round($y, 2) . '</td></tr>';
							}
							$h++;
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
	/*publishing compare data for device category */
	public function publish_compare_stats($data1, $data2)
	{
		if ((float) $data2 == 0) {
			if ((float) $data1 > 0) {
				return '∞ % <i class="small material-icons green-text">trending_up</i>';
			} else {
				return '- % <i class="small material-icons teal-text">trending_flat</i>';
			}
		} elseif ((float) $data2 > 0) {
			$stat_number = round((float) ((($data1 - $data2) / $data2) * 100), 2);
			if ($stat_number > 0) {
				$stat_return = (string) $stat_number . ' % <i class="small material-icons green-text">trending_up</i>';
			} elseif ($stat_number < 0) {
				$stat_return = (string) $stat_number . ' % <i class="small material-icons red-text">trending_down</i>';
			} else {
				$stat_return = (string) $stat_number . ' % <i class="small material-icons teal-text">trending_flat</i>';
			}
			return $stat_return;
		}
	}
	/* publishing simple bar chart */
	public function publish_simple_bar_chart($location, $title, $xtitle, $ytitle, $chart_data, $description)
	{
		if ($chart_data == false) {
			$chart_data = array();
		}
		foreach ($chart_data as $x => $y) {
			if (($title == 'Total Users on Date') || ($title == 'Overview Report')) {
				$xdate = date_format(date_create($x), 'd-M');
				$labels[] = $xdate;
			} else {
				$labels[] = $x;
			}
			if (is_array($y)) {
				$j = 0;
				foreach ($y as $z => $object) {
					$data[$j][] = round($object->value, 2);
					$j++;
				}
			} else {
				$data[] = round($y, 2);
			}
		}
		if ($title == 'Total Users on Date') {
			$len = (int) count($labels);
			$labels = array_slice($labels, $len / 2);
		}
		?>
		<div class="awca-box">
			<p class="awca-box-title">
				<?php echo $title; ?>
			</p>
			<p class="awca-box-title right"><i class="chart-info small material-icons teal-text">info</i></p>
			<p class="awca-box-description">
				<?php echo $description; ?>
			</p>
			<canvas id="<?php echo $location; ?>"></canvas>
		</div>
		<script>
			var ctx22 = document.getElementById('<?php echo $location; ?>');
			new Chart(<?php echo $location; ?>, {
				type: 'bar',
				options: {
					responsive: true,
					maintainAspectRatio: true,
				},
				data: {
					labels: <?php if (!empty($labels) && is_array($labels)) {
						echo json_encode($labels);
					} else {
						echo '[]';
					} ?>,
					<?php
					if (!empty($data)) {
						if (count($data) == count($data, COUNT_RECURSIVE)) {
							if ($title == 'Total Users on Date') {
								$len2 = (int) count($data);
								$data = array_slice($data, $len2 / 2);
							} ?>
																															datasets: [{
								label: '<?php echo $ytitle; ?>',
								data: <?php echo json_encode($data); ?>,
								borderWidth: 1
							}]
																								<?php } else {
							echo 'datasets: [';
							$j = 0;
							foreach ($data as $key => $array) {
								if ($title == 'Total Users on Date') {
									$len2 = (int) count($array);
									$array = array_slice($array, $len2 / 2);
								} ?>
																																{
								label: '<?php echo is_array($ytitle) ? $ytitle[$j] : $ytitle; ?>',
								data: <?php echo json_encode($array); ?>,
								borderWidth: 2
							},
							<?php $j++;
							}
							echo '],';
						}
					} else { ?>
																								datasets: [
					{
						label: '<?php echo $ytitle; ?>',
						data: [],
						borderWidth: 2
					},
				],
				<?php } ?>	
																				},
				options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
																			});
		</script>
		<?php
	}
	/* publishing simple line chart */
	public function publish_simple_line_chart($location, $title, $xtitle, $ytitle, $chart_data, $description)
	{
		if ($chart_data == false) {
			$chart_data = array();
		}
		foreach ($chart_data as $x => $y) {
			if (($title == 'Total Users on Date') || ($title == 'Overview Report')) {
				$xdate = date_format(date_create($x), 'd-M');
				$labels[] = $xdate;
			} else {
				$labels[] = $x;
			}
			if (is_array($y)) {
				$j = 0;
				foreach ($y as $z => $object) {
					$data[$j][] = round($object->value, 2);
					$j++;
				}
			} else {
				$data[] = round($y, 2);
			}
		}
		if ($title == 'Total Users on Date') {
			$len = (int) count($labels);
			$labels = array_slice($labels, $len / 2);
		}
		?>
		<div class="awca-box">
			<p class="awca-box-title">
				<?php echo $title; ?>
			</p>
			<p class="awca-box-title right"><i class="chart-info small material-icons teal-text">info</i></p>
			<p class="awca-box-description">
				<?php echo $description; ?>
			</p>
			<canvas id="<?php echo $location; ?>"></canvas>
		</div>
		<script>
			var ctx22 = document.getElementById('<?php echo $location; ?>');
			new Chart(<?php echo $location; ?>, {
				type: 'line',
				options: {
					responsive: true,
					maintainAspectRatio: true,
				},
				data: {
					labels: <?php if (!empty($labels) && is_array($labels)) {
						echo json_encode($labels);
					} else {
						echo '[]';
					} ?>,
					<?php
					if (!empty($data)) {
						if (count($data) == count($data, COUNT_RECURSIVE)) {
							if ($title == 'Total Users on Date') {
								$len2 = (int) count($data);
								$data = array_slice($data, $len2 / 2);
							} ?>
																															datasets: [{
								label: '<?php echo $ytitle; ?>',
								data: <?php echo json_encode($data); ?>,
								borderWidth: 1
							}]
																								<?php } else {
							echo 'datasets: [';
							$j = 0;
							foreach ($data as $key => $array) {
								if ($title == 'Total Users on Date') {
									$len2 = (int) count($array);
									$array = array_slice($array, $len2 / 2);
								} ?>
																																{
								label: '<?php echo $ytitle[$j]; ?>',
								data: <?php echo json_encode($array); ?>,
								borderWidth: 2
							},
							<?php $j++;
							}
							echo '],';
						}
					} else {
						$j = 0; ?>
																								datasets: [
					{
						label: '<?php echo is_array($ytitle) ? $ytitle[$j] : $ytitle; ?>',
						data: [],
						borderWidth: 2
					},
				],
				<?php } ?>	
																				},
				options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
																			});
		</script>
		<?php
	}
	/* publish simple pie chart */
	public function publish_simple_doughnut_chart($location, $title, $chart_data, $description)
	{
		if ($chart_data == false) {
			$chart_data = array();
		}
		foreach ($chart_data as $x => $y) {
			$labels[] = $x;
			if (is_array($y)) {
				$j = 0;
				foreach ($y as $z => $object) {
					$data[$j][] = round($object->value, 2);
					$j++;
				}
			} else {
				$data[] = round($y, 2);
			}
		} ?>
		<div class="awca-box">
			<p class="awca-box-title tooltipped" onclick="alert('this is new');" data-position="bottom"
				data-tooltip="I am a tooltip">
				<?php echo $title; ?>
			</p>
			<p class="awca-box-title right"><i class="small material-icons teal-text tooltipped" data-position="bottom"
					data-tooltip="I am a tooltip">info</i></p>
			<p class="awca-box-description">
				<?php echo $description; ?>
			</p>
			<canvas id="<?php echo $location; ?>"></canvas>
		</div>
		<script>
			var ctx22 = document.getElementById('<?php echo $location; ?>');

			new Chart(<?php echo $location; ?>, {
				type: 'doughnut',
				options: {
					responsive: true,
				},
				data: {
					labels: <?php if (!empty($labels) && is_array($labels)) {
						echo json_encode($labels);
					} else {
						echo '[]';
					} ?>,
					<?php
					if (!empty($data)) {
						if (count($data) == count($data, COUNT_RECURSIVE)) { ?>
																																			datasets: [{
								data: <?php echo json_encode($data); ?>,
								backgroundColor: [
									'rgb(255, 99, 132)',
									'rgb(54, 162, 235)',
									'rgb(255, 205, 86)'
								],
								hoverOffset: 4
							}]
																											<?php } else {
							echo 'datasets: [';
							$j = 0;
							foreach ($data as $key => $array) { ?>
																																													{
								data: <?php echo json_encode($array); ?>,
								backgroundColor: [
									'rgb(255, 99, 132)',
									'rgb(54, 162, 235)',
									'rgb(255, 205, 86)'
								],
								hoverOffset: 4
							},
							<?php $j++;
							}
							echo '],';
						}
					} else { ?>
																							datasets: [
					{
						data: [],
						backgroundColor: [
							'rgb(255, 99, 132)',
							'rgb(54, 162, 235)',
							'rgb(255, 205, 86)'
						],
						hoverOffset: 4
					},
				],
				<?php } ?>	 
																				},
																			});
		</script>
		<?php
	}

	public function publish_simple_funnel_chart($location, $title, $xtitle, $ytitle, $chart_data, $description)
	{
		if ($chart_data == false) {
			$chart_data = array();
		}
		$data = array(0, 0, 0, 0);
		foreach ($chart_data as $device_category) {
			$j = 0;
			foreach ($device_category as $date_range) {
				$i = 0;
				if ($j < 1) {
					foreach ($date_range as $object) {
						$data[$i] = $data[$i] + $device_category[0][$i]->value;
						$i++;
					}
				}
				$j++;
			}
		}
		?>
		<div class="awca-box">
			<p class="awca-box-title tooltipped" onclick="alert('this is new');" data-position="bottom"
				data-tooltip="I am a tooltip">
				<?php echo $title; ?>
			</p>
			<p class="awca-box-title right"><i class="small material-icons teal-text tooltipped" data-position="bottom"
					data-tooltip="I am a tooltip">info</i></p>
			<p class="awca-box-description">
				<?php echo $description; ?>
			</p>
			<canvas style="margin-bottom:60px" id="<?php echo $location; ?>"></canvas>
			<?php $this->publish_simple_table($location . '-table', 'Device Category Based Purchase Journey Report', $xtitle, $ytitle, $chart_data, $description); ?>
		</div>
		<script>
			var funnelChart = new Chart(<?php echo $location; ?>, {
				type: 'bar', // Using 'bar' to simulate funnel chart
				data: {
					labels: <?php if (!empty($ytitle) && is_array($ytitle)) {
						echo json_encode($ytitle);
					} else {
						echo '[]';
					} ?>,
					datasets: [{
						label: 'Funnel Report',
						data: <?php if (!empty($data) && is_array($data)) {
							echo json_encode($data);
						} else {
							echo '[]';
						} ?>, // Decreasing values to represent the funnel effect
						backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
					}]
				},
				options: {
					indexAxis: 'x', // Display bars horizontall
					plugins: {
						legend: {
							display: false
						}
					}
				}
			});
		</script>
		<?php
	}

	/* updating content of tab using ajax */
	public function tab_update()
	{
		check_ajax_referer('awca-tab-update', 'security');
		$tab_id = str_replace(array('#', '-tab'), '', $_POST['tab']);
		update_option('awca_current_tab_id', $tab_id);
		if (stripos($tab_id, 'et-')) {
			if (stripos($tab_id, 'track')) {
				$awca_settings = AWCA_Settings::get_instance();
				$defaults = $awca_settings->init_awca_track_defaults();
				if (!get_option('awca_track_settings')) {
					$awca_track_settings = $defaults;
					update_option('awca_track_settings', $defaults);
				} else {
					$awca_track_settings = get_option('awca_track_settings');
				} ?>
				<form action="" method="POST">
					<div class="awca-row">
						<div class="awca-col s12">
							<div class="input-field awca-col m6 s12">
								<h6>
									<?php _e('General Tracking settings', 'awca-text'); ?>
								</h6>
								<p>
									<label>
										<input type="checkbox" name="awca_track_settings[track_admin]"
											id="awca_track_settings[track_admin]" value="yes" <?php checked(isset($awca_track_settings['track_admin']) && $awca_track_settings['track_admin']); ?> />
										<span>
											<?php _e('Track Admins?', 'awca-text'); ?>
										</span>
									</label>
									<span class="helper-text" data-error="wrong" data-success="right">
										<?php _e('Track Admin activity on Website.', 'awca-text'); ?>
									</span>
								</p>
								<p>
									<label>
										<input type="checkbox" name="awca_track_settings[not_track_pageviews]"
											id="awca_track_settings[not_track_pageviews]" value="yes" <?php checked(isset($awca_track_settings['not_track_pageviews']) && $awca_track_settings['not_track_pageviews']); ?> />
										<span>
											<?php _e('Do not Track Pageviews', 'awca-text'); ?>
										</span>
									</label>
									<span class="helper-text" data-error="wrong" data-success="right">
										<?php _e('Stop tracking basic pageviews for website.', 'awca-text'); ?>
									</span>
								</p>
								<p>
									<label>
										<input type="checkbox" name="awca_track_settings[enhanced_link_attribution]"
											id="awca_track_settings[enhanced_link_attribution]" value="yes" <?php checked(isset($awca_track_settings['enhanced_link_attribution']) && $awca_track_settings['enhanced_link_attribution']); ?> />
										<span>
											<?php _e('Use Enhanced Link Attribution', 'awca-text'); ?>
										</span>
									</label>
									<span class="helper-text" data-error="wrong" data-success="right">
										<?php _e('Differenciating Links user interacted using enhanced link attribution.', 'awca-text'); ?>
									</span>
								</p>
							</div>
							<div class="input-field awca-col m6 s12">
								<?php
								if (class_exists('WooCommerce')) { ?>
									<h6>
										<?php _e('WooCommerce Settings', 'awca-text'); ?>
									</h6>
									<p>
										<label>
											<input type="checkbox" name="awca_track_settings[product_single_track]"
												id="awca_track_settings[product_single_track]" value="yes" <?php checked(isset($awca_track_settings['product_single_track']) && $awca_track_settings['product_single_track']); ?> />
											<span>
												<?php _e('On Single Product Pages', 'awca-text'); ?>
											</span>
										</label>
										<span class="helper-text" data-error="wrong" data-success="right">
											<?php _e('Tracking product impressions on Single Product pages.', 'awca-text'); ?>
										</span>
									</p>
									<p>
										<label>
											<input type="checkbox" name="awca_track_settings[product_archive_track]"
												id="awca_track_settings[product_archive_track]" value="yes" <?php checked(isset($awca_track_settings['product_archive_track']) && $awca_track_settings['product_archive_track']); ?> />
											<span>
												<?php _e('On Archive Pages', 'awca-text'); ?>
											</span>
										</label>
										<span class="helper-text" data-error="wrong" data-success="right">
											<?php _e('Tracking product impressions on Archive pages.', 'awca-text'); ?>
										</span>
									</p>
									<p>
										<label>
											<input type="checkbox" name="awca_track_settings[disable_on_hold_conversion]"
												id="awca_track_settings[disable_on_hold_conversion]" value="yes" <?php checked(isset($awca_track_settings['disable_on_hold_conversion']) && $awca_track_settings['disable_on_hold_conversion']); ?> />
											<span>
												<?php _e('Disable On Hold Transctions as Conversions', 'awca-text'); ?>
											</span>
										</label>
										<span class="helper-text" data-error="wrong" data-success="right">
											<?php _e('It will not consider on-hold transcations as conversions.', 'awca-text'); ?>
										</span>
									</p>
									<?php
								} ?>
							</div>
						</div>
					</div>
					<div class="awca-row">
						<div class="input-field awca-col m6 s12">
							<h6>GDPR Compliance</h6>
							<p>
								<label>
									<input type="checkbox" name="awca_track_settings[anonymize_ip]"
										id="awca_track_settings[anonymize_ip]" value="yes" <?php checked(isset($awca_track_settings['anonymize_ip']) && $awca_track_settings['anonymize_ip']); ?> />
									<span>
										<?php _e('Anonymize IP addresses', 'awca-text'); ?>
									</span>
								</label>
								<span class="helper-text" data-error="wrong" data-success="right">
									<?php _e('Anonymizes the ip address of website users.', 'awca-text'); ?>
								</span>
							</p>
							<p>
								<label>
									<input type="checkbox" name="awca_track_settings[track_interest]"
										id="awca_track_settings[track_interest]" value="yes" <?php checked(isset($awca_track_settings['track_interest']) && $awca_track_settings['track_interest']); ?> />
									<span>
										<?php _e('Disable Demographics and Interest for Ads Remarketing', 'awca-text'); ?>
									</span>
								</label>
								<span class="helper-text" data-error="wrong" data-success="right">
									<?php _e('Disable tracking User and associated activity on bases of interest and demography', 'awca-text'); ?>
								</span>
							</p>
						</div>
						<div class="input-field awca-col m6 s12">
							<p>
								<label>
									<input type="checkbox" name="awca_track_settings[not_track_user_id]"
										id="awca_track_settings[not_track_user_id]" value="yes" <?php checked(isset($awca_track_settings['not_track_user_id']) && $awca_track_settings['not_track_user_id']); ?> />
									<span>
										<?php _e('Do not Track User ID', 'awca-text'); ?>
									</span>
								</label>
								<span class="helper-text" data-error="wrong" data-success="right">
									<?php _e('Do not Track User and associated activity by their Ids.', 'awca-text'); ?>
								</span>
							</p>
							<p>
								<label>
									<input type="checkbox" name="awca_track_settings[track_ga_consent]"
										id="awca_track_settings[track_ga_consent]" value="yes" <?php checked(isset($awca_track_settings['track_ga_consent']) && $awca_track_settings['track_ga_consent']); ?> />
									<span>
										<?php _e('Enable Google Consent Mode', 'awca-text'); ?>
									</span>
								</label>
								<span class="helper-text" data-error="wrong" data-success="right">
									<?php _e('Based on User Consent Google will collect data, otherwise by default it will not collect sensitive data', 'awca-text'); ?>
								</span>
							</p>
						</div>
					</div>
					<div class="divider top-mar"></div>
					<div class="awca-row center-align">
						<button class="btn waves-effect waves-light top-mar-30" type="submit"
							name="awca_track_settings[awca_track_submit]" value="submit">
							<?php _e('Save Tracking Options', 'awca-text'); ?>
						</button>
					</div>
					<?php wp_nonce_field('awca_track_submit', 'awca_nonce_header'); ?>
				</form>
			<?php } elseif (stripos($tab_id, 'advanced')) {
				$awca_settings = AWCA_Settings::get_instance();
				$defaults = $awca_settings->init_awca_advance_defaults();
				if (!get_option('awca_advance_settings')) {
					$awca_advance_settings = $defaults;
				} else {
					$awca_advance_settings = get_option('awca_advance_settings');
				} ?>
				<form action="" method="POST">
					<div class="awca-row">
						<div class="awca-col s12">
							<div class="input-field awca-col m6">
								<h6>
									<?php _e('FaceBook Pixel', 'awca-text'); ?>
								</h6>
								<p>
									<label>
										<input type="checkbox" name="awca_advance_settings[facebook_pixel]"
											id="awca_advance_settings[facebook_pixel]" value="yes" <?php checked(isset($awca_advance_settings['facebook_pixel']) && $awca_advance_settings['facebook_pixel']); ?> />
										<span>
											<?php _e('Enable Facebook Pixel', 'awca-text'); ?>
										</span>
									</label>
								</p>
								<div class="input-field">
									<input placeholder="XXXXXXXXXX" id="awca_advance_settings[facebook_pixel_code]"
										name="awca_advance_settings[facebook_pixel_code]" type="text" value="<?php if (isset($awca_advance_settings['facebook_pixel_code'])) {
											echo $awca_advance_settings['facebook_pixel_code'];
										} ?>" class="validate">
									<span class="helper-text" data-error="wrong" data-success="right"><a
											href="https://advancedwcanalytics.com/conversion/get-facebook-pixel-code" target="_blank">
											<?php _e('How to get your Facebook pixel code?', 'awca-text'); ?>
										</a></span>
								</div>
								<div style="padding:10px 0px"></div>
								<h6>
									<?php _e('Google Analytics Debug Mode', 'awca-text'); ?>
								</h6>
								<p>
									<label>
										<input type="checkbox" name="awca_advance_settings[google_analytics_debug_mode]"
											id="awca_advance_settings[google_analytics_debug_mode]" value="yes" <?php checked(isset($awca_advance_settings['google_analytics_debug_mode']) && $awca_advance_settings['google_analytics_debug_mode']); ?> />
										<span>
											<?php _e('Enable Google Analytics Debug Mode', 'awca-text'); ?>
										</span>
									</label>
									<span class="helper-text" data-error="wrong" data-success="right"><a
											href=" https://advancedwcanalytics.com/google-analytics-debug-mode/" target="_blank">
											<?php _e('Know More About Google Debug Mode', 'awca-text'); ?>
										</a></span>
								</p>
								<div style="padding:10px 0px"></div>
							</div>
							<div class="input-field awca-col m6">
								<h6>
									<?php _e('Measurement Protocol API secrets (For Accurate Data Tracking)', 'awca-text'); ?>
								</h6>
								<?php
								$measurement_key = get_option('measurement_key');
								if (!empty($measurement_key)) {
									$awca_advance_settings['google_measurement_api'] = $measurement_key;
								}
								?>
								<div class="input-field">
									<input placeholder="XXXXXXXXXXXXXXXXXXXXXXX" id="awca_advance_settings[google_measurement_api]"
										name="awca_advance_settings[google_measurement_api]" type="text" value="<?php if (isset($awca_advance_settings['google_measurement_api'])) {
											echo $awca_advance_settings['google_measurement_api'];
										} ?>" class="validate">
									<span class="helper-text" data-error="wrong" data-success="right"><a
											href=" https://advancedwcanalytics.com/create-google-measurement-api-secrets"
											target="_blank">
											<?php _e('How to get your Measurement Protocol API secrets keys?', 'awca-text'); ?>
										</a></span>
								</div>

								<h6>
									<?php _e('Google Adwords', 'awca-text'); ?>
								</h6>
								<p>
									<label>
										<input type="checkbox" name="awca_advance_settings[google_adword]"
											id="awca_advance_settings[google_adword]" value="yes" <?php checked(isset($awca_advance_settings['google_adword']) && $awca_advance_settings['google_adword']); ?> />
										<span>
											<?php _e('Enable Google Adwords Conversion Tracking', 'awca-text'); ?>
										</span>
									</label>
								</p>
								<div class="input-field">
									<input placeholder="AW-CONVERSION_ID" id="awca_advance_settings[google_adword_code]"
										name="awca_advance_settings[google_adword_code]" type="text" value="<?php if (isset($awca_advance_settings['google_adword_code'])) {
											echo $awca_advance_settings['google_adword_code'];
										} ?>" class="validate">
								</div>
								<div class="input-field">
									<input placeholder="AW-CONVERSION_LABEL" id="awca_advance_settings[google_adword_label]"
										name="awca_advance_settings[google_adword_label]" type="text" value="<?php if (isset($awca_advance_settings['google_adword_label'])) {
											echo $awca_advance_settings['google_adword_label'];
										} ?>" class="validate">
									<span class="helper-text" data-error="wrong" data-success="right"><a
											href="https://advancedwcanalytics.com/conversion/get-google-ads-conversion-id-label"
											target="_blank">
											<?php _e('How to get your Google Ads Conversion ID and Label?', 'awca-text'); ?>
										</a></span>
								</div>
								<div style="padding:10px 0px"></div>
							</div>
						</div>
						<div class="divider" style="margin-top:15px"></div>
						<div class="awca-row center-align">
							<button class="btn waves-effect waves-light" style="margin-top:30px" type="submit" value="submit"
								name="awca_advance_submit">Save Advance Settings</button>
						</div>
						<?php wp_nonce_field('awca_advance_submit', 'awca_nonce_header'); ?>
				</form>
				<?php
			} elseif (stripos($tab_id, 'events')) {
				/* getting event settings */
				$awca_settings = AWCA_Settings::get_instance();
				$defaults = $awca_settings->init_awca_events_defaults();
				if (!get_option('awca_event_settings')) {
					$awca_event_settings = $defaults;
				} else {
					$awca_event_settings = get_option('awca_event_settings');
				}
				?>
				<form action="" method="POST">
					<div class="awca-row">
						<div class="awca-col s12">
							<?php
							foreach ($defaults as $key => $value) { ?>
								<div class="switch">
									<p class="awca-col m6 s12">
										<label>
											<input type="checkbox" name="awca_event_settings[<?php echo $key; ?>]"
												id="awca_event_settings[<?php echo $key; ?>]" value="yes" <?php checked(isset($awca_event_settings[$key]) && $awca_event_settings[$key]); ?> />
											<span class="lever"></span>
											<?php echo ucwords(str_replace("_", " ", $key)); ?>
										</label>
									</p>
								</div>
							<?php } ?>
						</div>
						<div class="clearfix"></div>
						<div class="divider top-mar"></div>
					</div>
					<div class="awca-row center-align">
						<button class="btn waves-effect waves-light top-mar" type="submit" value="submit"
							name="awca_event_settings[awca_event_submit]">
							<?php _e('Save Events Options', 'awca-text'); ?>
						</button>
					</div>
					<?php wp_nonce_field('awca_event_submit', 'awca_nonce_header'); ?>
				</form>
				<?php
			} elseif (stripos($tab_id, 'dashboard')) {
				/* getting event settings */
				$awca_settings = AWCA_Settings::get_instance();
				$defaults = $awca_settings->init_awca_dashboard_defaults();
				if (!get_option('awca_dashboard_settings')) {
					$awca_dashboard_settings = $defaults;
				} else {
					$awca_dashboard_settings = get_option('awca_dashboard_settings');
				}
				?>
				<form action="" method="POST">
					<div class="awca-row">
						<div class="awca-col s12">
							<?php
							foreach ($defaults as $key => $value) { ?>
								<div class="switch">
									<p class="awca-col m6 s12">
										<label>
											<input type="checkbox" name="awca_dashboard_settings[<?php echo $key; ?>]"
												id="awca_dashboard_settings[<?php echo $key; ?>]" value="yes" <?php checked(isset($awca_dashboard_settings[$key]) && $awca_dashboard_settings[$key]); ?> />
											<span class="lever"></span>
											<?php echo ucwords(str_replace("_", " ", $key)); ?>
										</label>
									</p>
								</div>
							<?php } ?>
						</div>
						<div class="clearfix"></div>
						<div class="divider top-mar"></div>
					</div>
					<div class="awca-row center-align">
						<button class="btn waves-effect waves-light top-mar" type="submit" value="submit"
							name="awca_dashboard_settings[awca_dashboard_submit]">
							<?php _e('Save Dashboard Options', 'awca-text'); ?>
						</button>
					</div>
					<?php wp_nonce_field('awca_dashboard_submit', 'awca_nonce_header'); ?>
				</form>
				<?php
			}
		} else {
			$property_id = $this->get_ga_property_id();
			$awca_dash_settings = $this->get_current_dash_settings();
			if (class_exists('WooCommerce')) {
				$currency_symbol = get_woocommerce_currency_symbol();
			} else {
				$currency_symbol = '$';
			}
			if (!empty($awca_dash_settings['report_view'])) {
				$new_api = $this->get_google_report_api();
				$transient_name = $this->awca_create_transient_name($awca_dash_settings['report_view'], $property_id, $awca_dash_settings['report_from'], $awca_dash_settings['report_to'], $tab_id);
				$transient_value = get_transient($transient_name);
				if ($transient_value == false) {
					$transient_value = $new_api->get_dashboard_data($awca_dash_settings['report_view'], $awca_dash_settings['report_from'], $awca_dash_settings['report_to'], $tab_id);
					if (isset($transient_value) && is_array($transient_value)) {
						set_transient($transient_name, $transient_value, 3600);
					}

				}
				$stats_data = $transient_value;
				$array_name = 'awca_report_chart_data_' . $tab_id;
				$chart_data_array = AWCA_Settings::get_instance()->$array_name;
			} else {
				$new_api = $this->get_google_analytics_data_api();
				$transient_name = $this->awca_create_transient_name(false, $property_id, $awca_dash_settings['report_from'], $awca_dash_settings['report_to'], $tab_id);
				$transient_value = get_transient($transient_name);
				if ($transient_value == false) {
					$transient_value = $new_api->get_dashboard_data($awca_dash_settings['report_from'], $awca_dash_settings['report_to'], $tab_id);
					if (isset($transient_value) && is_array($transient_value)) {
						set_transient($transient_name, $transient_value, 3600);
					}

				}
				$stats_data = $transient_value;
				$array_name = 'awca_report_chart_data_ga4_' . $tab_id;
				$chart_data_array = AWCA_Settings::get_instance()->$array_name;
			}
			$i = 0;
			if (!empty($stats_data) && !empty($chart_data_array)) {
				foreach ($chart_data_array as $chart_name => $chart_parameters) {
					if ($chart_name == 'stats') {
						if (!empty($awca_dash_settings['report_view'])) {
							$array_name = 'awca_dash_stats_data_' . $tab_id;
							$stats_array = AWCA_Settings::get_instance()->$array_name;
						} else {
							$array_name = 'awca_dash_stats_data_ga4_' . $tab_id;
							$stats_array = AWCA_Settings::get_instance()->$array_name;
						}
						$this->publish_stat_data($stats_data[$i], $stats_array, $currency_symbol);
					} else {
						if ($chart_parameters[0] == 'bar') {
							if (($chart_parameters[1] == 'Total Users on Date') || ($chart_parameters[1] == 'Overview Report')) {
								echo '<div class="awca-col l12 s12 chart_box">';
							} else {
								echo '<div class="awca-col l6 s12 chart_box">';
							}
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_bar_chart("chartdiv" . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'line') {
							if (($chart_parameters[1] == 'Total Users on Date') || ($chart_parameters[1] == 'Overview Report')) {
								echo '<div class="awca-col l12 s12 chart_box">';
							} else {
								echo '<div class="awca-col l6 s12 chart_box">';
							}
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_line_chart("chartdiv" . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'doughnut') {
							echo '<div class="awca-col l6 s12 chart_box">';
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_doughnut_chart("chartdiv" . $i . $tab_id, $chart_parameters[1], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'table') {
							echo '<div class="awca-col l6 s12 chart_box">';
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_table("chartdiv" . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'large_table') {
							echo '<div class="awca-col l12 s12 chart_box">';
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_table("chartdiv" . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'funnel') {
							echo '<div class="awca-col l12 s12 chart_box">';
							if (is_array($stats_data[$i])) {
								//$stats_data[$i] = array_reverse($stats_data[$i]);
							} else {
								$stats_data[$i] = array();
							}
							$this->publish_simple_funnel_chart("chartdiv" . $i + 1 . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						}
					}
					$i++;
				}
			} else {
				echo '<script>location.reload();</script>';
			}
			echo '</div>';
		}
		wp_die();
	}


	/* revoke google analytics access from website */
	public function web_revoke_access()
	{
		check_ajax_referer('awca-revoke-access', 'security');
		$url = $this->get_access_token_revoke_url();
		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			error_log(sprintf('Could not revoke access token: %s', json_encode($response->errors)), 0);
		}
		delete_option('awca_access_token');
		delete_option('awca_refresh_token');
		delete_option('awca_auth_settings');
		delete_option('awca_granted_scopes');
		delete_option('measurement_key');
		delete_option('measurement_key_process');
		delete_option('custom_dimension_process');
		delete_option('dimension_key');
		delete_option('awca_refresh_token_fail');
		delete_option('ga_properties');
		wp_die();
	}

	/* Adding localize script */
	public function load_local_script()
	{
		if (isset($_GET['tab']) && !empty($_GET['tab'])) {
			$tab_id = str_replace(array('#', '-tab'), '', $_GET['tab']);
			update_option('awca_current_tab_id', $tab_id);
		}
		wp_register_script('awca_js', AWCA_URL . 'assests/js/awca.js', array('jquery'), null, true);
		wp_localize_script(
			'awca_js',
			'awca_js_object',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'auth_url' => $this->get_link_url(),
				'revoke_access_nonce' => wp_create_nonce('awca-revoke-access'),
				'un_link_nonce' => wp_create_nonce('awca-un-link'),
				'tab_update_nonce' => wp_create_nonce('awca-tab-update'),
				'current_tab_id' => get_option('awca_current_tab_id', 'audience'),
			)
		);
		wp_enqueue_script('awca_js');
		if (isset($_GET['page']) && ($_GET['page'] == 'awca_pro_plugin_options')) {
			$this->awca_edit_action_scope();
		}
	}

	/* parsing access token */
	private function parse_access_token($json_token = '')
	{
		$token = [
			'access_token' => '',
			'expires_in' => 0,
			'created' => current_time('timestamp', true),
		];
		if (is_string($json_token) && '' !== $json_token) {
			$token = wp_parse_args((array) json_decode($json_token), $token);
		}
		return (object) $token;
	}

	/*get access token from database */
	public function get_access_token()
	{
		return get_option('awca_access_token', null);
	}

	/* get revoke url for revoking access token */
	public function get_access_token_revoke_url()
	{
		$revoke_url = null;
		$token = $this->parse_access_token($this->get_access_token())->access_token;
		if (isset($token) && !empty($token)) {
			$revoke_url = self::PROXY_URL . '/auth/revoke?token2=' . base64_encode($token);
		}
		return $revoke_url;
	}

	/* get linking url for linking Google Analytics and Website */
	public function get_link_url()
	{
		return self::PROXY_URL . '/auth?callback=' . urlencode($this->get_callback_url());
	}

	/* get call back url for completing rest work of linking */
	public function get_callback_url()
	{
		return get_home_url(null, 'wc-api/awca-analytics/auth');
	}

	/* get call back url for get refreshed token */
	public function get_refresh_callback_url()
	{
		return get_home_url(null, 'wc-api/awca-analytics/refresh');
	}

	/* authentication function */
	public function awca_authenticate()
	{
		if (isset($_GET['access_token'])) {
			if (!isset($_REQUEST['access_token']) || empty($_REQUEST['access_token'])) {
				echo '<script>setTimeout(function(){window.opener.auth_callback();}, 6000);</script>';
				wp_die('some error occured please try again after some time or use manual connect method');
			}
			$json_token = sanitize_text_field(base64_decode($_REQUEST['access_token'], true));
			$token = json_decode($json_token, true);
			if (!$token) {
				echo '<script>setTimeout(function(){window.opener.auth_callback();}, 6000);</script>';
				wp_die('some error occured please try again after some time or use manual connect method');
			}
			//if we receive require data from token then save value
			if (isset($token['refresh_token']) && isset($token['access_token'])) {
				$this->awca_granted_scopes($token['scope']);
				update_option('awca_access_token', $json_token);
				update_option('awca_refresh_token', $token['refresh_token']);
			}
			echo '<script>window.opener.auth_callback();</script>';
			wp_die();
		}
	}
	/* Save granted scopes and use later for recheck */
	public function awca_granted_scopes($scopes)
	{
		if (!empty($scopes) && (stripos($scopes, 'googleapis') !== false)) {
			$scopes = explode(' ', $scopes);
			update_option('awca_granted_scopes', $scopes);
		}
	}
	/* getting tracking id and account id information */
	private function get_ga_property_part($key)
	{
		$auth_settings = get_option('awca_auth_settings');
		$property = $auth_settings['property_id'];
		if (!$property) {
			return;
		}
		$pieces = explode('|', $property);
		if (!isset($pieces[$key])) {
			return;
		}
		return $pieces[$key];
	}

	/* getting account id */
	public function get_ga_account_id()
	{
		return $this->get_ga_property_part(0);
	}

	/* getting property id */
	public function get_ga_property_id()
	{
		return $this->get_ga_property_part(1);
	}

	/* getting list of ga4 properties */
	public function get_analytics_g4_properties()
	{
		$g4_account_summary = array();
		if ($this->get_access_token()) {
			$api = $this->get_google_management_api();
			$g4_account_summary_object = $api->get_g4_account_summaries();
			if (!empty($g4_account_summary_object)) {
				$g4_account_summary = $g4_account_summary_object->accountSummaries;
			} else {
				try {
					$token = $this->refresh_access_token();
				} catch (Exception $e) {
					error_log($e->getMessage());
					$token = $this->parse_access_token();
				}
			}
		}
		return $g4_account_summary;
	}

	/* getting list of properties 
		  public function get_analytics_properties()
		  {
			  $list_account_summaries = array();
			  if ($this->get_access_token()) {
				  $api = $this->get_google_management_api();
				  $account_summary_object = $api->get_account_summaries();
				  if (!empty($account_summary_object)) {
					  $list_account_summaries = $api->list_account_summaries($account_summary_object);
				  } else {
					  try {
						  $token = $this->refresh_access_token();
					  } catch (Exception $e) {
						  error_log($e->getMessage());
						  $token = $this->parse_access_token();
					  }
				  }
			  }
			  return $list_account_summaries;
		  }

		  /* getting list of views for property 
		  public function get_analytics_property_views()
		  {
			  $list_property_views = array();
			  $account_id = $this->get_ga_account_id();
			  $property_id = $this->get_ga_property_id();
			  if ($this->get_access_token() && !empty($property_id) && (strpos($property_id, 'UA') !== false) && !empty($account_id)) {
				  $api = $this->get_google_management_api();
				  $property_view_summary = $api->get_property_views($account_id, $property_id);
				  $list_property_views = $api->list_views($property_view_summary);
			  }
			  return $list_property_views;
		  }

		  /* getting management api */
	public function get_google_management_api()
	{
		require_once(AWCA_DIR . 'inc/api/awca-google-management-api.php');
		$token = $this->parse_access_token($this->get_access_token());
		// refresh token if it's expired
		if ($this->is_access_token_expired($token)) {
			try {
				$token = $this->refresh_access_token();
			} catch (Exception $e) {
				error_log($e->getMessage());
				$token = $this->parse_access_token();
			}
		}
		return $this->management_api = new AWCA_Google_Management_API($token->access_token);
	}

	/* getting report api */
	public function get_google_report_api()
	{
		require_once(AWCA_DIR . 'inc/api/awca-google-report-api.php');
		$token = $this->parse_access_token($this->get_access_token());
		// refresh token if it's expired
		if ($this->is_access_token_expired($token)) {
			try {
				$token = $this->refresh_access_token();
			} catch (Exception $e) {
				error_log($e->getMessage());
				$token = $this->parse_access_token();
			}
		}
		return $this->report_api = new AWCA_Google_Report_API($token->access_token);
	}

	/* getting report api */
	public function get_google_analytics_data_api()
	{
		require_once(AWCA_DIR . 'inc/api/awca-google-analytics-data-api.php');
		$token = $this->parse_access_token($this->get_access_token());
		// refresh token if it's expired
		if ($this->is_access_token_expired($token)) {
			try {
				$token = $this->refresh_access_token();
			} catch (Exception $e) {
				error_log($e->getMessage());
				$token = $this->parse_access_token();
			}
		}
		return $this->report_api = new AWCA_Google_Analytics_Data_API($token->access_token);
	}

	/* refeshing access token */
	private function refresh_access_token()
	{
		if (!$this->get_refresh_token()) {
			throw new Exception('Could not refresh access token: refresh token not available.');
		}
		$refresh_url = $this->get_access_token_refresh_url();
		$response = wp_remote_get($refresh_url, array('timeout' => MINUTE_IN_SECONDS));
		if ($response instanceof \WP_Error) {
			$updated = update_option('awca_access_token', false);
			throw new Exception(sprintf('Could not refresh access token: %s', json_encode($response->errors)));
			return false;
		}
		if (!$response || empty($response['body'])) {
			$updated = update_option('awca_access_token', false);
			throw new Exception('Could not refresh access token: response was empty.');
			return false;
		}
		if (isset($response['response']['code']) && 500 === (int) $response['response']['code']) {
			$updated = update_option('awca_access_token', false);
			throw new Exception('Could not refresh access token: a server error occurred.');
			return false;
		}
		// try to decode and sanitizing the token
		$json_token = sanitize_text_field(base64_decode($response['body'], true));
		if (!json_decode($json_token, true) || (stripos($json_token, 'error') !== false)) {
			$updated = update_option('awca_access_token', false);
			$process_status = get_option('awca_refresh_token_fail');
			if (empty($process_status)) {
				update_option('awca_refresh_token_fail', 'retry');
			} else {
				update_option('awca_refresh_token_fail', 'yes');
			}
			throw new Exception('Could not refresh access token: returned token was invalid.');
			return false;
		}
		$updated = update_option('awca_access_token', $json_token);
		return $this->parse_access_token($json_token);
	}

	/* getting refresh token url */
	public function get_access_token_refresh_url()
	{
		$refresh_url = null;
		if ($refresh_token = $this->get_refresh_token()) {
			$refresh_url = self::PROXY_URL . '/auth/refresh?token=' . base64_encode($refresh_token) . '&callback=' . urlencode($this->get_refresh_callback_url());
		}
		return $refresh_url;
	}

	/* getting refresh token */
	private function get_refresh_token()
	{
		return get_option('awca_refresh_token', null);

	}

	/* checking access token is expired or not */
	private function is_access_token_expired($token)
	{
		$expired = !(is_object($token) && $token->created && $token->expires_in);
		if (!$expired) {
			$time_now = current_time('timestamp', true);
			$time_expires = max(0, (int) $token->created + (int) $token->expires_in);
			$expired = $time_expires <= $time_now;
		}
		return $expired;
	}
}