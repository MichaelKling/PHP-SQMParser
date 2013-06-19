<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 15:40
 */
class SQMLibrary
{
    public static $natoAlphabet = array(
        'Alfa',
        'Bravo',
        'Charlie',
        'Delta',
        'Echo',
        'Foxtrot',
        'Golf',
        'Hotel',
        'India',
        'Juliett',
        'Kilo',
        'Limo',
        'Mike',
        'November',
        'Oscar',
        'Papa',
        'Quebec',
        'Romeo',
        'Sierra',
        'Tango',
        'Uniform',
        'Victor',
        'Whiskey',
        'X-ray',
        'Yankee',
        'Zulu',
    );

    public static $sites = array(
        'WEST' => 'BLUFOR',
        'EAST' => 'OPFOR',
        'GUER' => 'Independend',
        'LOGIC' => 'Civil',
        'CIVI' => 'Civil',
        'CIV' => 'Civil',
        'AMBIENT LIFE' => 'Civil',
    );

    public static $playerRoles = array(
        'PLAY CDG' => array(
            'Commander',
            'Driver',
            'Gunner'
        ),
        'PLAY CG' => array(
            'Commander',
            'Gunner'
        ),
        'PLAY CD' => array(
            'Commander',
            'Driver',
        ),
        'PLAY DG' => array(
            'Driver',
            'Gunner'
        ),
        'PLAYER DRIVER' => array(
            'Driver',
        ),
        'PLAY D' => array(
            'Driver',
        ),
        'PLAYER GUNNER' => array(
            'Gunner'
        ),
        'PLAY G' => array(
            'Gunner'
        ),
        'PLAYER COMMANDER' => array(
            'Commander',
        ),
        'PLAY C' => array(
            'Commander',
        ),
    );

    public static $ranks = array(
      'PRIVATE' => 'Private',
      'CORPORAL' => 'Corporal',
      'SERGEANT' => 'Sergeant',
      'LIEUTENANT' => 'Lieutnant',
      'CAPTAIN' => 'Captain',
      'MAJOR' => 'Major',
      'COLONEL' => 'Colonel',
    );

    public static $ranksShort = array(
        'PRIVATE' => 'Pvt.',
        'CORPORAL' => 'Cpl.',
        'SERGEANT' => 'Sgt.',
        'LIEUTENANT' => 'Ltn.',
        'CAPTAIN' => 'Ctn.',
        'MAJOR' => 'Mjr.',
        'COLONEL' => 'Cnl',
    );

    public static $classNamesMen = array();
    public static $classNamesVehicles = array();

    public static function indexToNatoAlphabet($index) {
        $count = count(SQMLibrary::$natoAlphabet);
        $prefix = "";
        if ($index > $count) {
            $prefix = (floor($index/$count)+1)."-";
        }
        $index = $index%$count;
        return $prefix.SQMLibrary::$natoAlphabet[$index];
    }

    public static function sideCodeToName($code) {
        if (isset(SQMLibrary::$sites[$code])) {
            return SQMLibrary::$sites[$code];
        } else {
            return $code;
        }
    }

    public static function rankToName($rank) {
        if (isset(SQMLibrary::$ranks[$rank])) {
            return SQMLibrary::$ranks[$rank];
        } else {
            return $rank;
        }
    }

    public static function rankToShortname($rank) {
        if (isset(SQMLibrary::$ranksShort[$rank])) {
            return SQMLibrary::$ranksShort[$rank];
        } else {
            return substr($rank,0,3).'.';
        }
    }

    public static function playerRoleToRoles($role) {
        if (isset(SQMLibrary::$playerRoles[$role])) {
            return SQMLibrary::$playerRoles[$role];
        } else {
            return $role;
        }
    }

    public static function classMenToName($class) {
        if (empty(SQMLibrary::$classNamesMen)) {
            SQMLibrary::_loadClassnameMen();
        }
        if (isset(SQMLibrary::$classNamesMen[$class])) {
            return SQMLibrary::$classNamesMen[$class];
        } else {
            return false;
        }
    }

    public static function classVehicleToName($class) {
        if (empty(SQMLibrary::$classNamesVehicles)) {
            SQMLibrary::_loadClassnameVehicles();
        }
        if (isset(SQMLibrary::$classNamesVehicles[$class])) {
            return SQMLibrary::$classNamesVehicles[$class];
        } else {
            return false;
        }
    }

    public static function _loadClassnameMen() {
        SQMLibrary::$classNamesMen = array();
        $result = array();
        $csvFiles = SQMLibrary::_getFileList("classnames/men/","csv");
        $jsonFiles = SQMLibrary::_getFileList("classnames/men/","json");

        foreach($csvFiles as $file)
        {
            $data = SQMLibrary::_parseCSV($file);
            $result = $data + $result;
        }

        foreach($jsonFiles as $file)
        {
            $data = SQMLibrary::_parseJSON($file);
            $result = $data + $result;
        }

        SQMLibrary::$classNamesMen = $result;
    }

    public static function _loadClassnameVehicles() {
        SQMLibrary::$classNamesVehicles = array();
        $result = array();
        $csvFiles = SQMLibrary::_getFileList("classnames/vehicle/","csv");
        $jsonFiles = SQMLibrary::_getFileList("classnames/vehicle/","json");


        foreach($csvFiles as $file)
        {
            $data = SQMLibrary::_parseCSV($file);
            $result = $data + $result;
        }

        foreach($jsonFiles as $file)
        {
            $data = SQMLibrary::_parseJSON($file);
            $result = $data + $result;
        }

        SQMLibrary::$classNamesVehicles = $result;
    }

    public static function _getFileList($dir,$ext) {
        return glob(SQMPARSER_BASE.$dir."*.".$ext);
    }

    public static function _parseCSV($file) {
        $result = array();
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $num = count($data);
                if ($num >= 2) {
                    $result[$data[0]] = $data[1];
                }
            }
            fclose($handle);
        }
        return $result;
    }

    public static function _parseJSON($file) {
        $result = array();

        $string = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode(file_get_contents($file)));
        $json=json_decode($string,true);

        if (is_array($json)) {
            foreach($json as $item) {
                if (isset($item['name']) && isset($item['displayname'])) {
                    $result[$item['name']] = $item['displayname'];
                }
            }
        }
        return $result;
    }
}
