<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        addraid.php
 * Began:       Mon Dec 23 2002
 * Date:        $Date: 2008-05-17 17:19:30 -0700 (Sat, 17 May 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 530 $
 */
 
define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
require_once($eqdkp_root_path . 'common.php');

class Add_Raid extends EQdkp_Admin
{
    var $raid     = array();            // Holds raid data if URI_RAID is set               @var raid
    var $old_raid = array();            // Holds raid data from before POST                 @var old_raid
    
    function add_raid()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $in;
        
        parent::eqdkp_admin();
        
        $this->raid = array(
            'raid_date'      => ( !$this->url_id ) ? $this->time : '',
            'raid_attendees' => $in->get('raid_attendees'),
            'raid_name'      => $in->getArray('raid_name', 'string'),
            'raid_note'      => $in->get('raid_note'),
            'raid_value'     => $in->get('raid_value') // Can't get this field as a float, bleh
        );
        
        // Vars used to confirm deletion
        $this->set_vars(array(
            'confirm_text'  => $user->lang['confirm_delete_raid'],
            'uri_parameter' => URI_RAID
        ));
        
        $this->assoc_buttons(array(
            'add' => array(
                'name'    => 'add',
                'process' => 'process_add',
                'check'   => 'a_raid_add'
            ),
            'update' => array(
                'name'    => 'update',
                'process' => 'process_update',
                'check'   => 'a_raid_upd'
            ),
            'delete' => array(
                'name'    => 'delete',
                'process' => 'process_delete',
                'check'   => 'a_raid_del'
            ),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_raid_'
            )
        ));
        
        // Build the raid array
        // ---------------------------------------------------------
        if ( $this->url_id )
        {
            $sql = "SELECT raid_id, raid_name, raid_date, raid_note, raid_value
                    FROM __raids
                    WHERE (`raid_id` = '{$this->url_id}')";
            $result = $db->query($sql);
            if ( !$row = $db->fetch_record($result) )
            {
                message_die($user->lang['error_invalid_raid_provided']);
            }
            $db->free_result($result);
        
            $this->time = $row['raid_date'];
            $this->raid = array(
                'raid_name'  => $in->get('raid_name', $row['raid_name']),
                'raid_note'  => $in->get('raid_note', $row['raid_note']),
                'raid_value' => $in->get('raid_value', floatval($row['raid_value']))
            );
            
            $attendees = $in->get('raid_attendees');
            if ( empty($attendees) )
            {
                $attendees = array();
                $sql = "SELECT member_name
                        FROM __raid_attendees
                        WHERE (`raid_id` = '{$this->url_id}')
                        ORDER BY member_name";
                $result = $db->query($sql);
                while ( $row = $db->fetch_record($result) )
                {
                    $attendees[] = $row['member_name'];
                }
            }
            $this->raid['raid_attendees'] = ( is_array($attendees) ) ? implode("\n", $attendees) : $attendees;
            unset($attendees);
        }
    }
    
    function error_check()
    {
        global $user, $in;
        
        $this->fv->is_filled('raid_attendees', $user->lang['fv_required_attendees']);
    
        $this->fv->is_within_range('mo', 1, 12,      $user->lang['fv_range_month']);
        $this->fv->is_within_range('d',  1, 31,      $user->lang['fv_range_day']);
        $this->fv->is_within_range('y',  1998, 2020, $user->lang['fv_range_year']); // How ambitious
        $this->fv->is_within_range('h',  0, 23,      $user->lang['fv_range_hour']);
        $this->fv->is_within_range('mi', 0, 59,      $user->lang['fv_range_minute']);
        $this->fv->is_within_range('s',  0, 59,      $user->lang['fv_range_second']);
        
        $raid_value = $in->get('raid_value');
        if ( !empty($raid_value) )
        {
            $this->fv->is_number('raid_value', $user->lang['fv_number_value']);
        }
    
        $raid_name = $in->getArray('raid_name', 'string');
        if ( empty($raid_name) )
        {
            $this->fv->errors['raid_name'] = $user->lang['fv_required_event_name'];
        }
        
        // FIXME: If we enter an invalid value in a date field, an error is generated, but we get back a bogus date
        $this->time = mktime($in->get('h', 0), $in->get('mi', 0), $in->get('s', 0),
            $in->get('mo', 0), $in->get('d', 0), $in->get('y', 0)
        );
        
        return $this->fv->is_error();
    }
    
    // ---------------------------------------------------------
    // Process Add
    // ---------------------------------------------------------
    function process_add()
    {
        global $db, $user, $pm, $in;
        
        $success_message = '';
        $this_raid_id    = 0;
        
        //
        // Raid loop for multiple events
        //
        $raid_names = $in->getArray('raid_name', 'string');
        foreach ( $raid_names as $raid_name )
        {
            // Get the raid value
            $raid_value = $this->_get_raid_value($raid_name);
            
            // Insert the raid to get the raid's ID for attendees
            $db->query("INSERT INTO __raids :params", array(
                'raid_name'     => $raid_name,
                'raid_date'     => $this->time,
                'raid_note'     => $in->get('raid_note'),
                'raid_value'    => $raid_value,
                'raid_added_by' => $this->admin_user
            ));
            $this_raid_id = $db->insert_id();
            
            // Attendee handling
            $raid_attendees = $this->_prepare_attendees();
            
            // Adds attendees to __raid_attendees; adds/updates Member entries as necessary
            $this->_process_attendees($raid_attendees, $this_raid_id, $raid_value);
            
            // Update firstraid / lastraid / raidcount
            $this->_update_member_cache($raid_attendees);
            
            // Call plugin add hooks
            $pm->do_hooks('/admin/addraid.php?action=add');
            
            //
            // Logging
            //
            $log_action = array(
                'header'        => '{L_ACTION_RAID_ADDED}',
                'id'            => $this_raid_id,
                '{L_EVENT}'     => $raid_name,
                '{L_ATTENDEES}' => implode(', ', $raid_attendees),
                '{L_NOTE}'      => $in->get('raid_note'),
                '{L_VALUE}'     => $raid_value,
                '{L_ADDED_BY}'  => $this->admin_user
            );
            $this->log_insert(array(
                'log_type'   => $log_action['header'],
                'log_action' => $log_action
            ));
            
            //
            // Append success message
            //
            $success_message .= sprintf($user->lang['admin_add_raid_success'], date($user->style['date_notime_short'], $this->time), sanitize($raid_name)) . '<br />';
            
            unset($raid_value);
        } // Raid loop
        
        // Update member active/inactive status if necessary
        $success_message .= $this->_update_member_status();
        
        //
        // Success message
        //
        $link_list = array(
            $user->lang['add_items_from_raid'] => edit_item_path() . path_params('raid_id', $this_raid_id),
            $user->lang['add_raid']            => edit_raid_path(),
            $user->lang['list_raids']          => raid_path()
        );
        $this->admin_die($success_message, $link_list);
    }
    
    // ---------------------------------------------------------
    // Process Update
    // ---------------------------------------------------------
    function process_update()
    {
        global $db, $user, $pm, $in;
        
        // Get the old data
        $this->_get_old_data();
        $old_raid_attendees = explode(',', $this->old_raid['raid_attendees']);
        
        // Get the raid value
        $raid_value = $this->_get_raid_value($in->get('raid_name'));
        
        // Attendee handling
        $raid_attendees = $this->_prepare_attendees();
        
        // NOTE: When $old is the first argument, we will not needlessly delete new attendees that aren't in the table to begin with
        $remove_attendees = array_diff($old_raid_attendees, $raid_attendees);
        
        ## ####################################################################
        ## 'Undo' the raid from old attendees
        ## ####################################################################
        
        // Remove the attendees from the old raid
        $sql = "DELETE FROM __raid_attendees
                WHERE (`raid_id` = '{$this->url_id}')
                AND (`member_name` IN (" . $db->sql_escape("','", $remove_attendees) . "))";
        $db->query($sql);
        
        // Remove the value of the old raid from the old attendees' earned
        $sql = "UPDATE __members
                SET `member_earned` = `member_earned` - {$this->old_raid['raid_value']}
                WHERE (`member_name` IN (" . $db->sql_escape("','", $old_raid_attendees) . "))";
        $db->query($sql);
        
        ## ####################################################################
        ## Update the array with current data
        ## ####################################################################
        
        //
        // Update the raid
        //
        $db->query("UPDATE __raids SET :params WHERE (`raid_id` = '{$this->url_id}')", array(
            'raid_date'       => $this->time,
            'raid_note'       => $in->get('raid_note'),
            'raid_value'      => $raid_value,
            'raid_name'       => $in->get('raid_name'),
            'raid_updated_by' => $this->admin_user
        ));
        
        // Replaces attendee entries in __raid_attendees; adds/updates Member entries as necessary
        $this->_process_attendees($raid_attendees, $this->url_id, $raid_value);
        
        // Update firstraid / lastraid / raidcount
        // NOTE: Merge these because if we delete someone, we want their cache udpated as well!
        $this->_update_member_cache(array_merge($raid_attendees, $remove_attendees));
        
        // Call plugin update hooks
        $pm->do_hooks('/admin/addraid.php?action=update');
        
        // Logging
        $log_action = array(
            'header'               => '{L_ACTION_RAID_UPDATED}',
            'id'                   => $this->url_id,
            '{L_EVENT_BEFORE}'     => $this->old_raid['raid_name'],
            '{L_ATTENDEES_BEFORE}' => implode(', ', $this->find_difference($raid_attendees, $old_raid_attendees)),
            '{L_NOTE_BEFORE}'      => $this->old_raid['raid_note'],
            '{L_VALUE_BEFORE}'     => $this->old_raid['raid_value'],
            '{L_EVENT_AFTER}'      => $this->find_difference($this->old_raid['raid_name'], $in->get('raid_name')),
            '{L_ATTENDEES_AFTER}'  => implode(', ', $this->find_difference($old_raid_attendees, $raid_attendees)),
            '{L_NOTE_AFTER}'       => $this->find_difference($this->old_raid['raid_note'], $in->get('raid_note')),
            '{L_VALUE_AFTER}'      => $this->find_difference($this->old_raid['raid_value'], $raid_value),
            '{L_UPDATED_BY}'       => $this->admin_user
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
        $success_message = sprintf($user->lang['admin_update_raid_success'], date($user->style['date_notime_short'], $this->time), sanitize($in->get('raid_name')));
        
        // Update member active/inactive status if necessary
        $success_message .= $this->_update_member_status();
        
        $link_list = array(
            $user->lang['add_items_from_raid'] => edit_item_path() . path_params('raid_id', $this->url_id),
            $user->lang['add_raid']            => edit_raid_path(),
            $user->lang['list_raids']          => raid_path()
        );
        $this->admin_die($success_message, $link_list);
    }
    
    // ---------------------------------------------------------
    // Process Delete (confirmed)
    // ---------------------------------------------------------
    function process_confirm()
    {
        global $db, $user, $pm;
        
        //
        // Get the old data
        //
        $this->_get_old_data();
        $raid_attendees = explode(',', $this->old_raid['raid_attendees']);
        
        //
        // Take the value away from the attendees
        //
        $sql = "UPDATE __members
                SET `member_earned` = `member_earned` - {$this->old_raid['raid_value']},
                    `member_raidcount` = `member_raidcount` - 1
                WHERE (`member_name` IN (" . $db->sql_escape("','", $raid_attendees) . "))";
        $db->query($sql);
        
        //
        // Remove cost of items from this raid from buyers
        //
        $sql = "SELECT item_id, item_buyer, item_value
                FROM __items
                WHERE (`raid_id` = '{$this->url_id}')";
        $result = $db->query($sql);
//gehITEM_DECORATION
		$game_item_ids = array();
//gehEND
        while ( $row = $db->fetch_record($result) )
        {
            $item_value = ( !empty($row['item_value']) ) ? floatval($row['item_value']) : 0.00;
//gehITEM_DECORATION
			$game_item_ids[] = $row['item_id'];
//gehEND
            // One less query if there's no value to remove
            if ( $item_value > 0 )
            {
                $sql = "UPDATE __members
                        SET `member_spent` = `member_spent` - {$item_value}
                        WHERE (`member_name` = '{$row['item_buyer']}')";
                $db->query($sql);
            }
        }
        $db->free_result($result);
        
        // Delete associated items
        $db->query("DELETE FROM __items WHERE (`raid_id` = '{$this->url_id}')");
//gehITEM_DECORATION
        $db->query("DELETE FROM __game_items WHERE (`item_id` IN  (". implode(",",$game_item_ids)."))");
//gehEND
        
        // Delete attendees
        $db->query("DELETE FROM __raid_attendees WHERE (`raid_id` = '{$this->url_id}')");
        
        // Remove the raid itself
        $db->query("DELETE FROM __raids WHERE (`raid_id` = '{$this->url_id}')");
        
        // Update firstraid / lastraid / raidcount
        $this->_update_member_cache($raid_attendees);
        
        // Call plugin delete hooks
        $pm->do_hooks('/admin/addraid.php?action=delete');
        
        //
        // Logging
        //
        $log_action = array(
            'header'        => '{L_ACTION_RAID_DELETED}',
            'id'            => $this->url_id,
            '{L_EVENT}'     => $this->old_raid['raid_name'],
            '{L_ATTENDEES}' => implode(', ', $raid_attendees),
            '{L_NOTE}'      => $this->old_raid['raid_note'],
            '{L_VALUE}'     => $this->old_raid['raid_value']
        );
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action
        ));
        
        //
        // Success message
        //
        $success_message = $user->lang['admin_delete_raid_success'];
        
        // Update member active/inactive status if necessary
        $success_message .= $this->_update_member_status();
        
        $link_list = array(
            $user->lang['add_raid']   => edit_raid_path(),
            $user->lang['list_raids'] => raid_path()
        );
        $this->admin_die($success_message, $link_list);
    }
    
    // ---------------------------------------------------------
    // Process helper methods
    // ---------------------------------------------------------
    /**
     * Populate the {@link $old_raid} array
     * 
     * @return void
     * @access private
     */
    function _get_old_data()
    {
        global $db, $pm;
        
        $sql = "SELECT raid_name, raid_value, raid_note, raid_date
                FROM __raids
                WHERE (`raid_id` = '{$this->url_id}')";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            // TODO: Find out if we really need addslashes here.
            $this->old_raid = array(
                'raid_name'  => addslashes($row['raid_name']),
                'raid_value' => floatval($row['raid_value']),
                'raid_note'  => addslashes($row['raid_note']),
                'raid_date'  => intval($row['raid_date'])
            );
        }
        $db->free_result($result);
        
        $attendees = array();
        $sql = "SELECT r.member_name
                FROM __raid_attendees AS r, __members AS m
                WHERE (m.`member_name` = r.`member_name`)
                AND (`raid_id` = '{$this->url_id}')
                ORDER BY `member_name`";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $attendees[] = $row['member_name'];
        }
        $this->old_raid['raid_attendees'] = implode(',', $attendees);
        unset($attendees);
    }
    
    /**
     * Fetch a events's value, given its name, or a user-supplied value if set
     *
     * @param string $raid_name Raid (event) name
     * @return float
     * @access private
     */
    function _get_raid_value($raid_name)
    {
        global $db, $in;
        
        $raid_name = $db->sql_escape($raid_name);
        
        $sql = "SELECT event_value
                FROM __events
                WHERE (`event_name` = {$raid_name})";
        $preset_value = $db->query_first($sql);
        
        // Use the input value to perform a one-time change, if provided
        $input_value = $in->get('raid_value');
        
        $raid_value = ( empty($input_value) ) ? $preset_value : $input_value;
        
        return floatval($raid_value);
    }
    
    /**
     * Grabs raid attendees from Input and puts them in a format for use
     * elsewhere in the class.
     *
     * @return array
     * @access private
     */
    function _prepare_attendees()
    {
        global $in;
        
        // Input should be a newline-separated list of attendee names
        $retval = $in->get('raid_attendees');
        
        // Replace any space character (including newlines) with a single space
        $retval = preg_replace('/\s+/', ' ', $retval);
        
        $retval = explode(' ', $retval);
        foreach ( $retval as $k => $v )
        {
            $v = trim($v);
            $v = ucfirst(strtolower($v));
            
            if ( !empty($v) )
            {
                $retval[$k] = $v;
            }
            else
            {
                unset($retval[$k]);
            }
        }

        $retval = array_unique($retval);
        sort($retval);
        reset($retval);
        
        return $retval;
    }
    
    /**
     * For each attendee on a raid, add a record in __raid_attendees and add or 
     * update their __members row
     *
     * @param string $att_array Array of attendees as prepared by {@link _prepare_attendees}
     * @param string $raid_id Raid ID
     * @param string $raid_value Raid value to give each attendee
     * @return void
     * @access private
     */
    function _process_attendees($att_array, $raid_id, $raid_value)
    {
        global $db, $user;
        
        $raid_id    = intval($raid_id);
        $raid_value = floatval($raid_value);
        
        // Gather data about our attendees that we'll need to rebuild their records
        // This has to be done instead of using REPLACE INTO deletes the record 
        // before re-inserting it, meaning we lose the member's data and the 
        // default database values get used (BAD!)
        $att_data = array();
        $sql = "SELECT *
                FROM __members
                WHERE (`member_name` IN (" . $db->sql_escape("','", $att_array) . "))
                ORDER BY member_name";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $att_data[ $row['member_name'] ] = $row;
        }
        $db->free_result($result);
        
        session_start();
        foreach ( $att_array as $attendee )
        {
            // Add each attendee to the attendees table for this raid
            $sql = "REPLACE INTO __raid_attendees (raid_id, member_name)
                    VALUES ('{$raid_id}', " . $db->sql_escape($attendee) . ")";
            $db->query($sql);
            
            // Set the bare-minimum values for a new member
            $row = array(
                'member_name' => $attendee,
            );
            
            ## ################################################################
            ## Update existing member data
            ## ################################################################
            if ( isset($att_data[$attendee]) )
            {
                // Inject our saved data into our row that gets updated
                $row = array_merge($row, $att_data[$attendee]);
                
                // Merge SESSION data from a log parse if it exists
                if ( isset($_SESSION['log']) )
                {
                    $row = array_merge($row, $this->_get_session_data($row));
                }
                
                // Some of our values need to be updated, so do that!
                $row['member_earned'] = floatval($row['member_earned']) + $raid_value;
                
                $db->query("UPDATE __members SET :params WHERE (`member_name` = " . $db->sql_escape($attendee) . ")", $row);
            }
            ## ################################################################
            ## Add new member
            ## ################################################################
            else
            {
                $row['member_earned'] = $raid_value;
                
                // Merge SESSION data from a log parse if it exists
                if ( isset($_SESSION['log']) )
                {
                    $row = array_merge($row, $this->_get_session_data($row));
                }
                
                $db->query("INSERT INTO __members :params", $row);
            }
        }
    }
    
    /**
     * Get member character data from SESSION, stored during a log parse
     *
     * @param array $member Member row
     * @return array Updated data ready to be inserted into __members
     * @access private
     */
    function _get_session_data($member)
    {
        global $gm;
        
        if ( !is_array($member) )
        {
            return;
        }
        
        $name = $member['member_name'];
        
        if ( isset($_SESSION['log'][$name]) )
        {
            $srow = $_SESSION['log'][$name];
            
            // Only update their level if it went up
            // TODO: Is there a sick game where a member could legitimately lose a level?
            if ( !isset($member['member_level']) || intval($srow['level']) > $member['member_level'] )
            {
                $member['member_level'] = $srow['level'];
            }
            
            // Member races and classes don't (shouldn't) change; only update these if previous values were blank
            if ( $member['member_race_id'] == 0 )
            {
                $member['member_race_id'] = intval($gm->lookup_race($srow['race']));
            }
            
            if ( $member['member_class_id'] == 0 )
            {
                $member['member_class_id'] = intval($gm->lookup_class($srow['class']));
            }
        }
        
        return $member;
    }
    
    /**
     * Recalculates and updates the first and last raids and raid counts for each
     * member in $att_array
     *
     * @param string $att_array Array of raid attendees
     * @return void
     * @access private
     */
    function _update_member_cache($att_array)
    {
        global $db;
        
        if ( !is_array($att_array) || count($att_array) == 0 )
        {
            return;
        }
        
        $sql = "SELECT m.member_name, MIN(r.raid_date) AS firstraid, 
                    MAX(r.raid_date) AS lastraid, COUNT(r.raid_id) AS raidcount
                FROM __members AS m
                LEFT JOIN __raid_attendees AS ra ON m.member_name = ra.member_name
                LEFT JOIN __raids AS r on ra.raid_id = r.raid_id
                WHERE (m.`member_name` IN (" . $db->sql_escape("','", $att_array) . "))
                GROUP BY m.member_name";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $db->query("UPDATE __members SET :params WHERE (`member_name` = " . $db->sql_escape($row['member_name']) . ")", array(
                'member_firstraid' => $row['firstraid'],
                'member_lastraid'  => $row['lastraid'],
                'member_raidcount' => $row['raidcount']
            ));
        }
        $db->free_result($result);
    }
    
    /**
     * Update active/inactive player status, inserting adjustments if necessary
     * 
     * @return string Success message
     * @access private
     */
    function _update_member_status()
    {
        global $db, $eqdkp, $user;
        
        if ( $eqdkp->config['hide_inactive'] == 0 )
        {        
            return;
        }
        
        // Timestamp for the active/inactive threshold; members with a lastraid before this date are inactive
        $inactive_time = strtotime(date('Y-m-d', time() - 60 * 60 * 24 * $eqdkp->config['inactive_period']));
        $current_time  = time();

        $active_members   = array();
        $inactive_members = array();
        
        $active_adj   = floatval($eqdkp->config['active_point_adj']);
        $inactive_adj = floatval($eqdkp->config['inactive_point_adj']);
        
        // Don't go through this whole thing of active/inactive adjustments if we don't need to.
        if ( $active_adj > 0 || $inactive_adj > 0 )
        {
            $sql = "SELECT member_name, member_status, member_lastraid
                    FROM __members";
            $result = $db->query($sql);
            while ( $row = $db->fetch_record($result) )
            {
                unset($adj_value, $adj_reason);
                
                // Active -> Inactive
                if ( $inactive_adj > 0 && $row['member_status'] == '1' && $row['member_lastraid'] < $inactive_time )
                {
                    $adj_value  = $eqdkp->config['inactive_point_adj'];
                    $adj_reason = $user->lang['inactive_adjustment'];
                    
                    $inactive_members[] = $row['member_name'];
                }
                // Inactive -> Active
                elseif ( $active_adj > 0 && $row['member_status'] == '0' && $row['member_lastraid'] >= $inactive_time )
                {
                    $adj_value  = $eqdkp->config['active_point_adj'];
                    $adj_reason = $user->lang['active_adjustment'];
                    
                    $active_members[] = $row['member_name'];
                }
                
                //
                // Insert individual adjustment
                //
                if ( isset($adj_value) && isset($adj_reason) )
                {
                    $group_key = $this->gen_group_key($current_time, $adj_reason, $adj_value);

                    $db->query("INSERT INTO __adjustments :params", array(
                        'adjustment_value'     => $adj_value,
                        'adjustment_date'      => $current_time,
                        'member_name'          => $row['member_name'],
                        'adjustment_reason'    => $adj_reason,
                        'adjustment_group_key' => $group_key,
                        'adjustment_added_by'  => $user->data['user_name']
                    ));
                }
            }
            
            // Update inactive members' adjustment
            if ( count($inactive_members) > 0 )
            {
                $adj_value  = $eqdkp->config['inactive_point_adj'];
                $adj_reason = 'Inactive adjustment';
                
                $sql = "UPDATE __members
                        SET `member_status` = 0, `member_adjustment` = `member_adjustment` + {$eqdkp->config['inactive_point_adj']}
                        WHERE (`member_name` IN (" . $db->sql_escape("','", $inactive_members) . "))";
                        
                $log_action = array(
                    'header'         => '{L_ACTION_INDIVADJ_ADDED}',
                    '{L_ADJUSTMENT}' => $eqdkp->config['inactive_point_adj'],
                    '{L_MEMBERS}'    => implode(', ', $inactive_members),
                    '{L_REASON}'     => $user->lang['inactive_adjustment'],
                    '{L_ADDED_BY}'   => $user->data['user_name']
                );
                $this->log_insert(array(
                    'log_type'   => $log_action['header'],
                    'log_action' => $log_action
                ));
            }
            
            // Update active members' adjustment
            if ( count($active_members) > 0 )
            {
                $sql = "UPDATE __members
                        SET `member_status` = 1, `member_adjustment` = `member_adjustment` + {$eqdkp->config['active_point_adj']}
                        WHERE (`member_name` IN (" . $db->sql_escape("','", $active_members) . "))";
                $db->query($sql);
                
                $log_action = array(
                    'header'         => '{L_ACTION_INDIVADJ_ADDED}',
                    '{L_ADJUSTMENT}' => $eqdkp->config['active_point_adj'],
                    '{L_MEMBERS}'    => implode(', ', $active_members),
                    '{L_REASON}'     => $user->lang['active_adjustment'],
                    '{L_ADDED_BY}'   => $user->data['user_name']
                );
                $this->log_insert(array(
                    'log_type'   => $log_action['header'],
                    'log_action' => $log_action
                ));
            }
        }
        else
        {
            // We're not dealing with active/inactive adjustments, so just update the status field
            
            // Active -> Inactive
            $db->query("UPDATE __members SET `member_status` = '0' WHERE (`member_lastraid` < {$inactive_time}) AND (`member_status` = 1)");
        
            // Inactive -> Active
            $db->query("UPDATE __members SET `member_status` = '1' WHERE (`member_lastraid` >= {$inactive_time}) AND (`member_status` = 0)");
        }
        
        $retval  = '<br /><br />' . $user->lang['admin_raid_success_hideinactive'];
        $retval .= ' ' . strtolower($user->lang['done']);
        
        return $retval;
    }
    
    // ---------------------------------------------------------
    // Display form
    // ---------------------------------------------------------
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        
        //
        // Find the value of the event, or use the one-time value from the form
        //
        $raid_name = $this->raid['raid_name'];
        if ( is_array($this->raid['raid_name']) )
        {
            if ( count($this->raid['raid_name']) > 0 )
            {
                $raid_name = $this->raid['raid_name'][0];
            }
            else
            {
                $raid_name = '';
            }
        }
        
        // This value is what we expect it to be based on the event's name
        $preset_value = $db->query_first("SELECT event_value FROM __events WHERE (`event_name` = " . $db->sql_escape($raid_name) . ")");
        $raid_value = ( $this->raid['raid_value'] == 0 )             ? '' : $this->raid['raid_value'];
        $raid_value = ( $this->raid['raid_value'] == $preset_value ) ? '' : $this->raid['raid_value'];
        
        // Use the preset value unless the user supplied a one-time change
        // $raid_value = ( $this->raid['raid_value'] != $preset_value ) ? $this->raid['raid_value'] : $preset_value;
        
        //
        // Build member drop-down
        //
        $sql = "SELECT member_name
                FROM __members
                ORDER BY member_name";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $tpl->assign_block_vars('members_row', array(
                'VALUE'  => sanitize($row['member_name'], ENT),
                'OPTION' => $row['member_name']
            ));
        }
        $db->free_result($result);
        
        //
        // Build event drop-down
        //
        $max_length = strlen(strval($db->query_first("SELECT MAX(event_value) FROM __events")));

        $sql = "SELECT event_id, event_name, event_value
                FROM __events
                ORDER BY event_name";
        $result = $db->query($sql);
        while ( $row = $db->fetch_record($result) )
        {
            $selected = '';
            
            if ( is_array($this->raid['raid_name']) )
            {
                $selected = option_selected(in_array($row['event_name'], $this->raid['raid_name']));
            }
            else
            {
                $selected = option_selected($row['event_name'] == $this->raid['raid_name']);
            }
            
            $event_value = number_format($row['event_value'], 2);
            
            $tpl->assign_block_vars('events_row', array(
                'VALUE'    => sanitize($row['event_name'], ENT),
                'SELECTED' => $selected,
                // NOTE: Kinda pointless since the select box isn't fixed width!
                'OPTION'   => str_pad($event_value, $max_length, ' ', STR_PAD_LEFT) . ' - ' . $row['event_name']
            ));
        }
        $db->free_result($result);
        
        $tpl->assign_vars(array(
            // Form vars
            'F_ADD_RAID'       => edit_raid_path(),
            'RAID_ID'          => $this->url_id,
            'U_ADD_EVENT'      => edit_event_path(),
            'S_EVENT_MULTIPLE' => ( !$this->url_id ) ? true : false,
            
            // Form values
            'RAID_ATTENDEES' => $this->raid['raid_attendees'],
            'RAID_VALUE'     => ( is_numeric($raid_value) ) ? number_format($raid_value, 2) : '',
            'RAID_NOTE'      => sanitize($this->raid['raid_note'], ENT),
            'MO'             => date('m', $this->time),
            'D'              => date('d', $this->time),
            'Y'              => date('Y', $this->time),
            'H'              => date('H', $this->time),
            'MI'             => date('i', $this->time),
            'S'              => date('s', $this->time),
            
            // Language
            'L_ADD_RAID_TITLE'        => $user->lang['addraid_title'],
            'L_ATTENDEES'             => $user->lang['attendees'],
            'L_PARSE_LOG'             => $user->lang['parse_log'],
            'L_SEARCH_MEMBERS'        => $user->lang['search_members'],
            'L_EVENT'                 => $user->lang['event'],
            'L_ADD_EVENT'             => strtolower($user->lang['add_event']),
            'L_VALUE'                 => $user->lang['value'],
            'L_ADDRAID_VALUE_NOTE'    => $user->lang['addraid_value_note'],
            'L_DATE'                  => $user->lang['date'],
            'L_TIME'                  => $user->lang['time'],
            'L_ADDRAID_DATETIME_NOTE' => $user->lang['addraid_datetime_note'],
            'L_NOTE'                  => $user->lang['note'],
            'L_ADD_RAID'              => $user->lang['add_raid'],
            'L_RESET'                 => $user->lang['reset'],
            'L_UPDATE_RAID'           => $user->lang['update_raid'],
            'L_DELETE_RAID'           => $user->lang['delete_raid'],
            
            // Form validation
            'FV_ATTENDEES'  => $this->fv->generate_error('raid_attendees'),
            'FV_EVENT_NAME' => $this->fv->generate_error('raid_name'),
            'FV_VALUE'      => $this->fv->generate_error('raid_value'),
            'FV_MO'         => $this->fv->generate_error('mo'),
            'FV_D'          => $this->fv->generate_error('d'),
            'FV_Y'          => $this->fv->generate_error('y'),
            'FV_H'          => $this->fv->generate_error('h'),
            'FV_MI'         => $this->fv->generate_error('mi'),
            'FV_S'          => $this->fv->generate_error('s'),
            
            // Javascript messages
            'MSG_ATTENDEES_EMPTY' => $user->lang['fv_required_attendees'],
            'MSG_NAME_EMPTY'      => $user->lang['fv_required_event_name'],
            // The file is now always parse_log.php, this provides legacy support in case someone doesn't update their HTML files
            'MSG_GAME_NAME'       => 'log',
            
            // Buttons
            'S_ADD' => ( !$this->url_id ) ? true : false)
        );
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['addraid_title']),
            'template_file' => 'admin/addraid.html',
            'display'       => true
        ));
    }
}

$add_raid = new Add_Raid;
$add_raid->process();