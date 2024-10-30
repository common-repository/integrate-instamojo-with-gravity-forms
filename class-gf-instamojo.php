<?php
GFForms::include_payment_addon_framework();

/**
 * Load instamojo class
 */
class GF_Instamojo extends GFPaymentAddOn
{
    /**
     * Instamojo attributes
     */
    const GF_INSTAMOJO_FEED_NAME = 'feedName';
    const GF_INSTAMOJO_API_KEY = 'apiKey';
    const GF_INSTAMOJO_AUTH_TOKEN = 'authToken';
    const GF_INSTAMOJO_ENVIRONMENT = 'environment';

    const GF_INSTAMOJO_TRANSACTION_TYPE = 'transactionType';
    const GF_INSTAMOJO_PRODUCT_AMOUNT = 'paymentAmount';
    const GF_INSTAMOJO_SUCCESS_URL = 'successUrl';
    const GF_INSTAMOJO_FAILURE_URL = 'failureUrl';
    const GF_INSTAMOJO_BILLING_INFORMATION = 'billingInformation_';
    const GF_INSTAMOJO_CONDITIONAL_LOGIC = 'conditionalLogic';

    const GF_INSTAMOJO_ORDER_ID = 'instamojoOrderId';

    const GF_INSTAMOJO_BILLING_INFORMATION_NAME = self::GF_INSTAMOJO_BILLING_INFORMATION . 'name';
    const GF_INSTAMOJO_BILLING_INFORMATION_EMAIL = self::GF_INSTAMOJO_BILLING_INFORMATION . 'email';
    const GF_INSTAMOJO_BILLING_INFORMATION_PHONE = self::GF_INSTAMOJO_BILLING_INFORMATION . 'phone';

    /**
     * Cookie set for one day
     */
    const COOKIE_DURATION = 86400;

    /**
     * @var string Version of current plugin
     */
    protected $_version = GF_INSTAMOJO_VERSION;

    /**
     * @var string Minimum version of gravity forms
     */
    protected $_min_gravityforms_version = '1.9.3';

    /**
     * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
     */
    protected $_slug = 'integrate-instamojo-with-gravity-forms';

    /**
     * @var string Relative path to the plugin from the plugins's folder. Example "gravityforms/gravityforms.php"
     */
    protected $_path = 'integrate-instamojo-with-gravity-forms/instamojo.php';

    /**
     * @var string Full path the plugin. Example: __FILE__
     */
    protected $_full_path = __FILE__;

    /**
     * @var string URL to the Gravity Forms website. Example: 'http://www.gravityforms.com' OR affiliate link
     */
    protected $_url = 'https://www.gravityforms.com/';

    /**
     * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
     */
    protected $_title = 'Integrate Instamojo with Gravity Forms';

    /**
     * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
     */
    protected $_short_title = 'Instamojo';

    /**
     * Defines if the payment add-on supports callbacks
     *
     * @since  Unknown
     * @access protected
     *
     * @used-by GFPaymentAddOn::upgrade_payment()
     *
     * @var bool True if the add-on supports callbacks. Otherwise, false
     */
    protected $_supports_callbacks = true;

    /**
     * If true, feeds will be processed asynchronously in the background
     *
     * @since 2.2
     * @var bool
     */
    public $_async_feed_processing = false;

    // --------------------------------------------- Permissions Start -------------------------------------------------

    /**
     * @var string|array A string or an array of capabilities or roles that have access to the settings page
     */
    protected $_capabilities_settings_page = 'gravityforms_instamojo';

    /**
     * @var string|array A string or an array of capabilities or roles that have access to the form settings
     */
    protected $_capabilities_form_settings = 'gravityforms_instamojo';

    /**
     * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
     */
    protected $_capabilities_uninstall = 'gravityforms_instamojo_uninstall';

    // --------------------------------------------- Permissions End ---------------------------------------------------

    /**
     * @var bool Used by rocketgenius plugins to activate auto-upgrade
     * @ignore
     */
    protected $_enable_rg_autoupgrade = true;

    /**
     * @var GF_Instamojo
     */
    private static $_instance = null;

