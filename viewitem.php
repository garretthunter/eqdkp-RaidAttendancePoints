<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        viewitem.php
 * Began:       Fri Dec 20 2002
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

$user->check_auth('u_item_view');

if ( $in->get(URI_ITEM, 0) )
{
    $sort_order = array(
        0 => array('i.item_date desc', 'i.item_date'),
        1 => array('i.item_buyer', 'i.item_buyer desc'),
        2 => array('i.item_value desc', 'i.item_value')
    );

    $current_order = switch_order($sort_order);

    // We want to view items by name and not id, so get the name
    $item_name = $db->query_first("SELECT item_name FROM __items WHERE (`item_id` = '" . $in->get(URI_ITEM, 0) . "')");

    if ( empty($item_name) )
    {
        message_die($user->lang['error_invalid_item_provided']);
    }

    $show_stats = false;
    $u_view_stats = '';

    $sql = "SELECT i.item_id, i.item_name, i.item_value, i.item_date, i.raid_id, i.item_buyer, r.raid_name
            FROM __items AS i, __raids AS r
            WHERE (r.`raid_id` = i.`raid_id`)
            AND (i.`item_name` = " . $db->sql_escape($item_name) . ")
            ORDER BY {$current_order['sql']}";
    if ( !($items_result = $db->query($sql)) )
    {
        message_die('Could not obtain item information', '', __FILE__, __LINE__, $sql);
    }
    while ( $item = $db->fetch_record($items_result) )
    {
        $tpl->assign_block_vars('items_row', array(
            'ROW_CLASS'    => $eqdkp->switch_row_class(),
            'DATE'         => ( !empty($item['item_date']) ) ? date($user->style['date_notime_short'], $item['item_date']) : '&nbsp;',
            'BUYER'        => ( !empty($item['item_buyer']) ) ? sanitize($item['item_buyer']) : '&nbsp;',
            'U_VIEW_BUYER' => member_path($item['item_buyer']),
            'U_VIEW_RAID'  => raid_path($item['raid_id']),
            'RAID'         => ( !empty($item['raid_name']) ) ? sanitize($item['raid_name']) : '&lt;<i>Not Found</i>&gt;',
            'VALUE'        => number_format($item['item_value'], 2)
        ));
    }

    $tpl->assign_vars(array(
        'S_STATS' => $show_stats,

        'L_PURCHASE_HISTORY_FOR' => sprintf($user->lang['purchase_history_for'], sanitize($item_name)),
        'L_DATE'                 => $user->lang['date'],
        'L_BUYER'                => $user->lang['buyer'],
        'L_RAID'                 => $user->lang['raid'],
        'L_VALUE'                => $user->lang['value'],

        'O_DATE'  => $current_order['uri'][0],
        'O_BUYER' => $current_order['uri'][1],
        'O_VALUE' => $current_order['uri'][2],

        'U_VIEW_ITEM'  => item_path($in->get(URI_ITEM, 0)) . '&amp;',
        'U_VIEW_STATS' => $u_view_stats,

        'VIEWITEM_FOOTCOUNT' => sprintf($user->lang['viewitem_footcount'], $db->num_rows($items_result)))
    );

    $pm->do_hooks('/viewitem.php');

    $eqdkp->set_vars(array(
        'page_title'    => page_title(sprintf($user->lang['viewitem_title'], sanitize($item_name))),
        'template_file' => 'viewitem.html',
        'display'       => true
    ));
}
else
{
    message_die($user->lang['error_invalid_item_provided']);
}