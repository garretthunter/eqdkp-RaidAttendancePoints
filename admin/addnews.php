<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        addnews.php
 * Began:       Wed Dec 25 2002
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 516 $
 */

define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
require_once($eqdkp_root_path . 'common.php');

class Add_News extends EQdkp_Admin
{
    var $news     = array();            // Holds news data if URI_NEWS is set               @var news
    var $old_news = array();            // Holds news data from before POST                 @var old_news

    function add_news()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;

        parent::eqdkp_admin();

        $this->news = array(
            'news_headline' => $in->get('news_headline'),
            'news_message'  => $in->get('news_message')
        );

        // Vars used to confirm deletion
        $this->set_vars(array(
            'confirm_text'  => $user->lang['confirm_delete_news'],
            'uri_parameter' => URI_NEWS
        ));

        $this->assoc_buttons(array(
            'add' => array(
                'name'    => 'add',
                'process' => 'process_add',
                'check'   => 'a_news_add'
            ),
            'update' => array(
                'name'    => 'update',
                'process' => 'process_update',
                'check'   => 'a_news_upd'
            ),
            'delete' => array(
                'name'    => 'delete',
                'process' => 'process_delete',
                'check'   => 'a_news_del'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_news_'
            )
        ));

        // Build the news array
        // ---------------------------------------------------------
        if ( $this->url_id )
        {
            $sql = "SELECT news_headline, news_message
                    FROM __news
                    WHERE (`news_id` = '{$this->url_id}')";
            $result = $db->query($sql);
            if ( !$row = $db->fetch_record($result) )
            {
                message_die($user->lang['error_invalid_news_provided']);
            }
            $db->free_result($result);

            $this->time = time();
            $this->news = array(
                'news_headline' => $in->get('news_headline', $row['news_headline']),
                'news_message'  => $in->get('news_message',  $row['news_message'])
            );
        }
    }

    function error_check()
    {
        global $user;

        $this->fv->is_filled(array(
            'news_headline' => $user->lang['fv_required_headline'],
            'news_message'  => $user->lang['fv_required_message']
        ));

        return $this->fv->is_error();
    }

    // ---------------------------------------------------------
    // Process Add
    // ---------------------------------------------------------
    function process_add()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;

        //
        // Insert the news
        //
        $db->query("INSERT INTO __news :params", array(
            'news_headline' => $in->get('news_headline'),
            'news_message'  => $in->get('news_message'),
            'news_date'     => $this->time,
            'user_id'       => $user->data['user_id']
        ));
        $this_news_id = $db->insert_id();

        //
        // Logging
        //
        $log_action = array(
            'header'           => '{L_ACTION_NEWS_ADDED}',
            'id'               => $this_news_id,
            '{L_HEADLINE}'     => $in->get('news_headline'),
            '{L_MESSAGE_BODY}' => $in->get('news_message'),
            '{L_ADDED_BY}'     => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));

        //
        // Success message
        //
        $success_message = $user->lang['admin_add_news_success'];
        $link_list = array(
            $user->lang['add_news']  => edit_news_path(),
            $user->lang['list_news'] => news_path(true)
        );
        $this->admin_die($success_message, $link_list);
    }

    // ---------------------------------------------------------
    // Process Update
    // ---------------------------------------------------------
    function process_update()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;

        //
        // Get the old data
        //
        $this->get_old_data();

        //
        // Update the news table
        //
        if ( $in->get('update_date', 0) == 1 )
        {
            $query = $db->sql_build_query('UPDATE', array(
                'news_headline' => $in->get('news_headline'),
                'news_message'  => $in->get('news_message'),
                'news_date'     => $this->time
            ));
        }
        else
        {
            $query = $db->sql_build_query('UPDATE', array(
                'news_headline' => $in->get('news_headline'),
                'news_message'  => $in->get('news_message')
            ));
        }
        $db->query("UPDATE __news SET {$query} WHERE (`news_id` = '{$this->url_id}')");

        //
        // Logging
        //
        $log_action = array(
            'header'              => '{L_ACTION_NEWS_UPDATED}',
            'id'                  => $this->url_id,
            '{L_HEADLINE_BEFORE}' => $this->old_news['news_headline'],
            '{L_MESSAGE_BEFORE}'  => $this->old_news['news_message'],
            '{L_HEADLINE_AFTER}'  => $this->find_difference($this->old_news['news_headline'], $in->get('news_headline')),
            '{L_MESSAGE_AFTER}'   => $in->get('news_message'),
            '{L_UPDATED_BY}'      => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));

        //
        // Success message
        //
        $success_message = $user->lang['admin_update_news_success'];
        $link_list = array(
            $user->lang['add_news']  => edit_news_path(),
            $user->lang['list_news'] => news_path(true)
        );
        $this->admin_die($success_message, $link_list);
    }

    // ---------------------------------------------------------
    // Process Delete (confirmed)
    // ---------------------------------------------------------
    function process_confirm()
    {
        global $db, $eqdkp, $user, $tpl, $pm;

        //
        // Get the old data
        //
        $this->get_old_data();

        //
        // Remove the news entry
        //
        $sql = "DELETE FROM __news
                WHERE (`news_id` = '{$this->url_id}')";
        $db->query($sql);

        //
        // Logging
        //
        $log_action = array(
            'header'           => '{L_ACTION_NEWS_DELETED}',
            'id'               => $this->url_id,
            '{L_HEADLINE}'     => $this->old_news['news_headline'],
            '{L_MESSAGE_BODY}' => $this->old_news['news_message']
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));

        //
        // Success message
        //
        $success_message = $user->lang['admin_delete_news_success'];
        $link_list = array(
            $user->lang['add_news']  => edit_news_path(),
            $user->lang['list_news'] => news_path(true)
        );
        $this->admin_die($success_message, $link_list);
    }

    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    function get_old_data()
    {
        global $db;

        $sql = "SELECT news_headline, news_message
                FROM __news
                WHERE (`news_id` = '{$this->url_id}')";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $this->old_news = array(
                'news_headline' => $row['news_headline'],
                'news_message'  => $row['news_message']
            );
        }
        $db->free_result($result);
    }

    // ---------------------------------------------------------
    // Display form
    // ---------------------------------------------------------
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm;

        $tpl->assign_vars(array(
            // Form vars
            'F_ADD_NEWS' => edit_news_path(),
            'NEWS_ID'    => $this->url_id,
            'S_UPDATE'   => ( $this->url_id ) ? true : false,

            // Form values
            'HEADLINE' => sanitize($this->news['news_headline'], ENT),
            'MESSAGE'  => sanitize($this->news['news_message'], ENT),

            // Language (General)
            'L_HEADLINE'       => $user->lang['headline'],
            'L_MESSAGE_BODY'   => $user->lang['message_body'],
            'L_ADD_NEWS'       => $user->lang['add_news'],
            'L_RESET'          => $user->lang['reset'],
            'L_UPDATE_NEWS'    => $user->lang['update_news'],
            'L_DELETE_NEWS'    => $user->lang['delete_news'],
            'L_UPDATE_DATE_TO' => sprintf($user->lang['update_date_to'], date('m/d/Y h:ia T', time())),

            // Language (Help messages)
            'L_B_HELP' => $user->lang['b_help'],
            'L_I_HELP' => $user->lang['i_help'],
            'L_U_HELP' => $user->lang['u_help'],
            'L_Q_HELP' => $user->lang['q_help'],
            'L_C_HELP' => $user->lang['c_help'],
            'L_P_HELP' => $user->lang['p_help'],
            'L_W_HELP' => $user->lang['w_help'],

            // Form validation
            'FV_HEADLINE' => $this->fv->generate_error('news_headline'),
            'FV_MESSAGE'  => $this->fv->generate_error('news_message'),

            // Javascript messages
            'MSG_HEADLINE_EMPTY' => $user->lang['fv_required_headline'],
            'MSG_MESSAGE_EMPTY'  => $user->lang['fv_required_message'],

            // Buttons
            'S_ADD' => ( !$this->url_id ) ? true : false
        ));

        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['addnews_title']),
            'template_file' => 'admin/addnews.html',
            'display'       => true
        ));
    }
}

$add_news = new Add_News;
$add_news->process();