<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 23.06.13
 * Time: 10:23
 */
class SQMTokenItem
{
    const T_SPACE = 0x01;
    const T_CLASS = 0x02;
    const T_ASSIGNMENT = 0x03;
    const T_BLOCKSTART = 0x04;
    const T_BLOCKEND = 0x05;
    const T_ARRAY = 0x06;
    const T_COMMA = 0x07;
    const T_SEMICOLON = 0x08;
    const T_IDENTIFIER = 0x09;
    const T_FLOAT = 0x0A;
    const T_INTEGER = 0x0B;
    const T_STRING = 0x0C;

    public function tokenToName() {
        switch ($this->token) {
            case SQMLexer::T_SPACE : return "T_SPACE"; break;
            case SQMLexer::T_CLASS : return "T_CLASS"; break;
            case SQMLexer::T_ASSIGNMENT : return "T_ASSIGNMENT"; break;
            case SQMLexer::T_BLOCKSTART : return "T_BLOCKEND"; break;
            case SQMLexer::T_BLOCKEND : return "T_BLOCKEND"; break;
            case SQMLexer::T_ARRAY : return "T_ARRAY"; break;
            case SQMLexer::T_COMMA : return "T_COMMA"; break;
            case SQMLexer::T_SEMICOLON : return "T_SEMICOLON"; break;
            case SQMLexer::T_IDENTIFIER : return "T_IDENTIFIER"; break;
            case SQMLexer::T_FLOAT : return "T_FLOAT"; break;
            case SQMLexer::T_INTEGER : return "T_INTEGER"; break;
            case SQMLexer::T_STRING : return "T_STRING"; break;
            default: return "UNKNOWN";
        }
    }

    public function __construct($match,$char,$token,$line) {
        $this->match = $match;
        $this->char = $char;
        $this->token = $token;
        $this->line = $line;
    }
    public $match = null;
    public $char = 1;
    public $token = SQMTokenItem::T_SPACE;
    public $line = 0;


    public $next = null;
}
