<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        mm_addalternates.php
 * Began:       Thu Jan 30 2003
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2006-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 516 $
 */

// This script handles adding, updating or deleting an alternate member.

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

class MM_AddAlternates extends EQdkp_Admin
{

    var $alternates     = array();      // Holds member data if URI_NAME is set             @var alternates
    var $old_alterantes = array();      // Holds member data from before POST               @var old_alternates

    function MM_AddAlternates()
    {
        global $db, $user, $in;

        parent::eqdkp_admin();

        $this->alternates = array(
            'member_id'  => $in->get('member_id', 'int'),
            'alternates' => $in->getArray('alternates', 'int')
        );

        $this->assoc_buttons(array(
            'proces' => array(
                'name'    => 'process',
                'process' => 'process_addalternates',
                'check'   => 'a_members_man'),
            'form' => array(
                'name'    => 'display_form',
                'process' => 'display_form',
                'check'   => 'a_members_man'),
        ));

    }

   function error_check()
    {
        global $db, $user, $in;

        if ( !$in->exists('member_id') )
        {
            $this->fv->errors['ca_missing_main'] = $user->lang['fv_missing_main'];
        }
        if ( !$in->exists('alternates') )
        {
            $this->fv->errors['ca_missing_alt']  = $user->lang['fv_missing_alt'];
        }
        if (array_search($in->get('member_id', 'int'), $in->getArray('alternates', 'int')) !== FALSE) {
            $this->fv->errors['ca_main_alt_same'] = $user->lang['fv_main_alt_same'];
        }
        $this->alternates = array(
            'member_id' => $in->get('member_id','int'),
            'alternates' => $in->getArray('alternates','int')
        );

        return $this->fv->is_error();
    }

    // ---------------------------------------------------------
    // Process add alternates
    // ---------------------------------------------------------
    function process_addalternates()
    {
        global $db, $eqdkp, $user, $tpl, $pm;

        // Create an SQL IN clause with all alt ids
        foreach ($this->alternates['alternates'] as $alt) {
            $this->alternates['alternates'][$alt] = $db->escape($alt);
        }
        $alt_list_sql_in = "(".implode(",",$this->alternates['alternates']).")";

        //
        // Update each member_main_id with the new member_id
        //
        $query = $db->build_query('UPDATE', array(
            'member_main_id'      => $this->alternates['member_id'])
        );
        $db->query('UPDATE __members SET ' . $query . " WHERE `member_id` IN ".$alt_list_sql_in);

        // -----------------------
        // Get logging information
        // -----------------------

        // get main's name
        $sql =   "SELECT member_name
                    FROM __members
                   WHERE (`member_id` = '" . $db->escape($this->alternates['member_id']) . "')";
        $member_name = $db->query_first($sql);

        // get alternates' names
        $sql =   "SELECT member_name
                    FROM __members
                   WHERE (`member_id` IN " . $alt_list_sql_in . ")";

        $alt_name_result = $db->query($sql);

        $alt_name_arr = array();
        while ( $row = $db->fetch_record($alt_name_result) )
        {
            $alt_name_arr[] = $row['member_name'];
        }
        $alt_name_list = implode(", ",$alt_name_arr);
        $db->free_result($alt_name_result);

        //
        // Write the log event
        //
        $log_action = array(
            'header'        => '{L_ACTION_ALTERNATE_ADDED}',
            '{L_MEMBER}'    => $member_name,
            '{L_ALTERNATE}' => $alt_name_list);
        $this->log_insert(array(
            'log_type'   => $log_action['header'],
            'log_action' => $log_action)
        );

        $success_message = sprintf($user->lang['admin_add_alternates_success'], $alt_name_list, $member_name);
        $link_list = array(
            $user->lang['add_alternates']       => path_default('admin/manage_members.php') . path_params('mode', 'addalternates'),
            $user->lang['list_edit_del_member'] => path_default('admin/manage_members.php') . path_params('mode', 'list')
        );
        $this->admin_die($success_message, $link_list);
    }

    // ---------------------------------------------------------
    // Display form
    // ---------------------------------------------------------
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $in;
        global $gm, $pm;

        // if nothing is selected in the list of mains alternates is returned as a single value & we always want it to be an array
        if (!is_array($this->alternates['alternates'])) {
            $this->alternates['alternates'] = array($this->alternates['alternates']);
        }

        // Generate the list main characters
        $sql = "SELECT member_id, member_name
                  FROM __members
                 WHERE (`member_main_id` IS NULL)
              ORDER BY `member_name`";
        $result = $db->query($sql);

        $count_of_mains = $db->num_rows($result);
        while ( $row = $db->fetch_record($result) )
        {
            $tpl->assign_block_vars('main_member_row', array(
                'VALUE'    => $row['member_id'],
                'SELECTED' => option_selected( $this->alternates['member_id'] == $row['member_id'] ),
                'OPTION'   => $row['member_name'])
            );
        }
        $db->free_result($result);

        // Generate the list of available alternates
        // Available alternates are mains w/o alternates
        $sql = "SELECT ma.member_name, ma.member_id, ma.member_main_id
                  FROM __members ma
                 WHERE (ma.`member_main_id` IS NULL)
                   AND (ma.`member_id` NOT IN (
                          SELECT mb.member_main_id
                            FROM __members mb
                           WHERE (mb.`member_main_id` = ma.`member_id`)))
                ORDER BY ma.`member_name`";

        $result = $db->query($sql);

        while ( $row = $db->fetch_record($result) )
        {
            $tpl->assign_block_vars('available_mains_row', array(
                'VALUE'    => $row['member_id'],
                'SELECTED' => option_selected( array_search($row['member_id'],$this->alternates['alternates']) !== FALSE ),
                'OPTION'   => $row['member_name'])
            );
        }
        $db->free_result($result);

        $tpl->assign_vars(array(
            // Form vars
            'F_ADD_ALTERNATES'              => path_default('admin/manage_members.php') . path_params('mode', 'addalternates'),

            // Language
            'L_ADD_ALTERNATES'              => $user->lang['add_alternates'],
            'L_ADD_ALTERNATE_DESCRIPTION'   => $user->lang['add_alternate_description'],
            'L_MEMBER'                      => $user->lang['member'],
            'L_POSSIBLE_ALTERNATES'         => $user->lang['possible_alternates'],
            'L_SELECT_1_OF_X_MAINS'         => sprintf($user->lang['select_1ofx_mains'], $count_of_mains),

            // Form validation
            'FV_ADD_ALTS_MISSING_MAIN'      => $this->fv->generate_error('ca_missing_main'),
            'FV_ADD_ALTS_MISSING_ALT'       => $this->fv->generate_error('ca_missing_alt'),
            'FV_ADD_ALTS_MAIN_ALT_SAME'     => $this->fv->generate_error('ca_main_alt_same')
        ));

        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['manage_members_title']),
            'template_file' => 'admin/mm_addalternates.html',
            'display'       => true
        ));
    }
}