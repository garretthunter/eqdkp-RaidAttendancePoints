<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        viewmember.php
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

$user->check_auth('u_member_view');

if ( $in->get(URI_NAME) != '' )
{
    $sort_order = array(
        0 => array('raid_name', 'raid_name desc'),
        1 => array('raid_count desc', 'raid_count')
    );

    $current_order = switch_order($sort_order);

//gehAVATARS
    $sql = "SELECT member_id, member_name, member_earned, member_spent, member_adjustment,
               (member_earned-member_spent+member_adjustment) AS member_current,
                member_firstraid, member_lastraid
			    ,member_level, member_main_id, class_name AS member_class, race_name AS member_race, member_gender
            FROM __members,
                 __classes,
                 __races
            WHERE (`member_name` = " . $db->sql_escape(unsanitize($in->get(URI_NAME))) . ")
              AND member_class_id = class_id
              AND member_race_id = race_id";
//geh
    if ( !($member_result = $db->query($sql)) )
    {
        message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
    }

    // Make sure they provided a valid member name
    if ( !$member = $db->fetch_record($member_result) )
    {
        message_die($user->lang['error_invalid_name_provided']);
    }

    // Find the percent of raids they've attended in the last 30, 60 and 90 days
    $percent_of_raids = array(
        '30'       => raid_count(strtotime(date('Y-m-d', time() - 60*60*24*30)), time(), $member['member_name']),
        '60'       => raid_count(strtotime(date('Y-m-d', time() - 60*60*24*60)), time(), $member['member_name']),
        '90'       => raid_count(strtotime(date('Y-m-d', time() - 60*60*24*90)), time(), $member['member_name']),
        'lifetime' => raid_count($member['member_firstraid'], $member['member_lastraid'], $member['member_name'])
    );
//gehALTERNATES
    //
    // Alternates
    //
    if ($member['member_main_id'] == '') {
        $member_main_id = $member['member_id'];
    } else {
        $member_main_id = $member['member_main_id'];
    }
    // Get the member info for each alternate associated with this main
    $sql = 'SELECT member_id, member_name, member_main_id
              FROM __members
             WHERE member_main_id = '.$member_main_id.'
                OR member_id = '.$member_main_id."
          ORDER BY member_name";

    if ( !($member_and_alt_result = $db->query($sql)) )
    {
        message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
    }

    if ($db->num_rows($member_and_alt_result) > 1) {
        $tpl->assign_vars(array(
            'S_HAS_ALTS' => true,
        ));

        while ( $member_members = $db->fetch_record($member_and_alt_result) )
        {
            $tpl->assign_block_vars('member_members_row', array(
                'MEMBER_NAME'    => $member_members['member_name'],
                'MEMBER_ID'      => $member_members['member_id'],
                'MEMBER_MAIN_ID' => $member_members['member_main_id'],
                'ALT_MENU_CLASS' => ( $member_members['member_id'] == $member['member_id'] ) ? "background-color: #333333": "",
                'SELECTED'       => ( !strcmp($member_members['member_name'],$_GET[URI_NAME]) ) ? 'selected="selected"' : ""

            ));
        }
        $db->free_result($member_and_alt_result);
    } else {
	    $tpl->assign_vars(array(
            'S_HAS_ALTS' => false,
        ));
	}
    $db->free_result($member_result);
//gehEND
/*
  TODO - Selecting an ALT does not bring me to the mains page
*/
    //
    // Raid Attendance
    //
    $rstart = $in->get('rstart', 0);

    // Find $current_earned based on the page.  This prevents us having to pass the
    // current earned as a GET variable which could result in user error
    if ( $rstart == 0 )
    {
        $current_earned = $member['member_earned'];
    }
    else
    {
        $current_earned = $member['member_earned'];
        $sql = "SELECT raid_value
                FROM __raids AS r, __raid_attendees AS ra
                WHERE (ra.raid_id = r.raid_id)
                AND (ra.`member_name` = " . $db->sql_escape($member['member_name']) . ")
                ORDER BY r.raid_date DESC
                LIMIT {$rstart}";
        if ( !($earned_result = $db->query($sql)) )
        {
            message_die('Could not obtain raid information', '', __FILE__, __LINE__, $sql);
        }
        while ( $ce_row = $db->fetch_record($earned_result) )
        {
            $current_earned -= $ce_row['raid_value'];
        }
        $db->free_result($earned_result);
    }

    $sql = "SELECT r.raid_id, r.raid_name, r.raid_date, r.raid_note, r.raid_value
            FROM __raids AS r, __raid_attendees AS ra
            WHERE (ra.raid_id = r.raid_id)
            AND (ra.`member_name` = " . $db->sql_escape($member['member_name']) . ")
            ORDER BY r.raid_date DESC
            LIMIT {$rstart},{$user->data['user_rlimit']}";
    if ( !($raids_result = $db->query($sql)) )
    {
        message_die('Could not obtain raid information', '', __FILE__, __LINE__, $sql);
    }
    while ( $raid = $db->fetch_record($raids_result) )
    {
        $tpl->assign_block_vars('raids_row', array(
            'ROW_CLASS'      => $eqdkp->switch_row_class(),
            'DATE'           => ( !empty($raid['raid_date']) ) ? date($user->style['date_notime_short'], $raid['raid_date']) : '&nbsp;',
            'U_VIEW_RAID'    => raid_path($raid['raid_id']),
            'NAME'           => ( !empty($raid['raid_name']) ) ? sanitize($raid['raid_name']) : '&lt;<i>Not Found</i>&gt;',
            'NOTE'           => ( !empty($raid['raid_note']) ) ? sanitize($raid['raid_note']) : '&nbsp;',
            'EARNED'         => number_format($raid['raid_value'], 2),
            'CURRENT_EARNED' => number_format($current_earned, 2)
        ));
        $current_earned -= $raid['raid_value'];
    }
    $db->free_result($raids_result);
    $sql = "SELECT COUNT(*)
            FROM __raids AS r, __raid_attendees AS ra
            WHERE (ra.raid_id = r.raid_id)
            AND (ra.`member_name` = " . $db->sql_escape($member['member_name']) . ")";
    $total_attended_raids = $db->query_first($sql);

    //
    // Item Purchase History
    //
    $istart = $in->get('istart', 0);

    if ( $istart == 0 )
    {
        $current_spent = $member['member_spent'];
    }
    else
    {
        $current_spent = $member['member_spent'];
        $sql = "SELECT item_value
                FROM __items
                WHERE (`item_buyer` = " . $db->sql_escape($member['member_name']) . ")
                ORDER BY item_date DESC
                LIMIT {$istart}";
        if ( !($spent_result = $db->query($sql)) )
        {
            message_die('Could not obtain item information', '', __FILE__, __LINE__, $sql);
        }
        while ( $cs_row = $db->fetch_record($spent_result) )
        {
            $current_spent -= $cs_row['item_value'];
        }
        $db->free_result($spent_result);
    }

    $sql = "SELECT i.item_id, i.item_name, i.item_value, i.item_date, i.raid_id, r.raid_name, item_ctrt_wowitemid
            FROM __items AS i LEFT JOIN __raids AS r ON r.raid_id = i.raid_id
            WHERE (i.`item_buyer` = " . $db->sql_escape($member['member_name']) . ")
            ORDER BY i.item_date DESC
            LIMIT {$istart},{$user->data['user_ilimit']}";
    if ( !($items_result = $db->query($sql)) )
    {
        message_die('Could not obtain item information', 'Database error', __FILE__, __LINE__, $sql);
    }
//gehITEM_DECORATION
	$items = array();
    while ( $item = $db->fetch_record($items_result) )
    {
		$items[] = $item;
		$item_ids[] = $item['item_id'];
	}
	$db->free_result($items_result);

    $sql = "SELECT * FROM __game_items";
	if (!empty($items)) {
		$sql .= " WHERE item_id IN (". implode(",",$item_ids).")" ;
	}
    if ( !($game_items_result = $db->query($sql)) )
    {
        message_die('Could not obtain game item information', 'Database error', __FILE__, __LINE__, $sql);
    }
    $game_items = array();
    while ( $game_item = $db->fetch_record($game_items_result) )
    {
		$game_items[$game_item['item_id']] = $game_item;
    }
    foreach( $items as $item)
    {
        $tpl->assign_block_vars('items_row', array(
            'ROW_CLASS'     => $eqdkp->switch_row_class(),
            'DATE'          => ( !empty($item['item_date']) ) ? date($user->style['date_notime_short'], $item['item_date']) : '&nbsp;',
            'U_VIEW_ITEM'   => item_path($item['item_id']),
            'U_VIEW_RAID'   => raid_path($item['raid_id']),
	        'NAME'          => sanitize($item['item_name']),
	        'RAID'          => ( !empty($item['raid_name']) ) ? sanitize($item['raid_name']) : '&lt;<i>Not Found</i>&gt;',
	        'SPENT'         => number_format($item['item_value'], 2),
	        'CURRENT_SPENT' => number_format($current_spent, 2)
//gehITEM_DECORATIONS
		   ,'GAME_ID'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_id'] : $item['item_ctrt_wowitemid'],
			'QUALITY'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_quality'] : 0,
			'ICON'	       => ( !empty($game_items[$item['item_id']]) ) ? strtolower($game_items[$item['item_id']]['game_item_icon']) : 'inv_misc_questionmark'
//geh	    
		));
	    $current_spent -= $item['item_value'];
	}

    $total_purchased_items = $db->query_first("SELECT COUNT(*) FROM __items WHERE (`item_buyer` = " . $db->sql_escape($member['member_name']) . ") ORDER BY item_date DESC");

    //
    // Adjustment History
    //
    $adjustment_addon = ( $member['member_firstraid'] > 0 ) ? "OR (member_name IS NULL AND adjustment_date >= {$member['member_firstraid']})" : '';
//gehRAIDGROUPS
//    $sql = "SELECT adjustment_value, adjustment_date, adjustment_reason, member_name
//            FROM __adjustments
//            WHERE (`member_name` = " . $db->sql_escape($member['member_name']) . ")
//            {$adjustment_addon}
//            ORDER BY adjustment_date DESC";
    $sql = "SELECT adjustment_value, adjustment_date, adjustment_reason, member_name
			,adjustment_event
            FROM __adjustments
            WHERE (`member_name` = " . $db->sql_escape($member['member_name']) . ")
            {$adjustment_addon}
            ORDER BY adjustment_date DESC";
//gehEND
    if ( !($adjustments_result = $db->query($sql)) )
    {
        message_die('Could not obtain adjustment information', '', __FILE__, __LINE__, $sql);
    }
    while ( $adjustment = $db->fetch_record($adjustments_result) )
    {
        $reason = ( is_null($adjustment['member_name']) ) ? $user->lang['group_adjustments'] : sanitize($adjustment['adjustment_reason']);
        
        $tpl->assign_block_vars('adjustments_row', array(
            'ROW_CLASS'               => $eqdkp->switch_row_class(),
            'DATE'                    => ( !empty($adjustment['adjustment_date']) ) ? date($user->style['date_notime_short'], $adjustment['adjustment_date']) : '&nbsp;',
            'REASON'                  => $reason,
            'C_INDIVIDUAL_ADJUSTMENT' => color_item($adjustment['adjustment_value']),
            'INDIVIDUAL_ADJUSTMENT'   => sanitize($adjustment['adjustment_value'])
        ));
    }
    $total_adjustments = $db->num_rows($adjustments_result);

    //
    // Attendance by Event
    //
    $raid_counts = array();

    // Find the count for each event for this member
    $sql = "SELECT e.event_id, r.raid_name, COUNT(ra.raid_id) AS raid_count
            FROM __events AS e, __raid_attendees AS ra, __raids AS r
            WHERE (e.event_name = r.raid_name)
            AND (r.raid_id = ra.raid_id)
            AND (ra.`member_name` = " . $db->sql_escape($member['member_name']) . ")
            AND (r.`raid_date` >= {$member['member_firstraid']})
            GROUP BY ra.member_name, r.raid_name";
    $result = $db->query($sql);
    while ( $row = $db->fetch_record($result) )
    {
        // The count now becomes the percent
        $raid_counts[ $row['raid_name'] ] = $row['raid_count'];
        $event_ids[ $row['raid_name'] ] = $row['event_id'];
    }
    $db->free_result($result);

    // Find the count for reach raid
    $sql = "SELECT raid_name, COUNT(raid_id) AS raid_count
            FROM __raids
            WHERE (`raid_date` >= {$member['member_firstraid']})
            GROUP BY raid_name";
    $result = $db->query($sql);
    while ( $row = $db->fetch_record($result) )
    {
        if ( isset($raid_counts[$row['raid_name']]) )
        {
            $percent = round(($raid_counts[ $row['raid_name'] ] / $row['raid_count']) * 100);
            $raid_counts[$row['raid_name']] = array('percent' => $percent, 'count' => $raid_counts[ $row['raid_name'] ]);

            unset($percent);
        }
    }
    $db->free_result($result);

    // Since we can't sort in SQL for this case, we have to sort
    // by the array
    switch ( $current_order['sql'] )
    {
        // Sort by key
        case 'raid_name':
            ksort($raid_counts);
            break;
        case 'raid_name desc':
            krsort($raid_counts);
            break;

        // Sort by value (keeping relational keys in-tact)
        case 'raid_count':
            asort($raid_counts);
            break;
        case 'raid_count desc':
            arsort($raid_counts);
            break;
    }
    reset($raid_counts);
    foreach ( $raid_counts as $event => $data )
    {
        $tpl->assign_block_vars('event_row', array(
            'EVENT'        => sanitize($event),
            'U_VIEW_EVENT' => event_path($event_ids[$event]),
            'BAR'          => create_bar($data['percent'], $data['count'] . ' (' . $data['percent'] . '%)')
        ));
    }
    unset($raid_counts, $event_ids);

//gehALTERNATES - Save portrait information
    $avatar_icons = $gm->get_avatar_icons ($member['member_race'],$member['member_class'],$member['member_gender'],$member['member_level']);
	$eqdkp->extra_css = "
#avatar {
	width: 120px;
	padding: 20px 0px;
	left: 0px;
}
#iconpanel {
	left:10px;
	position: absolute;
}

