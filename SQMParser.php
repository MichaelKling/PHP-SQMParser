<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:44
 */

define('SQMPARSER_BASE', dirname(__FILE__) . '/');

require_once SQMPARSER_BASE . 'SQMFile.php';
require_once SQMPARSER_BASE . 'SQMLexer/SQMLexer.php';
require_once SQMPARSER_BASE . 'SQMLibrary.php';
require_once SQMPARSER_BASE . 'SQMPlayerParser.php';

class SQMParser {
    const LOOKUP_BUFFER_MAX_SIZE = 3;
    private static $tokenCount = 0;
    private static $tokenLookAheadRingBuffer = array();
    private static $tokenLookAheadRingBufferPosition = 0;
    private static $parsedData = array();

    protected static function construct($sqmFile)
    {
        SQMParser::$tokenCount = 0;
        SQMParser::$tokenLookAheadRingBuffer = array();
        for ($i = 0;$i < SQMParser::LOOKUP_BUFFER_MAX_SIZE;++$i) {
            SQMParser::$tokenLookAheadRingBuffer[$i] = false;
        }
        SQMParser::$tokenLookAheadRingBufferPosition = 0;
        SQMParser::$parsedData = array();
        SQMLexer::init($sqmFile->content);
    }

    public static function parseFile($file) {
        $sqmFile = new SQMFile($file,SQMFile::INPUTTYPE_FILENAME);
        return $sqmFile;
    }

    public static function parseStream($stream) {
        $sqmFile = new SQMFile($stream,SQMFile::INPUTTYPE_RESSOURCE);
        return $sqmFile;
    }

    public static function parseString($stream) {
        $sqmFile = new SQMFile($stream,SQMFile::INPUTTYPE_STRING);
        return $sqmFile;
    }

    public static function parse($sqmFile) {
        SQMParser::construct($sqmFile);
        SQMParser::_run();
        return SQMParser::$parsedData;
    }

    protected static function _run() {
        //Expect list of Definition Items
        do {
            SQMParser::_parseRootDefition(SQMParser::$parsedData);
        } while (!SQMLexer::isEOF());
    }


