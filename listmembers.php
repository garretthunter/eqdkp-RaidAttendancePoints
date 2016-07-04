<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        listmembers.php
 * Began:       Wed Dec 18 2002
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

$user->check_auth('u_member_list');

//gehRAID_GROUPS
//$sort_order = array(
//    0 => array('member_name', 'member_name desc'),
//    1 => array('member_earned desc', 'member_earned'),
//    2 => array('member_spent desc', 'member_spent'),
//    3 => array('member_adjustment desc', 'member_adjustment'),
//    4 => array('member_current desc', 'member_current'),
//    5 => array('member_lastraid desc', 'member_lastraid'),
//    6 => array('member_level desc', 'member_level'),
//    7 => array('member_class', 'member_class desc'),
//    8 => array('rank_name', 'rank_name desc'),
//    9 => array('armor_type_id', 'armor_type_id desc')
//);
/**
 * ORDER MATTERS - the index values MUST be the same as the link
 */
$sort_order = array(
    0 => array('member_name asc', 'member_name desc'),
    1 => array('member_class asc', 'member_class desc'),
    2 => array('member_level asc', 'member_level desc'),
);
$footer_colspan = 9;
//gehEND

$current_order = switch_order($sort_order);

//gehLEADER_BOARD
if ( $in->exists('raidgroup_id')) { // means we are requesting to change the leader board raidgroup
    $raidgroup_id = $in->get('raidgroup_id');
} else {
    $raidgroup_id = -1;
}
//gehEND

//gehALTERNATES
/**
 * Are we showing alternates?
 */
 //gehTODO - no longer checking the $GET in the else means i no longer can tell when the show all link was clicked. need to add new var?