    /**
     * Initiate instamojo class
     * 
     * @return GF_Instamojo|null
     */
    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new GF_Instamojo();
        }

        return self::$_instance;
    }

    /**
	 * Return the plugin's icon for the plugin settings menu
     * 
	 * @return string
	 */
	public function get_menu_icon(): string
    {
		return 'gform-icon--credit-card';
	}

    /**
     * Get gravity forms environment fields details
     * 
     * @return array
     */
    public function get_environment_fields()
    {
        $choices = array(
            array(                
                'label' => esc_html__('Select a field', $this->_slug),
                'value' => '',
            ),
            array(
                'label' => esc_html__('Test', $this->_slug),
                'value' => 'test'
            ),                           
            array(
                'label' => esc_html__('Live', $this->_slug),
                'value' => 'live'
            ),
		);

        return $choices;
    }

    /**
     * Get gravity forms product amount fields details
     * 
     * @return array
     */
    public function get_product_amount_fields($fields)
    {
        $choices = array(
            array(                
                'label' => esc_html__('Select a field', $this->_slug),
                'value' => '',
            ),
            array(
                'label' => esc_html__('Form Total', $this->_slug),
                'value' => 'form_total',
            ),
		);

		foreach ($fields as $field) {
			$fieldId = $field->id;
			$fieldLabel = RGFormsModel::get_label($field);
			$choices[] = array(
                'label' => esc_html__($fieldLabel, $this->_slug),
                'value' => $fieldId,
            );
		}

        return $choices;
    }
    
    /**
     * Get gravity forms page url fields details
     * 
     * @return array
     */
    public function get_page_url_fields($pages)
    {
        $choices = array(
            array(                
                'label' => esc_html__('Select a field', $this->_slug),
                'value' => '',
            ),
        );

        foreach ($pages as $page) {                           
            $choices[] = array(
                'label' => esc_html__($page->post_title, $this->_slug),
                'value' => $page->ID,
            );
        }

        return $choices;
    }

    /**
     * Get gravity forms name fields details
     * 
     * @return array
     */
    public function get_billing_information_fields($fields)
    {
        $choices = array(
            array(                
                'label' => esc_html__('Select a field', $this->_slug),
                'value' => '',
            ),
        );

        foreach ($fields as $field) {
			$fieldId = $field->id;
			$fieldLabel = RGFormsModel::get_label($field);
			$choices[] = array(
                'label' => esc_html__($fieldLabel, $this->_slug),
                'value' => $fieldId,
            );
		}

        return $choices;
    }

    /**
     * Adding settings to gravity forms
     * 
     * @return array
     */
	public function feed_settings_fields()
    {
        $pages = get_pages();
        $form = $this->get_current_form();
        $productFields = GFAPI::get_fields_by_type($form, array('product'));
        $nameFields = GFAPI::get_fields_by_type($form, array('name'));
        $emailFields = GFAPI::get_fields_by_type($form, array('email'));
        $phoneFields = GFAPI::get_fields_by_type($form, array('phone'));

		return array(
			array(
				'title' => esc_html__('Instamojo Settings', $this->_slug),
				'fields' => array(
					array(
						'name' => self::GF_INSTAMOJO_FEED_NAME,
						'label' => esc_html__('Feed Name', $this->_slug),
						'required' => true,
						'class' => 'medium',
						'type' => 'text',
					),
                    array(
                        'name' => self::GF_INSTAMOJO_API_KEY,
                        'label' => esc_html__('API Key', $this->_slug),
                        'required' => true,
                        'class' => 'medium',
                        'type' => 'text',
                    ),
                    array(
                        'name' => self::GF_INSTAMOJO_AUTH_TOKEN,
                        'label' => esc_html__('Auth Token', $this->_slug),
                        'required' => true,
                        'class' => 'medium',
                        'type' => 'text',
                    ),
                    array(
                        'name' => self::GF_INSTAMOJO_ENVIRONMENT,
                        'label' => esc_html__('Environment', $this->_slug),
                        'required' => true,
                        'type' => 'select',
                        'choices' => $this->get_environment_fields(),
                    ),
				),
			),
			array(
				'title' => esc_html__('Form Settings', $this->_slug),
				'fields' => array(
                    array(
                        'name' => self::GF_INSTAMOJO_TRANSACTION_TYPE,
                        'label' => esc_html__('Transaction Type', $this->_slug),
                        'required' => true,
                        'class' => 'medium',
                        'type' => 'hidden',
						'default_value' => 'product',
                    ),
					array(
						'name' => self::GF_INSTAMOJO_PRODUCT_AMOUNT,
						'label' => esc_html__('Product Amount', $this->_slug),
						'required' => true,
						'type' => 'select',
						'choices' => $this->get_product_amount_fields($productFields),
					),
                    array(
                        'name' => self::GF_INSTAMOJO_SUCCESS_URL,
                        'label' => esc_html__('Success Page', $this->_slug),
                        'required' => true,
                        'type' => 'select',
                        'choices' => $this->get_page_url_fields($pages),
                    ),
                    array(
                        'name' => self::GF_INSTAMOJO_FAILURE_URL,
                        'label' => esc_html__('Failure Page', $this->_slug),
                        'required' => true,
                        'type' => 'select',
                        'choices' => $this->get_page_url_fields($pages),
                    ),
				),
			),
			array(
				'title' => esc_html__('Billing Settings', $this->_slug),
				'fields' => array(
                    array(
                        'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_NAME,
                        'label' => esc_html__('Name', $this->_slug),
						'required' => true,
						'type' => 'select',
                        'choices' => $this->get_billing_information_fields($nameFields),
                    ),
                    array(
                        'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_EMAIL,
                        'label' => esc_html__('Email', $this->_slug),
						'required' => true,
						'type' => 'select',
                        'choices' => $this->get_billing_information_fields($emailFields),
                    ),
                    array(
                        'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_PHONE,
                        'label' => esc_html__('Phone', $this->_slug),
						'required' => true,
						'type' => 'select',
                        'choices' => $this->get_billing_information_fields($phoneFields),
                    ),
				),
			),
			array(
				'title' => esc_html__('Other Settings', $this->_slug),
				'fields' => array(
                    array(
                        'name' => 'conditionalLogic',
                        'label' => esc_html__('Conditional Logic', $this->_slug),
                        'tooltip' => '<h6>' . esc_html__('Conditional Logic', $this->_slug) . '</h6>' . esc_html__('When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.', $this->_slug),
                        'type' => 'feed_condition',
                    ),
                ),
			),
		);
	}

    /**
     * Initiate instamojo on gravity forms frontend
     */
    public function init_frontend()
    {
        add_action('gform_after_submission', array($this, 'init_instamojo_order'), 10, 2);
        parent::init_frontend();
    }

    /**
     * Get gravity forms billing fields details
     * 
     * @return array
     */
    public function get_billing_info_fields()
    {
        $fields = array(
            array(
                'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_NAME, 
                'label' => esc_html__('Name', $this->_slug),
                'required' => true,
            ),
            array(
                'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_EMAIL, 
                'label' => esc_html__('Email', $this->_slug),
                'required' => true,
            ),
            array(
                'name' => self::GF_INSTAMOJO_BILLING_INFORMATION_PHONE, 
                'label' => esc_html__('Phone', $this->_slug),
                'required' => true,
            ),
        );

        return $fields;
    }

    /**
     * Get gravity forms customer details
     * 
     * @param $form
     * @param $feed
     * @param $entry
     * @return array
     */
    public function get_customer_fields($form, $feed, $entry)
    {
        $fields = array();
        $billingFields = $this->get_billing_info_fields();
        foreach ($billingFields as $field) {
            $fieldId = $feed['meta'][$field['name']];
            $value = $this->get_field_value($form, $entry, $fieldId);
            $fields[$field['name']] = $value;
        }

        return $fields;
    }

    /**
     * Generate instamojo order for payment
     * 
     * @param $entry
     * @param $form
     * @return void
     */
    public function init_instamojo_order($entry, $form)
    {
        global $wp;

        $page = home_url($wp->request);

        $feed = $this->get_payment_feed($entry);
        $submissionData = $this->get_submission_data($feed, $form, $entry);

        if (!$feed || empty($submissionData['payment_amount'])) {
            return true;
        }

        $environmentSetting = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_ENVIRONMENT]);
        $apiKey = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_API_KEY]);
        $authToken = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_AUTH_TOKEN]);

        if ($environmentSetting == 'test') {
            $authUrl = 'https://test.instamojo.com/api/1.1/payment-requests/';
        } else {
            $authUrl = 'https://www.instamojo.com/api/1.1/payment-requests/';
        }

        $paymentAmount = rgar($entry, 'payment_amount');

        if (empty($paymentAmount) === true) {
            $paymentAmount = GFCommon::get_order_total($form, $entry);
            gform_update_meta($entry['id'], 'payment_amount', $paymentAmount);
            $entry['payment_amount'] = $paymentAmount;
        }

        $entry['payment_status'] = 'Pending';
        $entry['payment_method'] = 'Instamojo';

        $feed = $this->get_payment_feed($entry, $form);
        $customerFields = $this->get_customer_fields($form, $feed, $entry);

        $returnUrl = $page . '?page=gf_instamojo_callback';

        $authBody = array(
            'purpose' => $form['fields'][3]['label'],
            'amount' => (int)$paymentAmount,
            'buyer_name' => !empty($customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_NAME]) ? $customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_NAME] : 'Test',
            'email' => !empty($customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_EMAIL]) ? $customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_EMAIL] : 'test@test.com',
            'phone' => !empty($customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_PHONE]) ? $customerFields[self::GF_INSTAMOJO_BILLING_INFORMATION_PHONE] : '9999999999',
            'redirect_url' => $returnUrl,
            'allow_repeated_payments' => false,
            'send_email' => false,
            'send_sms' => false
        );

        $authPayload = array(
            'body' => http_build_query($authBody),
            'headers' => array(
                'X-Api-Key' => $apiKey,
                'X-Auth-Token' => $authToken,
            ),
        );

        $response = wp_remote_post($authUrl, $authPayload);

        if (!is_wp_error($response)) {
            $arrayResponse = json_decode(wp_remote_retrieve_body($response), true);

            if ($arrayResponse['success'] === true) {
                $paymentRequestId = $arrayResponse['payment_request']['id'];
                $redirectUrl = $arrayResponse['payment_request']['longurl'];

                $entry[self::GF_INSTAMOJO_ORDER_ID] = $entry['id'] . '_' . $paymentRequestId;

                gform_update_meta($entry['id'], self::GF_INSTAMOJO_ORDER_ID, $entry[self::GF_INSTAMOJO_ORDER_ID]);
                GFAPI::update_entry($entry);

                setcookie(self::GF_INSTAMOJO_ORDER_ID, $entry['id'], time() + self::COOKIE_DURATION, COOKIEPATH, COOKIE_DOMAIN, false, true);
                
                $html =
                '
                <script language="javascript">
                    document.addEventListener("DOMContentLoaded", function(event) {
                        window.location.href = "' . $redirectUrl . '";
                    });
                </script>
                ';

                $allowed_html = array(
                    'script' => array(
                        'language' => 'javascript',
                    )
                );

                echo wp_kses($html, $allowed_html);
            }
        }
    }

    /**
     * Check is callback is valid
     * 
     * @return bool
     */
    public function is_callback_valid()
    {
        $response = false;

        if (
            (isset($_GET['page']) && ($_GET['page'] == 'gf_instamojo_callback')) && 
            (isset($_COOKIE[self::GF_INSTAMOJO_ORDER_ID]) && ($_COOKIE[self::GF_INSTAMOJO_ORDER_ID] != '')) && 
            (isset($_GET['payment_request_id']) && ($_GET['payment_request_id'] != ''))
        ) {
            $response = true;
        }

        return $response;
    }

    /**
     * Handling callback response
     * 
     * @return array
     */
    public function callback()
    {
        $instamojoOrderId = sanitize_text_field($_COOKIE[self::GF_INSTAMOJO_ORDER_ID]);
        $entry = GFAPI::get_entry($instamojoOrderId);
        $feed = $this->get_payment_feed($entry);

        if (is_array($entry) === true) {
            $paymentRequestId = sanitize_text_field($_GET['payment_request_id']);

            $environmentSetting = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_ENVIRONMENT]);
            $apiKey = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_API_KEY]);
            $authToken = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_AUTH_TOKEN]);
            
            if ($environmentSetting == 'test') {
                $authUrl = 'https://test.instamojo.com/api/1.1/payment-requests/' . $paymentRequestId . '/';
            } else {
                $authUrl = 'https://www.instamojo.com/api/1.1/payment-requests/' . $paymentRequestId . '/';
            }
    
            $authPayload = array(
                'headers' => array(
                    'X-Api-Key' => $apiKey,
                    'X-Auth-Token' => $authToken,
                ),
            );
    
            $response = wp_remote_get($authUrl, $authPayload);
    
            if (!is_wp_error($response)) {
                $arrayResponse = json_decode(wp_remote_retrieve_body($response), true);

                $action = array(
                    'id' => $paymentRequestId,
                    'type' => 'fail_payment',
                    'payment_method' => 'Instamojo',
                    'amount' => $entry['payment_amount'],
                    'entry_id' => $entry['id'],
                    'error' => null
                );

                if (($arrayResponse['success'] === true) && ($arrayResponse['payment_request']['payments']) && ($arrayResponse['payment_request']['payments'][0]['status'] === 'Credit')) {
                    $paymentRequestId = $arrayResponse['payment_request']['id'];
                    $paymentId = $arrayResponse['payment_request']['payments'][0]['payment_id'];
                    $amount = $arrayResponse['payment_request']['payments'][0]['amount'];

                    if (($entry['payment_status'] == 'Pending') && ($instamojoOrderId == $entry['id']) && ($amount == $entry['payment_amount'])) {
                        $action = array(
                            'id' => $paymentRequestId,
                            'type' => 'complete_payment',
                            'payment_method' => 'Instamojo',
                            'transaction_id' => $paymentId,
                            'amount' => $amount,
                            'entry_id' => $entry['id'],
                            'error' => null
                        );
                    }
                }

                return $action;
            }
        }
    }

    /**
     * Handle callback after post request
     * 
     * @param $callback_action
     * @param $callback_result
     * @return false|void
     */
    public function post_callback($callback_action, $callback_result)
    {
        global $wp;

        if (is_wp_error($callback_action) || !$callback_action) {
            return false;
        }
        
        $entry = null;
        $feed = null;

        if (isset($callback_action['entry_id']) === true) {
            $entry = GFAPI::get_entry($callback_action['entry_id']);
            $feed = $this->get_payment_feed($entry);

            $sUrl = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_SUCCESS_URL]);
            $fUrl = sanitize_text_field($feed['meta'][self::GF_INSTAMOJO_FAILURE_URL]);

            if (empty($entry['transaction_id']) === false) {
                header('Location: '.get_permalink($sUrl).'');
                exit;
            }

            if (empty($entry['transaction_id']) === true) {
                header('Location: '.get_permalink($fUrl).'');
                exit;
            }
        }
    }

    /**
     * Initiate notification event
     */
    public function init()
    {
        add_filter('gform_notification_events', array($this, 'notification_events'), 10, 2);
        $this->_supports_frontend_feeds = true;
        parent::init();
    }

    /**
     * Added custom event to provide option to chose event to send notifications
     * 
     * @param array $notification_events
     * @param array $form
     * @return array
     */
    public function notification_events($notification_events, $form)
    {
        $hasInstamojoFeed = function_exists('gf_instamojo') ? gf_instamojo()->get_feeds($form['id']) : false;
        if ($hasInstamojoFeed) {
            $paymentEvents = array(
                'complete_payment' => __('Payment Completed', 'gravityforms'),
            );
            return array_merge($notification_events, $paymentEvents);
        }

        return $notification_events;
    }

    /**
     * Add post payment action after payment success
     * 
     * @param array $entry
     * @param array $action
     */
    public function post_payment_action($entry, $action)
    {
        $form = GFAPI::get_form($entry['form_id']);
        GFAPI::send_notifications($form, $entry, rgar($action, 'type'));
    }
}
?>