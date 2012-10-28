<?php

/**
 * This is an INCOMPLETE class for interacting with the
 * Instagram API with WordPress. This code needs refactored. 
 * If you are going to use it, use it only as a starting
 * point until it is complete.
 *
 * The complete version will come with tests.
 */
class Jt_Instagram_Api {

	/**
	 * You have to create an application to get this
	 *
	 * @link http://instagram.com/developer/clients/register/
	 */
	protected $client_id = 'YOUR CLIENT ID HERE';

	/**
	 * This also comes from your application
	 *
	 * @link instagram.com/developer/clients/manage/
	 */
	protected $client_secret = 'YOUR CLIENT SECRET KEY HERE';

	/**
	 * URI users are redirected to after authenticating
	 *
	 * @link instagram.com/developer/clients/manage/
	 */
	protected $redirect_uri = 'YOUR REDIRECT-URI HERE';

	/**
	 * The access token you receive after authorizing the app
	 *
	 * There are two methods of authentication. One is Server-side (explicit)
	 * and the other is Client-Side.
	 *
	 * If you are going to be the only one using the app you create
	 * to show photos on your own personal website. The easiest way to
	 * get your access token is to use the implicit method.
	 *
	 * Just do go to this url in a browser but fill in the CLIENT_ID and
	 * REDIRECT-URI with your own.
	 * https://instagram.com/oauth/authorize/?client_id=CLIENT-ID&redirect_uri=REDIRECT-URI&response_type=token
	 *
	 * Your access token will be in the url fragment and you can simply
	 * copy and paste it below. Note this is not typically how you would do this,
	 * this is just a quick way to get up and running to start playing with the API.
	 *
	 * @link http://instagram.com/developer/authentication/
	 */
	public $token = 'YOUR ACCESS TOKEN HERE';


	/**
	 * Return the response of a call to wp_remote_get
	 *
	 * @param string $endpoint
	 * @param array $parameters
	 */
	public function remote_get( $endpoint, $parameters ) {

		$query = http_build_query($parameters);

		return wp_remote_get('https://api.instagram.com/v1/' . $endpoint . '?' . $query);
	}

	/**
	 * Returns the json_decoded response body
	 *
	 * @param string $endpoint
	 * @param array $parameters
	 */
	public function decoded_response_body( $endpoint, $parameters ) {

		$response = $this->remote_get($endpoint, $parameters );

		return json_decode($response['body']);
	}

	/**
	 * Returns the json decoded response of a recent media request
	 *
	 * @param string $username
	 * @param array $parameters
	 *
	 * @link http://instagram.com/developer/endpoints/users/#get_users_media_recent
	 */
	public function get_recent_media( $username, $parameters = array() ) {

		// return the cached data if available
		if ( false !== ( $recent_media = get_transient( $username . '_instagram_recent_media' ) ) ) {
			return $recent_media;
		}

		$recent_media = false;

		$user_id = $this->get_user_id($username);

		$defaults = array(
			'access_token' => $this->token,
			'count'        => 10
		);

		$parameters = wp_parse_args($parameters, $defaults);

		$recent_media = $this->decoded_response_body( '/users/' . $user_id . '/media/recent', $parameters );

		if ( $recent_media ) {
			set_transient( $username . '_instagram_recent_media', $recent_media, 60*60 );
		}

		return $recent_media;

	}

	/**
	 * Returns the user_id of $username if found
	 *
	 * @param string $username the username to search for
	 * @link http://instagram.com/developer/endpoints/users/#get_users_search
	 */
	public function get_user_id( $username ) {

		// return the cached data if available
		if ( false !== ( $user_id = get_transient( $username . '_instagram_user_id' ) ) ) {
			return $user_id;
		}

		$user_id = false;

		$parameters = array(
			'q'            => $username,
			'access_token' => $this->token
		);

		$body = $this->decoded_response_body('users/search', $parameters);

		$users_returned = $body->data;

		foreach ($users_returned as $user) {
			if ( $user->username == $username ) {
				$user_id = $user->id;
			}
		}

		if ( $user_id ) {
			set_transient( $username . '_instagram_user_id', $user_id, 60*60*24*7 );
		}

		return $user_id;
	}
}