if ($in->exists('show_alternates')) { // means we are requesting to change the state of show_alternates
    $show_alternates = ($in->get('show_alternates') ? false : true);
} else { // means a link was clicked within the page and we are preserving state
    $show_alternates = $in->get('show_alternates');
}
//gehALTERNATES
//
// Compare members
//
// TODO: if-else causes two different pages to be rendered. Split into separate files.
if ( $in->get('submit') == $user->lang['compare_members'] && $in->get('compare_ids', false) )
{
//gehALTERNATES
    redirect(member_path() . path_params('compare', implode(',', $in->getArray('compare_ids', 'int'))) . path_params('show_alternates', $in->get('show_alternates')));
//gehEND
}
//gehREMOVED - redundant
/*
elseif ( $in->get('compare', false) )
{
    $s_compare = true;
    $uri_addon = '';

    $compare = validateCompareInput($in->get('compare'));

    // Find 30 days ago, then find how many raids occurred in those 30 days, and 90 days
    $thirty_days = strtotime(date("Y-m-d", time() - 60 * 60 * 24 * 30));
    $ninety_days = strtotime(date("Y-m-d", time() - 60 * 60 * 24 * 90));

    $time = time();
    $raid_count_30 = $db->query_first("SELECT COUNT(*) FROM __raids WHERE (`raid_date` BETWEEN {$thirty_days} AND {$time})");
    $raid_count_90 = $db->query_first("SELECT COUNT(*) FROM __raids WHERE (`raid_date` BETWEEN {$ninety_days} AND {$time})");

    // Build an SQL query that includes each of the compare IDs
    $sql = "SELECT *, (member_earned-member_spent+member_adjustment) AS member_current,
                c.class_name AS member_class
            FROM __members AS m, __classes AS c
            WHERE (m.member_class_id = c.class_id)
            AND (member_id IN ({$compare}))
            ORDER BY {$current_order['sql']}";
    $result = $db->query($sql);

    // Output each row
    while ( $row = $db->fetch_record($result) )
    {
        $individual_raid_count_30 = 0;
        $individual_raid_count_90 = 0;

        $rc_sql = "SELECT COUNT(*)
                   FROM __raids AS r, __raid_attendees AS ra
                   WHERE (ra.raid_id = r.raid_id)
                   AND (ra.`member_name` = '" . $db->escape($row['member_name']) . "')
                   AND (r.raid_date BETWEEN {$thirty_days} AND {$time})";
        $individual_raid_count_30 = $db->query_first($rc_sql);

        $rc_sql = "SELECT COUNT(*)
                   FROM __raids AS r, __raid_attendees AS ra
                   WHERE (ra.raid_id = r.raid_id)
                   AND (ra.`member_name` = '" . $db->escape($row['member_name']) . "')
                   AND (r.raid_date BETWEEN {$ninety_days} AND {$time})";
        $individual_raid_count_90 = $db->query_first($rc_sql);

        // Prevent division by 0
        $percent_of_raids_30 = ( $raid_count_30 > 0 ) ? round(($individual_raid_count_30 / $raid_count_30) * 100) : 0;
        $percent_of_raids_90 = ( $raid_count_90 > 0 ) ? round(($individual_raid_count_90 / $raid_count_90) * 100) : 0;

        // If the member's spent is greater than 0, see how long ago they looted an item
        if ( $row['member_spent'] > 0 )
        {
            $ll_sql = "SELECT max(item_date) AS last_loot
                       FROM __items
                       WHERE (`item_buyer` = '" . $db->escape($row['member_name']) . "')";
            $last_loot = $db->query_first($ll_sql);
        }

        $tpl->assign_block_vars('members_row', array(
            'ROW_CLASS'       => $eqdkp->switch_row_class(),
            'ID'              => $row['member_id'],
            'NAME'            => sanitize($row['member_name']),
            'LEVEL'           => ( $row['member_level'] > 0 ) ? intval($row['member_level']) : '&nbsp;',
            'CLASS'           => ( !empty($row['member_class']) ) ? sanitize($row['member_class']) : '&nbsp;',
            'EARNED'          => number_format($row['member_earned'], 2),
            'SPENT'           => number_format($row['member_spent'], 2),
            'ADJUSTMENT'      => number_format($row['member_adjustment'], 2),
            'CURRENT'         => number_format($row['member_current'], 2),
            'LASTRAID'        => ( !empty($row['member_lastraid']) ) ? date($user->style['date_notime_short'], $row['member_lastraid']) : '&nbsp;',
            'LASTLOOT'        => ( isset($last_loot) ) ? date($user->style['date_notime_short'], $last_loot) : '&nbsp;',
            'RAIDS_30_DAYS'   => sprintf($user->lang['of_raids'], $percent_of_raids_30),
            'RAIDS_90_DAYS'   => sprintf($user->lang['of_raids'], $percent_of_raids_90),
            'C_ADJUSTMENT'    => color_item($row['member_adjustment']),
            'C_CURRENT'       => color_item($row['member_current']),
            'C_LASTRAID'      => 'neutral',
            'C_RAIDS_30_DAYS' => color_item($percent_of_raids_30, true),
            'C_RAIDS_90_DAYS' => color_item($percent_of_raids_90, true),
            'U_VIEW_MEMBER'   => member_path($row['member_name'])
        ));
        unset($last_loot);
    }
    $db->free_result($result);
    $footcount_text = $user->lang['listmembers_compare_footcount'];

    $tpl->assign_var('U_COMPARE_MEMBERS', member_path() . path_params('compare', $compare));
}
*/ //gehEND
//
// Normal member display
//
else
{
    $s_compare = false;

    $member_count = 0;
    $previous_data = '';

    // Figure out what data we're comparing from member to member
    // in order to rank them
    $sort_index = explode('.', $current_order['uri']['current']);
    $previous_source = preg_replace('/( (asc|desc))?/i', '', $sort_order[$sort_index[0]][$sort_index[1]]);

    $show_all = ( $in->get('show') == 'all' ) ? true : false;
//gehALTERNATES
    if ($in->exists('show')) {
        $show = $in->get('show');
    }
//gehALTERNATES

    // ---------------------------
    // Build filter drop-down
    // ---------------------------
    $filter = $in->get('filter');

    $filter_options = array(
        array('VALUE' => '', 'SELECTED' => '', 'OPTION' => $user->lang['none']),
    );

/* gehFILTER START
    $filter_options[] = array('VALUE' => '', 'SELECTED' => '', 'OPTION' => '---------');

    foreach ( $gm->sql_armor_types() as $armor_type )
    {
        $filter_options[] = array(
            'VALUE'    => sanitize("armor_" . $armor_type['name'], ENT),
            'SELECTED' => option_selected($filter == "armor_{$armor_type['name']}"),
            'OPTION'   => str_replace('_', ' ', $armor_type['name'])
        );
    }

    $filter_options[] = array('VALUE' => '', 'SELECTED' => '', 'OPTION' => '---------');
*/ //gehFILTER END
    foreach ( $gm->sql_classes() as $class )
    {
        $filter_options[] = array(
            'VALUE'    => sanitize($class['name'], ENT),
            'SELECTED' => option_selected($filter == $class['name']),
            'OPTION'   => $class['name']
        );
    }

/*
//gehCLASS_FILTER_RIBBON - overwrites the default filter
    // ---------------------------
    // Build class ribbon filter
    // ---------------------------
    unset($filter_options);
    $filter = $in->get('filter');
    if ( empty($filter) ) {
        $filter_by = '';
    } else {
        $input = $db->escape($in->get('filter'));
        $filter_by = " AND (`class_name` = '{$input}')";
    }

    if ( $in->exists('new_rg')) { // means we are requesting to change the state of show_alternates
        $raidgroup_id = $in->get('new_rg');
    } else {
        $raidgroup_id = -1;
    }
    foreach ( $gm->sql_classes() as $class )
    {
    	if ($class['name'] == $in->get('filter')) {
    		$classFilter = '' ;
    	} else {
    		$classFilter = path_params('filter', $class['name']);
    	}
		$filter_options[] = array(
    		'I_CLASS'   => $gm->get_class_icon($class['name']),
    		'CLASS'     => sanitize($class['name'], ENT),
    		'U_FILTER'  => member_path() . $classFilter . path_params('show_alternates', $in->get('show_alternates')) . path_params('raidgroup_id', $raidgroup_id)
	    );
	}
//gehEND
*/
    foreach ( $filter_options as $option )
    {
        $tpl->assign_block_vars('filter_row', $option);
    }

    // NOTE: Filtering by class or by armor may not be mutually exclusive actions. consider revising.
    // ---------------------------
    // Filter
    // ---------------------------
    $filter_by = '';
    if ( preg_match('/^armor_.+/', $filter) )
    {
        $input = $db->escape(str_replace('armor_', '', $in->get('filter')));
        $filter_by = " AND (`armor_type_name` = '{$input}')";
    }
    elseif ( empty($filter) )
    {
        $filter_by = '';
    }
    else
    {
        $input = $db->escape($in->get('filter'));
        $filter_by = " AND (`class_name` = '{$input}')";
    }

//gehALTERNATES - Show Alternates may be selected
    if (!$show_alternates) {
        $filter_by .= " AND member_main_id IS NULL";
    }
//gehEND
//gehCOMPARE - colapsed comare members into this IF statement
    if ( $in->get('compare') )
    {
        $s_compare = true;
        $uri_addon = "";
        $compare = validateCompareInput($_GET['compare']);
        $filter_by .= " AND m.member_id IN (".$compare.")";
    }
//gehEND
    // NOTE: We currently prevent duplicate entries for the same person, by filtering out the lowest *armor type IDs* for each member's class.
//gehMEMBER_RACE - get the member's race, split out the rank_prefix & suffix so I can tag inactive member names differently
       $sql="SELECT m.*, (m.member_earned-m.member_spent+m.member_adjustment) AS member_current,
                 m.member_status, r.rank_prefix, r.rank_suffix,
			     r.rank_name, r.rank_hide, r.rank_id,
                 c.class_name AS member_class,
                 at.armor_type_name AS armor_type, 
                 MAX(ca.armor_type_id) AS armor_type_id,
                 ca.armor_min_level AS min_level, 
                 ca.armor_max_level AS max_level,
                 race_name as member_race
             FROM __races, __members AS m, __member_ranks AS r, __classes AS c, __armor_types AS at, __class_armor AS ca
             WHERE (c.class_id = m.member_class_id)
             AND (ca.class_id = m.member_class_id)
             AND (at.armor_type_id = ca.armor_type_id)
             AND (m.member_rank_id = r.rank_id)
             AND (m.member_race_id = race_id)
             {$filter_by}";
//gehEND
    if ( $in->exists('rank') )
    {
        $sql .= " AND (r.`rank_id` = '" . $in->get('rank', 0) . "')";
    }

    // NOTE: As per the conditions of using MAX(), we need to group by something. We'll group by member ID, because it's essentially a transparent grouping.
    $sql .= " GROUP BY m.member_id";
    $sql .= " ORDER BY {$current_order['sql']}";

    if ( !($members_result = $db->query($sql)) )
    {
        message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
    }

//gehSTART RAIDGROUPS
    /**
     * Get the list of raid groupings flagged for display
     */
    $rg_sql = "SELECT *
                 FROM __raidgroups_raidgroups
				WHERE raidgroup_display = 'Y'
             ORDER BY raidgroup_display_order";
    $raidgroup_results = $db->query($rg_sql);

    $raidgroups = array();
    while ($raidgroup_array = $db->fetch_record($raidgroup_results)) {
		$raidgroups[] = $raidgroup_array;
    }
    $db->free_result($raidgroup_results);

    /**
     * Get the max index value for $sort_order so that we can append our raidgroup columns
     */
    $rg_start_sort_index = count($sort_order);  // index is supposed to start at zero & count up by 1. function switch_order() in functions.php expects this to be true
    $rg_temp_sort_index = $rg_start_sort_index; // Save our starting point

    /**
     * Load all raidgroup data into temp arrays. We will load the data into existing EQdkp arrays as we process
     */
    $rg_event_ids = array();
    $rg_sort_order = array();
    $rg_events = array();
    foreach ($raidgroups as $raidgroup) {
        /**
         * extract each event into an array and eliminate duplicates
         */
        $tmp_event_ids = unserialize($raidgroup['raidgroup_raid_ids']);
        foreach ($tmp_event_ids as $event_id) {
            $rg_event_ids[] = $event_id;
        }
        $rg_event_ids = array_unique($rg_event_ids);

        /**
         * Store our raidgroup column sort indicies for a later append to the $sort_order array
         */
        $sort_order[$rg_temp_sort_index++] = array("member_current_".$raidgroup["raidgroup_id"]." desc", "member_current_".$raidgroup["raidgroup_id"]." asc");
        $sort_order[$rg_temp_sort_index++] = array("member_attend_".$raidgroup["raidgroup_id"]." desc", "member_attend_".$raidgroup["raidgroup_id"]." asc");
    }

    /**
     * Get the event names that will be used to sum points in groups
     */
    $events_sql = "SELECT DISTINCT(event_name), event_id
                     FROM __events
                    WHERE (event_id IN ('".implode("','",$rg_event_ids)."'))";
    $events_results = $db->query($events_sql);

    while ($events = $db->fetch_record($events_results)) {
        $rg_events[$events["event_id"]] = stripslashes($events['event_name']);
    }
    $db->free_result($events_results);

    /**
     * Append the Total columns. We *always* have total colums. Do not increment the sort_index as we will use it to track
     * our raidgroup columns
     */
    $sort_order[$rg_temp_sort_index] = array("member_current_total desc", "member_current_total asc");
    $sort_order[$rg_temp_sort_index+1] = array("member_attend_total desc", "member_attend_total asc");

    $current_order = switch_order($sort_order);

    $rg_temp_sort_index = $rg_start_sort_index;
    foreach ($raidgroups as $raidgroup) {

        $tmp_event_ids = unserialize($raidgroup['raidgroup_raid_ids']);
        $tmp_event_names = array();
        foreach ($tmp_event_ids as $tmp_event_id) {
            $tmp_event_names[] = $rg_events[$tmp_event_id];
        }

        $tpl->assign_block_vars('raidgroups_row', array(
            "NAME"          => $raidgroup["raidgroup_name"],
            "DESCRIPTION"   => implode(", ",$tmp_event_names),
            "O_PCURR"       => $current_order['uri'][$rg_temp_sort_index],
            "O_AATT"        => $current_order['uri'][$rg_temp_sort_index+1]
            )
        );
        $rg_temp_sort_index = $rg_temp_sort_index + 2;
    }
    $tpl->assign_vars(array(
        'O_PTOTAL'  => $current_order['uri'][$rg_temp_sort_index],
        'O_ATOTAL'  => $current_order['uri'][$rg_temp_sort_index+1]));

/* gehREPLACED
    while ( $row = $db->fetch_record($members_result) )
    {
        // Figure out the rank search URL based on show and filter
        $u_rank_search  = member_path() . path_params('rank', $row['rank_id']);
        $u_rank_search .= ( ($eqdkp->config['hide_inactive'] == 1) && (!$show_all) ) ? '' : path_params('show', 'all');
        $u_rank_search .= ( $filter != 'none' ) ? path_params('filter', $filter) : '';

        if ( member_display($row, $show_all, $filter) )
        {
            $member_count++;

            $member_name = ( $row['member_status'] == 0 ) ? '<i>' . sanitize($row['member_name']) . '</i>' : sanitize($row['member_name']);

            $tpl->assign_block_vars('members_row', array(
                'ROW_CLASS'     => $eqdkp->switch_row_class(),
                'ID'            => $row['member_id'],
                'COUNT'         => ($row[$previous_source] == $previous_data) ? '&nbsp;' : $member_count,
                'NAME'          => sprintf($row['member_sname'], $member_name),
                'RANK'          => ( !empty($row['rank_name']) ) ? '<a href="'.$u_rank_search.'">' . sanitize($row['rank_name']) . '</a>' : '&nbsp;',
                'LEVEL'         => ( $row['member_level'] > 0 ) ? $row['member_level'] : '&nbsp;',
                'CLASS'         => ( !empty($row['member_class']) ) ? sanitize($row['member_class']) : '&nbsp;',
                'ARMOR'         => ( !empty($row['armor_type']) ) ? sanitize($row['armor_type']) : '&nbsp;',
                'EARNED'        => number_format($row['member_earned'], 2),
                'SPENT'         => number_format($row['member_spent'], 2),
                'ADJUSTMENT'    => number_format($row['member_adjustment'], 2),
                'CURRENT'       => number_format($row['member_current'], 2),
                'LASTRAID'      => ( !empty($row['member_lastraid']) ) ? date($user->style['date_notime_short'], $row['member_lastraid']) : '&nbsp;',
                'C_ADJUSTMENT'  => color_item($row['member_adjustment']),
                'C_CURRENT'     => color_item($row['member_current']),
                'C_LASTRAID'    => 'neutral',
                'U_VIEW_MEMBER' => member_path($row['member_name'])
            ));
            $u_rank_search = '';
            unset($last_loot);

            // So that we can compare this member to the next member,
            // set the value of the previous data to the source
            $previous_data = $row[$previous_source];
        }
    }
*/
//gehRAIDGROUPS
//gehDEBUG    if ($db->num_rows($members_result) > 0 ) {

        while ( $row = $db->fetch_record($members_result) )
        {
			/*
			 * If we find an Unknown class pull the current data from the WoW Armory
			 */
			if ( (!strcasecmp ($row['member_class'],'Unknown')) || ($row['member_level'] < 80) ) {
				$urlHandler = new URLHandler ();
				$url = 'http://www.wowarmory.com/character-sheet.xml?r=Bronzebeard&n='.$row['member_name'];
				//$url = 'http://www.wowarmory.com/guild-info.xml?r=Bronzebeard&n=Sleepless+Knights&p=1';
//gehDEBUG		
//echo "<a href=\"".$url."\">".$row['member_name']."</a>\n<BR>";
//gehDEBUG
				$characterXML = $urlHandler->read ($url);
//gehDEBUG
//echo "VIEW: charcterXML: ".$characterXML; //gehDEBUG
//gehDEBUG
				$doc = new DOMDocument();
				$doc->loadXML($characterXML);

				$memberList = $doc->getElementsByTagName('character');
				foreach ($memberList as $toon) {
//gehDEBUG
//$test = $toon->getAttribute('name');
//if (!strcasecmp($test,$row['member_name'])) { echo $row['member_name']."<br>\n"; }
//gehDEBUG
					$row['member_class'] = $toon->getAttribute('class');
					$row['member_gender'] = $toon->getAttribute('gender');
					$row['member_race'] = $toon->getAttribute('race');
					$row['member_level'] = $toon->getAttribute('level');
					$row['_CRAP_'] = $toon->getAttribute('name');
					break;
				}
//gehDEBUG
//print_r($row);
//gehDEBUG				
				/*
				 * We have updates so let's update the member
				 */
				$sql = "SELECT class_id from __classes where (class_name = '{$row['member_class']}')";
				$row['member_class_id'] = $db->query_first($sql);
				$sql = "SELECT race_id from __races where (race_name = '{$row['member_race']}')";
				$row['member_race_id'] = $db->query_first($sql);
				
		        $query = $db->build_query('UPDATE', array(
		            'member_gender'     => $row['member_gender'],
		            'member_race_id'    => $row['member_race_id'],
        		    'member_class_id'   => $row['member_class_id'],
        		    'member_level'   	=> $row['member_level'],
				));
//gehDEBUG
//echo "VIEW query: ".$query;
//gehDEBUG				
		        $db->query("UPDATE __members SET {$query} WHERE (`member_name` = '" . $db->escape($row['member_name']) . "')");
			}

            if ( member_display($row) )
            {
		// gehDEBUG added the rank_search here to prevent getting an error below by leaving RANK in the return set
        // Figure out the rank search URL based on show and filter
        $u_rank_search  = member_path() . path_params('rank', $row['rank_id']);
        $u_rank_search .= ( ($eqdkp->config['hide_inactive'] == 1) && (!$show_all) ) ? '' : path_params('show', 'all');
        $u_rank_search .= ( $filter != 'none' ) ? path_params('filter', $filter) : '';
			
//gehSTART ALTERNATES
// **general functionality** Alts + Mains should display the same point summary
// get all the alternates associated with this member & include their raids under the main
// UNTIL 1.4 we have to get the member_id separately for the main because we assume it's the main.

                // Get this member's name and any alts associated with it
                $alt_sql = 'SELECT member_id, member_main_id
                              FROM __members
                             WHERE (member_name = "'.$row['member_name'].'")';
                if ( !($alternates_result = $db->query($alt_sql)) ) {
                    message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
                }
                while ( $member_alternates = $db->fetch_record($alternates_result) ) {
                    $member_id = $member_alternates['member_id'];
                    $member_main_id = $member_alternates['member_main_id'];
                }
                $db->free_result($alternates_result);

                // Get the member info for each alternate associated with this main
                if ($member_main_id == '') {
                    $member_main_id = $member_id;
                }
                $alt_name_arr = array();
                $alt_sql = 'SELECT member_id, member_name
                              FROM __members
                             WHERE (member_main_id = '.$member_main_id.'
                                OR  member_id = '.$member_main_id.'
                                OR  member_id = '.$member_id.")";
                if ( !($alternates_result = $db->query($alt_sql)) ) {
                    message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
                }
                while ( $member_alternates = $db->fetch_record($alternates_result) ) {
                    $alt_name_arr[] = $member_alternates['member_name'];
                }
                $db->free_result($alternates_result);

                $in_clause = "('".implode("','",$alt_name_arr)."')";

                $points_earned_sql = "SELECT __raids.raid_name, SUM(raid_value) AS total_earned
                             FROM __raid_attendees AS ra
                        LEFT JOIN __raids ON ra.raid_id=__raids.raid_id
                            WHERE ra.member_name IN ".$in_clause."
                         GROUP by __raids.raid_name";
                $points_earned_result = $db->query($points_earned_sql); // STEP 1. GATHER ALL POINTS EARNED BY THIS MEMBER
//gehEND ALTERNATES

                /**
                 * Initialize:
                 *  - player event attendance entries (pc)
                 *  - event points (pv)
                 *  - total number of events entries (pt)
                 */
                unset($raids_attended_data);
                unset($points_earned_data);
                unset($total_raids_data);
                foreach ($rg_events as $event_id => $event_name) {
                    $points_earned_data[$event_name] = 0;
                    $raids_attended_data[$event_name] = 0;
                    $total_raids_data[$event_name] = 0;
                }
//gehALTERNATES
//        $raids_attended_sql = "SELECT raid_name FROM __raids AS r JOIN __raid_attendees AS ra ON r.raid_id = ra.raid_id WHERE ra.member_name = '".$row['member_name']."'";
				// STEP 2. GET A LIST OF RAIDS THE MEMBER AND ALTS ATTENDED
                $raids_attended_sql = "SELECT raid_name FROM __raids AS r JOIN __raid_attendees AS ra ON r.raid_id = ra.raid_id WHERE ra.member_name IN ".$in_clause;
//gehALTERNATES
                $raids_attended_result = $db->query($raids_attended_sql);
                while( $raids_attended_row = $db->fetch_record($raids_attended_result) ) {
					if (empty($raids_attended_data[$raids_attended_row['raid_name']])) {
						$raids_attended_data[$raids_attended_row['raid_name']] = 0; // gehTODO line 580 only works for raids in raid group. we are getting all raids here for some reason.
					}
                    $raids_attended_data[$raids_attended_row['raid_name']]++;
//gehTEST
                }
                $db->free_result($raids_attended_result);

                $total_raids_sql = "SELECT raid_name FROM __raids";
                $total_raids_result = $db->query($total_raids_sql);

                while( $total_raids_row = $db->fetch_record($total_raids_result) ){
					if (empty($total_raids_data[$total_raids_row['raid_name']])) {
						$total_raids_data[$total_raids_row['raid_name']] = 0;
					}
                    $total_raids_data[$total_raids_row['raid_name']]++;
                }
                $db->free_result($total_raids_result);

				// STEP 3. COMBINED THE EARNED POINTS DATA WITH ATTENDED EVENTS SO WE CAN OPERATE ON IT
                while( $points_earned_row = $db->fetch_record($points_earned_result) ){
                    $points_earned_data[$points_earned_row['raid_name']] = $points_earned_row['total_earned'];
                }
                $db->free_result($points_earned_result); 

//gehALTERNATES
//        $points_spent_sql = "SELECT _raids.raid_name, SUM(__items.item_value) FROM __items LEFT JOIN __raids ON __items.raid_id=__raids.raid_id WHERE __items.item_buyer = '".$row['member_name']."' GROUP BY __raids.raid_name;";
                $points_spent_result = $db->query("SELECT r.raid_name, SUM(__items.item_value) total_spent FROM __items LEFT JOIN __raids AS r ON __items.raid_id=r.raid_id WHERE __items.item_buyer IN ".$in_clause." GROUP BY r.raid_name;");
//gehALTERNATES

                while( $points_spent_row = $db->fetch_record($points_spent_result) ){
                    $points_earned_data[$points_spent_row['raid_name']] -= $points_spent_row['total_spent'];
                }
                $db->free_result($points_spent_result);

            // Get the sum of adjustments for this member
//gehALTERNATES
//        $point_adjs_sql = "SELECT adjustment_event, adjustment_value FROM __adjustments WHERE member_name = '".$row['member_name']."' OR member_name IS NULL;";
                $point_adjs_sql = "SELECT adjustment_event, adjustment_value FROM __adjustments WHERE member_name IN ".$in_clause." OR member_name IS NULL;";
//gehALTERNATES
                $point_adjs_result = $db->query($point_adjs_sql);

                while( $point_adjs_row = $db->fetch_record($point_adjs_result) )
                {
                    $points_earned_data[$point_adjs_row['adjustment_event']] += $point_adjs_row['adjustment_value'];
                }
                $db->free_result($point_adjs_result);

                $member_count++;
                $members_rows[$member_count] = $row;
                $members_rows[$member_count]['member_count'] = $member_count;

//gehRAIDGROUPS START
                /**
                 * Calculate raid statistics
                 */
                $rg_pv_total = 0;
                $raids_attended_total = 0;
                $total_raids_total = 0;
				
				// Take each collection of raids (raid group) and check attendance for each
                foreach($raidgroups as $raidgroup) {

                    $raid_ids = unserialize($raidgroup["raidgroup_raid_ids"]);
                    $rg_pv_subtotal = 0;
                    $raids_attended_subtotal = 0;
                    $total_raids_subtotal = 0;

                    /**
                     * Calculate each raidgroup's point totals
                     */
                    foreach($raid_ids as $raid_id) {
                        $rg_pv_subtotal += $points_earned_data[$rg_events[$raid_id]];
                    }
                    $members_rows[$member_count]["member_current_".$raidgroup["raidgroup_id"]] = round($rg_pv_subtotal, 2);
                    $rg_pv_total += $rg_pv_subtotal;

                    /**
                     * Calculate player's event attendance percentage
                     */
                    foreach($raid_ids as $raid_id) {
                        $raids_attended_subtotal += $raids_attended_data[$rg_events[$raid_id]]; // events attended
                        $total_raids_subtotal += $total_raids_data[$rg_events[$raid_id]]; // total events
                    }
                    $raids_attended_total += $raids_attended_subtotal;
                    $total_raids_total += $total_raids_subtotal;

                    if ($total_raids_subtotal > 0) {
                        $members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]] =
                                round($raids_attended_subtotal / $total_raids_subtotal * 100);
                    } else {
                        $members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]] = 0;
                    }

                    $members_rows[$member_count]["raidgroups"][] = array(
                        "CURRENT"   => $members_rows[$member_count]["member_current_".$raidgroup["raidgroup_id"]],
                        "C_CURRENT" => color_item ($members_rows[$member_count]["member_current_".$raidgroup["raidgroup_id"]]),
                        "ATTEND"    => $members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]],
                        "C_ATTEND"  => color_item ($members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]], true),
                        );

                }
