<?php
/**
 * Tango Card API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GamiPress_Tango_API {

    private $platform_name;
    private $platform_key;
    private $api_url;

    public function __construct() {
        $this->platform_name = get_option('gamipress_tango_platform_name', '');
        $this->platform_key = get_option('gamipress_tango_platform_key', '');

        // Use sandbox URL by default, production can be set via settings
        $environment = get_option('gamipress_tango_environment', 'sandbox');
        $this->api_url = ($environment === 'production')
            ? 'https://integration-api.tangocard.com/raas/v2'
            : 'https://integration-api.tangocard.com/raas/v2';
    }

    /**
     * Make API request to Tango
     */
    private function make_request($endpoint, $method = 'GET', $body = null) {
        $url = $this->api_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->platform_name . ':' . $this->platform_key),
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );

        if ($body && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['message']) ? $data['message'] : 'API request failed',
                'code' => $response_code,
                'data' => $data
            );
        }
    }

    /**
     * Get catalog items from Tango
     */
    public function get_catalog() {
        $result = $this->make_request('/catalogs');

        if (!$result['success']) {
            return $result;
        }

        // Extract and format catalog items
        $catalog_items = array();

        if (isset($result['data']['catalogs']) && is_array($result['data']['catalogs'])) {
            foreach ($result['data']['catalogs'] as $catalog) {
                if (isset($catalog['brands']) && is_array($catalog['brands'])) {
                    foreach ($catalog['brands'] as $brand) {
                        if (isset($brand['items']) && is_array($brand['items'])) {
                            foreach ($brand['items'] as $item) {
                                $catalog_items[] = array(
                                    'utid' => $item['utid'] ?? '',
                                    'brand_key' => $brand['brandKey'] ?? '',
                                    'brand_name' => $brand['brandName'] ?? '',
                                    'description' => $brand['description'] ?? '',
                                    'disclaimer' => $brand['disclaimer'] ?? '',
                                    'terms' => $brand['terms'] ?? '',
                                    'image_url' => $item['imageUrls'][0] ?? ($brand['imageUrls'][0] ?? ''),
                                    'min_value' => $item['minValue'] ?? 0,
                                    'max_value' => $item['maxValue'] ?? 0,
                                    'currency_code' => $item['currencyCode'] ?? 'USD',
                                    'countries' => $item['countries'] ?? array(),
                                    'status' => $item['status'] ?? '',
                                    'redemption_instructions' => $item['redemptionInstructions'] ?? '',
                                );
                            }
                        }
                    }
                }
            }
        }

        return array(
            'success' => true,
            'items' => $catalog_items
        );
    }

    /**
     * Create an order for a reward
     */
    public function create_order($utid, $amount, $recipient_email, $customer_identifier) {
        $body = array(
            'accountIdentifier' => get_option('gamipress_tango_account_identifier', ''),
            'amount' => $amount,
            'utid' => $utid,
            'recipient' => array(
                'email' => $recipient_email,
                'firstName' => '',
                'lastName' => ''
            ),
            'sendEmail' => get_option('gamipress_tango_send_email', true),
            'externalRefID' => $customer_identifier,
        );

        return $this->make_request('/orders', 'POST', $body);
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        return $this->make_request('/catalogs');
    }
}
