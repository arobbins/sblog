<?php

/**
 * Chimpy MailChimp API Wrapper Class
 * Partly based on official MailChimp API Wrapper for PHP
 * 
 * @class Chimpy_Mailchimp
 * @package Chimpy
 * @author RightPress
 */
if (!class_exists('Chimpy_Mailchimp')) {
    class Chimpy_Mailchimp
    {
        /**
         * API Key
         */
        public $apikey;
        public $ch;
        public $root = 'https://api.mailchimp.com/2.0';

        /**
         * Constructor class
         * 
         * @access public
         * @param string $apikey
         * @return void
         */
        public function __construct($apikey) {

            // Set up API Key
            if (!$apikey) {
                throw new Exception('You must provide a MailChimp API key');
            }

            $this->apikey = $apikey;

            // Set up host to connect to
            $dc = 'us1';

            if (strstr($this->apikey, '-')){
                list($key, $dc) = explode('-', $this->apikey, 2);

                if (!$dc) {
                    $dc = 'us1';
                }
            }

            $this->root = str_replace('https://api', 'https://' . $dc . '.api', $this->root);
            $this->root = rtrim($this->root, '/') . '/';

            // Initialize Curl
            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_USERAGENT, 'MailChimp-PHP/2.0.4');
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_HEADER, false);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);
        }

        /**
         * Destructor class
         * 
         * @access public
         * @return void
         */
        public function __destruct() {
            curl_close($this->ch);
        }

        /**
         * Make call to MailChimp
         * 
         * @param type $apikey
         * @param type $opts
         */
        public function call($url, $params) {

            $params['apikey'] = $this->apikey;
            $params = json_encode($params);

            $ch = $this->ch;

            curl_setopt($ch, CURLOPT_URL, $this->root . $url . '.json');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $start = microtime(true);

            $response_body = curl_exec($ch);
            $info = curl_getinfo($ch);
            $time = microtime(true) - $start;

            if (curl_error($ch)) {
                throw new Exception('API call to ' . $url . ' failed: ' . curl_error($ch));
            }

            $result = json_decode($response_body, true);

            if (floor($info['http_code'] / 100) >= 4) {
                if ($result['status'] !== 'error' || !$result['name']) {
                    throw new Exception('We received an unexpected error: ' . json_encode($result));
                }

                throw new Exception($result['error'], $result['code']);
            }

            return $result;
        }

        /**
         * Get list
         * 
         * @access public
         * @param array $filters
         * @param int $start
         * @param int $limit
         * @param string $sort_field
         * @param $string $sort_dir
         * @return mixed
         */
        public function lists_get_list($filters = array(), $start = 0, $limit = 25, $sort_field = 'created', $sort_dir = 'DESC')
        {
            $params = array('filters' => $filters, 'start' => $start, 'limit' => $limit, 'sort_field' => $sort_field, 'sort_dir' => $sort_dir);
            return $this->call('lists/list', $params);
        }

        /**
         * Get merge vars
         * 
         * @access public
         * @param string $id
         * @return mixed
         */
        public function lists_merge_vars($id)
        {
            $params = array('id' => $id);
            return $this->call('lists/merge-vars', $params);
        }

        /**
         * Get interest groupings
         * 
         * @access public
         * @param string $id
         * @param bool $counts
         * @return mixed
         */
        public function lists_interest_groupings($id, $counts = false)
        {
            $params = array('id' => $id, 'counts' => $counts);
            return $this->call('lists/interest-groupings', $params);
        }

        /**
         * Subscribe to list
         * 
         * @access public
         * @param string $id
         * @param string $email
         * @param mixed $merge_vars
         * @param string $email_type
         * @param bool $double_optin
         * @param bool $update_existing
         * @param bool $replace_interests
         * @param bool $send_welcome
         * @return mixed
         */
        public function lists_subscribe($id, $email, $merge_vars = null, $email_type = 'html', $double_optin = true, $update_existing = false, $replace_interests = true, $send_welcome = false)
        {
            $params = array('id' => $id, 'email' => $email, 'merge_vars' => $merge_vars, 'email_type' => $email_type, 'double_optin' => $double_optin, 'update_existing' => $update_existing, 'replace_interests' => $replace_interests, 'send_welcome' => $send_welcome);
            return $this->call('lists/subscribe', $params);
        }

        /**
         * Unsubscribe from list
         * 
         * @access public
         * @param string $id
         * @param string $email
         * @param bool $delete_member
         * @param bool $send_goodbye
         * @param bool $send_notify
         * @return mixed
         */
        public function lists_unsubscribe($id, $email, $delete_member = false, $send_goodbye = true, $send_notify = true)
        {
            $params = array('id' => $id, 'email' => $email, 'delete_member' => $delete_member, 'send_goodbye' => $send_goodbye, 'send_notify' => $send_notify);
            return $this->call('lists/unsubscribe', $params);
        }

        /**
         * Ping MailChimp
         * 
         * @access public
         * @return mixed
         */
        public function helper_ping()
        {
            $params = array();
            return $this->call('helper/ping', $params);
        }

        /**
         * Get account details
         * 
         * @access public
         * @param array $exclude
         * @return mixed
         */
        public function helper_account_details($exclude = array())
        {
            $params = array('exclude' => $exclude);
            return $this->call('helper/account-details', $params);
        }

    }
}
