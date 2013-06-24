<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 09:56
 *
 * A faster lexer who uses also lots of more memory.
 *
 */

require_once SQMPARSER_BASE . 'SQMLexer/SQMTokenItem.php';

class SQMFastLexer extends SQMLexer {
    //NOthing to do here.
}

class SQMLexer
{
    private static $rawTokens = null;
    private static $tokenCount = 0;
    private static $currentIndex = 0;

    public static function reset() {
        SQMLexer::$rawTokens = null;
        SQMLexer::$tokenCount = 0;
        SQMLexer::$currentIndex = 0;
    }

    public static function init($source) {
        SQMLexer::reset();
        $string = stream_get_contents($source);
        SQMLexer::$rawTokens = new ArrayObject(token_get_all("<?php ".$string." ?>"));
        SQMLexer::$tokenCount = count(SQMLexer::$rawTokens);
    }

    public static function isEOF() {
        return !(SQMLexer::$currentIndex < SQMLexer::$tokenCount);
    }

    public static function getNextToken() {
        $currentIndex = SQMLexer::$currentIndex;
        $count = SQMLexer::$tokenCount;
        $result = null;
        while ($currentIndex < $count) {
            $current = SQMLexer::$rawTokens[$currentIndex];
            switch ($current[SQMTokenItem::INDEX_TOKEN]) {
                case SQMTokenItem::T_CLASS:
                case SQMTokenItem::T_IDENTIFIER:
                case SQMTokenItem::T_FLOAT:
                case SQMTokenItem::T_INTEGER: $result = $current; break;
                case SQMTokenItem::T_STRING:
                    $string = $current[SQMTokenItem::INDEX_MATCH];
                    while (is_array(SQMLexer::$rawTokens[$currentIndex+1]) && SQMLexer::$rawTokens[$currentIndex+1][SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_STRING) {
                        ++$currentIndex;
                        $string .= SQMLexer::$rawTokens[$currentIndex][SQMTokenItem::INDEX_MATCH];
                    }
                    //As the string escape is "", we get multiple strings for everything...
                    $result = SQMTokenItem::construct(substr($string, 1, -1),null,$current[SQMTokenItem::INDEX_TOKEN],-1); break;
                case SQMTokenItem::T_SPACE:
                //Count some php tags as whitespace as well:
                case T_OPEN_TAG:
                case T_CLOSE_TAG: break;
                case SQMTokenItem::T_ASSIGNMENT:
                case SQMTokenItem::T_BLOCKSTART:
                case SQMTokenItem::T_BLOCKEND:
                case SQMTokenItem::T_COMMA:
                case SQMTokenItem::T_SEMICOLON: $result = $current; break;
                case SQMTokenItem::T_ARRAYSTART:
                    $current = SQMLexer::$rawTokens[++$currentIndex];
                    if (!is_array($current) && $current == SQMTokenItem::T_ARRAYEND) {
                        $result = array(SQMTokenItem::INDEX_TOKEN => SQMTokenItem::T_ARRAY);
                        break;
                    }
                    throw new Exception("Unable to parse line ?? reading key '".$current."' - error while concating array elements.");
                case SQMTokenItem::T_MINUS:
                    //Next element needs to be an int or float
                    $current = SQMLexer::$rawTokens[++$currentIndex];
                    if (is_array($current) && ($current[SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_INTEGER || $current[SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_FLOAT)) {
                        $current[SQMTokenItem::INDEX_MATCH] = (-1) * (float)$current[SQMTokenItem::INDEX_MATCH];
                        $result = $current;
                        break;
                    } else {
                        throw new Exception("Unable to parse line ?? reading key '".$current."' Found Minus but couldnt find an integer or float after.");
                    }
                default: throw new Exception("Unable to parse line ?? reading key '".$current."' - '".dechex(ord($current))."'.");
            }
            ++$currentIndex;
            if ($result) {
                SQMLexer::$currentIndex = $currentIndex;
                return $result;
            }
        }
        SQMLexer::$currentIndex = $currentIndex;
        return false;
    }
}