    protected static function _parseRootDefition(&$parentElement) {
        //Switch between Class, Assignment
        try {
            $nextToken = SQMParser::_tokenLookAhead(0);
        } catch (Exception $e) {
            //Only the root definition list should handle the end of the stream.
            return;
        }
        switch ($nextToken->token) {
            case SQMTokenItem::T_CLASS: SQMParser::_parseClass($parentElement);
                break;
            case SQMTokenItem::T_IDENTIFIER:
                SQMParser::_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }

        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");
        } else {
            SQMParser::_consumeTokens(1);
        }
    }

    protected static function _parseDefinition(&$parentElement) {
        //Switch between Class, Assignment
        $nextToken = SQMParser::_tokenLookAhead(0);
        switch ($nextToken->token) {
            case SQMTokenItem::T_CLASS: SQMParser::_parseClass($parentElement);
                            break;
            case SQMTokenItem::T_IDENTIFIER:
                SQMParser::_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }

        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");
        } else {
            SQMParser::_consumeTokens(1);
        }
    }

    protected static function _parseAssignment(&$parentElement) {
        //<identifier> [<arrayLiteral>] <assignmentLiteral> [<value>|<valueList>]
        $nextToken = SQMParser::_tokenLookAhead(0);
        $nextToken2 = SQMParser::_tokenLookAhead(1);
        $nextToken3 = SQMParser::_tokenLookAhead(2);

        if ($nextToken->token == SQMTokenItem::T_IDENTIFIER && $nextToken2->token == SQMTokenItem::T_ASSIGNMENT) {
            $parentElement[$nextToken->match] = null;
            SQMParser::_consumeTokens(2);

            SQMParser::_parseValue($parentElement[$nextToken->match]);
        } else if ($nextToken->token == SQMTokenItem::T_IDENTIFIER && $nextToken2->token == SQMTokenItem::T_ARRAY && $nextToken3->token == SQMTokenItem::T_ASSIGNMENT) {
            $parentElement[$nextToken->match] = null;
            SQMParser::_consumeTokens(3);

            SQMParser::_parseValueList($parentElement[$nextToken->match]);
        } else {
            throw new Exception("Next token set is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }
    }

    protected static function _parseClass(&$parentElement) {
        $classname = null;

        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_CLASS) {
            throw new Exception("Expected T_CLASS, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        SQMParser::_consumeTokens(1);

        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_IDENTIFIER) {
            throw new Exception("Expected T_IDENTIFIER, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        SQMParser::_consumeTokens(1);

        $classname = $nextToken->match;

        $parentElement[$classname] = null;

        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        SQMParser::_consumeTokens(1);

        $nextToken = SQMParser::_tokenLookAhead(0);
        while ($nextToken->token != SQMTokenItem::T_BLOCKEND) {
                SQMParser::_parseDefinition($parentElement[$classname]);
                $nextToken = SQMParser::_tokenLookAhead(0);
        }
        SQMParser::_consumeTokens(1);

        $parentElement[$classname] = (object)$parentElement[$classname];
        return;
    }

    protected static function _parseValueList(&$parentElement) {
        $nextToken = SQMParser::_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        SQMParser::_consumeTokens(1);

        $parentElement = array();

        $nextToken = SQMParser::_tokenLookAhead(0);
        while ($nextToken->token != SQMTokenItem::T_BLOCKEND) {
            $element = null;
            SQMParser::_parseValue($element);
            $parentElement[] = $element;

            $nextToken = SQMParser::_tokenLookAhead(0);
            if ($nextToken->token == SQMTokenItem::T_COMMA) {
                SQMParser::_consumeTokens(1);
                $nextToken = SQMParser::_tokenLookAhead(0);
            }
        }
        SQMParser::_consumeTokens(1);
    }

    protected static function _parseValue(&$parentElement) {
        //<string>|<float>|<integer>
        $nextToken = SQMParser::_tokenLookAhead(0);
        switch ($nextToken->token) {
            case SQMTokenItem::T_INTEGER:
            case SQMTokenItem::T_FLOAT:
                SQMParser::_consumeTokens(1);
                $parentElement = $nextToken->match;
                break;
            case SQMTokenItem::T_STRING:
                SQMParser::_consumeTokens(1);
                $parentElement = $nextToken->match;
                break;
            default: throw new Exception("Next token is not allowed: ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");;
        }
    }

    protected static function _tokenLookAhead($index) {
        $token = null;
        if ($index > 2) {
            throw new Exception("Tried to lookup ".($index+1)." items - lookup buffer is only ".SQMParser::LOOKUP_BUFFER_MAX_SIZE." items big.");
        } else if ($index < SQMParser::$tokenCount) {
            $token = SQMParser::$tokenLookAheadRingBuffer[(SQMParser::$tokenLookAheadRingBufferPosition + $index) % SQMParser::LOOKUP_BUFFER_MAX_SIZE];
        } else /*if ($index >= $this->tokenCount)*/ {
            $index -= SQMParser::$tokenCount-1;
            while ($index) {
                $token = SQMLexer::getNextToken();
                if (!$token) {
                    throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
                }
                SQMParser::$tokenLookAheadRingBuffer[(SQMParser::$tokenLookAheadRingBufferPosition + SQMParser::$tokenCount) % SQMParser::LOOKUP_BUFFER_MAX_SIZE] = $token;
                ++SQMParser::$tokenCount;
                --$index;
            }
        }

        if (!$token) {
            throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
        }

        return $token;
    }

    protected static function _consumeTokens($count) {
        if (SQMParser::$tokenCount == $count) {
            //Just declare the size to 0
            SQMParser::$tokenCount = 0;
        } else if (SQMParser::$tokenCount < $count) {
            //We have less tokens then we want to consume. Declare size to 0 and read the missing ones
            $count -= SQMParser::$tokenCount;
            SQMParser::$tokenCount = 0;
            while ($count) {
                if (SQMLexer::getNextToken() === false) {
                    throw new Exception("Tokenstream ended before it was supposed to end while consuming tokens.");
                }
                --$count;
            }
        } else /*if ($this->tokenCount > $count)*/ {
            //We have more tokens then we wanna consume, just move the pointer until we consumed the wished amount
            SQMParser::$tokenLookAheadRingBufferPosition = (SQMParser::$tokenLookAheadRingBufferPosition + $count) % SQMParser::LOOKUP_BUFFER_MAX_SIZE;
            SQMParser::$tokenCount -= $count;
        }
    }


}