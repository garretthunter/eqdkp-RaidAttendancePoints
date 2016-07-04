<?php
/******************************
 * EQdkp
 * Copyright 2002-2005
 * Licensed under the GNU GPL.  See COPYING for full terms.
 * ------------------
 * listmembers.php
 * begin: Wed December 18 2002
 *
 * $Id: listmembers.php,v 1.17 2007/02/21 07:42:54 garrett Exp $
 *
 ******************************/

define('EQDKP_INC', true);
$eqdkp_root_path = './';
include_once($eqdkp_root_path . 'common.php');
include_once($eqdkp_root_path . 'itemstatsfuncs.php');

$user->check_auth('u_member_list');

/**
 * ORDER MATTERS - the index values MUST be the same as the link
 */
$sort_order = array(
    0 => array('member_name', 'member_name desc'),
    1 => array('member_class', 'member_class desc'),
    2 => array('member_level', 'member_level desc'),
);

$footer_colspan = 9;
$cur_hash = hash_filename("listmembers.php");
//gehLEADER_BOARD
if ( isset($_POST['raidgroup_id'])) { // means we are requesting to change the state of show_alternates
    $raidgroup_id_filter = (int)$_POST['raidgroup_id'] ? (int)$_POST['raidgroup_id'] : -1;
} elseif (isset($_GET['raidgroup_id'])) { // means a link was clicked within the page and we are preserving state
    $raidgroup_id_filter = (int)$_GET['raidgroup_id'] ? (int)$_GET['raidgroup_id'] : -1;
} else {
	$raidgroup_id_filter = -1;
}
//gehEND

