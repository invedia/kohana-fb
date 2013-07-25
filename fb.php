<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @author: sblmasta <sblmasta@gmail.pl>
 * @copyright: sblmasta MIT License 2013
 */

require_once(APPPATH.'/vendor/facebook-sdk/src/facebook.php');

class Fb
{
	const APP_ID     = 'YOUR_APP_ID_HERE';
	const APP_SECRET = 'YOUR_APP_SECRET_HERE';

	protected $app_id;
	protected $app_secret;
	protected $object;

	public function __construct($app_id, $app_secret)
	{
		$this->app_id     = ($app_id ? $app_id : self::APP_ID);
		$this->app_secret = ($app_secret ? $app_secret : self::APP_SECRET);

		$this->object = new Facebook(array(
		  'appId'  => $this->app_id,
		  'secret' => $this->app_secret,
		));
	}

	public static function factory($app_id = NULL, $app_secret = NULL)
	{
		return new Fb($app_id, $app_secret);
	}

    /*
     * Get user object
     */
	public function getUser()
	{
		return ( $this->object->getUser() == 0 ? NULL : $this->object->getUser() );
	}

    /*
     * Get login url
     * You can set this into <a href="<?php echo $fb->getLoginUrl() ?>">Facebook Connect</a>
     */
	public function getLoginUrl($permissions = NULL)
	{
		if( !$permissions )
		{
			$permissions = 'email,user_location,user_website,user_photos';
		}

		return $this->object->getLoginUrl(array('scope' => $permissions));
	}

    /*
     * Session destroy for Facebook
     */
	public function getLogoutUrl()
	{
		return $this->object->getLogoutUrl();
	}

    /*
     * Execute API command
     * For example: $fb->api('/me')
     */
	public function api($cmd)
	{
		return $this->object->api($cmd);
	}

    /*
     * Check user if logged in
     * Return: boolean
     */
	public function is_logged()
	{
		if($this->object->getUser())
		{
			return TRUE;
		}

        return FALSE;
	}

    /*
     * Function check existing of user in 'users' table
     * You must add to 'users' column 'facebook_id' for User ID from facebook instance.
     */
	public function may_register()
	{
		$count = ORM::factory('user')->where('facebook_id','=',$this->user()->id)->and_where('email','=',$this->user()->email)->count_all();

		if($count == 0)
		{
			return TRUE;
		}

		return FALSE;
	}

    /*
     * Create an object from facebook API array with facebook user data.
     * Usage: $fb->user()->name
     * Debug: var_dump($fb->user()) for more details.
     */
	public function user()
	{
		if( $this->is_logged() )
		{
			$_user = new stdClass;

			foreach($this->api('/me') as $index => $value)
			{
				$_user->$index = $value;
			}

			return $_user;
		}

		return NULL;
	}

    /*
     * Universal function for logging and create an user account
     * Function check if user exists in database. Register account or sign in.
     */
	public function sign()
	{
		if(!Auth::instance()->logged_in('login'))
		{
			if($this->may_register())
			{
				$user                        = ORM::factory('user');
				$user->facebook_id           = $this->user()->id;
				$user->email                 = $this->user()->email;
				$user->password              = sha1($this->user()->id.'#'.uniqid());
				$user->save();

        			$user->add('roles', ORM::factory('role', array('name' => 'login')));
        
				/*
         			* You can add another columns for User Model
         			*/

				Request::current()->redirect(url::base());

			}
			else
			{
				$current_user = ORM::factory('user')
			                    ->where('facebook_id','=',$this->user()->id)
				            ->and_where('email','=',$this->user()->email)
			                    ->find();

				if( $current_user->loaded() )
				{
					Auth::instance()->force_login($current_user->email);
					Request::current()->redirect(url::base());
				}
				else
				{
					Request::current()->redirect(url::base());
				}
			}
		}
		else
		{
			Auth::instance()->logout();
			$this->sign();
		}
	}
}