//gehRAIDGROUPS END

                // The Total raidgroup
                $members_rows[$member_count]['member_current_total'] = $rg_pv_total;

                if ($total_raids_total > 0) {
                    $members_rows[$member_count]['member_attend_total'] = round($raids_attended_total / $total_raids_total * 100);
                } else {
                    $members_rows[$member_count]['member_attend_total'] = 0;
                }

                unset($last_loot);

                // So that we can compare this member to the next member,
                // set the value of the previous data to the source
                $previous_data = $row[$previous_source];
            }
        }
//gehLEADER_BOARD - build the leader board
// build the raidgroup filter
        $tpl->assign_block_vars('raidgroups_filter_row', array(
            "NAME"          => "Total",
            'ID'            => "-1",
            )
        );
        foreach ($raidgroups as $raidgroup) {
            $tpl->assign_block_vars('raidgroups_filter_row', array(
                "NAME"          => $raidgroup["raidgroup_name"],
                'ID'            => $raidgroup["raidgroup_id"],
                'SELECTED'      => ( $raidgroup['raidgroup_id'] == $raidgroup_id ) ? 'selected="selected"' : ""
                )
            );
        }

        $lb_header_row = array();
        $lb_count = 0;
        foreach ($gm->sql_classes() as $class_name) {
            $member_list = array();
            $sort_col = array();
            $i = 0;
            foreach ($members_rows as $member) {
				$class_style = $gm->get_class_style ($member['member_class']);
                if ($raidgroup_id != "-1") {
                    // leaderboard is being filtered
                    if ($member["member_class"] == $class_name['name']) {
                        $member_list[$member["member_current_".$raidgroup_id]] = array (
                            "TOTAL"     => $member["member_current_".$raidgroup_id],
                            "NAME"      => $member["member_name"],
							"CSS"		=> $class_style['css'],
                            "U_MEMBER"  => member_path($member["member_name"]),
                            "C_TOTAL"   => color_item($member["member_current_".$raidgroup_id]),
                            );
                        $sort_col[] = $member["member_current_total"];
                    }
                } else {
                    // no filter, return Totals
                    if ($member["member_class"] == $class_name['name']) {
                        $member_list[$member["member_current_total"]] = array (
                            "TOTAL"     => $member["member_current_total"],
                            "NAME"      => $member["member_name"],
							"CSS"		=> $class_style['css'],
                            "U_MEMBER"  => member_path($member["member_name"]),
                            "C_TOTAL"   => color_item($member["member_current_total"]),
                            );
                        $sort_col[] = $member["member_current_total"];
                    }
                }
            }

            /**
             * If a particular class has no members do not show
             */
            if (!empty($member_list)) {
				$class_style = $gm->get_class_style ($class_name['name']);
                $header = array (
                    "NAME"      => $class_name['name'],
					"CSS"		=> $class_style['css'],
                    'ROW_CLASS' => $eqdkp->switch_row_class(),
                    );

                $lb_count++;
                array_multisort($member_list, SORT_DESC, $member_list); // Only works if TOTAL is the first entry in the array
                $tpl->assign_block_vars('lb_header_row', $header);
				
                $i = 0;
                foreach ($member_list as $member) {
                    if ($i++ == 0) {
                        $open_strong = "<strong>";
                        $close_strong = "</strong>";
                    } else {
                        $open_strong = "";
                        $close_strong = "";
                    }
                    $tpl->assign_block_vars('lb_header_row.lb_member_row', array(
                        "NAME"      => $member["NAME"],
                        "U_MEMBER"  => member_path($member['NAME']),
                        "TOTAL"     => $member["TOTAL"],
                        "C_TOTAL"   => color_item($member["TOTAL"]),
                        "OPEN_STRONG"   => $open_strong,
                        "CLOSE_STRONG"  => $close_strong
                    ));
                }
            }
        }
        /**
         * Set the colspan for the leader board heading
         */
        $tpl->assign_vars(array(
            'LB_COUNT' => $lb_count
            ));
