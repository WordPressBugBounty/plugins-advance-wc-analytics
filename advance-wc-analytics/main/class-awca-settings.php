<?php
/*class for creating settings fields and prasing them before saving*/
if (!defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
class AWCA_Settings
{
	/*initiating variables */
	private static $instance = null;
	private $fevicon_url;
	/* general dashboard */
	public $awca_dash_stats_data_ga4_dash;
	public $awca_report_request_ga4_dash;
	public $awca_report_chart_data_ga4_dash;
	/* Audience dashboard */
	public $awca_dash_stats_data_ga4_audience;
	public $awca_report_request_ga4_audience;
	public $awca_report_chart_data_ga4_audience;
	/* Acquisition dashboard */
	public $awca_report_request_ga4_acquisition;
	public $awca_report_chart_data_ga4_acquisition;
	/* Behavior dashboard */
	public $awca_report_request_ga4_behavior;
	public $awca_report_chart_data_ga4_behavior;
	/* WooCommerce dashboard */
	public $awca_dash_stats_data_ga4_conversion;
	public $awca_report_request_ga4_conversion;
	public $awca_report_chart_data_ga4_conversion;
	/* Performance */
	public $awca_report_request_ga4_performance;
	public $awca_report_chart_data_ga4_performance;
	/* Journey */
	public $awca_report_request_ga4_journey;
	public $awca_report_chart_data_ga4_journey;
	/* GoogleAds dashboard */
	public $awca_dash_stats_data_ga4_googleAds;
	public $awca_report_request_ga4_googleAds;
	public $awca_report_chart_data_ga4_googleAds;
	public $awca_dash_data_ga4_widget;
	/* GoogleAdsense dashboard */
	public $awca_dash_stats_data_ga4_googleAdsense;
	public $awca_report_request_ga4_googleAdsense;
	public $awca_report_chart_data_ga4_googleAdsense;
	/* Genral Settings */
	public $awca_tracking_settings;
	public $awca_dash_settings;
	public $awca_event_settings;
	public $awca_dashboard_settings;
	public $awca_auth_settings;
	public $awca_advance_settings;
	public $awca_event_hooks;
	public $awca_features_list;
	public $awca_custom_dimensions;

	public function __construct()
	{
		$this->fevicon_url = get_site_icon_url(75);
		if (class_exists('WooCommerce')) {
			/* WooCommerce Dashboard */
			$this->awca_dash_stats_data_ga4_conversion = array(
				'purchaseRevenue' => array('payments', 'Total Revenue', 'money', true, true),
				'transactions' => array('receipt_long', 'Total Transctions', '', false, true),
				'averagePurchaseRevenuePerUser' => array('alarm_on', 'Revenue/Active User', 'money', true, true),
				'averagePurchaseRevenue' => array('receipt', 'Revenue/Transaction', 'money', true, true),
				'purchaseToViewRate' => array('add_shopping_cart', 'Buy/DetailRate', '%', false, true),
				'itemViews' => array('view_in_ar', 'Product Detail Views', '', false, true),
				'firstTimePurchasers' => array('people_alt', 'New Buyers', '', false, true),
				'sessionConversionRate:purchase' => array('add_shopping_cart', 'Session Conversion Rate', '100%', false, true),
			);
			$this->awca_report_request_ga4_conversion = array(
				'stats' => array(),
				'performance' => array('itemRevenue', 'itemName', 'itemRevenue'),
				'sourceBaseRevenue' => array('totalRevenue', 'sessionSource', 'totalRevenue'),
				'deviceBaseRevenue' => array('totalRevenue', 'deviceCategory', 'totalRevenue'),
				'regionBaseRevenue' => array('totalRevenue', 'region', 'totalRevenue'),
			);
			$this->awca_report_chart_data_ga4_conversion = array(
				'stats' => array(),
				'performance' => array('table', 'Product Base Revenue Report', 'Product Name', 'Product Revenue', 'Product Revenue', 'This Report help you to find most perfoming products of your WooCommerce Shop by ordering different product based on revenue generation during the specified period.'),
				'sourceBaseRevenue' => array('table', 'Source Base Revenue Report', 'Traffic Source/Medium', 'Total Revenue', 'This report shows which traffic sources generated better revenues by ordering different traffic sources to revenue generated during specified period.'),
				'deviceBaseRevenue' => array('doughnut', 'Device base conversion share', '', '', 'This report shows Device category breakdown with revenue each different devices category generated over the period of time.'),
				'regionBaseRevenue' => array('bar', 'State/Region Base Revenue Report', 'State/Region Name', 'Total Revenue', 'This report shows which state/region contributed most in total revenue generation by putting them in order based on revenue for specified period of time.'),
			);
			$this->awca_report_request_ga4_performance = array(
				'overallProductPerformance' => array(array('itemRevenue', 'itemsViewed', 'itemsAddedToCart', 'itemsPurchased'), 'itemName', 'itemRevenue'),
			);
			$this->awca_report_chart_data_ga4_performance = array(
				'overallProductPerformance' => array('large_table', 'Product Performance Report', 'Product Name', array('Product Revenue', 'Product Viewed', 'Product Added to Cart', 'Product Purchased'), 'Product Revenue', 'This Report help you to find most perfoming products of your WooCommerce Shop during the specified period.'),
			);
			$this->awca_report_request_ga4_journey = array(
				'purchaseJourney' => array(array('itemViews', 'addToCarts', 'checkouts', 'ecommercePurchases'), 'deviceCategory', 'ecommercePurchases'),
			);
			$this->awca_report_chart_data_ga4_journey = array(
				'purchaseJourney' => array('funnel', 'Purchase Journey Report', 'Devive Category', array('Products Views', 'Products Added to Cart', 'Products Reached Checkout', 'Products Purchased'), 'Products Views', 'This report shows Products Purchase Journey with Device Category Information and it\'s comparison for previous specified period.'),
			);
			/* Tracking Settings */
			$this->awca_tracking_settings = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'product_single_track' => true,
				'product_archive_track' => true,
				'disable_on_hold_conversion' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
			);
			/* dash Settings */
			$this->awca_dashboard_settings = array(
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'conversion' => true,
				'journey' => true,
				'performance' => true,
				'googleAds' => true,
				'googleAdsense' => true,
			);
			/* Event Tracking Settings */
			$this->awca_event_settings = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'viewed_signup_form' => true,
				'user_signup' => true,
				'viewed_shop' => true,
				'viewed_product' => true,
				'added_product' => true,
				'removed_product' => true,
				'changed_quantity' => true,
				'viewed_cart' => true,
				'wrong_coupon_applied' => true,
				'applied_coupon' => true,
				'removed_coupon' => true,
				'begin_checkout' => true,
				'filled_checkout_form' => true,
				'added_payment_method' => true,
				'added_shipping_method' => true,
				'order_failed' => true,
				'processing_payment' => true,
				'completed_purchase' => true,
				'wrote_review' => true,
				'commented' => true,
				'viewed_account' => true,
				'viewed_order' => true,
				'changed_password' => true,
				'lost_password' => true,
				'estimated_shipping' => true,
				'order_cancelled' => true,
				'order_refunded' => true,
				'log_error' => true,
			);
			/* Hooks Associated with events */
			$this->awca_event_hooks = array(
				'user_login' => array(2, 'wp_login'),
				'user_login_errors' => array('filter', 'login_errors'),
				'user_logout' => 'wp_logout',
				'viewed_signup_form' => 'woocommerce_register_form',
				'user_signup' => 'user_register',
				'viewed_shop' => 'wp_head',
				'viewed_product' => 'woocommerce_after_single_product_summary',
				'added_product' => 'woocommerce_add_to_cart',
				'removed_product' => 'woocommerce_remove_cart_item',
				'changed_quantity' => array(2, 'woocommerce_after_cart_item_quantity_update'),
				'viewed_cart' => array('woocommerce_cart_is_empty', 'woocommerce_after_cart_contents'),
				'wrong_coupon_applied' => array(3, 'filter', 'woocommerce_coupon_error'),
				'applied_coupon' => 'woocommerce_applied_coupon',
				'removed_coupon' => 'woocommerce_removed_coupon',
				'begin_checkout' => 'woocommerce_before_checkout_form',
				'filled_checkout_form' => 'woocommerce_after_checkout_form',
				'added_shipping_method' => 'woocommerce_after_checkout_form',
				'added_payment_method' => 'woocommerce_after_checkout_form',
				'order_failed' => array(2, 'woocommerce_order_status_failed'),
				'processing_payment' => 'woocommerce_checkout_order_processed',
				'completed_purchase' => array('woocommerce_order_status_on-hold', 'woocommerce_payment_complete', 'woocommerce_order_status_processing', 'woocommerce_order_status_completed', 'woocommerce_thankyou'),
				'wrote_review' => 'comment_post',
				'commented' => 'comment_post',
				'viewed_account' => 'woocommerce_after_my_account',
				'viewed_order' => 'woocommerce_view_order',
				'changed_password' => 'woocommerce_save_account_details',
				'lost_password' => array('filter', 'woocommerce_lost_password_confirmation_message'),
				'estimated_shipping' => 'woocommerce_calculated_shipping',
				'order_cancelled' => 'woocommerce_cancelled_order',
				'order_refunded' => array(2, 'woocommerce_order_refunded'),
				'log_error' => 'woocommerce_shutdown_error',
			);
		} else {
			/* Tracking Settings */
			$this->awca_tracking_settings = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
			);
			/* Event Tracking Settings */
			$this->awca_event_settings = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'wrote_review' => true,
				'commented' => true,
				'log_error' => true,
			);
			$this->awca_dashboard_settings = array(
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'googleAds' => true,
				'googleAdsense' => true,
			);
			/* Hooks Associated with events */
			$this->awca_event_hooks = array(
				'user_login' => array(2, 'wp_login'),
				'user_login_errors' => array('filter', 'login_errors'),
				'user_logout' => 'wp_logout',
				'wrote_review' => 'comment_post',
				'commented' => 'comment_post',
				'log_error' => 'woocommerce_shutdown_error',
			);
		}
		/* Dashboard Data Widgets */
		$this->awca_dash_data_ga4_widget = array(
			'AWCA: Overview Report' => array('Overview Report', 'line', 'No. of Users', array('total users', 'new users'), 1, 'description'),
			'AWCA: Users By Country Report' => array('Users By Country Report', 'bar', 'Country', 'No. of Users', 2, 'description'),
			'AWCA: Users By Language Report' => array('Users By Language Report', 'bar', 'Language', 'No. of Users', 3, 'description'),
			'AWCA: Users By Device Category Report' => array('Users By Device Category Report', 'doughnut', 'Device Category', 'No. of Users', 4, 'description'),
			'AWCA: Quick Stats' => array('Quick Stats', 'stats', 'Stats', 'No. of Users', 0, 'description'),
		);
		/* Dashboard Data Widgets */
		$this->awca_dash_stats_data_ga4_dash = array(
			'purchaseRevenue' => array('payments', 'Total Revenue', 'money', true, true),
			'transactions' => array('receipt_long', 'Total Transctions', '', false, true),
			'averagePurchaseRevenuePerUser' => array('alarm_on', 'Revenue/Active User', 'money', true, true),
			'averagePurchaseRevenue' => array('receipt', 'Revenue/Transaction', 'money', true, true),
			'totalUsers' => array('people_alt', 'Users', '', false, true),
			'newUsers' => array('group_add', 'New Users', '', false, true),
			'sessions' => array('hourglass_bottom', 'Total Sessions', '', false, true),
			'sessionsPerUser' => array('timelapse', 'Sessions/User', '', false, true),
		);
		$this->awca_report_request_ga4_dash = array(
			'stats' => array(),
			'dateViseVisitors' => array(array('totalUsers', 'newUsers'), 'date', 'date'),
			'countryViseVisitors' => array('totalUsers', 'country', 'totalUsers'),
			'languageViseVisitors' => array('totalUsers', 'language', 'totalUsers'),
			'deviceViseVisitors' => array('totalUsers', 'deviceCategory', 'totalUsers'),
			//'cityViseVisitors' => array('totalUsers','city','totalUsers'),
		);
		$this->awca_report_chart_data_ga4_dash = array(
			'stats' => array(),
			'dateViseVisitors' => array('line', 'Overview Report', 'Date', array('Users', 'New Users'), 'This report shows no. of users and from howmany were new users visited website for specific date over the period of time.'),
			'countryViseVisitors' => array('bar', 'Country Based Users Report', 'Country', 'No. of Users', 'This reports categories users to different countries based on their location for specific period of time. '),
			'languageViseVisitors' => array('bar', 'Language Based Users Report', 'Language', 'No. of Users', 'This reports categories users based on their browser language for specific period of time.'),
			'deviceViseVisitors' => array('doughnut', 'Device Based Users Report', '', '', 'This report categories users based of their device category for specific period of time.'),
			//'cityViseVisitors' => array('bar','City Based Users Report','City','No. of Users','This report helps you understand from which city your website received maximum users for specific period of time.'),
		);
		/* Audience Dashboard */
		$this->awca_dash_stats_data_ga4_audience = array(
			'totalUsers' => array('people_alt', 'Users', '', false, true),
			'newUsers' => array('group_add', 'New Users', '', false, true),
			'sessions' => array('hourglass_bottom', 'Total Sessions', '', false, true),
			'sessionsPerUser' => array('timelapse', 'Sessions/User', '', false, true),
			'screenPageViews' => array('pageview', 'Pageviews', '', false, true),
			'averageSessionDuration' => array('timer', 'Avg. Session Duration', 's', false, true),
			'engagementRate' => array('timeline', 'Engagement Rate', '', false, true),
			'screenPageViewsPerUser' => array('find_in_page', 'Pageviews/User', '', false, true),
		);
		$this->awca_report_request_ga4_audience = array(
			'stats' => array(),
			'dateViseVisitors' => array(array('totalUsers', 'newUsers'), 'date', 'date'),
			'countryViseVisitors' => array('totalUsers', 'country', 'totalUsers'),
			'languageViseVisitors' => array('totalUsers', 'language', 'totalUsers'),
			'deviceViseVisitors' => array('totalUsers', 'deviceCategory', 'totalUsers'),
			//'cityViseVisitors' => array('totalUsers','city','totalUsers'),
		);
		$this->awca_report_chart_data_ga4_audience = array(
			'stats' => array(),
			'dateViseVisitors' => array('line', 'Overview Report', 'Date', array('Users', 'New Users'), 'This report shows no. of users and from howmany were new users visited website for specific date over the period of time.'),
			'countryViseVisitors' => array('table', 'Country Based Users Report', 'Country', array('No. of Users'), 'This reports categories users to different countries based on their location for specific period of time. '),
			'languageViseVisitors' => array('table', 'Language Based Users Report', 'Language', array('No. of Users'), 'This reports categories users based on their browser language for specific period of time.'),
			'deviceViseVisitors' => array('doughnut', 'Device Based Users Report', '', '', 'This report categories users based of their device category for specific period of time.'),
			//'cityViseVisitors' => array('bar','City Based Users Report','City','No. of Users'),
		);
		/* Acquisition Dashboard */
		$this->awca_report_request_ga4_acquisition = array(
			'channelsReport' => array('activeUsers', 'sessionDefaultChannelGrouping', 'activeUsers'),
			'sourceReport' => array('activeUsers', 'sessionSource', 'activeUsers'),
			'referralsReport' => array('activeUsers', 'screenResolution', 'activeUsers'),
			//'campaignReport' =>array('users','campaign','users'),
			'mediumReport' => array('activeUsers', 'sessionMedium', 'activeUsers'),
			//'organicSearchReport' => array('organicSearches','sourceMedium','organicSearches'),
		);
		$this->awca_report_chart_data_ga4_acquisition = array(
			'channelsReport' => array('table', 'Users Based on Channels', 'Channels', 'No. of Users', 'This report shows analysis about which channel contributed most traffic for website for specified period of time.'),
			'sourceReport' => array('table', 'Users Based on Source', 'Source', 'No. of Users', 'This report classify users based on source/medium by using users reached website for specific period of time.'),
			'screenSizeReport' => array('table', 'Users Based on Screen Sizes', 'Screen Resolution', 'No. of Users', 'This report classify different screen sizes users were using for browsing website over period of time.'),
			//'campaignReport' =>array('bar','Users Based on Campaigns','Campaigns Name','No. of Users'),
			'mediumReport' => array('table', 'Users Based on Medium', 'Medium', 'No. of Users', 'This report classify users based on medium by using users reached website for specific period of time.'),
			//'organicSearchReport' => array('bar','Organic Search Report','Organic Source','No. of Users'),
		);
		/* Behavior Dashboard */
		$this->awca_report_request_ga4_behavior = array(
			'topPageReport' => array('totalUsers', 'pageTitle', 'totalUsers'),
			'timeOnPageReport' => array('userEngagementDuration', 'pageTitle', 'userEngagementDuration'),
			'usersAgeGroup' => array('totalUsers', 'userAgeBracket', 'totalUsers'),
			//'campaignReport' =>array('users','campaign','users'),
			'genderReport' => array('totalUsers', 'userGender', 'totalUsers'),
			'osReport' => array('totalUsers', 'operatingSystem', 'totalUsers'),
		);
		$this->awca_report_chart_data_ga4_behavior = array(
			'topPageReport' => array('table', 'Page Performance Report', 'Page Title', 'No. of Users', 'This report shows which pages have maxium visitors over the period of time.'),
			'timeOnPageReport' => array('table', 'Avg. Time Spend of Page', 'Page Title', 'Time on Page(sec.)', 'The total amount of time (in seconds) your website page was in the foreground of users\' devices for specified time period.'),
			'usersAgeGroup' => array('bar', 'Users based on Age-Group', 'Age Group', 'No. of Users', 'This is devision of traffic based on age groups over the period of time.'),
			//'campaignReport' =>array('bar','Users Based on Campaigns','Campaigns Name','No. of Users'),
			'genderReport' => array('doughnut', 'Gender Based User Report', '', '', 'Gender based division of Traffic over the period of time.'),
			'osReport' => array('bar', 'Operating System Report', 'Operating Report', 'No. of Users', 'Operating Report', 'No. of Users', 'This report shows highly used operating system by website users for specified period of time.'),
		);
		/* GoogleAds Dashboard */
		$this->awca_dash_stats_data_ga4_googleAds = array(
			//'impressions'=>array('featured_video','Total Ads impressions','',false),
			'advertiserAdClicks' => array('ads_click', 'Total Ad Clicks', '', false, true),
			'advertiserAdCost' => array('payments', 'Ad Cost', '$', true, false),
			//'CPM'=>array('1k','Cost/ 1k impressions','money',true),
			'advertiserAdCostPerClick' => array('ads_click', 'Cost/Click', 'money', true, false),
			//'CTR'=>array('mouse','Click Throughrate','%',false),
			//'costPerTransaction'=>array('add_shopping_cart','Cost Per Transaction','money',true),
			//'ROAS'=>array('restart_alt','Return on Ad Spend','%',false),
		);
		$this->awca_report_request_ga4_googleAds = array(
			//'stats'=>array(),
			'adCampaignCostReport' => array('advertiserAdCost', 'googleAdsAdGroupName', 'advertiserAdCost'),
			'adCampaignSuccessReport' => array('totalUsers', 'googleAdsAdGroupName', 'totalUsers'),
			'searchQuerySuccessReport' => array('totalUsers', 'sessionGoogleAdsQuery', 'totalUsers'),
			'adDistributionNetworkPerformanceReport' => array('totalUsers', 'firstUserGoogleAdsAdNetworkType', 'totalUsers'),
			//'adSlotPerformanceReport' => array('adClicks','adSlot','adClicks'),
		);
		$this->awca_report_chart_data_ga4_googleAds = array(
			//'stats'=>array(),
			'adCampaignCostReport' => array('bar', 'Ad Group Cost Report', 'Ad Group', 'Advertiser Ad Cost', 'This reports shows ad costing based different Ad groups over the period of time.'),
			'adCampaignSuccessReport' => array('bar', 'Ad Group Success Report', 'Ad Group', 'Total Users', 'This reports shows contribution of Ad Groups for generating website traffic for specified period of time.'),
			'searchQuerySuccessReport' => array('bar', 'Ad Search Query Reports', 'Search Query', 'Total Users', 'This report shows which search query triggered maxium ad clicks for specified period of time.'),
			'adDistributionNetworkPerformanceReport' => array('bar', 'Ad Distribution Network Performance Report(Ad Clicks)', 'Ad Network Type', 'Total Users', 'This reports shows ad clicks based on different ad slots for specific period of time.'),
			//'adSlotPerformanceReport' => array('bar','Ad Slot Report','Ad Slot','Ad Clicks'),
		);
		/* Google Adsense Dashboard */
		$this->awca_dash_stats_data_ga4_googleAdsense = array(
			'totalAdRevenue' => array('payments', 'Adsense Revenue', '$', true, true),
			'publisherAdImpressions' => array('featured_video', 'Ads Viewed', '', false, true),
			'publisherAdClicks' => array('ads_click', 'Ad Clicks', '', false, true),
			//'adsensePageImpressions'=>array('page','Ad Page Impressions','',false),
			//'adsenseECPM'=>array('1k','Revenue/1k Ad impressions','money',true),
			//'adsenseCTR'=>array('mouse','Click Throughrate','%',false),
			//'adsenseViewableImpressionPercent'=>array('view','Viewable Impression','%',false),
			//'adsenseCoverage'=>array('ad','Atlest One Ad Visible','%',false),
		);
		$this->awca_report_request_ga4_googleAdsense = array(
			'stats' => array(),
			'adsenseRevenuePagePeformance' => array('totalAdRevenue', 'pageTitle', 'totalAdRevenue'),
			'adsenseAdsClicksPagePerformance' => array('publisherAdClicks', 'pageTitle', 'publisherAdClicks'),
			//'adsenseAdsenseECPMPagePerformance' => array('adsenseECPM','pageTitle','adsenseECPM'),
		);
		$this->awca_report_chart_data_ga4_googleAdsense = array(
			'stats' => array(),
			'adsenseRevenuePagePeformance' => array('table', 'Adsense Revenue Based on PageTitle', 'Page Title', 'Adsense Revenue', 'This reports shows adsense revenue based on page for specified period of time.'),
			'adsenseAdsClicksPagePerformance' => array('table', 'Ad Clicks Based on PageTitle', 'Page Title', 'Ad Clicks', 'This report shows which page received maximum ad clicks over the period of time.'),
			//'adsenseAdsenseECPMPagePerformance' => array('bar','Revenue/1k Ad Impressions Based On PageTitle','Page Title','Revenue/ 1k Ad Impressions'),
		);
		/* Dashboard settings */
		$this->awca_dash_settings = array(
			'report_view' => '',
			'report_frame' => 'Last 30 days',
			'report_from' => '',
			'report_to' => '',
		);
		/* Authentication Settings */
		$this->awca_auth_settings = array(
			'trackind_id' => '',
			'property_id' => '',
			'api_secret' => '',
			'manual_tracking' => false,
			'agreement' => true,
		);
		/* Advance Settings */
		$this->awca_advance_settings = array(
			'facebook_pixel' => true,
			'facebook_pixel_code' => '',
			'google_analytics_debug_mode' => false,
			'google_adword' => true,
			'google_adword_code' => '',
			'google_adword_label' => '',
			'google_measurement' => true,
			'google_measurement_api' => '',
		);
		/* awca features list */
		$this->awca_features_list = array(
			'0' => array('Easy To Connect', 'Plugin offfers very easy connection with your google analytics.', false),
			'1' => array('Light Weight', 'Light weight plugin does effect performance of website performace.', false),
			'2' => array('Regular Updates', 'We offer regular updates to plugin so connection with your google analytics always works', false),
			'3' => array('User ID Tracking', 'It helps to understand user behaviour on website and tracking events associated with it.', false),
			'4' => array('Enhanced Link Attribution', 'Improves the accuracy of your In-Page Analytics report by automatically differentiating between multiple links.', false),
			'5' => array('IP Anonymization', 'Anonymize the ip address of user to avoid any collection of ip with other analytics data.', false),
			'6' => array('Ads Conversion Tracking', 'Plugin help you to track Google Ads Conversions with easy integration.', false),
			'7' => array('FB Pixel Integration', 'Plugin also help in tracking differnt events and converstion for FB Pixel', false),
			'8' => array('Audience Reports', 'Audience Reports allows you to identify characteristics of your users such like location, language, devices used by them, browser details and others important information.', true),
			'9' => array('Behavior Report', 'Behavior reports of Google Analytics allows you to understand what users do on your website. Specifically reports tells you what pages people visit and what actions they take while visiting.', true),
			'10' => array('WooCommerce Report Pro', 'Advanced reports help you to understand how your WooCommerce Store performing and what needs to improve to perform it more better.', true),
			'11' => array('Acquisition Report', 'Get Information about your traffic channels, resources and referrals from which your website receiving traffic.', true),
			'12' => array('Google Ads Report', 'Get performance and engagements of Google Ads campaigns and other useful information which help you to choose better strategies for success using ads.', true),
			'13' => array('Google Adsense Report', 'Find out which content is generating more revenue using google adsense and other realted information which helps you find better content placement strategies for higher revenue growth.', true),
			'14' => array('Tech Reports', 'Get details of different devices, browsers and screen resolutions your users using for accessing website.', true),
			'15' => array('User Behaviour Analysis', 'Track different events and Interactions of users live in real-time reports', true),
		);
		/* awca custom dimensions list */
		$this->awca_custom_dimensions = array(
			'1' => array('authorId3', 'EVENT', 'Author ID of Writer', 'author_id_3'),
			'2' => array('postTag3', 'EVENT', 'Tag of Post', 'post_tag_3'),
			'3' => array('postCat3', 'EVENT', 'Post Category', 'post_cat_3'),
			'4' => array('errorMsg3', 'EVENT', 'Description of Error occured on Website', 'error_msg_3'),
		);
	}

	/* Creating Instance and Returning where it requested */
	public static function get_instance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/* parsing dash settings before saving them */
	public function parse_awca_dash_settings($settings)
	{
		$settings = filter_var_array($settings, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		return $settings;
	}

	/* parse advance settings */
	public function parse_awca_advance_settings($settings)
	{
		$args = array(
			'google_measurement' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'google_measurement_api' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'facebook_pixel' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'facebook_pixel_code' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'google_analytics_debug_mode' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'google_adword' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'google_adword_code' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'google_adword_label' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
		);
		$settings = filter_var_array($settings, $args);
		return $settings;
	}


	/* parsing Authentication settings before saving them */
	public function parse_awca_auth_settings($settings)
	{
		$args = array(
			'tracking_id' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'property_id' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'api_secret' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'manual_tracking' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
			'agreement' => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags' => FILTER_REQUIRE_SCALAR,
			),
		);
		$settings = filter_var_array($settings, $args);
		return $settings;
	}

	/* parsing event settings before saving them */
	public function parse_awca_bool_settings($settings)
	{
		$settings = filter_var_array($settings, FILTER_VALIDATE_BOOLEAN);
		return $settings;
	}

	/* Defining Defaults for Dashboard settings */
	public function init_awca_dash_defaults()
	{
		$defaults = array(
			'report_view' => '',
			'report_frame' => 'Last 30 days',
			'report_from' => '',
			'report_to' => '',
		);
		return $defaults;
	}
	public function init_awca_dashboard_defaults()
	{
		if (class_exists('WooCommerce')) {
			$defaults = array(
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'conversion' => true,
				'journey' => true,
				'performance' => true,
				'googleAds' => true,
				'googleAdsense' => true,
			);
		} else {
			$defaults = array(
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'googleAds' => true,
				'googleAdsense' => true,
			);

		}
		return $defaults;
	}

	/* Defining Defaults for Authentication Settings */
	public function init_awca_auth_defaults()
	{
		$defaults = array(
			'trackind_id' => '',
			'property_id' => '',
			'api_secret' => '',
			'manual_tracking' => false,
			'agreement' => true,
		);
		return $defaults;
	}

	/* Defining Defaults for advance Settings */
	public function init_awca_advance_defaults()
	{
		$defaults = array(
			'google_measurement' => false,
			'google_measurement_api' => '',
			'facebook_pixel' => true,
			'facebook_pixel_code' => '',
			'google_analytics_debug_mode' => false,
			'google_adword' => true,
			'google_adword_code' => '',
			'google_adword_label' => '',
		);
		return $defaults;
	}

	/* Defining Defaults for Tracking Settings */
	public function init_awca_track_defaults()
	{
		if (class_exists('WooCommerce')) {
			$defaults = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'product_single_track' => true,
				'product_archive_track' => true,
				'disable_on_hold_conversion' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
			);
		} else {
			$defaults = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'anonymize_ip' => false,
				'track_interest' => false,
				'not_track_user_id' => false,
				'track_ga_consent' => false,
			);
		}
		return $defaults;
	}

	/* Defining Defaults for Events Settings */
	public function init_awca_events_defaults()
	{
		if (class_exists('WooCommerce')) {
			$defaults = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'viewed_signup_form' => true,
				'user_signup' => true,
				'viewed_shop' => true,
				'viewed_product' => true,
				'added_product' => true,
				'removed_product' => true,
				'changed_quantity' => true,
				'viewed_cart' => true,
				'wrong_coupon_applied' => true,
				'applied_coupon' => true,
				'removed_coupon' => true,
				'begin_checkout' => true,
				'filled_checkout_form' => true,
				'added_payment_method' => true,
				'added_shipping_method' => true,
				'order_failed' => true,
				'processing_payment' => true,
				'completed_purchase' => true,
				'wrote_review' => true,
				'commented' => true,
				'viewed_account' => true,
				'viewed_order' => true,
				'changed_password' => true,
				'lost_password' => true,
				'estimated_shipping' => true,
				'order_cancelled' => true,
				'order_refunded' => true,
				'log_error' => true,
			);
		} else {
			$defaults = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'wrote_review' => true,
				'commented' => true,
				'log_error' => true,
			);
		}
		return $defaults;
	}
}
