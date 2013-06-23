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

class SQMLexer
{
    private static $rawTokens = null;
    private static $iterator = null;
    private static $counter = 0;
    private static $lastKnownLine = 0;

    public static function reset() {
        SQMLexer::$rawTokens = null;
        SQMLexer::$iterator = null;
        SQMLexer::$lastKnownLine = 0;
    }

    public static function init($source) {
        SQMLexer::reset();
        $string = stream_get_contents($source);
        SQMLexer::$rawTokens = new ArrayObject(token_get_all("<?php ".$string." ?>"));
        SQMLexer::$iterator = SQMLexer::$rawTokens->getIterator();
    }

    public static function isEOF() {
        return !SQMLexer::$iterator->valid();
    }

    public static function getNextToken() {
        $result = null;
        while (SQMLexer::$iterator->valid()) {
            $current = SQMLexer::$iterator->current();
            if (is_array($current)) {
                SQMLexer::$lastKnownLine = $current[SQMTokenItem::INDEX_LINE];
                switch ($current[SQMTokenItem::INDEX_TOKEN]) {
                    case SQMTokenItem::T_CLASS:
                    case SQMTokenItem::T_IDENTIFIER:
                    case SQMTokenItem::T_FLOAT:
                    case SQMTokenItem::T_INTEGER: $result = $current; break;
                    case SQMTokenItem::T_STRING:
                        $string = $current[SQMTokenItem::INDEX_MATCH];
                        $currentIndex = SQMLexer::$iterator->key();
                        ++$currentIndex;
                        while (is_array(SQMLexer::$iterator[$currentIndex]) && SQMLexer::$iterator[$currentIndex][SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_STRING) {
                            SQMLexer::$iterator->next();
                            if (SQMLexer::$iterator->valid()) {
                                $current = SQMLexer::$iterator->current();
                                $string .= $current[SQMTokenItem::INDEX_MATCH];
                                ++$currentIndex;
                            } else {
                                throw new Exception("Unable to parse line " . $current[SQMTokenItem::INDEX_LINE] .". String ended unexpectedly.");
                            }
                        }
                        //As the string escape is "", we get multiple strings for everything...
                        $result = SQMTokenItem::construct(substr($string, 1, -1),null,$current[SQMTokenItem::INDEX_TOKEN],-1); break;

                    case SQMTokenItem::T_SPACE:
                    //Count some php tags as whitespace as well:
                    case T_OPEN_TAG:
                    case T_CLOSE_TAG: break;
                    default:
                        throw new Exception("Unable to parse line " . $current[SQMTokenItem::INDEX_LINE] ." next key is '".$current[SQMTokenItem::INDEX_MATCH]."', marked as token ".token_name($current[SQMTokenItem::INDEX_TOKEN]).".");
                }
            } else {
                switch ($current) {
                    case SQMTokenItem::T_ASSIGNMENT:
                    case SQMTokenItem::T_BLOCKSTART:
                    case SQMTokenItem::T_BLOCKEND:
                    case SQMTokenItem::T_COMMA:
                    case SQMTokenItem::T_SEMICOLON: $result = SQMTokenItem::construct(null,null,$current,-1); break;
                    case SQMTokenItem::T_ARRAYSTART:
                        SQMLexer::$iterator->next();
                        if (SQMLexer::$iterator->valid()) {
                            $current = SQMLexer::$iterator->current();
                            if (!is_array($current) && $current == SQMTokenItem::T_ARRAYEND) {
                                $result = SQMTokenItem::construct(null,null,SQMTokenItem::T_ARRAY,-1);
                                break;
                            }
                        }
                        throw new Exception("Unable to parse line ".SQMLexer::$lastKnownLine." reading key '".$current."' - error while concating array elements.");
                    case SQMTokenItem::T_MINUS:
                        //Next element needs to be an int or float
                        SQMLexer::$iterator->next();
                        if (SQMLexer::$iterator->valid()) {
                            $current = SQMLexer::$iterator->current();
                            if (is_array($current) && ($current[SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_INTEGER || $current[SQMTokenItem::INDEX_TOKEN] == SQMTokenItem::T_FLOAT)) {
                                $current[SQMTokenItem::INDEX_MATCH] = (-1) * (float)$current[SQMTokenItem::INDEX_MATCH];
                                $result = $current;
                                break;
                            } else {
                                throw new Exception("Unable to parse line ".SQMLexer::$lastKnownLine." reading key '".$current."' Found Minus but couldnt find an integer or float after.");
                            }
                        } else {
                            throw new Exception("Unable to parse line ".SQMLexer::$lastKnownLine." reading key '".$current."' Found Minus but string ended.");
                        }
                    default: throw new Exception("Unable to parse line ".SQMLexer::$lastKnownLine." reading key '".$current."'.");
                }
            }
            SQMLexer::$iterator->next();
            if ($result) {
                return $result;
            }
        }
        return false;
    }
}
