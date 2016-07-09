<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        game_wow.php
 * Began:       Thu Nov 15 2007
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     games
 * @version     $Rev: 516 $
 */

if (!defined('EQDKP_INC') || !defined('IN_GAME_MANAGER'))
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

$game_info = array(
    'id'        => 'wow',
    'name'      => 'World of Warcraft',
    'shortname' => 'WoW',
    'version'   => '4.0',
    'max_level' => 85,

    'available' => array(
        'armor_types' => true,
        'classes'     => true,
        'class_armor' => true,
        'factions'    => true,
        'races'       => true,

        'professions' => false,
        'parsing'     => true,
    ),
);


if (!isset($get_gameinfo))
{
    $game_data = array(
        // Armor types
        'armor_types'  => array(
            'cloth'        => array('id' => 1, 'name' => 'Cloth'),
            'leather'      => array('id' => 2, 'name' => 'Leather'),
            'chain'        => array('id' => 3, 'name' => 'Chain'),
            'plate'        => array('id' => 4, 'name' => 'Plate'),
        ),

        // Classes
        'classes'      => array(
            'druid'    => array(
                'id'       => 1,
				'blizz_id' => 11,
                'name'     => 'Druid',
                'color'    => 'FF7D0A',
            ),
            'hunter'   => array(
                'id'       => 2,
				'blizz_id' => 3,
                'name'     => 'Hunter',
                'color'    => 'ABD473',
            ),
            'mage'     => array(
                'id'       => 3,
				'blizz_id' => 8,
                'name'     => 'Mage',
                'color'    => '69CCF0',
            ),
            'paladin'  => array(
                'id'       => 4,
				'blizz_id' => 2,
                'name'     => 'Paladin',
                'color'    => 'F58CBA',
            ),
            'priest'   => array(
                'id'       => 5,
				'blizz_id' => 5,
                'name'     => 'Priest',
                'color'    => 'FFFFFF',
            ),
            'rogue'    => array(
                'id'       => 6,
				'blizz_id' => 4,
                'name'     => 'Rogue',
                'color'    => 'FFF569',
            ),
            'shaman'   => array(
                'id'       => 7,
				'blizz_id' => 7,
                'name'     => 'Shaman',
                'color'    => '2459FF',
            ),
            'warlock'  => array(
                'id'       => 8,
				'blizz_id' => 9,
                'name'     => 'Warlock',
                'color'    => '9482CA',
            ),
            'warrior'  => array(
                'id'       => 9,
				'blizz_id' => 1,
                'name'     => 'Warrior',
                'color'    => 'C79C6E',
            ),
            'death_knight'    => array(
                'id'       => 10,
				'blizz_id' => 6,
                'name'     => 'Death Knight',
                'color'    => 'C41E3B',
            ),
            'unknown'    => array(
                'id'       => 0,
				'blizz_id' => 0,
                'name'     => 'Unknown',
                'color'    => '',
                'css'      => 'Unknown',
            ),
        ),

        // Class-Armor mappings
        'class_armor'  => array(
            array('class' => 'death_knight', 'armor' => 'plate'),
            array('class' => 'druid',   'armor' => 'leather'),
            array('class' => 'hunter',  'armor' => 'leather'),
            array('class' => 'hunter',  'armor' => 'chain', 'min' => 40),
            array('class' => 'mage',    'armor' => 'cloth'),
            array('class' => 'paladin', 'armor' => 'chain'),
            array('class' => 'paladin', 'armor' => 'plate', 'min' => 40),
            array('class' => 'priest',  'armor' => 'cloth'),
            array('class' => 'rogue',   'armor' => 'leather'),
            array('class' => 'shaman',  'armor' => 'leather'),
            array('class' => 'shaman',  'armor' => 'chain', 'min' => 40),
            array('class' => 'warlock', 'armor' => 'cloth'),
            array('class' => 'warrior', 'armor' => 'chain'),
            array('class' => 'warrior', 'armor' => 'plate', 'min' => 40),
        ),

        // Factions
        'factions'     => array(
            'alliance' => array('id' => 1, 'name' => 'Alliance', 'races' => array('human','draenei','dwarf','gnome','night_elf','worgen')),
            'horde'    => array('id' => 2, 'name' => 'Horde', 'races' => array('blood_elf','orc','tauren','troll','undead','goblin')),
        ),

        // Races
        'races'        => array(
            'blood_elf'  => array(
                'id'       => 10,
				'blizz_id' => 10,
                'name'     => 'Blood Elf',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'hunter', 'mage', 'paladin', 'priest', 'rogue', 'warlock', 'warrior')
            ),
            'human'    => array(
                'id'       => 2,
				'blizz_id' => 1,
                'name'     => 'Human',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'hunter', 'mage', 'paladin', 'priest', 'rogue', 'warlock', 'warrior')
            ),
            'draenei'  => array(
                'id'       => 9,
				'blizz_id' => 11,
                'name'     => 'Draenei',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'hunter', 'mage', 'paladin', 'priest', 'shaman', 'warrior')
            ),
            'dwarf'    => array(
                'id'       => 3,
				'blizz_id' => 3,
                'name'     => 'Dwarf',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'hunter', 'mage', 'priest', 'rogue', 'warrior')
            ),
            'gnome'    => array(
                'id'       => 1,
				'blizz_id' => 7,
                'name'     => 'Gnome',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'mage', 'priest', 'rogue', 'warlock', 'warrior')
            ),
            'night_elf'  => array(
                'id'       => 4,
				'blizz_id' => 4,
                'name'     => 'Night Elf',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'druid', 'hunter', 'mage', 'priest', 'rogue', 'warrior')
            ),
            'orc'      => array(
                'id'       => 7,
				'blizz_id' => 2,
                'name'     => 'Orc',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'hunter', 'mage', 'rogue', 'shaman', 'warlock', 'warrior')
            ),
            'tauren'   => array(
                'id'       => 8,
				'blizz_id' => 6,
                'name'     => 'Tauren',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'druid', 'hunter', 'paladin', 'priest', 'shaman', 'warrior')
            ),
            'troll'    => array(
                'id'       => 5,
				'blizz_id' => 8,
                'name'     => 'Troll',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'druid', 'hunter', 'mage', 'priest', 'rogue', 'shaman', 'warrior')
            ),
            'undead'   => array(
                'id'       => 6,
				'blizz_id' => 5,
                'name'     => 'Undead',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'hunter', 'mage', 'priest', 'rogue', 'warlock', 'warrior')
            ),
            'worgen'   => array(
                'id'       => 12,
				'blizz_id' => 22,
                'name'     => 'Worgen',
                'faction'  => 'alliance',
                'classes'  => array('death_knight', 'druid', 'hunter', 'mage', 'priest', 'rogue', 'warlock', 'warrior')
            ),
            'goblin'   => array(
                'id'       => 13,
				'blizz_id' => 9,
                'name'     => 'Goblin',
                'faction'  => 'horde',
                'classes'  => array('death_knight', 'hunter', 'mage', 'priest', 'rogue', 'shaman', 'warlock', 'warrior')
            ),
            'unknown'  => array(
                'id'       => 0,
				'blizz_id' => 0,
                'name'     => 'Unknown',
                'faction'  => '',
                'classes'  => array()
            ),
        ),

//gehSTART - wow avatar customizations
        // Gender Types
        'genders'  => array(
            'male'        => array('id' => 0, 'blizz_id' => 0, 'name' => 'Male'),
            'female'      => array('id' => 1, 'blizz_id' => 1, 'name' => 'Female'),
        ),
//gehEND
        'parsing'      => array(
            //[Dazza]: Level 60 Night Elf Priest <Banimal> - Winterspring
            //[Kamien]: Level 70 Undead Rogue <Juggernaut> - Black Temple
            '[__name__]: Level __level__ __race__ __class__? <__guild__>?? - __zone__?',
        ),
    );
}
?>