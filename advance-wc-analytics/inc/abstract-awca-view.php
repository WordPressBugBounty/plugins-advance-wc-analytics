<?php
/* abstract view class for admin main view for plugin */
if (!defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
abstract class AWCA_View
{
	/* initiating variables */
	private $slug;
	private $page_id = null;
	protected $tabs = array();

	public function __construct($title, $slug = 'awca_pro_plugin_options', $submenu = false)
	{
		$this->slug = $slug;
		$this->page_id = add_menu_page(
			$title,
			$title,
			'manage_options',
			$this->slug,
			array($this, 'render'),
			AWCA_URL . 'assests/images/AWCAFevicon.png',
			2.000001
		);
		add_action('load-' . $this->page_id, array($this, 'on_load'));
	}

	/* getting slug value */
	public function get_slug()
	{
		return $this->slug;
	}

	/* creating view for individual tab */
	public function view($name, $options = array())
	{
		$file = AWCA_DIR . "inc/view/{$name}.php";
		$content = '';
		if (is_file($file)) {
			ob_start();
			if (isset($options['id'])) {
				$options['orig_id'] = $options['id'];
				$options['id'] = str_replace('/', '-', $options['id']);
			}
			extract($options);
			include $file;
			$content = ob_get_clean();
		}
		echo $content;
	}

	/* Get tab name for settings page */
	public function get_current_tab()
	{
		$tabs = $this->get_tabs();
		if (isset($_GET['view']) && array_key_exists(wp_unslash($_GET['view']), $tabs)) {
			return wp_unslash($_GET['view']);
		}
		if (empty($tabs)) {
			return false;
		}
		reset($tabs);
		$auth_settings = get_option('awca_auth_settings');
		$awca_refresh_token_fail = get_option('awca_refresh_token_fail');
		if (($auth_settings) && ($awca_refresh_token_fail != 'yes')) {
			//if ($auth_settings = get_option('awca_auth_settings')) {
			if (isset($auth_settings['property_id'])) {
				return 'dash';
			} else {
				return 'settings';
			}
		} else {
			return key($tabs);
		}
	}

	/* getting tab url */
	public function get_tab_url($tab)
	{
		$tabs = $this->get_tabs();
		if (!isset($tabs[$tab])) {
			return '';
		}
		if (is_multisite() && is_network_admin()) {
			return network_admin_url('admin.php?page=' . $this->slug . '&view=' . $tab);
		} else {
			return admin_url('admin.php?page=' . $this->slug . '&view=' . $tab);
		}
	}

	/*getting all tabs*/
	protected function get_tabs()
	{
		return apply_filters('awca_admin_page_tabs_' . $this->slug, $this->tabs);
	}
}
