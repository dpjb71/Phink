<?php
/*
 * Copyright (C) 2016 David Blanchard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
 namespace Phink\Xml;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//require_once 'phink/collections/arraylist.php';
//require_once 'phink/utils/string_utils.php';
//require_once 'xmlmatch.php';

use Phink\Core\TObject;
use Phink\Core\TRegistry;
use Phink\Collections\TArrayList;
use Phink\Utils\TStringUtils;

/**
 * Description of axmldocument
 *
 * @author david
 */
define('QUOTE', '"');
define('OPEN_TAG', '<');
define('CLOSE_TAG', '>');
define('TERMINATOR', '/');
define('TAB_MARK', "\t");
define('LF_MARK', "\n");
define('CR_MARK', "\r");
define('SKIP_MARK', '!');
define('QUEST_MARK', '?');
define('STR_EMPTY', '');
define('STR_SPACE', ' ');
define('TAG_PATTERN_ANY', "phx:");


class TXmlElementPos
{
    const None = 0;
    const Open = 1;
    const Close = 2;
}

class TXmlDocument extends TObject
{
    private $_count = 0;
    private $_cursor = 0;
    private $_text = STR_EMPTY;
    private $_matches = array();
    private $_id = -1;
    private $_match = null;
    private $_list = array();
    private $_depths = array();
    private $_matchesByDepth = array();
    private $_endPos = -1;

    public function __construct($text)
    {
        $this->_text = $text . OPEN_TAG . TAG_PATTERN_ANY . 'eof' . STR_SPACE . TERMINATOR . CLOSE_TAG;
//        if(strpos($text, CR_MARK . LF_MARK) > -1) {
//            $this->_text = str_replace("\r\n", '', $subject);
//        } elseif (strpos($text, LF_MARK) > -1) {
//            $this->_text = str_replace("\n", '', $subject);
//        } elseif (strpos($text, CR_MARK) > -1) {
//            $this->_text = str_replace("\r", '', $subject);
//        }
//
        
        $this->_endPos = strlen($this->_text);
    }

    public function getMatches()
    {
        return $this->_matches;
    }

    public function getCount()
    {
        return $this->_count;
    }

    public function getCursor()
    {
        return $this->_cursor;
    }

    public function getList()
    {
        return $this->_list;
    }
    
    public function fieldValue($i, $field, $value)
    {
        $this->_list[$i][$field] = $value;
    }

    public function getMaxDepth()
    {
        return count($this->_depths);
    }

    public function getMatchesByDepth()
    {
        return $this->_matchesByDepth;
    }

    public function elementName($s, $offset)
    {
        if (!isset($offset)) {
            $offset = 0;
        }
        $result = STR_EMPTY;
        $s2 = STR_EMPTY;

        $openElementPos = 0;
        $closeElementPos = 0;
        $spacePos = 0;

        if ($offset > 0 && $offset < strlen($s)) {
            //$openElementPos = $offset;
            $openElementPos = strpos($s, OPEN_TAG, $offset);
        } else {
            $openElementPos = strpos($s, OPEN_TAG);
        }

        if ($openElementPos == -1) {
            return $result;
        }

        $s2 = substr($s, $openElementPos, strlen($s) - $openElementPos);
        $spacePos = strpos($s2, STR_SPACE);
        $closeElementPos = strpos($s2, CLOSE_TAG);
        if ($closeElementPos > -1 && $spacePos > -1) {
            if ($closeElementPos < $spacePos) {
                $result = substr($s, $openElementPos + 1, $closeElementPos - 1);
            } else {
                $result = substr($s, $openElementPos + 1, $spacePos - 1);
            }
        } elseif ($closeElementPos > -1) {
            $result = substr($s, $openElementPos + 1, $closeElementPos - 1);
        }

        return $result;
    }

    public function getMatch()
    {
        if ($this->_match == null) {
            //$this->_match = new TXmlMatch($this->_list[$this->_matchesByDepth[$this->_id]]);
            $this->_match = new TXmlMatch($this->_list[$this->_id]);
        }

        return $this->_match;
    }

