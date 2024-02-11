<?php
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>
//


//======================================================================================================
// Overview:
//======================================================================================================
/* README:

   Evaluate a complex expression recursively to a returnable single result set

   $object->evaluate($expression,$data);

   RETURN: {"return": RETURN_VALUE}
     Return overrides the standard result return of true/false with a custom return value

   IF: {"if": [CONDITION, TRUE_RESPONSE, FALSE_RESPONSE]}
     If processes a condition (always another evalengine directive, and can be a nested complex expression)
     and returns the true response if the condition evaluates true, and false reponse otherwise

   EVAL: {"eval": "EVALUATION"}
     Eval is the core component which performs evaluation logic. Evaluation expression can be in several
     different forms and look at the supplied data for key/value lookups:

        KEY OPERATOR VALUE:  Search data for key that has a value matching the operator

     KEY: Can be a single string or a regex.  If regex, then OPERATOR can be prefixed with:
        {MINMATCH,MAXMATCH} MINMATCH must be > 0, MAXMATCH is optional

     OPERATORS:
        (Can be prefixed with {MINMATCH,MAXMATCH} in case of regex detected KEY
           "===": equivalence of value and type (returns boolean)
           "!==": non-equivalence of value and type (returns boolean)
           "==": equivalence of value (returns boolean)
           "!=": non-equivalence of value (returns boolean)
           "<": less-than value (returns boolean)
           ">":  greater-than value (returns boolean)
           "<=": less-than-or-equal value (returns boolean)
           ">=": greater-than-or-equal value (returns boolean)
           "~=": regular expression match value (returns 1 for true or 0 for false)
           ":=": regular expression match array (returns matching array elements as array)

     VALUE: A string|integer|boolean value or string regex, bounded optionally by single or double quotes

   AND: {"and": [CONDITION, CONDITION, ...]}
     And returns true if all conditions are true, otherwise false

   OR: {"or": [CONDITION, CONDITION, ...]}
     And returns true if any conditions are true, otherwise false

*/
//======================================================================================================

include_once 'base.class.php';