//gehEND
        $sordoptions = split(" ", $current_order['sql']);
        $sortcol = $sordoptions[0];

        if($sordoptions[1] == "desc")
        {
          $sortascdesc = SORT_DESC;
        }
        else
        {
          $sortascdesc = SORT_ASC;
        }

        $members_rows_fsort = array();

        foreach($members_rows as $members_line)
        {
            $members_rows_fsort[] = $members_line[$sortcol];
        }

        array_multisort($members_rows_fsort, $sortascdesc, $members_rows);

        $member_count = 0;

        foreach($members_rows as $row) {
            $member_count++;

            if ($filter == $row["member_class"]) {
                $classFilter = "";
            } else {
                $classFilter = $row["member_class"];
            }
			
			$class_style = $gm->get_class_style ($row['member_class']);
			
            $line_array = array(
                'ROW_CLASS'     => $eqdkp->switch_row_class(),
                'ID'            => $row['member_id'],
                'COUNT'         => $member_count,
                'NAME'          => $row['rank_prefix'] . (( $row['member_status'] == '0' ) ? '<i>' . $row['member_name'] . '</i>' : $row['member_name']) . $row['rank_suffix'],
//gehDEBUG - did i really need to comment out the RANK col??
                'RANK'          => ( !empty($row['rank_name']) ) ? '<a href="'.$u_rank_search.'">' . sanitize($row['rank_name']) . '</a>' : '&nbsp;',
                'LEVEL'         => ( $row['member_level'] > 0 ) ? $row['member_level'] : '&nbsp;',
                'CLASS'         => ( !empty($row['member_class']) ) ? sanitize($row['member_class']) : '&nbsp;',
                'ARMOR'         => ( !empty($row['armor_type']) ) ? sanitize($row['armor_type']) : '&nbsp;',
                'EARNED'        => number_format($row['member_earned'], 2),
                'SPENT'         => number_format($row['member_spent'], 2),
                'ADJUSTMENT'    => number_format($row['member_adjustment'], 2),
                'CURRENT'       => number_format($row['member_current'], 2),
                'LASTRAID'      => ( !empty($row['member_lastraid']) ) ? date($user->style['date_notime_short'], $row['member_lastraid']) : '&nbsp;',
                'C_ADJUSTMENT'  => color_item($row['member_adjustment']),
                'C_CURRENT'     => color_item($row['member_current']),
                'C_LASTRAID'    => 'neutral',

                // RaidGroups
                'I_RACE'    => $gm->get_race_icon ($row['member_race'],$row['member_gender']),
                'I_CLASS'   => $gm->get_class_icon ($row['member_class']),
                'CSS'   	=> $class_style['css'],
                'PTOTAL'    => $row['member_current_total'],
                'ATOTAL'    => $row['member_attend_total'],
                'C_PTOTAL'  => color_item($row['member_current_total']),
                'C_ATOTAL'  => color_item($row['member_attend_total'], true),

                'LASTRAID'      => ( !empty($row['member_lastraid']) ) ? date($user->style['date_notime_short'], $row['member_lastraid']) : '&nbsp;',
                'C_ADJUSTMENT'  => color_item($row['member_adjustment']),
                'C_CURRENT'     => color_item($row['member_current']),
                'C_LASTRAID'    => 'neutral',
                'U_VIEW_MEMBER' => member_path($row['member_name']),
                'U_FILTER'      => member_path() . path_params('filter', $classFilter) . path_params('show_alternates',$show_alternates),
            );

            $tpl->assign_block_vars('members_row', $line_array);
//gehRAIDGROUPS START
			if (!empty($raidgroups)) {
				foreach ($row['raidgroups'] as $raidgroup) {
					$tpl->assign_block_vars('members_row.raidgroups', $raidgroup);
				}
			}
//gehRAIDGROUPS END
        }
