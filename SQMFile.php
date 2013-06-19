<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:44
 */
class SQMFile
{
    public $path = "";
    public $filename = "";
    public $hash = "";
    public $rawData = "";
    public $parsedData = array();
    public $player = array();

    function SQMFile($file) {
        $this->path = $file;
        $this->filename = basename($file);
        $this->rawData = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        $this->hash = md5($this->rawData);
    }

    function parse() {
        $this->parsedData = SQMParser::parse($this);
    }

    function searchPlayableSlots($reduce = false) {
        $this->player = array();
        if (!empty($this->parsedData)) {
            $playerParser = new SQMPlayerParser($this);
            $this->player = $playerParser->parse($reduce);
        }


        return $this->player;
    }
}
