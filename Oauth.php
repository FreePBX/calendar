<?php
namespace FreePBX\modules\Calendar;

class Oauth {
    private $access_token = null;
	private $tenant = null;
	private $key = null;
	private $secret = null;
	private $instance_url = null;
	private $refresh_token = null;
	private $pbxURL = null;
	private $outlookurl = 'https://login.microsoftonline.com/';
	private $scope = 'Calendars.ReadWrite offline_access User.Read';

    public function __construct($tenant = null,$key = null, $secret = null, $access_token = null, $refresh_token = null, $pbxURL = null, $outlookurl = 'https://login.microsoftonline.com/') {
        if(!empty($instance_url)){
            $this->setInstance($instance_url);
        }
        if(!empty($key)){
            $this->setKey($key);
        }
        if(!empty($secret)){
            $this->setSecret($secret);
        }
        if(!empty($access_token)){
            $this->setToken($access_token);
        }
        if(!empty($refresh_token)){
            $this->setRefreshToken($refresh_token);
        }
        if(!empty($pbxURL)){
            $this->setPBXURL($pbxURL);
        }
        if(!empty($outlookurl)){
            $this->setOutlookUrl($outlookurl);
        }
        if(!empty($scope)){
            $this->setScope($scope);
        }
		if(!empty($tenant)){
            $this->setTenant($tenant);
        }
    }

    //Getters and Setters.
	public function getAuthURL($id, $pbxurl = '') {
		$oauthURL = $this->outlookurl.'/'.$this->tenant.'/oauth2/v2.0/authorize';
		$pbxURL = empty($pbxurl)?$this->pbxURL:$pbxurl;
		$pbxURL = rtrim($pbxURL,'/');
		if($oauthURL && $this->key && $pbxURL && $this->scope) {
			return sprintf('%s?client_id=%s&redirect_uri=%s/admin/config.php?display=calendar&response_type=code&scope=%s&state=%s&response_mode=query',$oauthURL,$this->key,$pbxURL,$this->scope,$id);
		} else {
			return '';
		}
	}
	public function setTenant($tenant) {
		$this->tenant = $tenant;
	}
	public function setInstance($instance_url) {
		$this->instance_url = $instance_url;
	}
	public function setKey($key) {
		$this->key = $key;
	}
	public function setSecret($secret) {
		$this->secret = $secret;
	}
	public function setToken($access_token) {
		$this->token = $access_token;
	}
	public function setRefreshToken($refresh_token) {
		$this->refresh_token = $refresh_token;
	}

	public function setPBXURL($pbxURL) {
		$this->pbxURL = $pbxURL;
	}
    public function setScope($scope) {
		$this->scope = $scope;
	}
	public function getInstance() {
		return empty($this->instance_url)?false:$this->instance_url;
	}

	public function getToken() {
		return empty($this->token)?false:$this->token;
	}
    public function getScope() {
		return empty($this->scope)?false:$this->scope;
	}
	public function getRefreshToken() {
		return empty($this->refresh_token)?false:$this->refresh_token;
	}

	public function setOutlookUrl($outlookurl) {
		$this->outlookurl = rtrim($outlookurl,'/');
	}

	public function getEvents() {
		$ch = curl_init();
		curl_setopt($cURLConnection, CURLOPT_URL, 'https://hostname.tld/phone-list');
	}

	public function getAuthToken($acode) {
		$oauthURL = $this->outlookurl.'/'.$this->tenant.'/oauth2/v2.0/token';
		$xmlpost  = [
			"client_id" => $this->key,
			"scope" => $this->scope,
			"code" => $acode,
			"redirect_uri" => $this->pbxURL."admin/config.php?display=calendar",
			"grant_type" => "authorization_code",
			"client_secret" => $this->secret,
		];
		
		$cpt = curl_init($oauthURL);
		curl_setopt_array($cpt, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => http_build_query($xmlpost),
		]);
		
		$result = curl_exec($cpt);

		return $result;
	}

	public function getCalEvents($atoken) {
		$cpt = curl_init("https://graph.microsoft.com/v1.0/me/calendar/events");
		curl_setopt($cpt, CURLOPT_HTTPHEADER,
				array(
					'Authorization: Bearer '.$atoken,
				)
			);
		curl_setopt($cpt, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($cpt);
		return $result;
	}

	public function getTokenRefresh($outlookDetails) {
		$oauthURL = $this->outlookurl.'/'.$this->tenant.'/oauth2/v2.0/token';
		$xmlpost  = [
			"client_id" => $this->key,
			"scope" => $this->scope,
			"refresh_token" => $outlookDetails['refresh_token'],
			"grant_type" => "refresh_token",
			"client_secret" => $this->secret,
		];

		$cpt = curl_init($oauthURL);
		curl_setopt_array($cpt, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => http_build_query($xmlpost),
		]);

		$result = curl_exec($cpt);

		return $result;
	}

	public function getUserCalendars($username,$atoken)
	{
		try {
			$cpt = curl_init("https://graph.microsoft.com/v1.0/users/".$username."/calendars");
			curl_setopt($cpt, CURLOPT_HTTPHEADER,
					array(
						'Authorization: Bearer '.$atoken,
						'Content-Type: application/json'
					)
				);
			curl_setopt($cpt, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cpt);
			return json_decode($result, true);
		} catch(\Exception $e) {
			$message = [
				'type' => 'danger',
				'message' => $e->getMessage()
			];
		}
	}
}
