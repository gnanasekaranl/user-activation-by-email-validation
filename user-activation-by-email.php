<?php
/*
 Plugin Name: User Activation By E-Mail
 Plugin URI: https://wordpress.org/plugins/user-activation-by-e-mail/
 Description: When user register into wordpress website its send mail to register mail ID after confirmation only user registration will complete.
 Author: Gnanasekaran
 Version: 1.0.1
 License: GPL V2
 Author URI: https://github.com/gnanasekaranl
 */
class email_verification {

  /**
   * The only instance of email_verification.
   *
   * @var email_verification
   */
  private static $instance;

  /**
   * Returns the main instance.
   *
   * @return email_verification
   */
  public static function instance() {
    if ( !isset( self::$instance ) ) {
      self::$instance = new email_verification();
    }
    return self::$instance;
  }

  private function __construct() {
  	//login auth verify
  	add_filter( 'wp_authenticate_user', array( $this, 'authenticate_user' ) );
   //user register add user status
    add_action( 'user_register', array( $this, 'add_user_status' ) );
   //email verification user status update
    add_action( 'new_user_approve_approve_user', array( $this, 'approve_user' ) );
  }


   /**
   * Determine if the user is good to sign in based on their status.
   *
   * @uses wp_authenticate_user
   * @param array $userdata
   */
   public function authenticate_user( $userdata ) {
   
    $status = $this->get_user_status( $userdata->ID );

       if ( empty( $status ) ) {
      // the user does not have a status so let's assume the user is good to go
      return $userdata;
    }

    $message = false;
    switch ( $status ) {
      case 'pending':
        $pending_message = $this->default_authentication_message( 'pending' );
        $message = new WP_Error( 'pending_approval', $pending_message );
        break;
      case 'denied':
        $denied_message = $this->default_authentication_message( 'denied' );
        $message = new WP_Error( 'denied_access', $denied_message );
        break;
      case 'approved':
        $message = $userdata;
        break;
    }

    return $message;
  }
  /**
   * Get the status of a user.
   *
   * @param int $user_id
   * @return string the status of the user
   */
   public function get_user_status( $user_id ) {
    $user_status = get_user_meta( $user_id, 'pw_user_status', true );

    if ( empty( $user_status ) ) {
      $user_status = 'approved';
    }

    return $user_status;
  }


  /**
   * The default message that is shown to a user depending on their status
   * when trying to sign in.
   *
   * @return string
   */
  public function default_authentication_message( $status ) {
    $message = '';

    if ( $status == 'pending' ) {
      $message = __( '<strong>ERROR</strong>: Your account is still pending approval.', 'new-user-approve' );
      $message = apply_filters( 'new_user_approve_pending_error', $message );
    } else if ( $status == 'denied' ) {
      $message = __( '<strong>ERROR</strong>: Your account has been denied access to this site.', 'new-user-approve' );
      $message = apply_filters( 'new_user_approve_denied_error', $message );
    }

    $message = apply_filters( 'new_user_approve_default_authentication_message', $message, $status );

    return $message;
  }

  /**
	 * Give the user a status
	 *
	 * @uses user_register
	 * @param int $user_id
	 */
	public function add_user_status( $user_id ) {
    $user_info = get_userdata($user_id);
    $user_email_id = $user_info->user_email;
    $redirectURL = wp_login_url();
    $to = $user_email_id;
    $subject = "Email confirmation from ".get_bloginfo( 'name' );

    $myVar = 'QgYLt\J]g$M8n$2S';    
    global $user_id;

    $user_id_encoded = base64_encode( $user_id );

    add_filter( 'send_password_change_email', '__return_false' );

    $msg = "<html><body><h1 style='margin:10px;'>Welcome to "
           .get_bloginfo( 'name' )."</h1><p style='margin:10px;'>Click on the below button to activate your account.</p><a style='margin:10px; background-color: #000000;color: #fff; padding: 10px; float: left;
             text-decoration: none;border-radius: 5px;' 
             href= $redirectURL?uid=$user_id_encoded&referrel=email>
             VERIFY YOUR ACCOUNT </a><br><br><br><br><br>
             <p style='margin:10px;'></body></html>";

    $headers = 'From: '.get_bloginfo( 'name' ).' <'.get_bloginfo( 'admin_email' ).'>'. "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    wp_mail( $to, $subject, $msg,  $headers);



		$status = 'pending';

		// This check needs to happen when a user is created in the admin
		if ( isset( $_REQUEST['action'] ) && 'createuser' == $_REQUEST['action'] ) {
			$status = 'approved';
		}
		update_user_meta( $user_id, 'pw_user_status', $status );

	}


