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
    private $tokens = array();
    private $tokenSize = 0;
    private $parsedData = array();

    protected function __construct($sqmFile)
    {
        $this->tokens = array_reverse(SQMLexer::run($sqmFile->content));
        $this->tokenSize = count($this->tokens);
        $this->parsedData = array();
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
        $parser = new SQMParser($sqmFile);
        $parser->_run();
        return $parser->parsedData;
    }

    protected function _run() {
        //Expect list of Definition Items
        do {
            $this->_parseDefinition($this->parsedData);
        } while ($this->tokenSize);
    }


    protected function _parseDefinition(&$parentElement) {
        //Switch between Class, Assignment
        $nextToken = $this->_tokenLookAhead(0);
        switch ($nextToken['token']) {
            case SQMTokenItem::T_CLASS: $this->_parseClass($parentElement);
                            break;
            case SQMTokenItem::T_IDENTIFIER:
                $this->_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken['token']->tokenToName().": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken['token']->tokenToName().": ".$nextToken['match']." at line ".$nextToken['line'].".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseAssignment(&$parentElement) {
        //<identifier> [<arrayLiteral>] <assignmentLiteral> [<value>|<valueList>]
        $nextToken = $this->_tokenLookAhead(0);
        $nextToken2 = $this->_tokenLookAhead(1);
        $nextToken3 = $this->_tokenLookAhead(2);

        if ($nextToken['token'] == SQMTokenItem::T_IDENTIFIER && $nextToken2['token'] == SQMLexer::T_ASSIGNMENT) {
            $parentElement[$nextToken['match']] = null;
            $this->_consumeTokens(2);

            $this->_parseValue($parentElement[$nextToken['match']]);
        } else if ($nextToken['token'] == SQMTokenItem::T_IDENTIFIER && $nextToken2['token'] == SQMLexer::T_ARRAY && $nextToken3['token'] == SQMLexer::T_ASSIGNMENT) {
            $parentElement[$nextToken['match']] = null;
            $this->_consumeTokens(3);

            $this->_parseValueList($parentElement[$nextToken['match']]);
        } else {
            throw new Exception("Next token set is not allowed: ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }
    }

    protected function _parseClass(&$parentElement) {
        $classname = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != SQMTokenItem::T_CLASS) {
            throw new Exception("Expected T_CLASS, got ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != SQMTokenItem::T_IDENTIFIER) {
            throw new Exception("Expected T_IDENTIFIER, got ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $classname = $nextToken['match'];

        $parentElement[$classname] = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken['token'] != SQMTokenItem::T_BLOCKEND) {
                $this->_parseDefinition($parentElement[$classname]);
                $nextToken = $this->_tokenLookAhead(0);
        }
        $this->_consumeTokens(1);

        $parentElement[$classname] = (object)$parentElement[$classname];
        return;
    }

    protected function _parseValueList(&$parentElement) {
        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $parentElement = array();

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken['token'] != SQMTokenItem::T_BLOCKEND) {
            $element = null;
            $this->_parseValue($element);
            $parentElement[] = $element;

            $nextToken = $this->_tokenLookAhead(0);
            if ($nextToken['token'] == SQMTokenItem::T_COMMA) {
                $this->_consumeTokens(1);
                $nextToken = $this->_tokenLookAhead(0);
            }
        }
        $this->_consumeTokens(1);
    }

    protected function _parseValue(&$parentElement) {
        //<string>|<float>|<integer>
        $nextToken = $this->_tokenLookAhead(0);
        switch ($nextToken['token']) {
            case SQMTokenItem::T_INTEGER:
            case SQMTokenItem::T_FLOAT:
                $this->_consumeTokens(1);
                $parentElement = $nextToken['match'];
                break;
            case SQMTokenItem::T_STRING:
                $this->_consumeTokens(1);
                $parentElement = $nextToken['match'];
                break;
            default: throw new Exception("Next token is not allowed: ".SQMLexer::tokenToName($nextToken['token']).": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }
    }

    protected function _tokenLookAhead($index) {
        $index = $this->tokenSize-$index-1;
        if ($index < 0) {
            throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
        }
        return $this->tokens[$index];
    }

    protected function _consumeTokens($count) {
        if ($this->tokenSize < $count) {
            throw new Exception("Tokenstream ended before it was supposed to end while consuming tokens.");
        }
        $this->tokenSize -= $count;
    }


}