<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        addadj.php
 * Began:       Sat Jan 4 2003
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

class Add_GroupAdj extends EQdkp_Admin
{
    var $adjustment     = array();      // Holds adjustment data if URI_ADJUSTMENT is set   @var adjustment
    var $old_adjustment = array();      // Holds adjustment data from before POST           @var old_adjustment
    
    function add_groupadj()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        parent::eqdkp_admin();
        
        $this->adjustment = array(
            'adjustment_value' => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            'adjustment_event' => $in->get('adjustment_event')
//gehEND
        );
        
        // Vars used to confirm deletion
        $this->set_vars(array(
            'confirm_text'  => $user->lang['confirm_delete_adj'],
            'uri_parameter' => URI_ADJUSTMENT
        ));
        
        $this->assoc_buttons(array(
            'add' => array(
                'name'    => 'add',
                'process' => 'process_add',
                'check'   => 'a_groupadj_add'
            ),
            'update' => array(
                'name'    => 'update',
                'process' => 'process_update',
                'check'   => 'a_groupadj_upd'
            ),
            'delete' => array(
                'name'    => 'delete',
                'process' => 'process_delete',
                'check'   => 'a_groupadj_del'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_groupadj_'
            )
        ));
        
        // Build the adjustment aray
        // -----------------------------------------------------
        if ( $this->url_id )
        {
//gehRAIDGROUPS
            $sql = "SELECT adjustment_value, adjustment_event, member_name
                    FROM __adjustments
                    WHERE (`adjustment_id` = ". $db->sql_escape($this->url_id).")";
//gehEND
            $result = $db->query($sql);
            if ( !$row = $db->fetch_record($result) )
            {
                message_die($user->lang['error_invalid_adjustment']);
            }
            $db->free_result($result);
        
            // If member name is set, it's an individual adjustment - put them back on that script
            if ( isset($row['member_name']) )
            {
                redirect(edit_iadjustment_path($this->url_id));
            }
        
            $this->adjustment = array(
                'adjustment_value' => $in->get('adjustment_value', floatval($row['adjustment_value'])),
//gehRAIDGROUPS
                'adjustment_event' => $in->get('adjustment_event', $row['adjustment_event'])
//gehEND
            );
        }
    }
    
    function error_check()
    {
        global $user, $in;
//gehRAIDGROUPS
        if ( $in->exists('adjustment_event') )
		{
            $this->fv->is_filled('adjustment_event', $user->lang['fv_required_event_name']);
		}
//gehEND

        if ( $in->exists('adjustment_value') )
        {
            $this->fv->is_number('adjustment_value', $user->lang['fv_number_adjustment']);
            $this->fv->is_filled('adjustment_value', $user->lang['fv_required_adjustment']);
        }
        
        return $this->fv->is_error();
    }
    
    // ---------------------------------------------------------
    // Process Add
    // ---------------------------------------------------------
    function process_add()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        //
        // Change member's adjustment column
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` + " . $db->sql_escape($in->get('adjustment_value', 0.00));
        $db->query($sql);
        
        //
        // Insert adjustment
        //
        $query = $db->sql_build_query('INSERT', array(
            'adjustment_value'    => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            'adjustment_event'    => $in->get('adjustment_event',''),
//gehEND
            'adjustment_date'     => $this->time,
            'adjustment_added_by' => $this->admin_user
        ));
        $db->query("INSERT INTO __adjustments {$query}");
        $this_adjustment_id = $db->insert_id();
        
        //
        // Logging
        //
        $log_action = array(
            'header'         => '{L_ACTION_GROUPADJ_ADDED}',
            'id'             => $this_adjustment_id,
            '{L_ADJUSTMENT}' => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            '{L_EVENT}'      => $in->get('adjustment_event'),
//gehEND
            '{L_ADDED_BY}'   => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
//gehRAIDGROUPS
       	$success_message = sprintf($user->lang['admin_add_adj_success'], $eqdkp->config['dkp_name'], sanitize($in->get('adjustment_value', 0.00)), sanitize($in->get('adjustment_event','')));
//gehEND
        $link_list = array(
            $user->lang['list_groupadj'] => adjustment_path(),
            $user->lang['list_members']  => member_path()
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
        // Remove the old adjustment from members that received it
        // If their first raid was before/on the adjustment date, then they
        // would have received a group adjustment
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` - {$this->old_adjustment['adjustment_value']}
                WHERE (`member_firstraid` <= {$this->old_adjustment['adjustment_date']})";
        $db->query($sql);
        
        //
        // Add the new adjustment
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` + " . $db->sql_escape($in->get('adjustment_value', 0.00)) . "
                WHERE (`member_firstraid` <= {$this->old_adjustment['adjustment_date']})";
        $db->query($sql);
        
        //
        // Update the adjustment table
        //
        $query = $db->sql_build_query('UPDATE', array(
            'adjustment_value'      => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            'adjustment_event'      => $in->get('adjustment_event'),
//gehEND
            'adjustment_updated_by' => $this->admin_user
        ));
        $sql = "UPDATE __adjustments SET {$query} WHERE (`adjustment_id` = " . $db->sql_escape($this->url_id) . ")";
        $db->query($sql);
        
        //
        // Logging
        //
        $log_action = array(
            'header'                => '{L_ACTION_GROUPADJ_UPDATED}',
            'id'                    => $this->url_id,
            '{L_ADJUSTMENT_BEFORE}' => $this->old_adjustment['adjustment_value'],
            '{L_ADJUSTMENT_AFTER}'  => $this->find_difference($this->old_adjustment['adjustment_value'], $in->get('adjustment_value', 0.00)),
//gehRAIDGROUPS
            '{L_EVENT_BEFORE}'      => $this->old_adjustment['adjustment_event'],
            '{L_EVENT_AFTER}'       => $this->find_difference($this->old_adjustment['adjustment_event'], $in->get('adjustment_event')),
//gehEND
            '{L_UPDATED_BY}'        => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
//gehRAIDGROUPS
       	$success_message = sprintf($user->lang['admin_update_adj_success'], $eqdkp->config['dkp_name'], sanitize($in->get('adjustment_value', 0.00)), sanitize($in->get('adjustment_event')));
//gehEND
        $link_list = array(
            $user->lang['list_groupadj'] => adjustment_path(),
            $user->lang['list_members']  => member_path()
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
        // Remove the old adjustment from members that received it
        // If their first raid was before/on the adjustment date, then they
        // would have received a group adjustment
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` - {$this->old_adjustment['adjustment_value']}
                WHERE (`member_firstraid` <= {$this->old_adjustment['adjustment_date']})";
        $db->query($sql);
        
        //
        // Remove the adjustment from members
        //
        $sql = "DELETE FROM __adjustments
                WHERE (`adjustment_id` = " . $db->sql_escape($this->url_id) . ")";
        $db->query($sql);
        
        //
        // Logging
        //    
        $log_action = array(
            'header'         => '{L_ACTION_GROUPADJ_DELETED}',
            'id'             => $this->url_id,
//gehRAIDGROUPS
            '{L_EVENT}'      => sanitize($this->old_adjustment['adjustment_event']),
//gehEND
            '{L_ADJUSTMENT}' => $this->old_adjustment['adjustment_value']
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //    
//gehRAIDGROUPS
        $success_message = sprintf($user->lang['admin_delete_adj_success'], $eqdkp->config['dkp_name'], $this->old_adjustment['adjustment_value'], $this->old_adjustment['adjustment_event']);
//gehEND
        $link_list = array(
            $user->lang['list_groupadj'] => adjustment_path(),
            $user->lang['list_members']  => member_path()
        );
        $this->admin_die($success_message, $link_list);    
    }
    
    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    function get_old_data()
    {
        global $db;

//gehRAIDGROUPS        
        $sql = "SELECT adjustment_value, adjustment_event, adjustment_date
                FROM __adjustments
                WHERE (`adjustment_id` = " . $db->sql_escape($this->url_id) . ")";
//gehEND
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $this->old_adjustment = array(
                'adjustment_value' => floatval($row['adjustment_value']),
//gehRAIDGROUPS
                'adjustment_event' => $row['adjustment_event'],
//gehEND
                'adjustment_date'  => intval($row['adjustment_date'])
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
//gehRAIDGROUPS
		global $in;
        //
        // Build event drop-down
        //
        //
        $sql = "SELECT event_name
                FROM __events
                ORDER BY `event_name`";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) ) 
        {
            if ( $this->url_id )
            {
                $selected = ( option_selected(!(strcmp($row['event_name'], $this->adjustment['adjustment_event']))) );
            }
            else
            {
                $selected = ( option_selected(!(strcmp($row['event_name'], $in->get('adjustment_event')))) );
            }
            $tpl->assign_block_vars('event_row', array(
                'VALUE'    => $row['event_name'],
                'SELECTED' => $selected,
                'OPTION'   => $row['event_name'])
            );
        }
        $db->free_result($result);
//gehEND
        $tpl->assign_vars(array(
            // Form vars
            'F_ADD_ADJUSTMENT' => edit_adjustment_path(),
            'ADJUSTMENT_ID'    => $this->url_id,
            
            // Form values
            'ADJUSTMENT'              => number_format($this->adjustment['adjustment_value'], 2),
            
            // Language
            'L_ADD_ADJ_TITLE'         => $user->lang['addadj_title'],
            'L_ADJUSTMENT_VALUE'      => $user->lang['adjustment_value'],
            'L_ADJUSTMENT_VALUE_NOTE' => '(' . strtolower($user->lang['adjustment_value_note']) . ')',
            'L_ADD_ADJUSTMENT'        => $user->lang['add_adjustment'],
            'L_RESET'                 => $user->lang['reset'],
            'L_UPDATE_ADJUSTMENT'     => $user->lang['update_adjustment'],
            'L_DELETE_ADJUSTMENT'     => $user->lang['delete_adjustment'],

            // Form validation
            'FV_ADJUSTMENT' => $this->fv->generate_error('adjustment_value'),
//gehRAIDGROUPS
            'ADJUSTMENT_EVENT'        => $this->adjustment['adjustment_event'],
            'L_EVENT'                 => $user->lang['event'],
            'FV_EVENT'      		  => $this->fv->generate_error('adjustment_event'),
            'MSG_EVENT_EMPTY'         => $user->lang['fv_required_event_name'],
//gehEND

            // Javascript messages
            'MSG_VALUE_EMPTY' => $user->lang['fv_required_adjustment'],
            
            // Buttons
            'S_ADD' => ( !$this->url_id ) ? true : false
        ));
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['addadj_title']),
            'template_file' => 'admin/addadj.html',
            'display'       => true
        ));
    }
}

$add_groupadj = new Add_GroupAdj;
$add_groupadj->process();
