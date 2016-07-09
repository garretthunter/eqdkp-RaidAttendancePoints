<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        addiadj.php
 * Began:       Sun Jan 5 2003
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

class Add_IndivAdj extends EQdkp_Admin
{
    var $adjustment     = array();      // Holds adjustment data if URI_ADJUSTMENT is set   @var adjustment
    var $old_adjustment = array();      // Holds adjustment data from before POST           @var old_adjustment
    
    function add_indivadj()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        parent::eqdkp_admin();
        
        $this->adjustment = array(
            'adjustment_value'  => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            'adjustment_event'  => $in->get('adjustment_event'),
//gehEND
            'adjustment_reason' => $in->get('adjustment_reason'),
            'member_names'      => $in->getArray('member_names', 'string')
        );
        
        // Vars used to confirm deletion
        $this->set_vars(array(
            'confirm_text'  => $user->lang['confirm_delete_iadj'],
            'uri_parameter' => URI_ADJUSTMENT
        ));
        
        $this->assoc_buttons(array(
            'add' => array(
                'name'    => 'add',
                'process' => 'process_add',
                'check'   => 'a_indivadj_add'
            ),
            'update' => array(
                'name'    => 'update',
                'process' => 'process_update',
                'check'   => 'a_indivadj_upd'
            ),
            'delete' => array(
                'name'    => 'delete',
                'process' => 'process_delete',
                'check'   => 'a_indivadj_del'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_indivadj_'
            )
        ));
        
        // Build the adjustment aray
        // -----------------------------------------------------
        if ( $this->url_id )
        {
            $sql = "SELECT adjustment_value, adjustment_event, adjustment_date, adjustment_reason, 
                        member_name, adjustment_group_key
                    FROM __adjustments
                    WHERE (`adjustment_id` =" . $db->sql_escape($this->url_id) . ")";
            $result = $db->query($sql);
            if ( !$row = $db->fetch_record($result) )
            {
                message_die($user->lang['error_invalid_adjustment']);
            }
            $db->free_result($result);
        
            // If member name isn't set, it's a group adjustment - put them back on that script
            if ( !isset($row['member_name']) )
            {
                redirect(edit_iadjustment_path());
            }
            
            $this->time = $row['adjustment_date'];
            $this->adjustment = array(
                'adjustment_value'  => $in->get('adjustment_value',  floatval($row['adjustment_value'])),
//gehRAIDGROUPS
                'adjustment_event'  => $in->get('adjustment_event',  $row['adjustment_event']),
//gehEND
                'adjustment_reason' => $in->get('adjustment_reason', $row['adjustment_reason'])
            );
            
            $members = $in->getArray('member_names', 'string');
            $sql = "SELECT member_name
                    FROM __adjustments
                    WHERE (`adjustment_group_key` = " . $db->sql_escape($row['adjustment_group_key']) . ")";
            $result = $db->query($sql);
            while ( $row = $db->fetch_record($result) )
            {
                $members[] = $row['member_name'];
            }
            $db->free_result($result);
            
            $this->adjustment['member_names'] = $members;
            unset($row, $members, $sql);
        }
    }
    
    function error_check()
    {
        global $user, $in;
        
        $members = $in->getArray('member_names', 'string');
        if ( count($members) == 0 )
        {
            $this->fv->errors['member_names'] = $user->lang['fv_required_members'];
        }

//gehRAIDGROUPS
        if ( !$in->exists('adjustment_event') )
        {
            $this->fv->errors['adjustment_event'] = $user->lang['fv_required_event_name'];
        }
//gehEND
        $this->fv->is_number('adjustment_value',    $user->lang['fv_number_adjustment']);
        $this->fv->is_filled('adjustment_value',    $user->lang['fv_required_adjustment']);
        $this->fv->is_within_range('mo', 1, 12,     $user->lang['fv_range_month']);
        $this->fv->is_within_range('d',  1, 31,     $user->lang['fv_range_day']);
        $this->fv->is_within_range('y', 1998, 2010, $user->lang['fv_range_year']);
        
        $this->time = mktime(0, 0, 0, $in->get('mo', 0), $in->get('d', 0), $in->get('y', 0));
        
        return $this->fv->is_error();
    }
    
    // ---------------------------------------------------------
    // Process Add
    // ---------------------------------------------------------
    function process_add()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        //
        // Generate our group key
        //
//gehRAIDGROUPS
        $group_key = $this->gen_group_key($this->time, $in->get('adjustment_reason'), $in->get('adjustment_event'), $in->get('adjustment_value', 0.00));
