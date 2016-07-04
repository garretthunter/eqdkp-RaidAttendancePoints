<?php
//geh ALTERNATES RAIDGROUPS
/**
* Return the icon name & location for race & class icons. We use the same
* naming convention as blizzard to hopefully ease the update of the icons
* as they evolve
*
* @param $race string
* @param $class string
* @param $gender string
* @param $level integer
*/
function getPlayerIcons ($race="unknown",$class="unknown",$gender="male",$level="1") {

	/**
	 * Location of level 0-59 character portraits
	 */
	$icon_dir = "wow-default";
	
	/**
	 * IDs are from Blizzard
	 */
	$validGender = array ('male' => 0,
						   'female' => 1);

	/**
	 * IDs are from Blizzard
	 */
	$validRace = array ('human' => 1,
						 'orc' => 2,
						 'dwarf' => 3,
						 'night elf' => 4,
						 'undead' => 5,
						 'tauren' => 6,
						 'gnome' => 7,
						 'troll' => 8,
						 'blood elf' => 10,
						 'draenei' => 11,
						 'unknown' => 'Unknown');
	
	/**
	 * IDs are from Blizzard
	 */
	$validClass = array('warrior' => 1,
						'paladin' => 2,
						'hunter' => 3,
						'rogue' => 4,
						'priest' => 5,
						'shaman' => 7,
						'mage' => 8,
						'warlock' => 9,
						'druid' => 11,
						'unknown' => 'Unknown');
	
	/**
	 * Blizzard stores icons in different directories based on level
	 */
	if ($level >= 60 && $level < 70) {
		$icon_dir = "wow";
	} elseif ($level == 70) {
		$icon_dir = "wow-70";
	}
	
	$playerIcons = array('race' => $validRace[strtolower($race)]."-".$validGender[strtolower($gender)],
						 'class' => $validClass[strtolower($class)],
						 'portrait' => $validGender[strtolower($gender)]."-".$validRace[strtolower($race)]."-".$validClass[strtolower($class)],
						 'icon_dir' => $icon_dir,
						 );
	
	return $playerIcons;

} //end getPlayerIcons
//geh ALTERNATES RAIDGROUPS
?>
