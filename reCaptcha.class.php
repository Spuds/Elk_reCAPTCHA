<?php

function icv_recaptcha(&$known_verifications)
{
	// Make sure its not already there.
	$key = array_search('ReCaptcha', $known_verifications);
	unset($known_verifications[$key]);

	$known_verifications[] = 'ReCaptcha';
	loadLanguage('reCaptcha');
}

/**
 * Class Verification_Controls_ReCaptcha
 */
class Verification_Controls_ReCaptcha implements Verification_Controls
{
	private $_options = null;
	private $_site_key = null;
	private $_secret_key = null;
	private $_recaptcha = null;
	private $_userIP = null;

	/**
	 * Verification_Controls_ReCaptcha constructor.
	 *
	 * @param null|array $verificationOptions
	 */
	public function __construct($verificationOptions = null)
	{
		global $modSettings, $user_info;

		require_once(EXTDIR . '/recaptchalib.php');

		$this->_site_key = !empty($modSettings['recaptcha_site_key']) ? $modSettings['recaptcha_site_key'] : '';
		$this->_secret_key = !empty($modSettings['recaptcha_secret_key']) ? $modSettings['recaptcha_secret_key'] : '';
		$this->_userIP = $user_info['ip'];

		if (!empty($verificationOptions))
		{
			$this->_options = $verificationOptions;
		}
	}

	/**
	 * Show the verification if its enabled
	 *
	 * @param bool $isNew
	 * @param bool $force_refresh
	 *
	 * @return bool
	 */
	public function showVerification($isNew, $force_refresh = true)
	{
		global $modSettings;

		$this->show_captcha = false;

		// Language parameter
		$lang = !empty($modSettings['recaptcha_language']) ? '&hl=' . $modSettings['recaptcha_language'] : '';

		// On and valid, well non empty keys.
		if (!empty($modSettings['recaptcha_enable']) && !empty($this->_site_key) && !empty($this->_secret_key))
		{
			$this->show_captcha = true;

			loadTemplate('reCaptcha');
			loadTemplate('VerificationControls');
			addInlineJavascript('
     			var onloadreCaptcha = function() {
       				grecaptcha.render("g-recaptcha", {
							"sitekey" : "' . $this->_site_key . '"
					});
				};');
			loadJavascriptFile('https://www.google.com/recaptcha/api.js?onload=onloadreCaptcha&render=explicit' . $lang, array('defer' => true, 'async' => 'true'));
		}

		return $this->show_captcha;
	}

	/**
	 * Done by the JS script
	 *
	 * @param bool $refresh
	 *
	 * @return bool
	 */
	public function createTest($refresh = true)
	{
	}

	/**
	 * Return an array that will be used in VerificationControls.template
	 *
	 * @return array
	 */
	public function prepareContext()
	{
		return array(
			'template' => 'recaptcha',
			'values' => array(
				'site_key' => $this->_site_key,
			)
		);
	}

	/**
	 * Run the test, return the result
	 *
	 * @return bool|string
	 */
	public function doTest()
	{
		if (!empty($_POST["g-recaptcha-response"]))
		{
			$this->_recaptcha = new ReCaptcha($this->_secret_key);
			$resp = $this->_recaptcha->verifyResponse($this->_userIP, $_POST['g-recaptcha-response']);

			if (!$resp->success)
			{
				return 'wrong_verification_code';
			}
		}
		else
		{
			return 'wrong_verification_code';
		}

		return true;
	}

	/**
	 * Visible form? you bet it is
	 *
	 * @return bool
	 */
	public function hasVisibleTemplate()
	{
		return true;
	}

	/**
	 * Settings for the ACP
	 *
	 * @return array
	 */
	public function settings()
	{
		global $txt;

		// Visual verification.
		$config_vars = array(
			array('title', 'recaptcha_verification'),
			array('desc', 'recaptcha_desc'),
			array('check', 'recaptcha_enable'),
			array('text', 'recaptcha_site_key'),
			array('text', 'recaptcha_secret_key'),
			array('text', 'recaptcha_language', 6, 'postinput' => $txt['recaptcha_language_desc']),
		);

		return $config_vars;
	}
}
