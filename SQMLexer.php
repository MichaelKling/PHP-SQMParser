<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 09:56
 */
class SQMLexer
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

    protected static $_tokens = array(
        "/^(\s+)/" => SQMLexer::T_SPACE,
        "/^(class)/" => SQMLexer::T_CLASS,
        "/^(=)/" => SQMLexer::T_ASSIGNMENT,
        "/^({)/" => SQMLexer::T_BLOCKSTART,
        "/^(})/" => SQMLexer::T_BLOCKEND,
        "/^(\[\])/" => SQMLexer::T_ARRAY,
        "/^(,)/" => SQMLexer::T_COMMA,
        "/^(;)/" => SQMLexer::T_SEMICOLON,
        "/^([a-zA-Z_][a-zA-Z0-9_]*)/" => SQMLexer::T_IDENTIFIER,
        "/^([-+]?(([0-9]*)\.([0-9]+)))/" => SQMLexer::T_FLOAT,
        "/^([-+]?([0-9]+))/" => SQMLexer::T_INTEGER,
        '/^(".*")/' => SQMLexer::T_STRING,
    );

    public static function tokenToName($token) {
        switch ($token) {
            case T_SPACE : "T_SPACE"; break;
            case T_CLASS : "T_CLASS"; break;
            case T_ASSIGNMENT : "T_ASSIGNMENT"; break;
            case T_BLOCKSTART : "T_BLOCKEND"; break;
            case T_BLOCKEND : "T_BLOCKEND"; break;
            case T_ARRAY : "T_ARRAY"; break;
            case T_COMMA : "T_COMMA"; break;
            case T_SEMICOLON : "T_SEMICOLON"; break;
            case T_IDENTIFIER : "T_IDENTIFIER"; break;
            case T_FLOAT : "T_FLOAT"; break;
            case T_INTEGER : "T_INTEGER"; break;
            case T_STRING : "T_STRING"; break;
        }
    }

    public static function run($source) {
         $tokens = array();

        $number = 0;
        while (($line = fgets($source))) {
            $offset = 0;

            while($offset < strlen($line)) {
                $result = static::_match($line, $number, $offset);
                if($result === false) {
                    throw new Exception("Unable to parse line " . ($number+1) . ":$offset near \"$line\" at ". substr($line, $offset) .".");
                }
                if ($result['token'] != SQMLexer::T_SPACE) {
                    $tokens[] = $result;
                }
                $offset += strlen($result['match']);
            }

            $number++;
        }

        return $tokens;
    }

    protected static function _match($line, $number, $offset) {
        $string = substr($line, $offset);

        foreach(static::$_tokens as $pattern => $name) {

            if ($name == SQMLexer::T_STRING) {
                //The escape sequence is very similar to the string end and generates therefore a huge stack using preg_match...
                if ($string != "") {
                    $string = str_replace('""','ESCAPEDSTRING2l215ll123IOh3',$string);
                }
            }
            if(preg_match($pattern, $string, $matches)) {
                if ($name == SQMLexer::T_STRING) {
                    //The escape sequence is very similar to the string end and generates therefore a huge stack using preg_match...
                    //Also deleting string quotes, as we directly overtake this as a php string
                    if ($matches[1] != "") {
                        $matches[1] = str_replace('ESCAPEDSTRING2l215ll123IOh3','""',$matches[1]);
                    }
                }
                return array(
                    'match' => $matches[1],
                    'token' => $name,
                    'line' => $number+1
                );
            }
        }

        return false;
    }
}
