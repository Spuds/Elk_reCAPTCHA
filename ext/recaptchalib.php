<?php
/**
 * This is a PHP library that handles calling reCAPTCHA,
 * UPDATED/MODIFIED to integrate with ElkArte
 *
 * @copyright Copyright (c) 2014, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A ReCaptchaResponse is returned from checkAnswer().
 */
class ReCaptchaResponse
{
	public $success;
	public $errorCodes;
}

class ReCaptcha
{
	private static $_siteVerifyUrl = "https://www.google.com/recaptcha/api/siteverify?";
	private $_secret;

	/**
	 * Constructor.
	 *
	 * @param string $secret shared secret between site and ReCAPTCHA server.
	 */
	public function __construct($secret)
	{
		$this->_secret = $secret;
	}

	/**
	 * Encodes the given data into a query string format.
	 *
	 * @param array $data array of string elements to be encoded.
	 *
	 * @return string - encoded request.
	 */
	private function _encodeQS($data)
	{
		$req = array();
		foreach ($data as $key => $value)
		{
			$req[] = $key . '=' . urlencode(stripslashes($value));
		}

		// Put it all together
		return implode('&', $req);
	}

	/**
	 * Submits an HTTP POST to a reCAPTCHA server.
	 *
	 * @param array $data array of parameters to be sent.
	 */
	private function _submitHTTPPost($data)
	{
		require_once(SUBSDIR . '/Package.subs.php');

		$req = $this->_encodeQS($data);
		return fetch_web_data(self::$_siteVerifyUrl, $req);
	}

	/**
	 * Calls the reCAPTCHA siteverify API to verify whether the user passes
	 * CAPTCHA test.
	 *
	 * @param string $remoteIp IP address of end user.
	 * @param string $response response string from recaptcha verification.
	 *
	 * @return ReCaptchaResponse
	 */
	public function verifyResponse($remoteIp, $response)
	{
		// Discard empty solution submissions
		if (empty($response))
		{
			$recaptchaResponse = new ReCaptchaResponse();
			$recaptchaResponse->success = false;
			$recaptchaResponse->errorCodes = 'missing-input';

			return $recaptchaResponse;
		}

		$getResponse = $this->_submitHTTPPost(
			array(
				'secret' => $this->_secret,
				'remoteip' => $remoteIp,
				'response' => $response
			)
		);
		$answers = json_decode($getResponse, true);
		$recaptchaResponse = new ReCaptchaResponse();

		if (trim($answers['success']) == true)
		{
			$recaptchaResponse->success = true;
		}
		else
		{
			$recaptchaResponse->success = false;
			$recaptchaResponse->errorCodes = $answers['error-codes'];
		}

		return $recaptchaResponse;
	}
}
