<?php

/**
 * G2APay PHP Library
 * @author  	Frontlabs, <info@frontlabs.co>
 * @copyright 	Copyright (c) 2016 Frontlabs
 * @license 	https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace G2APay;

class G2APay
{
	const API_URL = 'https://checkout.pay.g2a.com';
	const API_TEST_URL = 'https://checkout.test.pay.g2a.com';

	const REST_API_URL = 'https://www.pay.g2a.com';
	const REST_API_TEST_URL = 'https://www.test.pay.g2a.com';

	const CURRENCY_USD = 'USD';
	const CURRENCY_EUR = 'EUR';

	private $apiUrl;
	private $restApiUrl;

	private $apiHash;
	private $apiSecret;
	private $apiEmail;

	private $urlSuccess;
	private $urlFail;

	private $currency;

	private $items = [];

	public function __construct(string $apiHash, string $apiSecret, string $apiEmail, string $urlSuccess = '', string $urlFail = '', string $currency = 'USD')
	{
		$this->apiUrl = self::API_URL;
		$this->restApiUrl = self::REST_API_URL;
		$this->apiHash = $apiHash;
		$this->apiEmail = $apiEmail;
		$this->apiSecret = $apiSecret;
		$this->urlSuccess = $urlSuccess;
		$this->urlFail = $urlFail;
		$this->currency = $currency;
	}

	public function addItem($sku, string $name, int $quantity, $id, float $price, string $url = '', string $extra = '', string $type = '')
	{
		$this->items[] = [
			'sku' => $sku,
			'name' => $name,
			'amount' => floatval($quantity * $price),
			'qty' => $quantity,
			'id' => $id,
			'price' => $price,
			'url' => $url,
			'extra' => $extra,
			'type' => $type,
		];

		return $this;
	}

	public function test()
	{
		$this->apiUrl = self::API_TEST_URL;
		$this->restApiUrl = self::REST_API_TEST_URL;

		return $this;
	}

	public function createOrder($orderId, array $extra = [])
	{
		// Temporary save api url, then reset to default
		$url = $this->apiUrl;
		$this->apiUrl = self::API_URL;

		// Calculate total price of items
		$amount = array_sum(array_column($this->items, 'amount'));

		// Prepare array with data to query G2A
		$fields = array_merge([
			'api_hash'		=> $this->apiHash,
			'hash'			=> $this->calculateHash($orderId, $amount),
			'order_id'		=> $orderId,
			'amount'		=> $amount,
			'currency'		=> $this->currency,
			// 'description' => '',
			// 'email'		 => '',
			'url_failure'	=> $this->urlFail,
			'url_ok'		=> $this->urlSuccess,
			// 'cart_type'	 => '' // 'physical' or 'digital'
			'items'			=> $this->items,
			// 'addresses'	 => [],
		], $extra);

		// Request API server
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url.'/index/createQuote');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		// Convert response from JSON text to PHP object/array
		$result = json_decode($response);

		if (isset($result->token)) {
			return [
				'success' => true,
				'url' => ($url.'/index/gateway?token='.$result->token)
			];
		} else {
			return [
				'success' => false,
				'message' => $result->message
			];
		}
	}

	public function checkTransaction(string $transactionCode = '')
	{
		// Temporary save rest api url, then reset to default
		$url = $this->restApiUrl;
		$this->restApiUrl = self::REST_API_URL;

		$headers = [
			'Authorization: '. $this->apiHash.';'.$this->calculateAuthHash(),
		];

		// Request API server
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url.'/rest/transactions/'.$transactionCode);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([]));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);
		curl_close($ch);

		// Convert response from JSON text to PHP object/array
		$result = json_decode($response);

		return $result;
	}

	private function calculateHash($orderId, $amount)
	{
		return hash('sha256', $orderId.number_format($amount, 2).$this->currency.$this->apiSecret);
	}

	private function calculateAuthHash()
	{
		return hash('sha256', $this->apiHash.$this->apiEmail.$this->apiSecret);
	}
}