    public function nextMatch()
    {
        $this->_match = null;
        if ($this->_id == $this->_count - 1) {
            return false;
        }

        $this->_id++;

        return ($this->getMatch());
    }

    public function replaceMatch($replace)
    {
        if ($this->_match->hasChildren()) {
            $start = $this->_match->getStart();
            $length = $this->_match->getEnd() - $this->_match->getStart() + 1;
            $needle = substr($this->_text, $start, $length);
            $this->_text = str_replace($needle, $replace, $this->_text);
        } else {
            $this->_text = str_replace($this->_match->getText(), $replace, $this->_text);
        }

        return $this->_text;
    }

    public static function replaceThisMatch(TXmlMatch $match, $text, $replace)
    {
        
        
        //debug(var_export($match, true));
        if ($match->hasChildren()) {
            $start = $match->getStart();
            $closer = $match->getCloser();
            $length = $closer['endsAt'] - $match->getStart() + 1;
            $needle = substr($text, $start, $length);
            $text = str_replace($needle, $replace, $text);
        } else {
            $text = str_replace($match->getText(), $replace, $text);
        }

        return $text;
    }
    
    private function _parse($tag, $text, $cursor)
    {
        $properties = array();
        
        $endElementPos = strpos($text, OPEN_TAG . TERMINATOR . $tag, $cursor);
        $openElementPos = strpos($text, OPEN_TAG . $tag, $cursor);
        if ($openElementPos > -1 && $endElementPos > -1 && $openElementPos > $endElementPos) {
            $openElementPos = $endElementPos;
            $closeElementPos = strpos($text, CLOSE_TAG, $openElementPos);
            return [$openElementPos, $closeElementPos, $properties];
        }
        
        $spacePos = strpos($text, STR_SPACE, $openElementPos);
        $equalPos = strpos($text, '=', $spacePos);
        $openQuotePos = strpos($text, QUOTE, $openElementPos);
        $closeQuotePos = strpos($text, QUOTE, $openQuotePos + 1);
        $lastCloseQuotePos = $closeQuotePos;
        $closeElementPos =  strpos($text, CLOSE_TAG, $lastCloseQuotePos);
        while ($openQuotePos > -1 && $closeQuotePos < $closeElementPos) {
            $key = substr($text, $spacePos + 1, $equalPos - $spacePos - 1);
            $value = substr($text, $openQuotePos + 1, $closeQuotePos - $openQuotePos - 1);
            $properties[trim($key)] = $value;
            $lastCloseQuotePos = $closeQuotePos;
            
            $spacePos = strpos($text, STR_SPACE, $closeQuotePos);
            $equalPos = strpos($text, '=', $spacePos);
            $openQuotePos = strpos($text, QUOTE, $closeQuotePos + 1);
            $closeQuotePos = strpos($text, QUOTE, $openQuotePos + 1);
            $closeElementPos =  strpos($text, CLOSE_TAG, $lastCloseQuotePos);
            if ($openQuotePos < $closeElementPos && $closeQuotePos > $closeElementPos) {
                $closeElementPos =  strpos($text, CLOSE_TAG, $closeQuotePos);
            }
        }
        if ($lastCloseQuotePos > -1) {
            $closeElementPos =  strpos($text, CLOSE_TAG, $lastCloseQuotePos);
        } else {
            $closeElementPos =  strpos($text, CLOSE_TAG, $openElementPos);
        }
        
        return [$openElementPos, $closeElementPos, $properties];
    }