//gehDEBUG    } // end did we find rows

    $uri_addon  = ''; // Added to the end of the sort links
    $uri_addon .= path_params('filter', $filter);
    $uri_addon .= ( $in->get('show') != '' ) ? path_params('show', sanitize($in->get('show'))) : '';
//gehALTERNATES
    $uri_addon .= path_params('show_alternates', $show_alternates);
//gehALTERNATES

    if ( ($eqdkp->config['hide_inactive'] == 1) && (!$show_all) )
    {
        // TODO: Holy god this is fugly
        $footcount_text = sprintf($user->lang['listmembers_active_footcount'], $member_count,
//gehALTERNATES
//                                  '<a href="' . member_path() . path_params(array(
//                                      URI_ORDER => $current_order['uri']['current'],
//                                      'show'    => 'all'
//                                   )) . '" class="rowfoot">'
         '<a href="' . member_path() . path_params(URI_ORDER,$current_order['uri']['current']) . path_params('show','all') . path_params('filter',urlencode($filter)) . path_params('show_alternates',$show_alternates).'" class="rowfoot">'
//gehEND
        );
    }
    else
    {
        $footcount_text = sprintf($user->lang['listmembers_footcount'], $member_count);
    }
    $db->free_result($members_result);
}

$tpl->assign_vars(array(
    'F_MEMBERS' => member_path(),

    'L_FILTER'        => $user->lang['filter'],
    'L_NAME'          => $user->lang['name'],
    'L_RANK'          => $user->lang['rank'],
    'L_LEVEL'         => $user->lang['level'],
    'L_CLASS'         => $user->lang['class'],
    'L_ARMOR'         => $user->lang['armor'],
    'L_EARNED'        => $user->lang['earned'],
    'L_SPENT'         => $user->lang['spent'],
    'L_ADJUSTMENT'    => $user->lang['adjustment'],
    'L_CURRENT'       => $user->lang['current'],
    'L_LASTRAID'      => $user->lang['lastraid'],
    'L_LASTLOOT'      => $user->lang['lastloot'],
    'L_RAIDS_30_DAYS' => sprintf($user->lang['raids_x_days'], 30),
    'L_RAIDS_90_DAYS' => sprintf($user->lang['raids_x_days'], 90),
    'BUTTON_NAME'     => 'submit',
    'BUTTON_VALUE'    => $user->lang['compare_members'],

//gehRAID_GROUPS
//    'O_NAME'       => $current_order['uri'][0],
//    'O_RANK'       => $current_order['uri'][8],
//    'O_LEVEL'      => $current_order['uri'][6],
//    'O_CLASS'      => $current_order['uri'][7],
//    'O_ARMOR'      => $current_order['uri'][9],
//    'O_EARNED'     => $current_order['uri'][1],
//    'O_SPENT'      => $current_order['uri'][2],
//    'O_ADJUSTMENT' => $current_order['uri'][3],
//    'O_CURRENT'    => $current_order['uri'][4],
//    'O_LASTRAID'   => $current_order['uri'][5],
    /**
     * Sort keys: these must correspond to the indexed values in the $sort_order array declared up top
     */
    'O_NAME'       => $current_order['uri'][0],
    'O_CLASS'      => $current_order['uri'][1],
    'O_LEVEL'      => $current_order['uri'][2],

    'RG_FILTER'      => $raidgroup_id,
    'FOOTER_COLSPAN' => $footer_colspan + (count($raidgroups) * 2),
//gehEND

//gehCLASS_FILTER
// Form variables for maintaining page state
    'FILTER'            => urlencode($filter),
//gehDEBUG
//    'SHOW'              => $show,
//gehEND

//gehALTERNATES
    'SHOW_ALTERNATES'       => $show_alternates,
    'L_ALTERNATES_BUTTON'   => $show_alternates ? $user->lang['hide_alternates'] : $user->lang['show_alternates'],

	'WOW_ARMORY_IMAGES'	=> WOW_ARMORY_IMAGES,
//gehEND


    'URI_ADDON'     => $uri_addon,
    'U_LIST_MEMBERS' => member_path() . '&amp;',

    'S_COMPARE' => $s_compare,
    'S_NOTMM'   => true,
	
    'LISTMEMBERS_FOOTCOUNT' => ( $s_compare ) ? sprintf($footcount_text, sizeof(explode(',', $compare))) : $footcount_text)
);

