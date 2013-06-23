<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 09:56
 */

require_once SQMPARSER_BASE . 'SQMLexer/SQMTokenItem.php';

class SQMLexer
{

    protected static $_tokens = array(
        "/^(\s+)/" => SQMTokenItem::T_SPACE,
        "/^([a-zA-Z_][a-zA-Z0-9_]*)/" => SQMTokenItem::T_IDENTIFIER,
        "/^([-+]?(([0-9]*)\.([0-9]+)))/" => SQMTokenItem::T_FLOAT,
        "/^([-+]?([0-9]+))/" => SQMTokenItem::T_INTEGER,
        '/^(".*")/' => SQMTokenItem::T_STRING,
    );


    public static function run($source) {
         $tokens = array();

        $number = 0;
        while (($line = fgets($source))) {
            $number++;
            $offset = 0;
            $lineLength = strlen($line);
            while($offset < $lineLength) {
                $result = SQMLexer::_match($line, $number, $offset);
                if($result === false) {
                    throw new Exception("Unable to parse line " . ($number+1) . ":$offset near \"$line\" - next key is '".$line[0]."' '".bin2hex($line[0])."'.");
                }
                if ($result['token'] != SQMTokenItem::T_SPACE) {
                    $tokens[] = $result;
                }
                $length = $result['chars'];
                $line = substr($line, $length);
                $offset += $length;
            }
        }

        return $tokens;
    }

    protected static function _match($string, $number) {
        //Try first fix matches:
        switch ($string[0]) {
            case "=":
                    return array(
                        'chars' => 1,
                        'token' => SQMTokenItem::T_ASSIGNMENT,
                        'line' => $number
                    );
                    break;
            case "{":
                    return array(
                        'chars' => 1,
                        'token' => SQMTokenItem::T_BLOCKSTART,
                        'line' => $number
                    );
                    break;
            case "}":
                    return array(
                        'chars' => 1,
                        'token' => SQMTokenItem::T_BLOCKEND,
                        'line' => $number
                    );
                    break;

            case ",":
                    return array(
                        'chars' => 1,
                        'token' => SQMTokenItem::T_COMMA,
                        'line' => $number
                    );
                    break;

            case ";":
                    return array(
                        'chars' => 1,
                        'token' => SQMTokenItem::T_SEMICOLON,
                        'line' => $number
                    );
                    break;

            case "[":
                    if ($string[1] == "]") {
                        return array(
                            'chars' => 2,
                            'token' => SQMTokenItem::T_ARRAY,
                            'line' => $number
                        );
                    }
                    break;
            case "c":
                if ($string[1] == "l" && $string[2] == "a" && $string[3] == "s" && $string[4] == "s") {
                    return array(
                        'chars' => 5,
                        'token' => SQMTokenItem::T_CLASS,
                        'line' => $number
                    );
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1]),
                        'token' => SQMTokenItem::T_IDENTIFIER,
                        'line' => $number
                    );
                }
                break;
            case "0":
            case "1":
            case "2":
            case "3":
            case "4":
            case "5":
            case "6":
            case "7":
            case "8":
            case "9":
            case ".":
            case "-":
            case "+":
                if (preg_match("/^([-+]?(([0-9]*)\.([0-9]+))([eE][-+]?[0-9]+)?)/S", $string, $matches)) {
                    return array(
                        'match' => (float)($matches[1]),
                        'chars' => strlen($matches[1]),
                        'token' => SQMTokenItem::T_FLOAT,
                        'line' => $number
                    );
                }
                if (preg_match("/^([-+]?([0-9]+))/S", $string, $matches)) {
                    return array(
                        'match' => (int)($matches[1]),
                        'chars' => strlen($matches[1]),
                        'token' => SQMTokenItem::T_INTEGER,
                        'line' => $number
                    );
                }
               break;
            case "\"":
                $string = substr($string,1);
                if ($string != "\"") {
                    $string = str_replace('""','XX',$string);
                }
                if (preg_match('/^(.*)"/S', $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1])+2,
                        'token' => SQMTokenItem::T_STRING,
                        'line' => $number
                    );
                }
                break;
            default:
                if (preg_match("/^(\s+)/S", $string, $matches)) {
                    return array(
                        'chars' => strlen($matches[1]),
                        'token' => SQMTokenItem::T_SPACE,
                        'line' => $number
                    );
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1]),
                        'token' => SQMTokenItem::T_IDENTIFIER,
                        'line' => $number
                    );
                }
                break;
        }
        return false;
    }
}