//gehALTERNATES
//
// Are we showing alternates?
//
if ( isset($_POST['show_alternates'])) { // means we are requesting to change the state of show_alternates
    $show_alternates = ($_POST['show_alternates'] ? false : true);
} elseif (isset($_GET['show_alternates'])) { // means a link was clicked within the page and we are preserving state
    $show_alternates = $_GET['show_alternates'];
}
//gehALTERNATES
//
// Compare members
//
if ( isset($_POST['submit']) && ($_POST['submit'] == $user->lang['compare_members']) && isset($_POST['compare_ids']) )
{
    redirect('listmembers.php?compare='.implode(',', $_POST['compare_ids'])."&amp;show_alternates=".$show_alternates);
}
//
// Normal member display
//
else
{

    $s_compare = false;

    $member_count = 0;
    $previous_data = '';

//gehALTERNATES
//    $show_all = ( (!empty($_GET['show'])) && ($_GET['show'] == 'all') ) ? true : false;
    if (isset($_GET['show'])) {
        $show_all = ($_GET['show'] == 'all') ? true : false;
        $show = $_GET['show'];
    } elseif (isset($_POST['show'])) {
        $show_all = ($_POST['show'] == 'all') ? true : false;
        $show = $_POST['show'];
    } else {
        $show_all = false;
    }
//gehALTERNATES

    //
    // Filtering
    //
//gehALTERNATES
//    $filter = ( isset($_GET['filter']) ) ? urldecode($_GET['filter']) : 'none';
    $filter = "";
    if (isset($_GET['filter'])) {
        $filter = urldecode($_GET['filter']);
    } elseif (isset($_POST['filter'])) {
        $filter = urldecode($_POST['filter']);
    }
//gehALTERNATES
    $filter = ( preg_match('#\-{1,}#', $filter) ) ? 'none' : $filter;

    // Grab class_id

    if ( !empty($filter)) {
        $query_by_class = 1;
        $query_by_armor = 0;
    }

    // Moved the class/race/faction information to the database
    $sql = "SELECT DISTINCT(member_class_id), member_level, class_id, class_name
              FROM " . CLASS_TABLE .",
                   " . MEMBERS_TABLE ."
             WHERE member_class_id = class_id
          GROUP BY class_name";
    $result = $db->query($sql);

	// Save the list of classes for later use
	$classList = array();
    while ( $row = $db->fetch_record($result) ) {
		$classList[] = $row["class_name"];
        $playerIcons = getPlayerIcons ('',$row['class_name'],'',$row['member_level']);
		if ($filter == $row["class_name"]) {
			$row["class_name"] = "";
		}
        $tpl->assign_block_vars('class_filter_row', array(
            'I_CLASS'   => $playerIcons['class'],
            'CLASS'     => $row['class_name'],
            'U_FILTER'  => "listmembers.php" . $SID . "&amp;filter=" . $row['class_name'] . "&amp;show_alternates=" . $show_alternates . "&amp;raidgroup_id=". $raidgroup_id_filter
        ));
    }

    $db->free_result($result);

    // end database move of race/class/faction

    // Build SQL query based on GET options
    $sql = 'SELECT m.*, (m.member_earned-m.member_spent+m.member_adjustment) AS member_current,
           member_status, r.rank_name, r.rank_hide, r.rank_prefix, r.rank_suffix,
           race_name as member_race,
                   c.class_name AS member_class,
                   m.member_id,
                   m.member_main_id,
                   m.member_ctprofile,
                   m.member_status,
                   m.member_level,
                   m.member_gender,
                   r.rank_name,
                   r.rank_hide,
                   race_name as member_race
              FROM ' . MEMBERS_TABLE . ' m,
                   ' . MEMBER_RANKS_TABLE . ' r,
                   ' . CLASS_TABLE . ' c,
                   ' . RACE_TABLE .'
             WHERE c.class_id = m.member_class_id
               AND m.member_rank_id = r.rank_id
               AND m.member_race_id = race_id';
    if (!$show_alternates) {
        $sql .= " AND member_main_id IS NULL";
    }
    if ( $query_by_class == '1' )
    {
        $sql .= " AND c.class_name =  '$filter'";
    }

    if ( isset($_GET['compare']) ) {

        $s_compare = true;
        $uri_addon = "";

        $compare = validateCompareInput($_GET['compare']);

        $sql .= " AND m.member_id IN (".$compare.")";
    }

    $sql .= " ORDER BY m.member_name";
    if ( !($members_result = $db->query($sql)) )
    {
        message_die('Could not obtain member information', '', __FILE__, __LINE__, $sql);
    }

//gehSTART RAIDGROUPS
    /**
     * Get the list of raid groupings
     */
    $rg_sql = "SELECT *
                 FROM ".RG_RAIDGROUPS_TABLE."
             ORDER BY raidgroup_display_order";
    $raidgroup_results = $db->query($rg_sql);

    $raidgroups = array();
    while ($raidgroup_array = $db->fetch_record($raidgroup_results)) {

        /**
         * Only raidgroups flagged for display will be used in calculations
         */
        if ($raidgroup_array['raidgroup_display'] == "Y") {
            $raidgroups[] = $raidgroup_array;
        }
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
        $sort_order[$rg_temp_sort_index] = array("member_current_".$raidgroup["raidgroup_id"]." desc", "member_name");
        $sort_order[$rg_temp_sort_index+1] = array("member_attend_".$raidgroup["raidgroup_id"]." desc", "member_name");

        $rg_temp_sort_index = $rg_temp_sort_index + 2;
    }

    /**
     * Get the event names that will be used to sum point in groups
     */
    $events_sql = "SELECT DISTINCT(event_name), event_id
                     FROM ".EVENTS_TABLE."
                    WHERE event_id IN ('".implode("','",$rg_event_ids)."')";
    $events_results = $db->query($events_sql);

    while ($events = $db->fetch_record($events_results)) {
        $rg_events[$events["event_id"]] = stripslashes($events['event_name']);
    }
    $db->free_result($events_results);

    /**
     * Append the Total columns. We *always* have total colums. Do not increment the sort_index as we will use it to track
     * our raidgroup columns
     */
    $sort_order[$rg_temp_sort_index] = array("member_current_total desc", "member_name");
    $sort_order[$rg_temp_sort_index+1] = array("member_attend_total desc", "member_name");

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


    // Figure out what data we're comparing from member to member
    // in order to rank them
    $sort_index = explode('.', $current_order['uri']['current']);
    $previous_source = preg_replace('/( (asc|desc))?/i', '', $sort_order[$sort_index[0]][$sort_index[1]]);

//gehEND RAIDGROUPS

    if ($db->num_rows($members_result) > 0 ) {
        while ( $row = $db->fetch_record($members_result) )
        {
            if ( member_display($row) )
            {
//gehSTART ALTERNATES
// **general functionality** Alts + Mains should display the same point summary
// get all the alternates associated with this member & include their raids under the main
// UNTIL 1.4 we have to get the member_id separately for the main becuase we assume its the main.

                // Get this member's name and any alts associated with it
                $alt_sql = 'SELECT member_id, member_main_id
                              FROM '.MEMBERS_TABLE.'
                             WHERE member_name = "'.$row['member_name'].'"';
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
                              FROM '.MEMBERS_TABLE.'
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

                $points_earned_sql = "SELECT ".RAIDS_TABLE.".raid_name, SUM(raid_value)
                             FROM ".RAID_ATTENDEES_TABLE."
                        LEFT JOIN ".RAIDS_TABLE." ON ".RAID_ATTENDEES_TABLE.".raid_id=".RAIDS_TABLE.".raid_id
                            WHERE ".RAID_ATTENDEES_TABLE.".member_name IN ".$in_clause."
                         GROUP by ".RAIDS_TABLE.".raid_name";
                $points_earned_result = $db->query($points_earned_sql);
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
//        $raids_attended_sql = "SELECT raid_name FROM ".RAIDS_TABLE." r JOIN ".RAID_ATTENDEES_TABLE." ra ON r.raid_id = ra.raid_id WHERE ra.member_name = '".$row['member_name']."'";
                $raids_attended_sql = "SELECT raid_name FROM ".RAIDS_TABLE." r JOIN ".RAID_ATTENDEES_TABLE." ra ON r.raid_id = ra.raid_id WHERE ra.member_name IN ".$in_clause;
//gehALTERNATES
                $raids_attended_result = $db->query($raids_attended_sql);
                while( $raids_attended_row = $db->fetch_record($raids_attended_result) ) {
                    $raids_attended_data[$raids_attended_row[0]]++;
                }
                $db->free_result($raids_attended_result);

                $total_raids_sql = "SELECT raid_name FROM ".RAIDS_TABLE;
                $total_raids_result = $db->query($total_raids_sql);

                while( $total_raids_row = $db->fetch_record($total_raids_result) ){
                    $total_raids_data[$total_raids_row[0]]++;
                }
                $db->free_result($total_raids_result);

                while( $points_earned_row = $db->fetch_record($points_earned_result) ){
                    $points_earned_data[$points_earned_row[0]] = $points_earned_row[1];
                }
                $db->free_result($points_earned_result);

//gehALTERNATES
//        $points_spent_sql = "SELECT ".RAIDS_TABLE.".raid_name, SUM(".ITEMS_TABLE.".item_value) FROM ".ITEMS_TABLE." LEFT JOIN ".RAIDS_TABLE." ON ".ITEMS_TABLE.".raid_id=".RAIDS_TABLE.".raid_id WHERE ".ITEMS_TABLE.".item_buyer = '".$row['member_name']."' GROUP BY ".RAIDS_TABLE.".raid_name;";
                $points_spent_sql = "SELECT ".RAIDS_TABLE.".raid_name, SUM(".ITEMS_TABLE.".item_value) FROM ".ITEMS_TABLE." LEFT JOIN ".RAIDS_TABLE." ON ".ITEMS_TABLE.".raid_id=".RAIDS_TABLE.".raid_id WHERE ".ITEMS_TABLE.".item_buyer IN ".$in_clause." GROUP BY ".RAIDS_TABLE.".raid_name;";
//gehALTERNATES
                $points_spent_result = $db->query($points_spent_sql);

                while( $points_spent_row = $db->fetch_record($points_spent_result) ){
                    $points_earned_data[$points_spent_row[0]] -= $points_spent_row[1];
                }
                $db->free_result($points_spent_result);

            // Get the sum of adjustments for this member
//gehALTERNATES
//        $point_adjs_sql = "SELECT adjustment_event, adjustment_value FROM ".ADJUSTMENTS_TABLE." WHERE member_name = '".$row['member_name']."' OR member_name IS NULL;";
                $point_adjs_sql = "SELECT adjustment_event, adjustment_value FROM ".ADJUSTMENTS_TABLE." WHERE member_name IN ".$in_clause." OR member_name IS NULL;";
//gehALTERNATES
                $point_adjs_result = $db->query($point_adjs_sql);

                while( $point_adjs_row = $db->fetch_record($point_adjs_result) )
                {
                    $points_earned_data[$point_adjs_row['adjustment_event']] += $point_adjs_row['adjustment_value'];
                }
                $db->free_result($point_adjs_result);

                /**
                 * Special item tracking
                 */
/*                $special_items_sql = "SELECT item_name
                       FROM ".ITEMS_TABLE."
                       WHERE item_buyer = '".$row['member_name']."'";
                $special_items_result = $db->query($special_items_sql);

                unset($special_items_data);
                $special_items_data["The Master's Key"] = 0; // Key to Karazhan

                while( $special_items_row = $db->fetch_record($special_items_result) )
                {
                    $special_items_data[$special_items_row[0]] = 1;
                }
                $db->free_result($special_items_result);
*/
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

                // Special Items
                $members_rows[$member_count]['member_has_masters_key'] = $special_items_data["The Master's Key"];

                unset($last_loot);

                // So that we can compare this member to the next member,
                // set the value of the previous data to the source
                $previous_data = $row[$previous_source];
            }
        }
//gehTEST - build the leader board

// build the raidgroup filter
		$tpl->assign_block_vars('raidgroups_filter_row', array(
			"NAME"          => "Total",
			'ID'			=> "-1",
			)
		);
		foreach ($raidgroups as $raidgroup) {
			$tpl->assign_block_vars('raidgroups_filter_row', array(
				"NAME"          => $raidgroup["raidgroup_name"],
				'ID'			=> $raidgroup["raidgroup_id"],
                'SELECTED'      => ( $raidgroup['raidgroup_id'] == $raidgroup_id_filter ) ? 'selected="selected"' : ""
				)
			);
		}
//
/*
		"CURRENT"   => $members_rows[$member_count]["member_current_".$raidgroup["raidgroup_id"]],
		"C_CURRENT" => color_item ($members_rows[$member_count]["member_current_".$raidgroup["raidgroup_id"]]),
		"ATTEND"    => $members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]],
		"C_ATTEND"  => color_item ($members_rows[$member_count]["member_attend_".$raidgroup["raidgroup_id"]], true),
*/

		$lb_header_row = array();
		$lb_count = 0;
		foreach ($classList as $class_name) {
			$member_list = array();
			$sort_col = array();
			foreach ($members_rows as $member) {
			
				if ($raidgroup_id_filter != "-1") {
					// leaderboard is being filtered
					if ($member["member_class"] == $class_name) { 
						$member_list[] = array (
							"NAME" 		=> $member["member_name"],
							"U_MEMBER" 	=> 'viewmember.php' . $SID . '&amp;' . URI_NAME . '='.$member["member_name"],
							"TOTAL" 	=> $member["member_current_".$raidgroup_id_filter],
							"C_TOTAL" 	=> color_item($member["member_current_".$raidgroup_id_filter]),
							"OPEN_STRONG"	=> $open_strong,
							"CLOSE_STRONG"	=> $close_strong
							);
						$sort_col[] = $member["member_current_total"];
					}
				} else {
					// no filter, return Totals
					if ($member["member_class"] == $class_name) { 
						$member_list[] = array (
							"NAME" 		=> $member["member_name"],
							"U_MEMBER" 	=> 'viewmember.php' . $SID . '&amp;' . URI_NAME . '='.$member["member_name"],
							"TOTAL" 	=> $member["member_current_total"],
							"C_TOTAL" 	=> color_item($member["member_current_total"]),
							"OPEN_STRONG"	=> $open_strong,
							"CLOSE_STRONG"	=> $close_strong
							);
						$sort_col[] = $member["member_current_total"];
					}
				}
			}

			/**
			 * If a particular class has no members do not show
			 */			
			if (!empty($member_list)) {
				$header = array (
					"NAME" 		=> $class_name,
					'ROW_CLASS'	=> $eqdkp->switch_row_class(),				
					);
				
				$lb_count++;
				array_multisort($sort_col, SORT_DESC, $member_list);
	
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
						"NAME" 		=> $member["NAME"],
						"U_MEMBER" 	=> 'viewmember.php' . $SID . '&amp;' . URI_NAME . '='.$member["NAME"],
						"TOTAL" 	=> $member["TOTAL"],
						"C_TOTAL" 	=> color_item($member["TOTAL"]),
						"OPEN_STRONG"	=> $open_strong,
						"CLOSE_STRONG"	=> $close_strong
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

//print_r($members_rows);exit; //gehDEBUG
//gehTEST
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

            $playerIcons = getPlayerIcons ($row['member_race'],$row['member_class'],$row['member_gender'], $row['member_level']);

			if ($filter == $row["member_class"]) {
				$classFilter = "";
			} else {
				$classFilter = $row["member_class"];
			}

            $line_array = array(
                'ROW_CLASS'     => $eqdkp->switch_row_class(),
                'CTPROFILE'     => ( !empty($row['member_ctprofile'])) ? '<a href="http://ctprofiles.net/'.$row['member_ctprofile'].'" target="new"><img src="images/ctprofile_icon.gif" alt="Character Profile" title="Character Profile" /></a>' : "&nbsp;",
                'ID'            => $row['member_id'],
                'COUNT'         => $member_count,
                'NAME'          => $row['rank_prefix'] . (( $row['member_status'] == '0' ) ? '<i>' . $row['member_name'] . '</i>' : $row['member_name']) . $row['rank_suffix'],
                'I_RACE'        => $playerIcons['race'],
                'I_CLASS'       => $playerIcons['class'],
                'RACE'          => ($row['member_race']." ".$row['member_gender']),
                'CLASS'         => $row['member_class'],
                'LEVEL'         => $row['member_level'],
				'U_FILTER'  	=> "listmembers.php" . $SID . "&amp;filter=" . $classFilter . "&amp;show_alternates=" . $show_alternates,

                // RaidGroups
                'PTOTAL'    => $row['member_current_total'],
                'ATOTAL'    => $row['member_attend_total'],
                'C_PTOTAL'  => color_item($row['member_current_total']),
                'C_ATOTAL'  => color_item($row['member_attend_total'], true),

                // Goodies
                'IMASTERSKEY'   => ( $row['member_has_masters_key'] == 1 ) ? get_itemstats_decorate_name(stripslashes("The Master's Key"),"smallitemicon",FALSE) : '&nbsp;',

                'LASTRAID'      => ( !empty($row['member_lastraid']) ) ? date($user->style['date_notime_short'], $row['member_lastraid']) : '&nbsp;',
                'C_ADJUSTMENT'  => color_item($row['member_adjustment']),
                'C_CURRENT'     => color_item($row['member_current']),
                'C_LASTRAID'    => 'neutral',
                'U_VIEW_MEMBER' => 'viewmember.php' . $SID . '&amp;' . URI_NAME . '='.$row['member_name']
            );

            $tpl->assign_block_vars('members_row', $line_array);
//gehRAIDGROUPS START
            foreach ($row["raidgroups"] as $raidgroup) {
                $tpl->assign_block_vars('members_row.raidgroups', $raidgroup);
            }
//gehRAIDGROUPS END
        }
    } // end did we find rows

    $uri_addon  = ''; // Added to the end of the sort links
    $uri_addon .= '&amp;filter=' . urlencode($filter);
//gehALTERNATES
//    $uri_addon .= ( isset($_GET['show']) ) ? '&amp;show=' . htmlspecialchars(strip_tags($_GET['show']), ENT_QUOTES) : '';
    $uri_addon .= ( $show ) ? '&amp;show=' . $show : '';
    $uri_addon .= '&amp;show_alternates=' . $show_alternates;
//gehALTERNATES

    if ( ($eqdkp->config['hide_inactive'] == 1) && (!$show_all) )
    {
        $footcount_text = sprintf($user->lang['listmembers_active_footcount'], $member_count,
                                  '<a href="listmembers.php' . $SID . '&amp;' . URI_ORDER . '=' . $current_order['uri']['current'] . '&amp;show=all&amp;filter=' . urlencode($filter) . "&amp;show_alternates=".$show_alternates.'" class="rowfoot">');
    }
    else
    {
        $footcount_text = sprintf($user->lang['listmembers_footcount'], $member_count);
    }
    $db->free_result($members_result);
}

$tpl->assign_vars(array(
// Form variables for maintaining page state
    'F_MEMBERS' => 'listmembers.php'.$SID,
    'V_SID'     => str_replace('?' . URI_SESSION . '=', '', $SID),
    'FILTER'            => urlencode($filter),
    'SHOW'              => $show,
	'RG_FILTER'		=> $raidgroup_id_filter,
//gehALTERNATES
    'SHOW_ALTERNATES'   => $show_alternates,
    'L_ALTERNATES_BUTTON'   => $show_alternates ? $user->lang['hide_alternates'] : $user->lang['show_alternates'],
//gehALTERNATES
    'L_FILTER'        => $user->lang['class_filter'],
    'L_NAME'          => $user->lang['name'],
    'L_CLASS'         => $user->lang['class'],
    'L_LEVEL'         => $user->lang['level'],
    'BUTTON_NAME'     => 'submit',
    'BUTTON_VALUE'    => $user->lang['compare_members'],


    // these must correspond to the indexed values in the $sort_order array declared up top
    'O_NAME'       => $current_order['uri'][0],
    'O_CLASS'      => $current_order['uri'][1],
    'O_LEVEL'      => $current_order['uri'][2],

     // Goodies
    'H_MASTERSKEY'  => get_itemstats_decorate_name(stripslashes("The Master's Key"),'smallitemicon',TRUE),
    'O_IMASTERSKEY' => $current_order['uri'][3],

    "FOOTER_COLSPAN"=> $footer_colspan + (count($raidgroups) * 2),
    'URI_ADDON'     => $uri_addon,
    'PAGE_HASH'     => $cur_hash,
    'U_LIST_MEMBERS'=> 'listmembers.php' . $SID . '&amp;',

    'S_COMPARE' => $s_compare,
    'S_NOTMM'   => true,

    'LISTMEMBERS_FOOTCOUNT' => ( isset($_GET['compare']) ) ? sprintf($footcount_text, sizeof(explode(',', $compare_ids))) : $footcount_text)
);

$eqdkp->set_vars(array(
    'page_title'    => sprintf($user->lang['title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['listmembers_title'],
    'template_file' => 'listmembers.html',
    'display'       => true)
);

function member_display(&$row)
{
    global $eqdkp;
    global $query_by_class, $filter, $filters, $show_all, $id;

    // Replace space with underscore (for array indices)
    // Damn you Shadow Knights!
    $d_filter = ucwords(str_replace('_', ' ', $filter));
    $d_filter = str_replace(' ', '_', $d_filter);

    $member_display = null;

    // Are we showing all?
    if ( $show_all ) {
           $member_display = true;
    } else {
        // Are we hiding inactive members?
        if ( $eqdkp->config['hide_inactive'] == '0' ) {
            //Are we hiding their rank?
            $member_display = ( $row['rank_hide'] == '0' ) ? true : false;
        } else {
            // Are they active?
            if ( $row['member_status'] == '0' ) {
                $member_display = false;
            } else {
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

    // Remove anything that's not a comma or alpha-numeric
    $retval = preg_replace('#[^A-Za-z0-9\,]#', '', $retval);

    // Remove any extra commas as a result of removing bogus entries above
    $retval = str_replace(',,', ',', $retval);

    // Remove a trailing blank entry
    $retval = preg_replace('#,$#', '', $retval);

    return $retval;
}
?>
