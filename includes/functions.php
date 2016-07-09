<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        functions.php
 * Began:       Tue Dec 17 2002
 * Date:        $Date: 2008-08-16 22:57:29 -0700 (Sat, 16 Aug 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 570 $
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

## ############################################################################
## Template helpers
## ############################################################################

/**
 * Keep a consistent page title across the entire application
 *
 * @param     string     $title            The dynamic part of the page title, appears before " - Guild Name DKP"
 * @return    string
 */
function page_title($title = '')
{
    global $eqdkp, $user;

    $retval = '';

    $section = ( defined('IN_ADMIN') ) ? $user->lang['admin_title_prefix'] : $user->lang['title_prefix'];
    $global_title = sprintf($section, $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']);

    $retval = ( $title != '' ) ? "{$title} - " : '';
    $retval .= $global_title;

    return sanitize($retval, TAG);
}

/**
 * Option Checked value method
 *
 * Returns ' selected="selected"' for use in <option> tags if $condition is true
 *
 * @param bool $condition Condition to evaluate
 * @return string
 */
function option_checked($condition)
{
    return ( $condition ) ? ' checked="checked"' : '';
}

/**
 * Option Selected value method
 *
 * Returns ' checked="checked"' for use in checkbox/radio <input> tags if $condition is true
 *
 * @param bool $condition Condition to evaluate
 * @return string
 */
function option_selected($condition)
{
    return ( $condition ) ? ' selected="selected"' : '';
}

/**
 * Returns an array of valid Style ID options for use in populating a <select> tag
 *
 * @param     mixed      $comparison       Used with {@link option_selected} to determine the selected row in the drop-down
 * @return    array
 */
function select_style($comparison)
{
    global $db, $eqdkp;

    $retval = array();

    $sql = "SELECT style_id, style_name
            FROM __styles
            ORDER BY `style_name`";
    $result = $db->query($sql);
    while ( $row = $db->fetch_record($result) )
    {
        $retval[] = array(
            'VALUE'    => $row['style_id'],
            'SELECTED' => option_selected(intval($comparison) == intval($row['style_id'])),
            'OPTION'   => $row['style_name']
        );
    }
    $db->free_result($result);

    return $retval;
}

/**
 * Returns an array of valid template folder names for use in populating a <select> tag
 *
 * @param     mixed      $comparison       Used with {@link option_selected} to determine the selected row in the drop-down
 * @return    array
 */
function select_template($comparison)
{
    global $eqdkp;

    $retval = array();
    if ( $dir = @opendir($eqdkp->root_path . 'templates/') )
    {
        while ( $file = @readdir($dir) )
        {
            if ( valid_folder("{$eqdkp->root_path}templates/{$file}") )
            {
                $retval[] = array(
                    'VALUE'    => $file,
                    'SELECTED' => option_selected(strtolower($comparison) == strtolower($file)),
                    'OPTION'   => $file
                );
            }
        }
    }

    return $retval;
}

/**
 * Returns an array of valid language folders for use in populating a <select> tag
 *
 * @param     mixed      $comparison       Used with {@link option_selected} to determine the selected row in the drop-down
 * @return    array
 */
function select_language($comparison)
{
    global $eqdkp;

    $retval = array();
    if ( $dir = @opendir($eqdkp->root_path . 'language/') )
    {
        while ( $file = @readdir($dir) )
        {
            if ( valid_folder("{$eqdkp->root_path}language/{$file}") )
            {
                $retval[] = array(
                    'VALUE'    => $file,
                    'SELECTED' => option_selected(strtolower($comparison) == strtolower($file)),
                    'OPTION'   => ucfirst($file)
                );
            }
        }
    }

    return $retval;
}

/**
 * Determines if a folder path is valid. Ignores .svn, CVS, cache, etc.
 *
 * @param     string     $path             Path to check
 * @return    boolean
 */
function valid_folder($path)
{
    $ignore = array('.', '..', '.svn', 'CVS', 'cache', 'install');

    if ( !is_file($path) && !is_link($path) && !in_array(basename($path), $ignore) )
    {
        return true;
    }

    return false;
}

define('ENT', 1); // Escape HTML entities
define('TAG', 2); // Strip HTML tags

/**
 * Translate qoute characters to their HTML entities, and strip HTML tags. Calls
 * stripslashes() if magic quotes are enabled.
 *
 * @param     string     $input            Input to sanitize
 * @param     int        $options          ENT | TAG
 * @return    string
 */
function sanitize($input, $options = 3, $ignore = null)
{
    if ( !is_null($ignore) )
    {
        trigger_error('Third parameter to sanitize is deprecated!', E_USER_WARNING);
    }

    $input = ( $options & TAG ) ? strip_tags($input) : $input;
    $input = ( $options & ENT )  ? htmlspecialchars($input, ENT_QUOTES) : $input;
    $input = ( get_magic_quotes_gpc() ) ? stripslashes($input) : $input;

    return $input;
}

/**
 * Reverse the effects of htmlspecialchars()
 *
 * @param     string     $input            Input to reverse
 * @return    string
 */
function unsanitize($input)
{
    if ( function_exists('htmlspecialchars_decode') )
    {
        return htmlspecialchars_decode($input, ENT_QUOTES); // PHP >= 5.1.0
    }
    else
    {
        $retval = $input;
        $retval = str_replace('&amp;', '&', $retval);
        $retval = str_replace('&#039;', '\'', $retval);
        $retval = str_replace('&quot;', '"', $retval);
        $retval = str_replace('&lt;', '<', $retval);
        $retval = str_replace('&gt;', '>', $retval);

        return $retval;
    }
}

/**
 * Create a CSS bar graph
 *
 * @param     int        $width            Width of the bar
 * @param     string     $text             Text to show
 * @return    string
 */
function create_bar($width, $text = '')
{
    if ( strstr($width, '%') )
    {
        $width = intval(str_replace('%', '', $width));
        if ( $width > 0 )
        {
            $width = ( intval($width) <= 100 ) ? $width . '%' : '100%';
        }
    }

    $text = ( $text == '' ) ? $width . '%' : $text;

    return "<div class=\"graph\"><strong class=\"bar\" style=\"width: {$width}%;\">{$text}</strong></div>\n";
}

## ############################################################################
## Password hashing
## ############################################################################

/**
 * Static function to abstract password encryption
 *
 * @param string $string String to encrypt
 * @param string $salt Salt value
 * @return string SHA1-encrypted and salted hash
 */
function hash_password($plaintext, $salt)
{
    return sha1("{$plaintext}_{$salt}");
}

/**
 * Generate a string suitable for use as a password salt
 *
 * @return string
 */
function generate_salt()
{
    $chars = array(
        'a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H','i','I',
        'j','J','k','K','l','L','m','M','n','N','o','O','p','P','q','Q','r','R',
        's','S','t','T','u','U','v','V','w','W','x','X','y','Y','z','Z',

        '1','2','3','4','5','6','7','8','9','0',

        '!','@','#','$','%','^','&','*','_','+','|'
    );

    $max_chars = count($chars) - 1;
    srand( (double) microtime() * 1000000);

    $salt_length = rand(8, 20);

    $retval = '';
    for($i = 0; $i < $salt_length; $i++)
    {
        $retval = $retval . $chars[rand(0, $max_chars)];
    }

    return $retval;
}

## ############################################################################
## Other stuff
## ############################################################################

/**
 * Outputs a message with debugging info (if needed) and ends output
 *
 * Clean replacement for die()
 *
 * @param     string     $text             Message text
 * @param     string     $title            Message title
 * @param     string     $file             File name
 * @param     int        $line             File line
 * @param     string     $sql              SQL code
 */
function message_die($text = '', $title = '', $file = '', $line = '', $sql = '')
{
    global $db, $tpl, $eqdkp, $user, $pm;
    global $gen_simple_header, $start_time, $eqdkp_root_path;

    $error_text = '';
    if ( (DEBUG == 1) && ($db->error_die) )
    {
        $sql_error = $db->error();

        $error_text = '';

        if ( $sql_error['message'] != '' )
        {
            $error_text .= '<b>SQL error:</b> ' . $sql_error['message'] . '<br />';
        }

        if ( $sql_error['code'] != '' )
        {
            $error_text .= '<b>SQL error code:</b> ' . $sql_error['code'] . '<br />';
        }

        if ( $sql != '' )
        {
            $error_text .= '<b>SQL:</b> ' . $sql . '<br />';
        }

        if ( ($line != '') && ($file != '') )
        {
            $error_text .= '<b>File:</b> ' . $file . '<br />';
            $error_text .= '<b>Line:</b> ' . $line . '<br />';
        }
    }

    // Add the debug info if we need it
    if ( (DEBUG == 1) && ($db->error_die) )
    {
        if ( $error_text != '' )
        {
            $text .= '<br /><br /><b>Debug Mode</b><br />' . $error_text;
        }
    }

    if ( !is_object($tpl) )
    {
        die($text);
    }

    $tpl->assign_vars(array(
        'MSG_TITLE'  => ( $title != '' ) ? $title : '&nbsp;',
        'MSG_TEXT'   => ( $text  != '' ) ? $text  : '&nbsp;')
    );

    if ( !defined('HEADER_INC') )
    {
        if ( (is_object($user)) && (is_object($eqdkp)) && (@is_array($eqdkp->config)) && (isset($user->lang['title_prefix'])) )
        {
            $page_title = sprintf($user->lang['title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']) . ': '
                . (( !empty($title) ) ? $title : ' Message');
        }
        else
        {
            $page_title = $user->lang['message_title'];
        }

        $eqdkp->set_vars(array(
            'gen_simple_header' => $gen_simple_header,
            'page_title'        => $page_title,
            'template_file'     => 'message.html')
        );

        $eqdkp->page_header();
    }
    $eqdkp->page_tail();
    exit;
}

/**
 * Returns the appropriate CSS class to use based on a number's range
 *
 * @param     string     $item             The number
 * @param     boolean    $percentage       Treat the number like a percentage?
 * @return    mixed                        CSS Class / false
 */
function color_item($item, $percentage = false)
{
    if ( !is_numeric($item) )
    {
        return false;
    }

    if ( !$percentage )
    {
        if ( $item < 0 )
        {
            $class = 'negative';
        }
        elseif ( $item > 0)
        {
            $class = 'positive';
        }
        else
        {
            $class = 'neutral';
        }
    }
    elseif ( $percentage )
    {
        if ( ($item >= 0) && ($item <= 34) )
        {
            $class = 'negative';
        }
        elseif ( ($item >= 35) && ($item <= 66) )
        {
            $class = 'neutral';
        }
        elseif ( ($item >= 67) && ($item <= 100) )
        {
            $class = 'positive';
        }
        else
        {
            $class = 'neutral';
        }
    }

    return $class;
}

/**
 * Switches the sorting order of a supplied array
 *
 * The array is in the format [number][0/1] (0 = the default, 1 = the opposite)
 * Returns an array containing the code to use in an SQL query and the code to
 * use to pass the sort value through the URI.  URI is in the format
 * (number).(0/1)
 *
 * Also contains checks to make sure the first element is not larger than the
 * sort_order array and that the second selement is either 0 or 1
 *
 * @param     array      $sort_order       Sorting order array
 * @return    array
 */
function switch_order($sort_order)
{
    global $in;

    $uri_order = $in->get(URI_ORDER, 0.0);
    $uri_order = explode('.', $uri_order);
    $element1 = ( isset($uri_order[0]) ) ? $uri_order[0] : 0;
    $element2 = ( isset($uri_order[1]) ) ? $uri_order[1] : 0;

    $array_size = count($sort_order);
    if ( $element1 > $array_size - 1 )
    {
        $element1 = $array_size - 1;
    }
    if ( $element2 > 1 )
    {
        $element2 = 0;
    }

    for ( $i = 0; $i < $array_size; $i++ )
    {
        if ( $element1 == $i )
        {
            $uri_element2 = ( $element2 == 0 ) ? 1 : 0;
        }
        else
        {
            $uri_element2 = 0;
        }
        $current_order['uri'][$i] = $i . '.' . $uri_element2;
    }

    $current_order['uri']['current'] = $element1.'.'.$element2;
    $current_order['sql'] = $sort_order[$element1][$element2];

    return $current_order;
}

/**
 * Returns a string with a list of available pages
 *
 * @param     string     $base_url         The starting URL for each page link
 * @param     int        $num_items        The number of items we're paging through
 * @param     int        $per_page         How many items to display per page
 * @param     int        $start_item       Which number are we starting on
 * @param     string     $start_variable   In case you need to call your _GET var something other than 'start'
 * @return    string
 */
function generate_pagination($base_url, $num_items, $per_page, $start_item, $start_variable='start')
{
    global $user;

    $total_pages = ceil($num_items / $per_page);

    if ( ($total_pages == 1) || (!$num_items) )
    {
        return '';
    }

    $uri_symbol = ( strpos($base_url, '?') ) ? '&amp;' : '?';

    $on_page = floor($start_item / $per_page) + 1;

    //��

    $pagination = '';
    $pagination = ( $on_page == 1 ) ? '<b>1</b>' : '<a href="'.$base_url . $uri_symbol . $start_variable.'='.( ($on_page - 2) * $per_page).'" title="'.$user->lang['previous_page'].'" class="copy">&lt;</a>&nbsp;&nbsp;<a href="'.$base_url.'" class="copy">1</a>';

    if ( $total_pages > 5 )
    {
        $start_count = min(max(1, $on_page - 6), $total_pages - 5);
        $end_count = max(min($total_pages, $on_page + 6), 5);

        $pagination .= ( $start_count > 1 ) ? ' ... ' : ' ';

        for ( $i = $start_count + 1; $i < $end_count; $i++ )
        {
            $pagination .= ($i == $on_page) ? '<b>'.$i.'</b> ' : '<a href="'.$base_url . $uri_symbol . $start_variable.'='.( ($i - 1) * $per_page).
                           '" title="'.$user->lang['page'].' '.$i.'" class="copy">'.$i.'</a>';
            if ( $i < $end_count - 1 )
            {
                $pagination .= ' ';
            }
        }

        $pagination .= ($end_count < $total_pages ) ? ' ... ' : ' ';
    }
    else
    {
        $pagination .= ' ';

        for ( $i = 2; $i < $total_pages; $i++ )
        {
            $pagination .= ($i == $on_page) ? '<b>'.$i.'</b> ' : '<a href="'.$base_url . $uri_symbol . $start_variable.'='.( ($i - 1) * $per_page).
                           '" title="'.$user->lang['page'].' '.$i.'" class="copy">'.$i.'</a> ';
            if ( $i < $total_pages )
            {
                $pagination .= ' ';
            }
        }
    }

    $pagination .= ( $on_page == $total_pages ) ? '<b>'.$total_pages.'</b>' : '<a href="'.$base_url . $uri_symbol . $start_variable.'='.(($total_pages - 1) * $per_page) . '" class="copy">'.$total_pages.'</a>&nbsp;&nbsp;<a href="'.$base_url.'&amp;'.$start_variable.'='.($on_page * $per_page).
                   '" title="'.$user->lang['next_page'].'" class="copy">&gt;</a>';

    return $pagination;
}

/**
 * Redirects the user to another page and exits cleanly
 *
 * @param     string     $page         Page to redirect to
 * @param     bool       $return       Whether to return the generated redirect url (true) or just redirect to the page (false)
 * @return    mixed                    null, else the parsed redirect url if return is true.
 */
function redirect($page, $return = false)
{
    $server_name = (!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : getenv('SERVER_NAME');
    $server_port = (!empty($_SERVER['SERVER_PORT'])) ? (int) $_SERVER['SERVER_PORT'] : (int) getenv('SERVER_PORT');
    $secure      = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 1 : 0;

    $script_name = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
    if (!$script_name)
    {
        $script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
    }

    // Replace backslashes and doubled slashes (could happen on some proxy setups)
    $script_name = str_replace(array('\\', '//'), '/', $script_name);
    $script_path = trim(dirname($script_name));

    $url = (($secure) ? 'https://' : 'http://') . $server_name;

    if ($server_port && (($secure && $server_port <> 443) || (!$secure && $server_port <> 80)))
    {
        $url .= ':' . $server_port;
    }

    $url .= $script_path . '/' . str_replace('&amp;', '&', $page);
    
    if ( $return )
    {
        return $url;
    }
    else
    {
    	header('Location: ' . $url);
    	exit;
	}
}

/**
 * Perform a clean redirect via meta refresh after a defined delay
 *
 * @param string $time Time delay
 * @param string $url URL to redirect to
 * @return void
 */
function meta_refresh($time, $url)
{
    global $tpl;

    $tpl->assign_vars(array(
        'META' => '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '" />'
    ));
}

/**
 * PHP5 Compatibility functions
 */

/**
 * Takes two or more arrays and removes any entries from the first array whose key values
 * are present in any of the other arrays.
 * 
 * http://au.php.net/manual/en/function.array-diff-key.php#76100
 *
 * @return array
 */
if (!function_exists("array_diff_key"))
{
    function array_diff_key()
    {
        $arrs = func_get_args();
        $result = array_shift($arrs);
        foreach ($arrs as $array)
        {
            foreach ($result as $key => $v)
            {
                if (array_key_exists($key, $array))
                {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}
//gehFUNCTIONS
/**
 * Builds an array for class names, icons, and URLs to be used as a filter
 *
 * @return  mixed      array    I_CLASS = icon name without extension, CLASS = string, U_FILTER = url
 */
function getClassFilterOptions ($current_filter='') {

    global $db, $in;

//    $filter = $in->get('filter');
//    if ( empty($filter) ) {
//        $filter_by = '';
//    } else {
    if ($current_filter != '') {
        $input = $db->sql_escape($in->get('filter'));
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

    return $filter_options;
}

/**
 * URL Handler handles URL input / output operations
 * @subpackage CTRTAdmin
 */
class URLHandler
{
    function URLHandler() {}

	/**
	 * [code pulled from ItemStats by Yahourt (http://itemstats.free.fr/)
	 * Attempts to read the specified url and returns it as a string.
	 */ 
	function read($url)
	{        
		// Try cURL first. If that isn't available, check if we're allowed to
		// use fopen on URLs.  If that doesn't work, just die.
		if (function_exists('curl_init'))
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			@curl_setopt($ch, CURLOPT_HEADER, 0);
			@curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1?)");
			@curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
			$html_data = curl_exec($ch);
			@curl_close($ch);
		}
		else if (ini_get('allow_url_fopen') == 1)
		{
			$html_data = @file_get_contents($url);
		}
		else
		{
			// Thanks to Aki Uusitalo
			$url_array = parse_url($url);
	
			$fp = fsockopen($url_array['host'], 80, $errno, $errstr, 5); 
	
			if (!fp)
			{
				die("cURL isn't installed, 'allow_url_fopen' isn't set and socket opening failed. Socket failed because: <br /><br /> $errstr ($errno)");
	
			}
			else
			{
				$out = "GET " . $url_array[path] . "?" . $url_array[query] ." HTTP/1.0\r\n";
				$out .= "Host: " . $url_array[host] . " \r\n";
				$out .= "Connection: Close\r\n\r\n";
	
				fwrite($fp, $out);
	
				// Get rid of the HTTP headers
				while ($fp && !feof($fp))
				{
					$headerbuffer = fgets($fp, 1024);
					if (urlencode($headerbuffer) == "%0D%0A")
					{
						// We've reached the end of the headers
						break;
					}
				}
	
				$html_data = '';
				// Read the raw data from the socket in 1kb chunks
				// Hopefully, it's just HTML.
				
				while (!feof($fp))
				{
					$html_data .= fgets($fp, 1024);
				}
				fclose($fp);
			}        
		}
		return $html_data;
	}
}

function getClassCSS () {
	global $gm, $user;
	
	$css = '';
    $gamedata = $gm->get_game_data();

	if ( $gamedata !== false ) {
		foreach ($gamedata['data']['classes'] as $class_key => $class_data) {
	
			$cname = strtolower($class_key);
			$cname = str_replace("_","-",$cname);
			
// code from Daz
//			$css .= "." . $cname . "{color:#" . $class_data['color'] . ";}" . "\n";
			$css .= ".{$cname}, .{$cname}:link, .{$cname}:visited, .{$cname}:active {text-decoration: none; font-family: {$user->style['fontface1']}; font-size: {$user->style['fontsize2']}px; color: #{$class_data['color']}; }\n";
			$css .= ".{$cname}:link:hover		{ text-decoration: underline; font-family: {$user->style['fontface1']}; font-size: {$user->style['fontsize2']}px; color: #{$class_data['color']}; }\n";
//			$css .= "td.{$cname}, td.{$cname} a:link, td.{$cname} a:visited, td.{$cname} a:active {text-decoration: none; font-family: {$user->style['fontface1']}; font-size: {$user->style['fontsize2']}px; color: #{$class_data['color']}; }\n";
//			$css .= "td.{$cname} a:hover { text-decoration: underline; font-family: {$user->style['fontface1']}; font-size: {$user->style['fontsize2']}px; color: #{$class_data['color']}; }\n\n";
		}
	}	
	return($css);
}
//gehEND
?>