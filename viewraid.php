<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        viewraid.php
 * Began:       Thu Dec 19 2002
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 516 $
 */

define('EQDKP_INC', true);
$eqdkp_root_path = './';
require_once($eqdkp_root_path . 'common.php');

$user->check_auth('u_raid_view');

if ( $in->get(URI_RAID, 0) )
{
    $sql = "SELECT raid_id, raid_name, raid_date, raid_note, raid_value, 
                raid_added_by, raid_updated_by
            FROM __raids
            WHERE (`raid_id` = '" . $in->get(URI_RAID, 0) . "')";
    if ( !($raid_result = $db->query($sql)) )
    {
        message_die('Could not obtain raid information', '', __FILE__, __LINE__, $sql);
    }

    // Check for valid raid
    if ( !$raid = $db->fetch_record($raid_result) )
    {
        message_die($user->lang['error_invalid_raid_provided']);
    }
    $db->free_result($raid_result);

    //
    // Attendee and Class distribution
    //
    $attendees = array();
    $classes   = array();
    
    $sql = "SELECT ra.member_name, c.class_name AS member_class,
                CONCAT(r.rank_prefix, '%s', r.rank_suffix) AS member_sname
            FROM __raid_attendees AS ra, __members AS m
                LEFT JOIN __member_ranks AS r ON r.rank_id = m.member_rank_id
                LEFT JOIN __classes AS c ON c.class_id = m.member_class_id
            WHERE (m.member_name = ra.member_name)
            AND (`raid_id` = '{$raid['raid_id']}')
            ORDER BY member_name";
    $result = $db->query($sql);
    while ( $arow = $db->fetch_record($result) )
    {
        $attendees[] = array('name' => $arow['member_name'], 'styled' => sprintf($arow['member_sname'], sanitize($arow['member_name'])));
        $classes[ $arow['member_class'] ][] = sprintf($arow['member_sname'], sanitize($arow['member_name']));
    }
    $db->free_result($result);
    $total_attendees = sizeof($attendees);

    if ( sizeof($attendees) > 0 )
    {
        $rows = ceil(sizeof($attendees) / $user->style['attendees_columns']);

        // First loop: iterate through the rows
        // Second loop: iterate through the columns as defined in template_config,
        // then "add" an array to $block_vars that contains the column definitions,
        // then assign the block vars.
        // Prevents one column from being assigned and the rest of the columns for
        // that row being blank
        for ( $i = 0; $i < $rows; $i++ )
        {
            $block_vars = array();
            for ( $j = 0; $j < $user->style['attendees_columns']; $j++ )
            {
                $offset = ($i + ($rows * $j));
                $attendee = ( isset($attendees[$offset]) ) ? $attendees[$offset] : '';

                if ( is_array($attendee) )
                {
                    $block_vars += array(
                        'COLUMN'.$j.'_NAME' => '<a href="' . member_path($attendee['name']) . '">' . $attendee['styled'] . '</a>'
                    );
                }
                else
                {
                    $block_vars += array(
                        'COLUMN'.$j.'_NAME' => ''
                    );
                }

                // Are we showing this column?
                $s_column = 's_column'.$j;
                ${$s_column} = true;
            }
            $tpl->assign_block_vars('attendees_row', $block_vars);
        }
        $column_width = floor(100 / $user->style['attendees_columns']);
    }
    else
    {
        message_die('Could not get raid attendee information.','Critical Error');
    }

    //
    // Drops
    //
    $sql = "SELECT item_id, item_buyer, item_name, item_value, item_ctrt_wowitemid
            FROM __items
            WHERE (`raid_id` = '{$raid['raid_id']}')";
    if ( !($items_result = $db->query($sql)) )
    {
        message_die('Could not obtain item information', '', __FILE__, __LINE__, $sql);
    }
//gehITEM_DECORATION
    $sql = "SELECT * FROM __game_items";
    if ( !($game_items_result = $db->query($sql)) )
    {
        message_die('Could not obtain game item information', 'Database error', __FILE__, __LINE__, $sql);
    }
    $game_items = array();
    while ( $game_item = $db->fetch_record($game_items_result) )
    {
		$game_items[$game_item['item_id']] = $game_item;
    }
//geh
    while ( $item = $db->fetch_record($items_result) )
    {
        $tpl->assign_block_vars('items_row', array(
            'ROW_CLASS'    => $eqdkp->switch_row_class(),
            'BUYER'        => sanitize($item['item_buyer']),
	        'U_VIEW_BUYER' => member_path($item['item_buyer']),
	        'NAME'         => sanitize($item['item_name']),
	        'U_VIEW_ITEM'  => item_path($item['item_id']),
	        'VALUE'        => number_format($item['item_value'], 2)
//gehITEM_DECORATIONS
		   ,'GAME_ID'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_id'] : $item['item_ctrt_wowitemid'],
			'QUALITY'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_quality'] : 0,
			'ICON'	       => ( !empty($game_items[$item['item_id']]) ) ? strtolower($game_items[$item['item_id']]['game_item_icon']) : 'inv_misc_questionmark'
//geh	    
	    ));
    }

    //
    // Class distribution
    //
    ksort($classes);
    foreach ( $classes as $class => $members )
        {
        // NOTE: We're potentially calling count() multiple times on the same class type, but it shouldn't be much overhead
        $class_count = count($classes[$class]);
        $percentage =  ( $total_attendees > 0 ) ? round(($class_count / $total_attendees) * 100) : 0;

        $tpl->assign_block_vars('class_row', array(
            'ROW_CLASS' => $eqdkp->switch_row_class(),
            'CLASS'     => sanitize($class),
            'BAR'       => create_bar($percentage),
            'ATTENDEES' => implode(', ', $members) // Each member name has already been sanitize()'d
        ));
    }
    unset($classes);

    $tpl->assign_vars(array(
        'L_MEMBERS_PRESENT_AT' => sprintf($user->lang['members_present_at'], sanitize($raid['raid_name']),
                                  date($user->style['date_notime_long'], $raid['raid_date'])),
        'L_ADDED_BY'           => $user->lang['added_by'],
        'L_UPDATED_BY'         => $user->lang['updated_by'],
        'L_NOTE'               => $user->lang['note'],
        'L_VALUE'              => $user->lang['value'],
        'L_DROPS'              => $user->lang['drops'],
        'L_BUYER'              => $user->lang['buyer'],
        'L_ITEM'               => $user->lang['item'],
        'L_SPENT'              => $user->lang['spent'],
        'L_ATTENDEES'          => $user->lang['attendees'],
        'L_CLASS_DISTRIBUTION' => $user->lang['class_distribution'],
        'L_CLASS'              => $user->lang['class'],
        'L_PERCENT'            => $user->lang['percent'],
        'L_RANK_DISTRIBUTION'  => $user->lang['rank_distribution'],
        'L_RANK'               => $user->lang['rank'],

        'S_COLUMN0' => isset($s_column0),
        'S_COLUMN1' => isset($s_column1),
        'S_COLUMN2' => isset($s_column2),
        'S_COLUMN3' => isset($s_column3),
        'S_COLUMN4' => isset($s_column4),
        'S_COLUMN5' => isset($s_column5),
        'S_COLUMN6' => isset($s_column6),
        'S_COLUMN7' => isset($s_column7),
        'S_COLUMN8' => isset($s_column8),
        'S_COLUMN9' => isset($s_column9),

        'COLUMN_WIDTH' => ( isset($column_width) ) ? $column_width : 0,
        'COLSPAN'      => $user->style['attendees_columns'],

        'RAID_ADDED_BY'       => ( !empty($raid['raid_added_by']) ) ? sanitize($raid['raid_added_by']) : 'N/A',
        'RAID_UPDATED_BY'     => ( !empty($raid['raid_updated_by']) ) ? sanitize($raid['raid_updated_by']) : 'N/A',
        'RAID_NOTE'           => ( !empty($raid['raid_note']) ) ? sanitize($raid['raid_note']) : '&nbsp;',
        'DKP_NAME'            => $eqdkp->config['dkp_name'],
        'RAID_VALUE'          => number_format($raid['raid_value'], 2),
        'ATTENDEES_FOOTCOUNT' => sprintf($user->lang['viewraid_attendees_footcount'], sizeof($attendees)),
        'ITEM_FOOTCOUNT'      => sprintf($user->lang['viewraid_drops_footcount'], $db->num_rows($items_result))
    ));

    $eqdkp->set_vars(array(
        'page_title'    => page_title($user->lang['viewraid_title']),
        'template_file' => 'viewraid.html',
        'display'       => true
    ));
}
else
{
    message_die($user->lang['error_invalid_raid_provided']);
}
