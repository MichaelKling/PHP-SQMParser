<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:44
 */
class SQMFile
{
    const INPUTTYPE_FILENAME = 0x01;
    const INPUTTYPE_STRING = 0x02;
    const INPUTTYPE_RESSOURCE = 0x02;

    public $path = "";
    public $filename = "";
    public $hash = "";
    public $content = null;
    public $parsedData = array();
    public $player = array();

    function SQMFile($file,$inputType = SQMFile::INPUTTYPE_FILENAME) {
        switch ($inputType) {
            case SQMFile::INPUTTYPE_FILENAME :
                $this->path = $file;
                $this->filename = basename($file);;
                $this->content = fopen($file,"r");
                break;
            case SQMFile::INPUTTYPE_RESSOURCE :
                $this->path = "RESSOURCE";
                $this->filename = "";
                $this->content = $file;
                break;
            case SQMFile::INPUTTYPE_STRING :
            default:
                $this->path = "STRING";
                $this->filename = "";
                $this->content = fopen("php://memory","r+");
                fputs($this->content,$file,strlen($file));
                break;
        }
        rewind($this->content);
        $this->hash = md5(stream_get_contents($this->content));
        rewind($this->content);
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

    public function __destruct()
    {
        if ($this->content) {
            fclose($this->content);
            $this->content = null;
        }
    }
}
