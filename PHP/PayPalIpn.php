<?php
/**
 * @modified by Lucas Maliszewski <lucascube@gmail.com>
 */
namespace PayPalIpn;

/**
 * Class PayPalIpn
 *
 * This is an adopted technology from a third-party that has been modified to be a class member.
 *
 * <a target="_blank" href="https://github.com/zendman/ipn-code-samples">original code</a>
 *
 * @link https://github.com/zendman/ipn-code-samples
 * @package PayPalIpn
 * @filesource
 */
class PayPalIpn {
	/**
	 * @todo when ready turn DEBUG to false.
	 */
	const DEBUG = true;
	const SANDBOX = true;
	const LOGFILE = '/var/log/httpd/PAYPAL.LOG';
	const DATABASE = 'PayPalIpn';
	const COLLECTION = 'transactions';
	const PAYPAL_URL = 'https://www.paypal.com/cgi-bin/webscr';
	const PAYPAL_SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

	/**
	 * Contains the array of post values given.
	 * @var $rpd array
	 */
	private $rpd;
	/**
	 * Contains the curl post parameters generated from $this->prd
	 *
	 * @see $this->prd
	 * @var $req string
	 */
	private $req;
	/**
	 * Contains the Curl object
	 * @var $ch resource
	 */
	private $ch;
	/**
	 * Contains the result sets from the curl call
	 * @var $res string
	 */
	private $res;
	/**
	 * Contains the Mongo Client Wrapper that will interact with the built in PHP MongoDB driver.
	 * @var \Lib\MongoClientWrapper
	 * @see MongoClient()
	 */
	private $mc;

	/**
	 * @param \Lib\MongoClientWrapper $mcw
	 *
	 * @throws \Exception
	 */
	public function __construct(\Lib\MongoClientWrapper $mcw){
		$this->mc = $mcw;
		try{
			$this->mc->makeTheConnection(self::DATABASE, self::COLLECTION);
		}catch (Exception $e){
			throw $e;
		}
	} // end constructor

	/**
	 * Will filter through the post data and generate a paypal compliant call
	 */
	public function readPostData($raw_post_data){
		if(empty($raw_post_data) || !is_string($raw_post_data) ){
			throw new InvalidArgumentException('Raw post data given is invalid');
		}

		$this->rpd = $raw_post_data;

		$raw_post_array = explode('&', $this->rpd);
		$myPost = array();
		foreach ($raw_post_array as $keyval) {
			$keyval = explode ('=', $keyval);
			if (count($keyval) == 2)
				$myPost[$keyval[0]] = urldecode($keyval[1]);
		}
		// read the post from PayPal system and add 'cmd'
		$this->req = 'cmd=_notify-validate';
		if(function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($myPost as $key => $value) {
			/**
			 * FIXME Always will return false.
			 * @deprecated get_magic_quotes_gpc()
			 */
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			$this->req .= '&$key=$value';
		}
	} // end readPostData

	/**
	 * Cross check paypal
	 *
	 * This method reaches out to paypal server to obtain information to cross reference what paypal has.
	 *
	 * @throws UnexpectedValueException
	 */
	public function crossCheckPaypal(){
		$this->ch = curl_init();
		if(self::SANDBOX == true) {
			curl_setopt($this->ch, CURLOPT_URL, self::PAYPAL_SANDBOX_URL);
		} else {
			curl_setopt($this->ch, CURLOPT_URL, self::PAYPAL_URL);
		}
		curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->ch, CURLOPT_FORBID_REUSE, 1);
		if ($this->ch == FALSE) {
			throw new UnexpectedValueException('Curl was not initialized or access to the curl object has been denied.');
		}

		if(empty($this->req) || !is_string($this->req)){
			throw new UnexpectedValueException('Please run readPostData before attempting to make the call');
		}

		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->req);

		if(self::DEBUG == true) {
			curl_setopt($this->ch, CURLOPT_HEADER, 1);
			curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);
		}

		// Set TCP timeout to 30 seconds
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

		$this->res = curl_exec($this->ch);

		if (curl_errno($this->ch) != 0) // cURL error
		{
			if(self::DEBUG == true) {
				error_log(date('[Y-m-d H:i e] '). 'Can\'t connect to PayPal to validate IPN message: ' . curl_error($this->ch) . PHP_EOL, 3, self::LOGFILE);
			}
			curl_close($this->ch);
			exit;

		} else {
			// Log the entire HTTP response if debug is switched on.
			if(self::DEBUG == true) {
				error_log(date('[Y-m-d H:i e] '). 'HTTP request of validation request:'. curl_getinfo($this->ch, CURLINFO_HEADER_OUT) .' for IPN payload: {$this->req}' . PHP_EOL, 3, self::LOGFILE);
				error_log(date('[Y-m-d H:i e] '). 'HTTP response of validation request: ' . $this->res . PHP_EOL, 3, self::LOGFILE);
			}
			curl_close($this->ch);
		}
	} // end make the call

	/**
	 * Verify Valid Purchase
	 *
	 * Verifies the values coming back to insure the information given are legitimate. In verified it will write to the
	 * database.
	 *
	 * @throws \Exception
	 * @throws \UnexpectedValueException
	 */
	public function verifyValidPurchase(){
		if(empty($this->res) || !is_string($this->res)){
			throw new \UnexpectedValueException('The results from the curl call are not valid: {$this->res}');
		}

		$tokens = explode('\r\n\r\n', trim($this->res));
		$res = trim(end($tokens));

		if (strcmp ($res, 'VERIFIED') == 0) {
			// write to the database
			parse_str($this->req, $dataArray);

			try{
				$res = $this->mc->put($dataArray);
			}catch (\Exception $e){
				throw $e;
			}

			// Response we should get back
			if(!empty($res)
				&& (array_key_exists('err', $res) && !empty($res['err']))){
				throw new \UnexpectedValueException(json_encode($res));
			}

			if(self::DEBUG == true) {
				error_log('['.date('r').'] [notice] Finished!' . PHP_EOL, 3, self::LOGFILE);
			}

		} else if (strcmp ($res, 'INVALID') == 0) {
			// log for manual investigation
			// Add business logic here which deals with invalid IPN messages
			if(self::DEBUG == true) {
				error_log(date('[Y-m-d H:i e] '). 'Invalid IPN:' . $this->req . PHP_EOL, 3, self::LOGFILE);
			}
		}
	} // end verify
} // end class