#portraitborder {
	position: absolute;
	left:-9px;
	top:-9px; 
	width: 82px;
	height: 83px;
}
#portrait {
	position:relative; 
	left:40px; 
	width: 64px; 
	height: 64px;
}
.portrait-player-level {
	position:absolute; 
	right:9px; 
	top:61px;
	color:#FFFF00;
	font-weight:bold;
	font-size:x-small;
}";
//gehEND

    $tpl->assign_vars(array(
        'GUILDTAG' => sanitize($eqdkp->config['guildtag']),
        'NAME'     => sanitize($member['member_name']),
//gehALTERNATES
        'F_MEMBERS'      => member_path(),

        'I_PORTRAIT'     => $avatar_icons['portrait'],
        'I_RACE'         => $avatar_icons['race'],
        'I_CLASS'        => $avatar_icons['class'],
        'I_DIR'          => $avatar_icons['icon_dir'],

        'RACE'           => $member['member_race'],
        'CLASS'          => $member['member_class'],
        'LEVEL'          => $member['member_level'],
        'GENDER'         => $member['member_gender'],

        'L_ALTERNATES'   => $user->lang['alternate_list'],
        'L_MEMBER_DETAILS' => $user->lang['member_details'],

		'WOW_ARMORY_IMAGES'		=> WOW_ARMORY_IMAGES,
//gehEND

        'L_EARNED'                        => $user->lang['earned'],
        'L_SPENT'                         => $user->lang['spent'],
        'L_ADJUSTMENT'                    => $user->lang['adjustment'],
        'L_CURRENT'                       => $user->lang['current'],
        'L_RAIDS_30_DAYS'                 => sprintf($user->lang['raids_x_days'], 30),
        'L_RAIDS_60_DAYS'                 => sprintf($user->lang['raids_x_days'], 60),
        'L_RAIDS_90_DAYS'                 => sprintf($user->lang['raids_x_days'], 90),
        'L_RAIDS_LIFETIME'                => sprintf($user->lang['raids_lifetime'],
                                                date($user->style['date_notime_short'], $member['member_firstraid']),
                                                date($user->style['date_notime_short'], $member['member_lastraid'])),
        'L_RAID_ATTENDANCE_HISTORY'       => $user->lang['raid_attendance_history'],
        'L_DATE'                          => $user->lang['date'],
        'L_NAME'                          => $user->lang['name'],
        'L_NOTE'                          => $user->lang['note'],
        'L_EARNED'                        => $user->lang['earned'],
        'L_CURRENT'                       => $user->lang['current'],
        'L_ITEM_PURCHASE_HISTORY'         => $user->lang['item_purchase_history'],
        'L_RAID'                          => $user->lang['raid'],
        'L_INDIVIDUAL_ADJUSTMENT_HISTORY' => $user->lang['individual_adjustment_history'],
        'L_REASON'                        => $user->lang['reason'],
        'L_ADJUSTMENT'                    => $user->lang['adjustment'],
        'L_ATTENDANCE_BY_EVENT'           => $user->lang['attendance_by_event'],
        'L_EVENT'                         => $user->lang['event'],
        'L_PERCENT'                       => $user->lang['percent'],

        'O_EVENT'   => $current_order['uri'][0],
        'O_PERCENT' => $current_order['uri'][1],

        'EARNED'         => number_format($member['member_earned'], 2),
        'SPENT'          => number_format($member['member_spent'], 2),
        'ADJUSTMENT'     => number_format($member['member_adjustment'], 2),
        'CURRENT'        => number_format($member['member_current'], 2),
        'RAIDS_30_DAYS'  => sprintf($user->lang['of_raids'], $percent_of_raids['30']),
        'RAIDS_60_DAYS'  => sprintf($user->lang['of_raids'], $percent_of_raids['60']),
        'RAIDS_90_DAYS'  => sprintf($user->lang['of_raids'], $percent_of_raids['90']),
        'RAIDS_LIFETIME' => sprintf($user->lang['of_raids'], $percent_of_raids['lifetime']),

        'C_ADJUSTMENT'     => color_item($member['member_adjustment']),
        'C_CURRENT'        => color_item($member['member_current']),
        'C_RAIDS_30_DAYS'  => color_item($percent_of_raids['30'], true),
        'C_RAIDS_60_DAYS'  => color_item($percent_of_raids['60'], true),
        'C_RAIDS_90_DAYS'  => color_item($percent_of_raids['90'], true),
        'C_RAIDS_LIFETIME' => color_item($percent_of_raids['lifetime'], true),

        'RAID_FOOTCOUNT'       => sprintf($user->lang['viewmember_raid_footcount'], $total_attended_raids, $user->data['user_rlimit']),
        'ITEM_FOOTCOUNT'       => sprintf($user->lang['viewmember_item_footcount'], $total_purchased_items, $user->data['user_ilimit']),
        'ADJUSTMENT_FOOTCOUNT' => sprintf($user->lang['viewmember_adjustment_footcount'], $total_adjustments),
        'RAID_PAGINATION'      => generate_pagination(member_path($member['member_name']) . path_params('istart', $istart), $total_attended_raids, $user->data['user_rlimit'], $rstart, 'rstart'),
        'ITEM_PAGINATION'      => generate_pagination(member_path($member['member_name']) . path_params('rstart', $rstart), $total_purchased_items, $user->data['user_ilimit'], $istart, 'istart'),

        'U_VIEW_MEMBER' => member_path($member['member_name']) . '&amp;'
    ));

    $db->free_result($adjustments_result);

    $pm->do_hooks('/viewmember.php');

    $eqdkp->set_vars(array(
        'page_title'    => page_title(sprintf($user->lang['viewmember_title'], $member['member_name'])),
        'template_file' => 'viewmember.html',
        'display'       => true
    ));
}
else
{
    message_die($user->lang['error_invalid_name_provided']);
}

function raid_count($start_date, $end_date, $member_name)
{
    global $db;

    $member_name = $db->sql_escape($member_name);
    $start_date  = intval($start_date);
    $end_date    = intval($end_date);

    $raid_count = $db->query_first("SELECT COUNT(*) FROM __raids WHERE (raid_date BETWEEN {$start_date} AND {$end_date})");

    $sql = "SELECT COUNT(*)
            FROM __raids AS r, __raid_attendees AS ra
            WHERE (ra.`raid_id` = r.`raid_id`)
            AND (ra.`member_name` = " . $db->sql_escape($member_name) . ")
            AND (r.`raid_date` BETWEEN {$start_date} AND {$end_date})";
    $individual_raid_count = $db->query_first($sql);

    $percent_of_raids = ( $raid_count > 0 ) ? round(($individual_raid_count / $raid_count) * 100) : 0;

    return $percent_of_raids;
}
