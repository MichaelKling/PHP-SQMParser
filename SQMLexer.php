<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 09:56
 */
class SQMLexer
{
    protected static $_tokens = array(
        "/^(\s+)/" => "T_SPACE",
        "/^(class)/" => "T_CLASS",
        "/^(=)/" => "T_ASSIGNMENT",
        "/^({)/" => "T_BLOCKSTART",
        "/^(})/" => "T_BLOCKEND",
        "/^(\[\])/" => "T_ARRAY",
        "/^(,)/" => "T_COMMA",
        "/^(;)/" => "T_SEMICOLON",
        "/^([a-zA-Z_][a-zA-Z0-9_]*)/" => "T_IDENTIFIER",
        "/^([-+]?(([0-9]*)\.([0-9]+)))/" => "T_FLOAT",
        "/^([-+]?([0-9]+))/" => "T_INTEGER",
        '/^(".*")/' => "T_STRING",
    );
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
                if ($result['token'] != 'T_SPACE') {
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

            if ($name == 'T_STRING') {
                //The escape sequence is very similar to the string end and generates therefore a huge stack using preg_match...
                if ($string != "") {
                    $string = str_replace('""','ESCAPEDSTRING2l215ll123IOh3',$string);
                }
            }
            if(preg_match($pattern, $string, $matches)) {
                if ($name == 'T_STRING') {
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
