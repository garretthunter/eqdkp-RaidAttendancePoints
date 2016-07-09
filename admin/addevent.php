<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        addevent.php
 * Began:       Mon Dec 30 2002
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

class Add_Event extends EQdkp_Admin
{
    var $event     = array();           // Holds event data if URI_EVENT Is set             @var event
    var $old_event = array();           // Holds event data from before POST                @var old_event
    
    function add_event()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        parent::eqdkp_admin();
        
        $this->event = array(
            'event_name'  => $in->get('event_name', ''),
            'event_value' => $in->get('event_value', 0.00)
        );
        
        // Vars used to confirm deletion
        $this->set_vars(array(
            'confirm_text'  => $user->lang['confirm_delete_event'],
            'uri_parameter' => URI_EVENT
        ));
        
        $this->assoc_buttons(array(
            'add' => array(
                'name'    => 'add',
                'process' => 'process_add',
                'check'   => 'a_event_add'
            ),
            'update' => array(
                'name'    => 'update',
                'process' => 'process_update',
                'check'   => 'a_event_upd'
            ),
            'delete' => array(
                'name'    => 'delete',
                'process' => 'process_delete',
                'check'   => 'a_event_del'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_event_'
            )
        ));
        
        // Build the event array
        // ---------------------------------------------------------
        if ( $this->url_id )
        {
            $sql = "SELECT event_name, event_value
                    FROM __events
                    WHERE (`event_id` = " . $db->sql_escape($this->url_id) . ")";
            $result = $db->query($sql);
            if ( !$row = $db->fetch_record($result) )
            {
                message_die($user->lang['error_invalid_event_provided']);
            }
            $db->free_result($result);

            $this->event = array(
                'event_name'  => $in->get('event_name', $row['event_name']),
                'event_value' => $in->get('event_value', floatval($row['event_value']))
            );
        }
    }
    
    function error_check()
    {
        global $user;
        
        $this->fv->is_number('event_value', $user->lang['fv_number_value']);
        
        $this->fv->is_filled(array(
            'event_name'  => $user->lang['fv_required_name'],
            'event_value' => $user->lang['fv_required_value']
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
        // Insert event
        //

        $query = $db->sql_build_query('INSERT', array(
            'event_name'     => $in->get('event_name'),
            'event_value'    => $in->get('event_value', 0.00),
            'event_added_by' => $this->admin_user)
        );
        $db->query("INSERT INTO __events {$query}");
        $this_event_id = $db->insert_id();

        //
        // Call plugin update hooks
        //
        $pm->do_hooks('/admin/addevent.php?action=add');
        
        //
        // Logging
        //
        $log_action = array(
            'header'       => '{L_ACTION_EVENT_ADDED}',
            'id'           => $this_event_id,
            '{L_NAME}'     => $in->get('event_name'),
            '{L_VALUE}'    => $in->get('event_value', 0.00),
            '{L_ADDED_BY}' => $this->admin_user);
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
        $success_message = sprintf($user->lang['admin_add_event_success'], $in->get('event_value', 0.00), sanitize($in->get('event_name')));
        $link_list = array(
            $user->lang['list_events'] => event_path(),
            $user->lang['add_event']   => edit_event_path(),
            $user->lang['add_raid']    => edit_raid_path()
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
        $event_name = $in->get('event_name');
        
        //
        // Update any raids with the old name
        //
        if ( $this->old_event['event_name'] != $event_name )
        {
            $sql = "UPDATE __raids
                    SET `raid_name` = " . $db->sql_escape($event_name) . "
                    WHERE (`raid_name` = " . $db->sql_escape($this->old_event['event_name']) . ")";
            $db->query($sql);
        }
        
        //
        // Update the event
        //
        $query = $db->sql_build_query('UPDATE', array(
            'event_name'  => $event_name,
            'event_value' => $in->get('event_value', 0.00)
        ));
        $sql = "UPDATE __events SET {$query} WHERE (`event_id` = '{$this->url_id}')";
        $db->query($sql);

        //
        // Call plugin update hooks
        //
        $pm->do_hooks('/admin/addevent.php?action=update');
        
        //
        // Logging
        //
        $log_action = array(
            'header'           => '{L_ACTION_EVENT_UPDATED}',
            'id'               => $this->url_id,
            '{L_NAME_BEFORE}'  => $this->old_event['event_name'],
            '{L_VALUE_BEFORE}' => $this->old_event['event_value'],
            '{L_NAME_AFTER}'   => $this->find_difference($this->old_event['event_name'],  $event_name),
            '{L_VALUE_AFTER}'  => $this->find_difference($this->old_event['event_value'], $in->get('event_value', 0.00)),
            '{L_UPDATED_BY}'   => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
        $success_message = sprintf($user->lang['admin_update_event_success'], $in->get('event_value', 0.00), sanitize($event_name));
        $link_list = array(
            $user->lang['list_events'] => event_path(),
            $user->lang['add_event']   => edit_event_path(),
            $user->lang['add_raid']    => edit_raid_path()
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
        // Delete the event
        //
        $sql = "DELETE FROM __events
                WHERE (`event_id` = '{$this->url_id}')";
        $db->query($sql);
        
        //
        // Logging
        //
        $log_action = array(
            'header'    => '{L_ACTION_EVENT_DELETED}',
            'id'        => $this->url_id,
            '{L_NAME}'  => $this->old_event['event_name'],
            '{L_VALUE}' => $this->old_event['event_value']
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
        $success_message = sprintf($user->lang['admin_delete_event_success'], $this->old_event['event_value'], sanitize($this->old_event['event_name']));
        $link_list = array(
            $user->lang['list_events'] => event_path(),
            $user->lang['add_event']   => edit_event_path(),
            $user->lang['add_raid']    => edit_raid_path()
        );
        $this->admin_die($success_message, $link_list);
    }
    
    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    function get_old_data()
    {
        global $db;
        
        $sql = "SELECT event_name, event_value
                FROM __events
                WHERE (`event_id` = '{$this->url_id}')";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $this->old_event = array(
                'event_name'  => $row['event_name'],
                'event_value' => floatval($row['event_value'])
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
            'F_ADD_EVENT' => edit_event_path(),
            'EVENT_ID'    => $this->url_id,
            
            // Form values
            'EVENT_NAME'  => sanitize($this->event['event_name'], ENT),
            'EVENT_VALUE' => number_format(sanitize($this->event['event_value'], ENT), 2),
            
            // Language
            'L_ADD_EVENT_TITLE' => $user->lang['addevent_title'],
            'L_NAME'            => $user->lang['name'],
            'L_DKP_VALUE'       => sprintf($user->lang['dkp_value'], $eqdkp->config['dkp_name']),
            'L_ADD_EVENT'       => $user->lang['add_event'],
            'L_RESET'           => $user->lang['reset'],
            'L_UPDATE_EVENT'    => $user->lang['update_event'],
            'L_DELETE_EVENT'    => $user->lang['delete_event'],
            
            // Form validation
            'FV_NAME'  => $this->fv->generate_error('event_name'),
            'FV_VALUE' => $this->fv->generate_error('event_value'),
            
            // Javascript messages
            'MSG_NAME_EMPTY'  => $user->lang['fv_required_name'],
            'MSG_VALUE_EMPTY' => $user->lang['fv_required_value'],
            
            // Buttons
            'S_ADD' => ( !$this->url_id ) ? true : false
        ));
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['addevent_title']),
            'template_file' => 'admin/addevent.html',
            'display'       => true
        ));
    }
}

$add_event = new Add_Event;
$add_event->process();