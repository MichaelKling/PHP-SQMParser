<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 16:02
 */
class SQMPlayerParser
{
    protected $rawData = array();
    protected $parsedData = array();

    public function __construct($sqmFile)
    {
        $this->rawData = $sqmFile->parsedData;
    }

    public function parse($reduce = false) {
        if (empty($this->parsedData)) {
            $sites =  array_flip(SQMLibrary::$sites);
            foreach($sites as $key => $site ) {
                $this->parsedData[$key] = array();
            }

            $mission = $this->rawData['Mission'];

            if (isset($mission->Groups) && !empty($mission->Groups)) {
                $this->_parseGroups($mission->Groups);
            }
            if (isset($mission->Vehicles) && !empty($mission->Vehicles)) {
                $newGroup = array(
                    "name" => SQMLibrary::indexToNatoAlphabet(count($this->parsedData['Civil'])),
                    "squadId" => count($this->parsedData['Civil']),
                    "slots" => array(),
                );

                $this->_parseVehicles($mission->Vehicles,$newGroup);

                $this->parsedData['Civil'][] = $newGroup;
            }
        }
        if ($reduce) {
            $this->_reduceResults();
        }
        return $this->parsedData;
    }

    protected function _parseGroups($groups) {
        $groups = (array)$groups;
        foreach ($groups as $group) {
            if (isset($group->side)) {
                $side = SQMLibrary::sideCodeToName($group->side);
            } else {
                $side = 'Civil';
            }
            if (isset($group->Vehicles)) {
                $newGroup = array(
                    "name" => SQMLibrary::indexToNatoAlphabet(count($this->parsedData[$side])),
                    "squadId" => count($this->parsedData[$side]),
                    "slots" => array(),
                );

                $this->_parseVehicles($group->Vehicles,$newGroup);

                $this->parsedData[$side][] = $newGroup;
            }
        }
    }

    protected function _parseVehicles($vehicles,&$currentGroup) {
        $vehicles = (array)$vehicles;
        $counter = 0;
        foreach ($vehicles as $vehicle) {
            if (is_object($vehicle)) {
                if (isset($vehicle->player)) {
                    $isVehicle = true;
                    $classname = SQMLibrary::classVehicleToName($vehicle->vehicle);
                    if (!$classname) {
                        $isVehicle = false;
                        $classname = SQMLibrary::classMenToName($vehicle->vehicle);
                    }

                    $rank = (isset($vehicle->rank))?$vehicle->rank:"PRIVATE";
                    $slot = array(
                        'id' => $vehicle->id,
                        'groupId' => $counter,
                        'rank' => $rank,
                        'rankName' => SQMLibrary::rankToName($rank),
                        'rankShortName' => SQMLibrary::rankToShortname($rank),
                        'class' => $vehicle->vehicle,
                        'classname' => $classname,
                        'isLeader' => (isset($vehicle->leader)&&($vehicle->leader == 1)),
                        'description' => (isset($vehicle->description)?($vehicle->description):""),
                        'position' => "",
                    );

                    if ($isVehicle) {
                        $positions = SQMLibrary::playerRoleToRoles($vehicle->player);
                        foreach ($positions as $position) {
                            $slot['position'] = $position;
                            $currentGroup['slots'][] = $slot;
                        }
                    } else {
                        $currentGroup['slots'][] = $slot;
                    }
                }
                $counter++;
            }
        }
    }

    protected function _reduceResults() {
        $copy = $this->parsedData;
        //Deleting empty groups
        foreach ($copy as $siteId => $site) {
            foreach ($copy[$siteId] as $groupId => $group) {
                if (count($group['slots']) == 0) {
                    unset($this->parsedData[$siteId][$groupId]);
                }
            }

            if (count($this->parsedData[$siteId]) == 0) {
                unset($this->parsedData[$siteId]);
            }
        }
    }
    
}
