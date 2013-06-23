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
    private $currentToken = null;
    private $lastToken = null;
    private $tokenCount = 0;
    private $parsedData = array();

    protected function __construct($sqmFile)
    {
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
            default: throw new Exception("Next token is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseDefinition(&$parentElement) {
        //Switch between Class, Assignment
        $nextToken = $this->_tokenLookAhead(0);
        switch ($nextToken->token) {
            case SQMTokenItem::T_CLASS: $this->_parseClass($parentElement);
                            break;
            case SQMTokenItem::T_IDENTIFIER:
                $this->_parseAssignment($parentElement);
                break;
            default: throw new Exception("Next token is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_SEMICOLON) {
            throw new Exception("Expected T_SEMICOLON, got ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");
        } else {
            $this->_consumeTokens(1);
        }
    }

    protected function _parseAssignment(&$parentElement) {
        //<identifier> [<arrayLiteral>] <assignmentLiteral> [<value>|<valueList>]
        $nextToken = $this->_tokenLookAhead(0);
        $nextToken2 = $this->_tokenLookAhead(1);
        $nextToken3 = $this->_tokenLookAhead(2);

        if ($nextToken->token == SQMTokenItem::T_IDENTIFIER && $nextToken2->token == SQMTokenItem::T_ASSIGNMENT) {
            $parentElement[$nextToken->match] = null;
            $this->_consumeTokens(2);

            $this->_parseValue($parentElement[$nextToken->match]);
        } else if ($nextToken->token == SQMTokenItem::T_IDENTIFIER && $nextToken2->token == SQMTokenItem::T_ARRAY && $nextToken3->token == SQMTokenItem::T_ASSIGNMENT) {
            $parentElement[$nextToken->match] = null;
            $this->_consumeTokens(3);

            $this->_parseValueList($parentElement[$nextToken->match]);
        } else {
            throw new Exception("Next token set is not allowed: ".$nextToken->tokenToName().": ".$nextToken->match." at line ".$nextToken->line.".");;
        }
    }

    protected function _parseClass(&$parentElement) {
        $classname = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_CLASS) {
            throw new Exception("Expected T_CLASS, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_IDENTIFIER) {
            throw new Exception("Expected T_IDENTIFIER, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        $this->_consumeTokens(1);

        $classname = $nextToken->match;

        $parentElement[$classname] = null;

        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        $this->_consumeTokens(1);

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken->token != SQMTokenItem::T_BLOCKEND) {
                $this->_parseDefinition($parentElement[$classname]);
                $nextToken = $this->_tokenLookAhead(0);
        }
        $this->_consumeTokens(1);

        $parentElement[$classname] = (object)$parentElement[$classname];
        return;
    }

    protected function _parseValueList(&$parentElement) {
        $nextToken = $this->_tokenLookAhead(0);
        if ($nextToken->token != SQMTokenItem::T_BLOCKSTART) {
            throw new Exception("Expected T_BLOCKSTART, got ".($nextToken->tokenToName()).": ".$nextToken->match." at line ".$nextToken->line.".");
        }
        $this->_consumeTokens(1);

        $parentElement = array();

        $nextToken = $this->_tokenLookAhead(0);
        while ($nextToken->token != SQMTokenItem::T_BLOCKEND) {
            $element = null;
            $this->_parseValue($element);
            $parentElement[] = $element;

            $nextToken = $this->_tokenLookAhead(0);
            if ($nextToken->token == SQMTokenItem::T_COMMA) {
                $this->_consumeTokens(1);
                $nextToken = $this->_tokenLookAhead(0);
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
        if ($index == $this->tokenCount-1) {
            $token = $this->lastToken;
        } else if ($index < $this->tokenCount) {
            $token = $this->currentToken;
            while ($index) {
                $token = $token->next;
                $index--;
            }
        } else /*if ($index >= $this->tokenCount)*/ {
            $index -= $this->tokenCount-1;
            if ($this->tokenCount == 0) {
                $token = SQMLexer::getNextToken();
                $this->currentToken = $token;
                $this->lastToken = $token;
                $this->tokenCount = 1;
                $index--;
                if (!$token) {
                    throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
                }
            } else {
                $token = $this->lastToken;
            }

            while($index > 0) {
                $token = SQMLexer::getNextToken();
                if (!$token) {
                    throw new Exception("Tokenstream ended before it was supposed to end while looking ahead for tokens.");
                }
                $this->lastToken->next = $token;
                $this->lastToken = $token;
                $this->tokenCount++;
                $index--;
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
                $count--;
            }

        } else /*if ($this->tokenCount > $count)*/ {
            //We have more tokens then we wanna consume, just move the pointer until we consumed the wished amount
            while ($count) {
                $this->currentToken = $this->currentToken->next;
                $this->tokenCount--;
                $count--;
            }
        }
    }


}