	/**
	 * email verfied by user
	 *
	 * @uses new_user_approve_approve_user
	 */
	public function approve_user( $user_id ) {
  		global $wpdb;

  		$user = new WP_User( $user_id );

  		wp_cache_delete( $user->ID, 'users' );
  		wp_cache_delete( $user->data->user_login, 'userlogins' );

  		// change usermeta tag in database to approved
  		update_user_meta( $user->ID, 'pw_user_status', 'approved' );
      
      $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ( $special_chars )
          $chars .= '!@#$%^&*()';
        if ( $extra_special_chars )
          $chars .= '-_ []{}<>~`+=,.;:/?|';

        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
          $password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
        }

          $key = $password;

  	    /** This action is documented in wp-login.php */
  	    do_action( 'retrieve_password_key', $user->user_login, $key );

  	    // Now insert the key, hashed, into the DB.
  	    if ( empty( $wp_hasher ) ) {
  	        require_once ABSPATH . WPINC . '/class-phpass.php';
  	        $wp_hasher = new PasswordHash( 8, true );
  	    }
  	    $hashed = time() . ':' . $wp_hasher->HashPassword( $key );

  		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

  		do_action( 'new_user_approve_user_approved', $user );
  	}

  }

  /* initialize instance */
  function email_verification_call() {
    return email_verification::instance();
  }
  email_verification_call();


  /* Check user ID is valid or not when email verification  */
  function user_id_exists($user){
      global $wpdb;
      $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));
      if($count == 1){ return true; }else{ return false; }
  }

  /* activation success / error message */
  if (isset( $_GET['uid'] ) && isset( $_GET['referrel'] ) && $_GET['referrel'] == 'mail') {
    $ID = $_GET['uid'];
    $userExist = user_id_exists($ID);
    if($userExist){
      do_action( 'new_user_approve_approve_user', $ID );
      echo '<span class="alert alert-success">Acount activated successfully</span>';
    }else{
      echo '<span class="alert alert-danger">Invalid account details </span>';
    }
  }

  /* email config in wp-admin */
  add_action( 'admin_menu', 'email_confirmation_menu' );

  function email_confirmation_menu() {
    add_options_page( 'emailConfirmation', 'E-Mail Confirmation', 'manage_options', 'emailConfirmation-config', 'email_redirection_options' );
  }



  /* If is login page and  authentication of registered user is done by getting uuid of user in url as keyword arg and mark that user account as activated user. */
  $login_pattern =$_SERVER['PHP_SELF']; 
  $link_array = explode('/',$login_pattern);
  $page = end($link_array);

  /* Checks whether the current page url is login pageurl(wp-login.php). */
  if ( ($page == 'wp-login.php') && isset( $_GET['uid'] ) )
  {      
      $ID = rtrim( base64_decode($_GET['uid']) );

      do_action( 'new_user_approve_approve_user', $ID );//trigger the account activation for user;
      add_filter( 'login_message', 
        'activate_user_success_notifymessage' ); //trigger the message notifier to display on login panel
  }


  // Notify message for account activation message of user.
  function activate_user_success_notifymessage( $message_login ) {
      if ( empty($message_login) ){

          return '<div class="notice notice-success is-dismissible"><p>Account activated successfully!</p></div>';
      } else {
          return $message_login;
      }
  }