    public function matchAll($tag = TAG_PATTERN_ANY)
    {
        $i = 0;
        $j = -1;

        $s = STR_EMPTY;
        $firstName = STR_EMPTY;
        $secondName = STR_EMPTY;
        
        $cursor = 0;

        $text = $this->_text;

        $result = $this->_parse($tag, $text, $cursor);
        $openElementPos = $result[0];
        $closeElementPos = $result[1];
        $properties = $result[2];
        
        $parentId[0] = -1;
        
        $depth = 0;
        //$this->_depths[$depth] = 1;

        while ($openElementPos > -1 && $closeElementPos > $openElementPos) {
            $s = trim(substr($text, $openElementPos, $closeElementPos - $openElementPos + 1));
            $firstName = $this->elementName($s, $cursor);

            $arr = explode(':', $firstName);

            if ($arr[1] == 'eof') {
                break;
            }
            
            $this->_list[$i]['id'] = $i;
            $this->_list[$i]['method'] = $arr[1];
            $this->_list[$i]['element'] = $s;
            $this->_list[$i]['name'] = $arr[1];
            $this->_list[$i]['startsAt'] = $openElementPos;
            $this->_list[$i]['endsAt'] = $closeElementPos;
            $this->_list[$i]['depth'] = $depth;
            $this->_list[$i]['hasCloser'] = false;
            $this->_list[$i]['childName'] = '';
            if (!isset($parentId[$depth])) {
                $parentId[$depth] = $i - 1;
            }
            $this->_list[$i]['parentId'] = $parentId[$depth];
 
            $p = strpos($s, STR_SPACE);
//            if ($p == strlen($firstName) + 1 && $closeElementPos > $p) {
//                $attributes = trim(substr($s, $p + 1, strlen($s) - $p - 3));
//                $this->_list[$i]['properties'] = TStringUtils::parameterStringToArray($attributes);
//            }

            $this->_list[$i]['properties'] = $properties;

            $cursor = $closeElementPos + 1;
            $secondName = $this->elementName($text, $cursor);
            
            if (TERMINATOR . $firstName != $secondName) {
                if ($s[1] == TERMINATOR) {
                    $depth--;
                    $this->_list[$i]['depth'] = $depth;
                    $pId = $this->_list[$i]['parentId'];
                    $this->_list[$pId]['hasCloser'] = true;
                    if ($this->_list[$pId]['depth'] > 0 && (empty($this->_list[$pId]['properties']['content']))) {
                        $contents = substr($text, $this->_list[$pId]['endsAt'] + 1, $this->_list[$i]['startsAt'] - $this->_list[$pId]['endsAt'] - 1);
                        $this->_list[$pId]['properties']['content'] = '!#base64#' . base64_encode($contents); // uniqid();
                        
                        // TRegistry::write('xml_content', $this->_list[$pId]['properties']['content'], base64_encode($contents));
                    }
                    
                    $this->_list[$pId]['closer'] = $this->_list[$i];
                    unset($this->_list[$i]);
                } elseif ($s[1] == QUEST_MARK) {
                } elseif ($s[strlen($s) - 2] == TERMINATOR) {
                } elseif ($s[1] == SKIP_MARK) {
                } else {
                    $sa = explode(':', $secondName);
                    if (isset($sa[1])) {
                        $this->_list[$i]['childName'] = $sa[1];
                    }

                    $depth++;
                    $this->_depths[$depth] = 1;
                    unset($parentId[$depth]);

                    // if(!isset($sa[1])) {
                    //     $ex = [__CLASS__, __METHOD__, $this->_list[$i], $secondName, $i];
                    //     $this->getLogger()->dump('Unable to match ending tag', $ex);
                    // }
                }
            }

            $result = $this->_parse($tag, $text, $cursor);
            $openElementPos = $result[0];
            $closeElementPos = $result[1];
            $properties = $result[2];
            $cursor = $openElementPos;

            $i++;
        }

        $this->_matchesByDepth = $this->sortMatchesByDepth();

        $this->_count = count($this->_list);
        return ($this->_count > 0);
    }

    public function sortMatchesByDepth()
    {
        $maxDepth = count($this->_depths);
        $result = array();
        for ($i = $maxDepth; $i > -1; $i--) {
            foreach ($this->_list as $part) {
                if ($part["depth"] == $i) {
                    $count = count($result);
                    $result[$count] = $part['id'];
                }
            }
        }

        return $result;
    }
}
