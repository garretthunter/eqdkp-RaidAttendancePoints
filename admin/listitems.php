<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        listitems.php
 * Began:       Fri Dec 27 2002
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

$user->check_auth('a_item_');

$sort_order = array(
    0 => array('item_date desc', 'item_date'),
    1 => array('item_buyer', 'item_buyer desc'),
    2 => array('item_name', 'item_name desc'),
    3 => array('raid_name', 'raid_name desc'),
    4 => array('item_value desc', 'item_value')
);

$current_order = switch_order($sort_order);

$total_items = $db->query_first("SELECT COUNT(*) FROM __items");
$start = $in->get('start', 0);

$sql = "SELECT i.item_id, i.item_name, i.item_buyer, i.item_date, i.raid_id, item_ctrt_wowitemid,
            i.item_value, r.raid_name
        FROM __items AS i, __raids AS r
        WHERE (r.raid_id = i.raid_id)
        ORDER BY {$current_order['sql']}
        LIMIT {$start},{$user->data['user_ilimit']}";

$listitems_footcount = sprintf($user->lang['listpurchased_footcount'], $total_items, $user->data['user_ilimit']);
$pagination = generate_pagination(item_path() . path_params(URI_ORDER, $current_order['uri']['current']), $total_items, $user->data['user_ilimit'], $start);

if ( !($items_result = $db->query($sql)) )
{
    message_die('Could not obtain item information', 'Database error', __FILE__, __LINE__, $sql);
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
		'DATE'         => ( !empty($item['item_date']) ) ? date($user->style['date_notime_short'], $item['item_date']) : '&nbsp;',
		'BUYER'        => ( !empty($item['item_buyer']) ) ? sanitize($item['item_buyer']) : '&lt;<i>Not Found</i>&gt;',
		'U_VIEW_BUYER' => ( !empty($item['item_buyer']) ) ? member_path($item['item_buyer']) : '',
		'NAME'         => sanitize($item['item_name']),
		'U_VIEW_ITEM'  => edit_item_path($item['item_id']),
		'RAID'         => ( !empty($item['raid_name']) ) ? sanitize($item['raid_name']) : '&lt;<i>Not Found</i>&gt;',
		'U_VIEW_RAID'  => ( !empty($item['raid_name']) ) ? edit_raid_path($item['raid_id']) : '',
		'VALUE'        => number_format($item['item_value'], 2)
//gehITEM_DECORATIONS
	   ,'GAME_ID'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_id'] : $item['item_ctrt_wowitemid'],
		'QUALITY'	   => ( !empty($game_items[$item['item_id']]) ) ? $game_items[$item['item_id']]['game_item_quality'] : 0,
		'ICON'	       => ( !empty($game_items[$item['item_id']]) ) ? strtolower($game_items[$item['item_id']]['game_item_icon']) : 'inv_misc_questionmark'
//geh
	));
}
$db->free_result($items_result);

$tpl->assign_vars(array(
    'L_DATE' => $user->lang['date'],
    'L_BUYER' => $user->lang['buyer'],
    'L_ITEM' => $user->lang['item'],
    'L_RAID' => $user->lang['raid'],
    'L_VALUE' => $user->lang['value'],
    
    'O_DATE' => $current_order['uri'][0],
    'O_BUYER' => $current_order['uri'][1],
    'O_NAME' => $current_order['uri'][2],
    'O_RAID' => $current_order['uri'][3],
    'O_VALUE' => $current_order['uri'][4],
    
    'U_LIST_ITEMS' => item_path() . '&amp;',
    
    'START' => $start,
    'S_HISTORY' => true,
    'LISTITEMS_FOOTCOUNT' => $listitems_footcount,
    'ITEM_PAGINATION'     => $pagination
));

$eqdkp->set_vars(array(
    'page_title'    => page_title($user->lang['listpurchased_title']),
    'template_file' => 'listitems.html',
    'display'       => true
));