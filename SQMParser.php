<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:44
 */

define('SQMPARSER_BASE', dirname(__FILE__) . '/');

require_once SQMPARSER_BASE . 'SQMFile.php';
require_once SQMPARSER_BASE . 'SQMLexer.php';
require_once SQMPARSER_BASE . 'SQMLibrary.php';
require_once SQMPARSER_BASE . 'SQMPlayerParser.php';

class SQMParser {
    private $tokens = array();
    private $parsedData = array();

    protected function __construct($sqmFile)
    {
        $this->tokens = SQMLexer::run($sqmFile->content);
        $this->parsedData = array();
    }

    public static function parseFile($file) {
        $sqmFile = new SQMFile($file);
        return $sqmFile;
    }

    public static function parseStream($stream) {
        $sqmFile = new SQMFile($stream);
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
        } while (!empty($this->tokens));
    }


    protected function _parseDefinition(&$parentElement) {
        //Switch between Class, Assignment
        $nextToken = $this->_tokenLookAhead(0);
        switch ($nextToken['token']) {
            case 'T_CLASS': $this->_parseClass($parentElement);
                            break;
            case 'T_IDENTIFIER':
                $this->_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != 'T_SEMICOLON') {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseAssignment(&$parentElement) {
        //<identifier> [<arrayLiteral>] <assignmentLiteral> [<value>|<valueList>]
        $nextToken = $this->_tokenLookAhead(0);
        $nextToken2 = $this->_tokenLookAhead(1);
        $nextToken3 = $this->_tokenLookAhead(2);

        if ($nextToken['token'] == 'T_IDENTIFIER' && $nextToken2['token'] == 'T_ASSIGNMENT') {
            $parentElement[$nextToken['match']] = null;
            $this->_consumeTokens(2);

            $this->_parseValue($parentElement[$nextToken['match']]);
        } else if ($nextToken['token'] == 'T_IDENTIFIER' && $nextToken2['token'] == 'T_ARRAY' && $nextToken3['token'] == 'T_ASSIGNMENT') {
            $parentElement[$nextToken['match']] = null;
            $this->_consumeTokens(3);

            $this->_parseValueList($parentElement[$nextToken['match']]);
        } else {
            throw new Exception("Next token set is not allowed: ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }
    }

    protected function _parseClass(&$parentElement) {
        $classname = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != 'T_CLASS') {
            throw new Exception("Expected T_CLASS, got ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != 'T_IDENTIFIER') {
            throw new Exception("Expected T_IDENTIFIER, got ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $classname = $nextToken['match'];

        $parentElement[$classname] = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != 'T_BLOCKSTART') {
            throw new Exception("Expected T_BLOCKSTART, got ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken['token'] != 'T_BLOCKEND') {
                $this->_parseDefinition($parentElement[$classname]);
                $nextToken = $this->_tokenLookAhead(0);
        }
        $this->_consumeTokens(1);

        $parentElement[$classname] = (object)$parentElement[$classname];
        return;
    }

    protected function _parseValueList(&$parentElement) {
        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken['token'] != 'T_BLOCKSTART') {
            throw new Exception("Expected T_BLOCKSTART, got ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");
        }
        $this->_consumeTokens(1);

        $parentElement = array();

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken['token'] != 'T_BLOCKEND') {
            $element = null;
            $this->_parseValue($element);
            $parentElement[] = $element;

            $nextToken = $this->_tokenLookAhead(0);
            if ($nextToken['token'] == 'T_COMMA') {
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
            case 'T_INTEGER':
            case 'T_FLOAT':
                $this->_consumeTokens(1);
                $parentElement = $nextToken['match'];
                break;
            case 'T_STRING':
                $this->_consumeTokens(1);
                $parentElement = substr(str_replace('""','"',$nextToken['match']),1,-1);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken['token'].": ".$nextToken['match']." at line ".$nextToken['line'].".");;
        }
    }

    protected function _tokenLookAhead($index) {
        if (count($this->tokens) < $index+1) {
            throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
        }
        return $this->tokens[$index];
    }

    protected function _consumeTokens($count) {
        if (count($this->tokens) < $count) {
            throw new Exception("Tokenstream ended before it was supposed to end while consuming tokens.");
        }
        while ($count > 0) {
            array_shift($this->tokens);
            $count--;
        }
    }


}