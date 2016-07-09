<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        register.php
 * Began:       Sat Jan 4 2003
 * Date:        $Date: 2009-01-23 05:22:04 -0800 (Fri, 23 Jan 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 608 $
 */
 
define('EQDKP_INC', true);
$eqdkp_root_path = './';
require_once($eqdkp_root_path . 'common.php');

class Register extends EQdkp_Admin
{
    var $server_url  = '';
    var $data        = array();
    
    function register()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        //
        // If they're trying access this page while logged in, redirect to settings.php
        //
        if ( $user->data['user_id'] != ANONYMOUS && !$in->get('key', false) )
        {
            header('Location: ' . path_default('settings.php'));
        }
        
        parent::eqdkp_admin();
        
        // Data to be put into the form
        // If it's not in POST, we get it from config defaults
        $this->data = array(
            'user_name'   => $in->get('username'),
            'user_email'  => $in->get('user_email'),
            'user_alimit' => $in->get('user_alimit', intval($eqdkp->config['default_alimit'])),
            'user_elimit' => $in->get('user_elimit', intval($eqdkp->config['default_elimit'])),
            'user_ilimit' => $in->get('user_ilimit', intval($eqdkp->config['default_ilimit'])),
            'user_nlimit' => $in->get('user_nlimit', intval($eqdkp->config['default_nlimit'])),
            'user_rlimit' => $in->get('user_rlimit', intval($eqdkp->config['default_rlimit'])),
            'user_lang'   => $in->get('user_lang',   $eqdkp->config['default_lang']),
            'user_style'  => $in->get('user_style',  intval($eqdkp->config['default_style']))
        );
        
        $this->assoc_buttons(array(
            'submit' => array(
                'name'    => 'submit',
                'process' => 'process_submit'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form'
            )
        ));
        
        $this->assoc_params(array(
            'lostpassword' => array(
                'name'    => 'mode',
                'value'   => 'lostpassword',
                'process' => 'process_lostpassword'
            ),
            'activate' => array(
                'name'    => 'mode',
                'value'   => 'activate',
                'process' => 'process_activate'
            )
        ));
        
        // Build the server URL
        $this->server_url = redirect('register.php', true);
    }
    
    function error_check()
    {
        global $db, $user, $in;
        
        if ( $in->get('submit', false) )
        {
            $sql = "SELECT user_id
                    FROM __users
                    WHERE (`user_name` = " . $db->sql_escape($in->get('username')) . ")";
            if ( $db->num_rows($db->query($sql)) > 0 )
            {
                $this->fv->errors['username'] = $user->lang['fv_already_registered_username'];
            }
            $sql = "SELECT user_id
                    FROM __users
                    WHERE (`user_email` = " . $db->sql_escape($in->get('user_email')) . ")";
            if ( $db->num_rows($db->query($sql)) > 0 )
            {
                $this->fv->errors['user_email'] = $user->lang['fv_already_registered_email'];
            }
            
            $this->fv->matching_passwords('user_password1', 'user_password2', $user->lang['fv_match_password']);
            
            $this->fv->is_number(array(
                'user_alimit' => $user->lang['fv_number'],
                'user_elimit' => $user->lang['fv_number'],
                'user_ilimit' => $user->lang['fv_number'],
                'user_nlimit' => $user->lang['fv_number'],
                'user_rlimit' => $user->lang['fv_number']
            ));
            
            $this->fv->is_email_address('user_email', $user->lang['fv_invalid_email']);
            
            $this->fv->is_filled(array(
                'username'       => $user->lang['fv_required_user'],
                'user_email'     => $user->lang['fv_required_email'],
                'user_password1' => $user->lang['fv_required_password'],
                'user_password2' => ''
            ));
        }
        
        return $this->fv->is_error();
    }
    
    // ---------------------------------------------------------
    // Process Submit
    // ---------------------------------------------------------
    function process_submit()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;

        // If the config requires account activation, generate a random key for validation
        if ( $eqdkp->config['account_activation'] == USER_ACTIVATION_SELF || $eqdkp->config['account_activation'] == USER_ACTIVATION_ADMIN )
        {
            $user_key = $this->random_string(true);
            $user_active = '0';

            if ($user->data['user_id'] != ANONYMOUS)
            {
                $user->destroy();
            }
        }
        else
        {
            $user_key = '';
            $user_active = '1';
        }

        // Insert them into the users table
        $salt  = generate_salt();
        $query = $db->sql_build_query('INSERT', array(
            'user_name'      => $in->get('username'),
            'user_salt'      => $salt,
            'user_password'  => hash_password($in->get('user_password1'), $salt),
            'user_email'     => $in->get('user_email'),
            'user_alimit'    => $in->get('user_alimit', 0),
            'user_elimit'    => $in->get('user_elimit', 0),
            'user_ilimit'    => $in->get('user_ilimit', 0),
            'user_nlimit'    => $in->get('user_nlimit', 0),
            'user_rlimit'    => $in->get('user_rlimit', 0),
            'user_style'     => $in->get('user_style', 0),
            'user_lang'      => $in->get('user_lang'),
            'user_key'       => $user_key,
            'user_active'    => $user_active,
            'user_lastvisit' => $this->time
        ));
        $sql = "INSERT INTO __users {$query}";

        if ( !($db->query($sql)) )
        {
            message_die('Could not add user information', '', __FILE__, __LINE__, $sql);
        }
        $user_id = $db->insert_id();
        
        // Insert their permissions into the table
        $sql = "SELECT auth_id, auth_default
                FROM __auth_options
                ORDER BY auth_id";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $db->query("INSERT INTO __auth_users :params", array(
                'user_id'      => $user_id,
                'auth_id'      => intval($row['auth_id']),
                'auth_setting' => $row['auth_default']
            ));
        }
        
        if ($eqdkp->config['account_activation'] == USER_ACTIVATION_SELF)
        {
            $success_message = sprintf($user->lang['register_activation_self'], sanitize($in->get('user_email')));
            $email_template = 'register_activation_self';
        }
        elseif ($eqdkp->config['account_activation'] == USER_ACTIVATION_ADMIN)
        {
            $success_message = sprintf($user->lang['register_activation_admin'], sanitize($in->get('user_email')));
            $email_template = 'register_activation_admin';
        }
        else
        {
            $success_message = sprintf($user->lang['register_activation_none'], '<a href="' . path_default('login.php') . '">', '</a>', sanitize($in->get('user_email')));
            $email_template = 'register_activation_none';
        }

		//
        // Email a notice
        //
        $this->send_mail($in->get('user_email'),
            array(
                'template' => $email_template,
                'lang'     => $in->get('user_lang')
            ),
            array(
                'USERNAME'   => sanitize($in->get('username')),
                'PASSWORD'   => sanitize($in->get('user_password1')),
                'U_ACTIVATE' => $this->server_url . '?mode=activate&key=' . $user_key
            )
        );
        
        // Now email the admin if we need to
        if ( $eqdkp->config['account_activation'] == USER_ACTIVATION_ADMIN )
        {
            $this->send_mail($eqdkp->config['admin_email'],
                array(
                    'template' => 'register_activation_admin_activate',
                    'lang'     => $eqdkp->config['default_lang']
                ),
                array(
                    'USERNAME'   => sanitize($in->get('username')),
                    'U_ACTIVATE' => $this->server_url . '?mode=activate&key=' . $user_key
                )
            );
        }
        
        message_die($success_message);
    }
    
    // ---------------------------------------------------------
    // Process Lost Password
    // ---------------------------------------------------------
    function process_lostpassword()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        $username   = $in->get('username', '');
        $user_email = $in->get('user_email', '');
        
        //
        // Look up record based on the username and e-mail
        //
        $sql = "SELECT user_id, user_name, user_email, user_active, user_lang,
                    user_salt
                FROM __users
                WHERE (`user_email` = " . $db->sql_escape($user_email) . ")
                AND (`user_name` = " . $db->sql_escape($username) . ")";
        if ( $result = $db->query($sql) )
        {
            if ( $row = $db->fetch_record($result) )
            {
                // Account's inactive, can't give them their password
                if ( !$row['user_active'] )
                {
                    message_die($user->lang['error_account_inactive']);
                }
 
                $username = $row['user_name'];
                
                // Create a new activation key
                $user_key = $this->random_string(true);
                $user_password = $this->random_string(false);
                
                $sql = "UPDATE __users
                        SET `user_newpassword` = '" . hash_password($user_password, $row['user_salt']) . "', `user_key` = '{$user_key}'
                        WHERE (`user_id` = " . $db->sql_escape($row['user_id']) . ")";
                if ( !$db->query($sql) )
                {
                    message_die('Could not update password information', '', __FILE__, __LINE__, $sql);
                }
                
                //
                // Email them their new password
                //
                $this->send_mail($row['user_email'],
                    array(
                        'template' => 'user_new_password',
                        'lang'     => $row['user_lang'],
                    ),
                    array(
                        'USERNAME'   => sanitize($row['user_name']),
                    'DATETIME'   => date('m/d/y h:ia T', time()),
                        'IPADDRESS'  => sanitize($user->ip),
                    'U_ACTIVATE' => $this->server_url . '?mode=activate&key=' . $user_key,
                        'PASSWORD'   => $user_password
                    )
                );
                
                message_die($user->lang['password_sent']);
            }
            else
            {
                message_die($user->lang['error_invalid_email']);
            }
        }
        else
        {
            message_die('Could not obtain user information', '', __FILE__, __LINE__, $sql);
        }
    }
    
    // ---------------------------------------------------------
    // Process Activate
    // ---------------------------------------------------------
    function process_activate()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        $sql = "SELECT user_id, user_name, user_active, user_email, user_newpassword, user_lang, user_key
                FROM __users
                WHERE (`user_key` = " . $db->sql_escape($in->hash('key')) . ")";
        if ( !($result = $db->query($sql)) )
        {
            message_die('Could not obtain user information', '', __FILE__, __LINE__, $sql);
        }
        if ( $row = $db->fetch_record($result) )
        {
            // If they're already active, just bump them back
            if ( ($row['user_active'] == '1') && ($row['user_key'] == '') )
            {
                message_die($user->lang['error_already_activated']);
            }
            else
            {
                $update = array(
                    'user_active' => '1',
                    'user_key'    => '',
                );
                // Update the password if we need to
                if ( !empty($row['user_newpassword']) )
                {
                    $update['user_password'] = $row['user_newpassword'];
                    $update['user_newpassword'] = null;
                }
                $query = $db->sql_build_query('UPDATE', $update);
                $sql = "UPDATE __users SET {$query}
                        WHERE (`user_id` = " . $db->sql_escape($row['user_id']) . ")";
                $db->query($sql);
                
                // E-mail the user if this was activated by the admin
                if ( $eqdkp->config['account_activation'] == USER_ACTIVATION_ADMIN )
                {
                    $this->send_mail($row['user_email'], 
                        array(
                            'template' => 'register_activation_none', 
                            'lang'     => $row['user_lang']
                        ), 
                        array(
                            'USERNAME' => sanitize($row['user_name']),
                            'PASSWORD' => '(encrypted)'
                        )
                    );
                    
                    $success_message = $user->lang['account_activated_admin'];
                }
                else
                {
                    $url = path_default('login.php');
                    meta_refresh(3, $url);
                    $success_message = sprintf($user->lang['account_activated_user'], '<a href="' . $url . '">', '</a>');
                }
                
                message_die($success_message);
            }
        }
        else
        {
            message_die($user->lang['error_invalid_key']);
        }
    }
    
    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    function random_string($hash = false)
    {
    	$chars = array('a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H','i','I','j','J',  
                       'k','K','l','L','m','M','n','N','o','O','p','P','q','Q','r','R','s','S','t','T',
                       'u','U','v','V','w','W','x','X','y','Y','z','Z','1','2','3','4','5','6','7','8',
                       '9','0');
    
    	$max_chars = count($chars) - 1;
    	srand( (double) microtime()*1000000);
    
    	$rand_str = '';
    	for($i = 0; $i < 8; $i++)
    	{
    		$rand_str = ( $i == 0 ) ? $chars[rand(0, $max_chars)] : $rand_str . $chars[rand(0, $max_chars)];
    	}
    
    	return ( $hash ) ? md5($rand_str) : $rand_str;
    }
    
    /**
     * Send an e-mail to the user or administrator after registration
     *
     * @param string $address Address to mail
     * @param array $options Array of key/value options to assign ('template' and 'lang' required)
     * @param array $vars Array of key/value template variable pairs to assign
     * @return void
     */
    function send_mail($address, $options, $vars)
    {
        global $eqdkp;
        
        $extra_headers = "From: {$eqdkp->config['admin_email']}\nReturn-Path: {$eqdkp->config['admin_email']}\r\n";
        $constant_vars = array(
            'GUILDTAG' => $eqdkp->config['guildtag'],
            'DKP_NAME' => $eqdkp->config['dkp_name'],
        );
        
        include_once($eqdkp->root_path . 'includes/class_email.php');
        $email = new EMail;
        
        $headers = "From: " . $eqdkp->config['admin_email'] . "\nReturn-Path: " . $eqdkp->config['admin_email'] . "\r\n";
        
        $email->set_template($options['template'], $options['lang']);
        $email->address($address);
        $email->subject();
        $email->extra_headers($extra_headers);
        
        $email->assign_vars(array_merge($constant_vars, $vars));
        $email->send();
        $email->reset();
    }
    
    // ---------------------------------------------------------
    // Display form
    // ---------------------------------------------------------
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        $tpl->assign_vars(array(
            'F_SETTINGS' => path_default('register.php'),
            
            'S_CURRENT_PASSWORD' => false,
            'S_NEW_PASSWORD'     => false,
            'S_SETTING_ADMIN'    => false,
            'S_MU_TABLE'         => false,

            'L_REGISTRATION_INFORMATION' => $user->lang['registration_information'],
            'L_REQUIRED_FIELD_NOTE'      => $user->lang['required_field_note'],
            'L_USERNAME'                 => $user->lang['username'],
            'L_EMAIL_ADDRESS'            => $user->lang['email_address'],
            'L_PASSWORD'                 => $user->lang['password'],
            'L_CONFIRM_PASSWORD'         => $user->lang['confirm_password'],
            'L_PREFERENCES'              => $user->lang['preferences'],
            'L_ADJUSTMENTS_PER_PAGE'     => $user->lang['adjustments_per_page'],
            'L_EVENTS_PER_PAGE'          => $user->lang['events_per_page'],
            'L_ITEMS_PER_PAGE'           => $user->lang['items_per_page'],
            'L_NEWS_PER_PAGE'            => $user->lang['news_per_page'],
            'L_RAIDS_PER_PAGE'           => $user->lang['raids_per_page'],
            'L_LANGUAGE'                 => $user->lang['language'],
            'L_STYLE'                    => $user->lang['style'],
            'L_PREVIEW'                  => $user->lang['preview'],
            'L_SUBMIT'                   => $user->lang['submit'],
            'L_RESET'                    => $user->lang['reset'],

            'USERNAME'    => sanitize($this->data['user_name'], ENT),
            'USER_EMAIL'  => sanitize($this->data['user_email'], ENT),
            'USER_ALIMIT' => intval($this->data['user_alimit']),
            'USER_ELIMIT' => intval($this->data['user_elimit']),
            'USER_ILIMIT' => intval($this->data['user_ilimit']),
            'USER_NLIMIT' => intval($this->data['user_nlimit']),
            'USER_RLIMIT' => intval($this->data['user_rlimit']),

            'FV_USERNAME'      => $this->fv->generate_error('user_name'),
            'FV_USER_PASSWORD' => $this->fv->generate_error('user_password1'),
            'FV_USER_EMAIL'    => $this->fv->generate_error('user_email'),
            'FV_USER_ALIMIT'   => $this->fv->generate_error('user_alimit'),
            'FV_USER_ELIMIT'   => $this->fv->generate_error('user_elimit'),
            'FV_USER_ILIMIT'   => $this->fv->generate_error('user_ilimit'),
            'FV_USER_NLIMIT'   => $this->fv->generate_error('user_nlimit'),
            'FV_USER_RLIMIT'   => $this->fv->generate_error('user_rlimit'))
        );

        // Build language drop-down
        foreach ( select_language($this->data['user_lang']) as $row )
        {
            $tpl->assign_block_vars('lang_row', $row);
        }

        // Build style drop-down
        foreach ( select_style($this->data['user_style']) as $row )
        {
            $tpl->assign_block_vars('style_row', $row);
        }
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['register_title']),
            'template_file' => 'settings.html',
            'display'       => true
        ));
    }
}

$register = new Register;
$register->process();
