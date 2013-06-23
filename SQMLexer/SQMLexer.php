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
    private static $source = null;
    private static $lineNumber = 0;
    private static $lineOffset = 0;
    private static $line = false;
    private static $lineLength = 0;

    protected static $_tokens = array(
        "/^(\s+)/" => SQMTokenItem::T_SPACE,
        "/^([a-zA-Z_][a-zA-Z0-9_]*)/" => SQMTokenItem::T_IDENTIFIER,
        "/^([-+]?(([0-9]*)\.([0-9]+)))/" => SQMTokenItem::T_FLOAT,
        "/^([-+]?([0-9]+))/" => SQMTokenItem::T_INTEGER,
        '/^(".*")/' => SQMTokenItem::T_STRING,
    );

    public static function init($source) {
        SQMLexer::$source = $source;
        SQMLexer::$lineNumber = 0;
        SQMLexer::$line = false;
        SQMLexer::$lineOffset = 0;
        SQMLexer::$lineLength = 0;
    }

    public static function isEOF() {
        //End of line?
        if (SQMLexer::$lineOffset >= SQMLexer::$lineLength) {
            ++SQMLexer::$lineNumber;
            SQMLexer::$line = fgets(SQMLexer::$source);
            SQMLexer::$lineOffset = 0;
            SQMLexer::$lineLength = strlen(SQMLexer::$line);
        }
        return SQMLexer::$line === FALSE;
    }

    public static function getNextToken() {
        do {
            //End of line?
            if (SQMLexer::$lineOffset >= SQMLexer::$lineLength) {
                ++SQMLexer::$lineNumber;
                SQMLexer::$line = fgets(SQMLexer::$source);
                SQMLexer::$lineOffset = 0;
                SQMLexer::$lineLength = strlen(SQMLexer::$line);
            }

            //End of file?
            if (SQMLexer::$line === FALSE) {
                return false;
            }

            $result = SQMLexer::_match(SQMLexer::$line, SQMLexer::$lineNumber, SQMLexer::$lineOffset);
            if($result === false) {
                throw new Exception("Unable to parse line " . (SQMLexer::$lineNumber) . ":".SQMLexer::$lineOffset." near \"".SQMLexer::$line."\" - next key is '".SQMLexer::$line[0]."' '".bin2hex(SQMLexer::$line[0])."'.");
            }

            SQMLexer::$line = substr(SQMLexer::$line,$result->length);
            SQMLexer::$lineOffset += $result->length;

        } while ($result->token == SQMTokenItem::T_SPACE);

        return $result;
    }

    protected static function _match($string, $number) {
        //Try first fix matches:
        $char = ord($string[0]);
        switch ($char) {
            case 0x3d/*"="*/: return new SQMTokenItem(null,1,SQMTokenItem::T_ASSIGNMENT,$number);
                    break;
            case 0x7b/*"{"*/: return new SQMTokenItem(null,1,SQMTokenItem::T_BLOCKSTART,$number);
                    break;
            case 0x7d/*"}"*/: return new SQMTokenItem(null,1,SQMTokenItem::T_BLOCKEND,$number);
                    break;
            case 0x2c/*","*/: return new SQMTokenItem(null,1,SQMTokenItem::T_COMMA,$number);
                    break;
            case 0x3b/*";"*/: return new SQMTokenItem(null,1,SQMTokenItem::T_SEMICOLON,$number);
                    break;
            case 0x5b/*"["*/:
                    if ($string[1] == "]") {
                        return new SQMTokenItem(null,2,SQMTokenItem::T_ARRAY,$number);
                    }
                    break;
            case 0x63/*"c"*/:
                if ($string[1] == "l" && $string[2] == "a" && $string[3] == "s" && $string[4] == "s") {
                    return new SQMTokenItem(null,5,SQMTokenItem::T_CLASS,$number);
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return new SQMTokenItem($matches[1],strlen($matches[1]),SQMTokenItem::T_IDENTIFIER,$number);
                }
                break;
            case 0x30/*"0"*/:
            case 0x31/*"1"*/:
            case 0x32/*"2"*/:
            case 0x33/*"3"*/:
            case 0x34/*"4"*/:
            case 0x35/*"5"*/:
            case 0x36/*"6"*/:
            case 0x37/*"7"*/:
            case 0x38/*"8"*/:
            case 0x39/*"9"*/:
            case 0x2e/*"."*/:
            case 0x2d/*"-"*/:
            case 0x2b/*"+"*/:
                if (preg_match("/^([-+]?(([0-9]*)\.([0-9]+))([eE][-+]?[0-9]+)?)/S", $string, $matches)) {
                    return new SQMTokenItem((float)$matches[1],strlen($matches[1]),SQMTokenItem::T_FLOAT,$number);
                }
                if (preg_match("/^([-+]?([0-9]+))/S", $string, $matches)) {
                    return new SQMTokenItem((int)$matches[1],strlen($matches[1]),SQMTokenItem::T_INTEGER,$number);
                }
               break;
            case 0x22/*"\""*/:
                $string = substr($string,1);
                if ($string != "\"") {
                    $string = str_replace('""','XX',$string);
                }
                $end = strpos($string,"\"");
                if ($end !== false) {
                    $string = substr($string,0,$end);
                    return new SQMTokenItem($string,$end+2,SQMTokenItem::T_STRING,$number);
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