class EvalEngine extends Base
{
   protected $version  = 1.0;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }

   public function evaluate($input, $values)
   {
      if (isset($input['return'])) {
         $result = $input['return'];
         $this->debug(9,"RETURN result: ".json_encode($result));
         return $result;
      }
      else if ($input['if']) {
         $this->debug(9,"IF");
         $result = ($this->evaluate($input['if'][0],$values)) ? $this->evaluate($input['if'][1],$values) : $this->evaluate($input['if'][2],$values);
         $this->debug(9,"IF result: ".json_encode($result));
         return $result;
      }
      else if ($input['eval']) {
         $result = false;
         $this->debug(9,"EVAL");

         if (preg_match("/^(\S+)\s+(\S+)\s+((?:['\"])?.*(?:['\"])?)$/",$input['eval'],$match)) {
            $matchKey   = $match[1];
            $matchOp    = $match[2];
            $matchValue = $match[3];
            $matchType  = 'exact';
            $matchList  = array();
            $minMatch   = null;
            $maxMatch   = null;

            // turn true|false|null from their string forms (user didn't place them in quotes) to their proper boolean/null
            if (preg_match('/^(true|false|null)$/i',$matchValue)) {
               $matchValue = strtolower($matchValue);
               $matchValue = ($matchValue == 'null') ? null : filter_var($matchValue,FILTER_VALIDATE_BOOLEAN);
            }
            else {
               // pull off any quotes attached as we no longer need them
               $matchValue = trim($matchValue,"'\"");
            }

            // We found a regex key request, build list of possible keys from data
            if (preg_match('@^[/~]@',$matchKey)) {
               $matchList = array_filter(preg_grep($matchKey,array_keys($values)));

               if (empty($matchList)) {
                  $this->debug(9,"Eval couldn't find $matchType matching keys: $matchKey");
                  return $result;
               }

               $matchType = 'regex';
            }
            // Single match key found
            else {
               if (!array_key_exists($matchKey,$values)) {
                  $this->debug(9,"Eval couldn't find $matchType matching key: $matchKey");
                  return $result;
               }

               $matchList[] = $matchKey;
            }

            $listCount = count($matchList);

            // Look for limits on the operator and extract them
            if (preg_match('/^\{(.*?)\}(.*)/',$matchOp,$limitMatch)) {
               list($minMatch,$maxMatch) = explode(',',$limitMatch[1]);

               if ($minMatch == '*') { $minMatch = $listCount; }
               if ($maxMatch == '*') { $maxMatch = $listCount; }

               if ($minMatch < 0)          { $minMatch = 0; }
               if ($maxMatch > $listCount) { $maxMatch = $listCount; }

               $matchOp = $limitMatch[2];
            }

            $matchCount   = 0;
            $enforceCount = ($matchType == 'regex') ? true : false;

            if ($enforceCount) {
               // If no limits were imposed, we default assume that all list items must match for true result
               if (is_null($minMatch) && is_null($maxMatch)) { $minMatch = $listCount; }

               $this->debug(9,"List($listCount): min($minMatch) max($maxMatch) op($matchOp) value($matchValue)");
            }

            if ($enforceCount && $minMatch == 0 && $maxMatch == $listCount) {
               $this->debug(9,"Auto true evaluation due to min=0 max=listCount");
               $result = true;
            }
            else {
               foreach ($matchList as $listKey) {
                  $result = $this->operator($listKey,$values[$listKey],$matchOp,$matchValue);
                  $this->debug(9,"Result $listKey (".$values[$listKey].") = $result");

                  if ($result) { $matchCount++; }
               }
            }

            // If we had imposed match limits, determine outcome now
            if ($enforceCount) {
               if ((!is_null($minMatch) && $matchCount < $minMatch) || (!is_null($maxMatch) && $matchCount > $maxMatch)) {
                  $this->debug(9,"List limit violation: found($matchCount) minWanted($minMatch) maxWanted($maxMatch)");
                  $result = false;
               }
               else { $result = true; }
            }
         }

         $this->debug(9,"EVAL result: ".json_encode($result));

         return $result;
      }
      else if ($input['and']) {
         $this->debug(9,"AND");
         foreach ($input['and'] as $addList) {
            $result = $this->evaluate($addList,$values);
            if (!$result) {
               $this->debug(9,"AND false");
               return false;
            }
         }
         $this->debug(9,"AND true");
         return true;
      }
      else if ($input['or']) {
         $this->debug(9,"OR loop");
         foreach ($input['or'] as $orList) {
            $result = $this->evaluate($orList,$values);
            if ($result) {
               $this->debug(9,"OR true");
               return true;
            }
         }
         $this->debug(9,"OR false");
         return false;
      }
   }

   public function operator($evalKey, $evalValue, $evalOp, $matchValue)
   {
      $this->debug(9,"Evaluating: $evalKey $evalOp ".json_encode(array('match' => $matchValue, 'value' => $evalValue)).")");

      $result = false;

      switch ($evalOp) {
         case "===": $result = ($evalValue === $matchValue); break;
         case "!==": $result = ($evalValue !== $matchValue); break;
         case "==": $result = ($evalValue == $matchValue); break;
         case "!=": $result = ($evalValue != $matchValue); break;
         case "<":  $result = ($evalValue < $matchValue); break;
         case ">":  $result = ($evalValue > $matchValue); break;
         case "<=": $result = ($evalValue <= $matchValue); break;
         case ">=": $result = ($evalValue >= $matchValue); break;
         case "~=": $result = (preg_match($matchValue,$evalValue)); break;
         case ":=": $result = (preg_grep($matchValue,$evalValue)); break;
      }

      return $result;
   }
}

?>