$eqdkp->set_vars(array(
    'page_title'    => page_title($user->lang['listmembers_title']),
    'template_file' => 'listmembers.html',
    'display'       => true
));

function member_display(&$row, $show_all = false, $filter = null)
{
    global $eqdkp;

    // Replace space with underscore (for array indices)
    // Damn you Shadow Knights!
    $d_filter = ucwords(str_replace('_', ' ', $filter));
    $d_filter = str_replace(' ', '_', $d_filter);

    $member_display = null;

    // Are we showing all?
    if ( $show_all )
    {
           $member_display = true;
    }
    else
    {
        // Are we hiding inactive members?
        if ( $eqdkp->config['hide_inactive'] == '0' )
        {
            //Are we hiding their rank?
            $member_display = ( $row['rank_hide'] == '0' ) ? true : false;
        }
        else
        {
            // Are they active?
            if ( $row['member_status'] == '0' )
            {
                $member_display = false;
            }
            else
            {
                $member_display = ( $row['rank_hide'] == '0' ) ? true : false;
            } // Member inactive
        } // Not showing inactive members
    } // Not showing all

    return $member_display;
}

function validateCompareInput($input)
{
    // Remove codes from the list, like "%20"
    $retval = urldecode($input);

    // Remove anything that's not a comma or numeric
    $retval = preg_replace('#[^0-9\,]#', '', $retval);

    // Remove any extra commas as a result of removing bogus entries above
    $retval = preg_replace('#\,{2,}#', ',', $retval);

    // Remove a trailing blank entry
    $retval = preg_replace('#,$#', '', $retval);

    return $retval;
}