//gehEND
        //
        // Add adjustment to selected members
        //
        $member_names = $in->getArray('member_names', 'string');
        foreach ( $member_names as $member_name )
        {
            $this->add_new_adjustment($member_name, $group_key);
        }
        
        //
        // Logging
        //
        $log_action = array(
            'header'         => '{L_ACTION_INDIVADJ_ADDED}',
            '{L_ADJUSTMENT}' => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            '{L_EVENT}'      => $in->get('adjustment_event'),
//gehEND
            '{L_REASON}'     => $in->get('adjustment_reason'),
            '{L_MEMBERS}'    => implode(', ', $member_names),
            '{L_ADDED_BY}'   => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
//gehRAIDGROUP
       	$success_message = sprintf($user->lang['admin_add_iadj_success'], $eqdkp->config['dkp_name'], sanitize($in->get('adjustment_value', 0.00)), sanitize($in->get('adjustment_event')), sanitize(implode(', ', $member_names)));
//gehEND
        $link_list = array(
            $user->lang['list_indivadj'] => iadjustment_path(),
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
        // Remove the old adjustment from members that received it
        // and then remove the adjustment itself
        //
        $this->remove_old_adjustment();
        
        //
        // Generate a new group key
        //
//gehRAIDGROUP
        $group_key = $this->gen_group_key($this->time, $in->get('adjustment_reason'), $in->get('adjustment_value', 0.00), $in->get('adjustment_event'));
//gehEND
        //
        // Add the new adjustment to selected members
        //
        $member_names = $in->getArray('member_names', 'string');
        foreach ( $member_names as $member_name )
        {
            $this->add_new_adjustment($member_name, $group_key);
        }
        
        //
        // Logging
        //
        $log_action = array(
            'header'                => '{L_ACTION_INDIVADJ_UPDATED}',
            'id'                    => $this->url_id,
            '{L_ADJUSTMENT_BEFORE}' => $this->old_adjustment['adjustment_value'],
            '{L_REASON_BEFORE}'     => $this->old_adjustment['adjustment_reason'],
            '{L_MEMBERS_BEFORE}'    => implode(', ', $this->old_adjustment['member_names']),
            '{L_ADJUSTMENT_AFTER}'  => $this->find_difference($this->old_adjustment['adjustment_value'],  $in->get('adjustment_value', 0.00)),
            '{L_REASON_AFTER}'      => $this->find_difference($this->old_adjustment['adjustment_reason'], $in->get('adjustment_reason')),
            '{L_MEMBERS_AFTER}'     => implode(', ', $this->find_difference($this->old_adjustment['member_names'], $member_names)),
//gehRAIDGROUPS
            '{L_EVENT_BEFORE}'      => $this->old_adjustment['adjustment_event'],
            '{L_EVENT_AFTER}'       => $this->find_difference($this->old_adjustment['adjustment_event'],  $in->get('adjustment_event')),
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
        $success_message = sprintf($user->lang['admin_update_iadj_success'], $eqdkp->config['dkp_name'], sanitize($in->get('adjustment_value', 0.00)), sanitize($in->get('adjustment_event')), sanitize(implode(', ', $member_names)));
//gehEND
        $link_list = array(
            $user->lang['list_indivadj'] => iadjustment_path(),
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
        // Remove the old adjustment from members that received it
        // and then remove the adjustment itself
        //
        $this->remove_old_adjustment();
        
        //
        // Logging
        //
        $log_action = array(
            'header'         => '{L_ACTION_INDIVADJ_DELETED}',
            'id'             => $this->url_id,
//gehRAIDGROUPS
            '{L_EVENT}'      => $this->old_adjustment['adjustment_event'],
//gehEND
            '{L_ADJUSTMENT}' => $this->old_adjustment['adjustment_value'],
            '{L_REASON}'     => $this->old_adjustment['adjustment_reason'],
            '{L_MEMBERS}'    => implode(', ', $this->old_adjustment['member_names'])
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success messages
        //
        $success_message = sprintf($user->lang['admin_delete_iadj_success'], $eqdkp->config['dkp_name'], sanitize($this->old_adjustment['adjustment_value']), sanitize($this->old_adjustment['adjustment_event']), sanitize(implode(', ', $this->old_adjustment['member_names'])));
        $link_list = array(
            $user->lang['list_indivadj'] => iadjustment_path(),
            $user->lang['list_members']  => member_path()
        );
        $this->admin_die($success_message, $link_list);
    }
    
    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    function remove_old_adjustment()
    {
        global $db;
    
        $adjustment_ids = array();
        $old_members    = array();
        
        $sql = "SELECT a2.*
                FROM __adjustments AS a1 LEFT JOIN __adjustments AS a2 
                ON a1.`adjustment_group_key` = a2.`adjustment_group_key`
                WHERE (a1.`adjustment_id` = " . $db->sql_escape($this->url_id) . ")";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $adjustment_ids[] = intval($row['adjustment_id']);

            $old_members[] = $row['member_name'];
            $this->old_adjustment = array(
                'adjustment_value'  => floatval($row['adjustment_value']),
                'adjustment_date'   => intval($row['adjustment_date']),
//gehRAIDGROUPS
                'adjustment_event'  => $row['adjustment_event'],
//gehEND
                'member_names'      => $old_members,
                'adjustment_reason' => $row['adjustment_reason']
            );
        }
        
        //
        // Remove the adjustment value from adjustments table
        //
        $sql = "DELETE FROM __adjustments
                WHERE (`adjustment_id` IN (" . $db->sql_escape(',', $adjustment_ids) . "))";
        $db->query($sql);
        
        //
        // Remove the adjustment value from members
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` - {$this->old_adjustment['adjustment_value']}
                WHERE (`member_name` IN (" . $db->sql_escape("', '", $this->old_adjustment['member_names']) . "'))";
//gehDEBUG
echo "what is happening with sql_escape on old adjustment: $sql"; //gehDEBUG
        $db->query($sql);
    }
    
    function add_new_adjustment($member_name, $group_key)
    {
        global $db, $in;
        
        //
        // Add the adjustment to the member
        //
        $sql = "UPDATE __members
                SET `member_adjustment` = `member_adjustment` + " . $db->sql_escape($in->get('adjustment_value', 0.00)) . "
                WHERE (`member_name` = '" . $db->sql_escape($member_name) . "')";
        $db->query($sql);
        unset($sql);
        
        //
        // Add the adjustment to the database
        //
        $query = $db->sql_build_query('INSERT', array(
            'adjustment_value'     => $in->get('adjustment_value', 0.00),
//gehRAIDGROUPS
            'adjustment_event'     => $in->get('adjustment_event'),
//gehEND
            'adjustment_date'      => $this->time,
            'member_name'          => $member_name,
            'adjustment_reason'    => $in->get('adjustment_reason'),
            'adjustment_group_key' => $group_key,
            'adjustment_added_by'  => $this->admin_user
        ));
        $db->query("INSERT INTO __adjustments {$query}");
    }
    
    // ---------------------------------------------------------
    // Display form
    // ---------------------------------------------------------
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        //
        // Build event drop-down
        //
        //
        $sql = "SELECT event_name
                FROM __events
                ORDER BY event_name";
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
		
        // Build member drop-down
        //
        $sql = "SELECT member_name
                FROM __members
                ORDER BY member_name";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            if ( $this->url_id )
            {
                $selected = option_selected(@in_array($row['member_name'], $this->adjustment['member_names']));
            }
            else
            {
                $selected = option_selected(@in_array($row['member_name'], $in->getArray('member_names', 'string')));
            }
            
            $tpl->assign_block_vars('members_row', array(
                'VALUE'    => sanitize($row['member_name'], ENT),
                'SELECTED' => $selected,
                'OPTION'   => sanitize($row['member_name'], ENT),
            ));
        }
        $db->free_result($result);
        
        $tpl->assign_vars(array(
            // Form vars
            'F_ADD_ADJUSTMENT' => edit_iadjustment_path(),
            'ADJUSTMENT_ID'    => $this->url_id,
            
            // Form values
            'ADJUSTMENT_VALUE'  => number_format(sanitize($this->adjustment['adjustment_value'], ENT), 2),
            'ADJUSTMENT_REASON' => sanitize($this->adjustment['adjustment_reason'], ENT),
            'MO'                => date('m', $this->time),
            'D'                 => date('d', $this->time),
            'Y'                 => date('Y', $this->time),
            'H'                 => date('h', $this->time),
            'MI'                => date('i', $this->time),
            'S'                 => date('s', $this->time),

//gehRAIDGROUPS
            'ADJUSTMENT_EVENT'  => sanitize($this->adjustment['adjustment_event'], ENT),
            'L_EVENT'           => $user->lang['event'],
            'FV_EVENT'          => $this->fv->generate_error('adjustment_event'),
            'MSG_EVENT_EMPTY'   => $user->lang['fv_required_event_name'],
//gehEND
            // Language
            'L_ADD_IADJ_TITLE'        => $user->lang['addiadj_title'],
            'L_MEMBERS'               => $user->lang['members'],
            'L_HOLD_CTRL_NOTE'        => '(' . $user->lang['hold_ctrl_note'] . ')<br />',
            'L_REASON'                => $user->lang['reason'],
            'L_VALUE'                 => $user->lang['value'],
            'L_ADJUSTMENT_VALUE_NOTE' => strtolower($user->lang['adjustment_value_note']),
            'L_DATE'                  => $user->lang['date'],
            'L_ADD_ADJUSTMENT'        => $user->lang['add_adjustment'],
            'L_RESET'                 => $user->lang['reset'],
            'L_UPDATE_ADJUSTMENT'     => $user->lang['update_adjustment'],
            'L_DELETE_ADJUSTMENT'     => $user->lang['delete_adjustment'],
            
            // Form validation
            'FV_MEMBERS'    => $this->fv->generate_error('member_names'),
            'FV_ADJUSTMENT' => $this->fv->generate_error('adjustment_value'),
            'FV_MO'         => $this->fv->generate_error('mo'),
            'FV_D'          => $this->fv->generate_error('d'),
            'FV_Y'          => $this->fv->generate_error('y'),
            
            // Javascript messages
            'MSG_VALUE_EMPTY' => $user->lang['fv_required_adjustment'],

            // Buttons
            'S_ADD' => ( !$this->url_id ) ? true : false
        ));
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['addiadj_title']),
            'template_file' => 'admin/addiadj.html',
            'display'       => true
        ));
    }
}

$add_indivadj = new Add_IndivAdj;
$add_indivadj->process();
