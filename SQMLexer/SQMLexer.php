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
                if ($result->token != SQMTokenItem::T_SPACE) {
                    $tokens[] = $result;
                }
                $length = $result->char;
                $line = substr($line, $length);
                $offset += $length;
            }
        }

        return $tokens;
    }

    protected static function _match($string, $number) {
        //Try first fix matches:
        switch ($string[0]) {
            case "=": return new SQMTokenItem(null,1,SQMTokenItem::T_ASSIGNMENT,$number);
                    break;
            case "{": return new SQMTokenItem(null,1,SQMTokenItem::T_BLOCKSTART,$number);
                    break;
            case "}": return new SQMTokenItem(null,1,SQMTokenItem::T_BLOCKEND,$number);
                    break;
            case ",": return new SQMTokenItem(null,1,SQMTokenItem::T_COMMA,$number);
                    break;
            case ";": return new SQMTokenItem(null,1,SQMTokenItem::T_SEMICOLON,$number);
                    break;
            case "[":
                    if ($string[1] == "]") {
                        return new SQMTokenItem(null,2,SQMTokenItem::T_ARRAY,$number);
                    }
                    break;
            case "c":
                if ($string[1] == "l" && $string[2] == "a" && $string[3] == "s" && $string[4] == "s") {
                    return new SQMTokenItem(null,5,SQMTokenItem::T_CLASS,$number);
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return new SQMTokenItem($matches[1],strlen($matches[1]),SQMTokenItem::T_IDENTIFIER,$number);
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
                    return new SQMTokenItem((float)$matches[1],strlen($matches[1]),SQMTokenItem::T_FLOAT,$number);
                }
                if (preg_match("/^([-+]?([0-9]+))/S", $string, $matches)) {
                    return new SQMTokenItem((int)$matches[1],strlen($matches[1]),SQMTokenItem::T_INTEGER,$number);
                }
               break;
            case "\"":
                $string = substr($string,1);
                if ($string != "\"") {
                    $string = str_replace('""','XX',$string);
                }
                if (preg_match('/^(.*)"/S', $string, $matches)) {
                    return new SQMTokenItem($matches[1],strlen($matches[1])+2,SQMTokenItem::T_STRING,$number);
                }
                break;
            default:
                if (preg_match("/^(\s+)/S", $string, $matches)) {
                    return new SQMTokenItem(null,strlen($matches[1]),SQMTokenItem::T_SPACE,$number);
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return new SQMTokenItem($matches[1],strlen($matches[1]),SQMTokenItem::T_IDENTIFIER,$number);
                }
                break;
        }
        return false;
    }
}
