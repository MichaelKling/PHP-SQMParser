<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 23.06.13
 * Time: 10:23
 */
class SQMTokenItem
{
    const T_CLASS = T_CLASS;
    const T_ASSIGNMENT =  "=";
    const T_BLOCKSTART = "{";
    const T_BLOCKEND = "}";
    const T_ARRAY = "[]";
    const T_COMMA = ",";
    const T_SEMICOLON = ";";
    const T_IDENTIFIER = T_STRING;
    const T_FLOAT = T_DNUMBER;
    const T_INTEGER = T_LNUMBER;
    const T_STRING = T_CONSTANT_ENCAPSED_STRING;

    //This tokens will be eliminated by the parser
    const T_MINUS = "-";
    const T_SPACE = T_WHITESPACE;
    const T_ARRAYSTART = "[";
    const T_ARRAYEND = "]";

    const INDEX_TOKEN = 0;
    const INDEX_MATCH = 1;
    const INDEX_LINE = 2;
    const INDEX_LENGTH = 3;


    public static function tokenToName(&$token) {
        $output = (is_array($token)?$token[SQMTokenItem::INDEX_TOKEN]:$token);
        switch ($output) {
            case SQMTokenItem::T_SPACE : return "T_SPACE"; break;
            case SQMTokenItem::T_CLASS : return "T_CLASS"; break;
            case SQMTokenItem::T_ASSIGNMENT : return "T_ASSIGNMENT"; break;
            case SQMTokenItem::T_BLOCKSTART : return "T_BLOCKEND"; break;
            case SQMTokenItem::T_BLOCKEND : return "T_BLOCKEND"; break;
            case SQMTokenItem::T_ARRAY : return "T_ARRAY"; break;
            case SQMTokenItem::T_COMMA : return "T_COMMA"; break;
            case SQMTokenItem::T_SEMICOLON : return "T_SEMICOLON"; break;
            case SQMTokenItem::T_IDENTIFIER : return "T_IDENTIFIER"; break;
            case SQMTokenItem::T_FLOAT : return "T_FLOAT"; break;
            case SQMTokenItem::T_INTEGER : return "T_INTEGER"; break;
            case SQMTokenItem::T_STRING : return "T_STRING"; break;
            default: return "UNKNOWN";
        }
    }

    public static function &construct($match,$length,$token,$line) {
        $output = array(
            SQMTokenItem::INDEX_TOKEN => $token,
            SQMTokenItem::INDEX_MATCH => $match,
            SQMTokenItem::INDEX_LINE => $line,
            SQMTokenItem::INDEX_LENGTH => $length,
        );
        return $output;
    }
}
