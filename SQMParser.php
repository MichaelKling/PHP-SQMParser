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
    private $tokenCount = 0;
    private $tokenLookAheadRingBuffer = array();
    private $tokenLookAheadRingBufferPosition = 0;
    private $parsedData = array();

    protected function __construct($sqmFile)
    {
        for ($i = 0;$i < SQMParser::LOOKUP_BUFFER_MAX_SIZE;++$i) {
            $this->tokenLookAheadRingBuffer[$i] = false;
        }
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
        $parser = new SQMParser($sqmFile);
        $parser->_run();
        return $parser->parsedData;
    }

    protected function _run() {
        //Expect list of Definition Items
        do {
            $this->_parseRootDefition($this->parsedData);
        } while (!SQMLexer::isEOF());
    }


    protected function _parseRootDefition(&$parentElement) {
        //Switch between Class, Assignment
        try {
            $nextToken = $this->_tokenLookAhead(0);
        } catch (Exception $e) {
            //Only the root definition list should handle the end of the stream.
            return;
        }
        switch ($nextToken->token) {
            case SQMTokenItem::T_CLASS: $this->_parseClass($parentElement);
                break;
            case SQMTokenItem::T_IDENTIFIER:
                $this->_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$this->_tokenLookAhead(0)->tokenToName().": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");;
        }

        if ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$this->_tokenLookAhead(0)->tokenToName().": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseDefinition(&$parentElement) {
        //Switch between Class, Assignment
        switch ($this->_tokenLookAhead(0)->token) {
            case SQMTokenItem::T_CLASS:
                $this->_parseClass($parentElement);
                break;
            case SQMTokenItem::T_IDENTIFIER:
                $this->_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$this->_tokenLookAhead(0)->tokenToName().": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");;
        }

        if ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$this->_tokenLookAhead(0)->tokenToName().": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseAssignment(&$parentElement) {
        //<identifier> [<arrayLiteral>] <assignmentLiteral> [<value>|<valueList>]
        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token == SQMTokenItem::T_IDENTIFIER) {
            switch ($this->_tokenLookAhead(1)->token) {
                case SQMTokenItem::T_ASSIGNMENT :
                    $this->_consumeTokens(2);
                    $this->_parseValue($parentElement[$nextToken->match]);
                    return;
                case SQMTokenItem::T_ARRAY :
                    if ($this->_tokenLookAhead(2)->token == SQMTokenItem::T_ASSIGNMENT) {
                        $this->_consumeTokens(3);
                        $this->_parseValueList($parentElement[$nextToken->match]);
                        return;
                    }
            }
        }
        throw new Exception("Next token set is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");
    }

    protected function _parseClass(&$parentElement) {
        if ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_CLASS) {
            throw new Exception("Expected T_CLASS, got ".($this->_tokenLookAhead(0)->tokenToName()).": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");
        }

        $nextToken = $this->_tokenLookAhead(1);
        if ($nextToken->token != SQMTokenItem::T_IDENTIFIER) {
            throw new Exception("Expected T_IDENTIFIER, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        $className = $nextToken->match;
        $parentElement[$className] = null;

        if ($this->_tokenLookAhead(2)->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($this->_tokenLookAhead(1)->tokenToName()).": ".$this->_tokenLookAhead(1)->match." at line ".$this->_tokenLookAhead(1)->line.".");
        }
        $this->_consumeTokens(3);

        while ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_BLOCKEND) {
                $this->_parseDefinition($parentElement[$className]);
        }
        $this->_consumeTokens(1);

        $parentElement[$className] = (object)$parentElement[$className];
        return;
    }

    protected function _parseValueList(&$parentElement) {
        if ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($this->_tokenLookAhead(0)->tokenToName()).": ".$this->_tokenLookAhead(0)->match." at line ".$this->_tokenLookAhead(0)->line.".");
        }
        $this->_consumeTokens(1);

        $parentElement = array();

        while ($this->_tokenLookAhead(0)->token != SQMTokenItem::T_BLOCKEND) {
            $element = null;
            $this->_parseValue($element);
            $parentElement[] = $element;

            if ($this->_tokenLookAhead(0)->token == SQMTokenItem::T_COMMA) {
                $this->_consumeTokens(1);
            }
        }
        $this->_consumeTokens(1);
    }

    protected function _parseValue(&$parentElement) {
        //<string>|<float>|<integer>
        $nextToken = $this->_tokenLookAhead(0);
        switch ($nextToken->token) {
            case SQMTokenItem::T_INTEGER:
            case SQMTokenItem::T_FLOAT:
                $this->_consumeTokens(1);
                $parentElement = $nextToken->match;
                break;
            case SQMTokenItem::T_STRING:
                $this->_consumeTokens(1);
                $parentElement = $nextToken->match;
                break;
            default: throw new Exception("Next token is not allowed: ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");;
        }
    }

    protected function _tokenLookAhead($index) {
        $token = null;
        if ($index > 2) {
            throw new Exception("Tried to lookup ".($index+1)." items - lookup buffer is only ".SQMParser::LOOKUP_BUFFER_MAX_SIZE." items big.");
        } else if ($index < $this->tokenCount) {
            $token = $this->tokenLookAheadRingBuffer[($this->tokenLookAheadRingBufferPosition + $index) % SQMParser::LOOKUP_BUFFER_MAX_SIZE];
        } else /*if ($index >= $this->tokenCount)*/ {
            $index -= $this->tokenCount-1;
            while ($index) {
                $token = SQMLexer::getNextToken();
                if (!$token) {
                    throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
                }
                $this->tokenLookAheadRingBuffer[($this->tokenLookAheadRingBufferPosition + $this->tokenCount) % SQMParser::LOOKUP_BUFFER_MAX_SIZE] = $token;
                ++$this->tokenCount;
                --$index;
            }
        }

        if (!$token) {
            throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
        }

        return $token;
    }

    protected function _consumeTokens($count) {
        if ($this->tokenCount == $count) {
            //Just declare the size to 0
            $this->tokenCount = 0;
        } else if ($this->tokenCount < $count) {
            //We have less tokens then we want to consume. Declare size to 0 and read the missing ones
            $count -= $this->tokenCount;
            $this->tokenCount = 0;
            while ($count) {
                if (SQMLexer::getNextToken() === false) {
                    throw new Exception("Tokenstream ended before it was supposed to end while consuming tokens.");
                }
                --$count;
            }
        } else /*if ($this->tokenCount > $count)*/ {
            //We have more tokens then we wanna consume, just move the pointer until we consumed the wished amount
            $this->tokenLookAheadRingBufferPosition = ($this->tokenLookAheadRingBufferPosition + $count) % SQMParser::LOOKUP_BUFFER_MAX_SIZE;
            $this->tokenCount -= $count;
        }
    }


}