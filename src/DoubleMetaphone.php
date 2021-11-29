<?php
/**
 * DoubleMetaphone
 *
 * @package     Doublemetaphone
 * @author      Adrian Green
 * @copyright   Copyright (c) 2021
 */
declare(strict_types=1);

namespace AdrianGreen\Phonetic;

use function \in_array;
use function \mb_strlen;
use function \mb_strpos;
use function \mb_strtoupper;
use function \mb_substr;
use function \trim;

/**
 * Encodes a string into a double metaphone $value.
 * Ported from org.apache.commons.codec.language.DoubleMetaphone
 */
class DoubleMetaphone
{
    /**
     * "Vowels" to test for
     */
    private const VOWELS = "AEIOUY";
    /**
     * Prefixes when present which are not pronounced
     */
    private const SILENT_START                     = ["GN", "KN", "PN", "WR", "PS"];
    private const L_R_N_M_B_H_F_V_W_SPACE          = ["L", "R", "N", "M", "B", "H", "F", "V", "W", " "];
    private const ES_EP_EB_EL_EY_IB_IL_IN_IE_EI_ER = ["ES", "EP", "EB", "EL", "EY", "IB", "IL", "IN", "IE", "EI", "ER"];
    private const L_T_K_S_N_M_B_Z                  = ["L", "T", "K", "S", "N", "M", "B", "Z"];
    /**
     * Maximum length of an encoding, default is 4
     */
    protected int $maxCodeLen = 4;

    private string $rawValue;

    public function __construct(int $maxCodeLen = 4)
    {
        $this->maxCodeLen = $maxCodeLen;
    }

    /**
     * Check if the Double Metaphone values of two <code>string</code> values
     * are equal, optionally using the alternate $value.
     *
     * @param string $value1    The left-hand side of the encoded
     * @param string $value2    The right-hand side of the encoded
     * @param bool   $alternate use the alternate $value if <code>true</code>.
     *
     * @return bool <code>true</code> if the encoded <code>string</code>s are equal;
     *          <code>false</code> otherwise.
     */
    public function isDoubleMetaphoneEqual(string $value1, string $value2, bool $alternate = false): bool
    {
        return
            $this->doubleMetaphone($value1, $alternate)
            ===
            $this->doubleMetaphone($value2, $alternate);
    }

    /**
     * Encode a $value with Double Metaphone, optionally using the alternate
     * encoding.
     *
     * @param $value     string to encode
     * @param $alternate bool alternate encode
     *
     * @return string
     */
    public function doubleMetaphone(string $value, bool $alternate = false): string
    {
        $this->rawValue = $value;
        $value = $this->cleanInput($value);
        if ($value === '') {
            return '';
        }

        $result = $this->doubleMetaphoneResult($value);

        return $alternate ? $result->getAlternate() : $result->getPrimary();
    }

    /**
     * Encode a $value with Double Metaphone and return DoubleMetaphoneResult pair
     * of encoded values
     *
     * @param $value     string to encode
     *
     * @return DoubleMetaphoneResult
     */
    public function getDoubleMetaphoneResult(string $value): DoubleMetaphoneResult
    {
        $this->rawValue = $value;
        $value = $this->cleanInput($value);
        if ($value === '') {
            new DoubleMetaphoneResult($value);
        }

        return $this->doubleMetaphoneResult($value);
    }

    /**
     * Cleans the input
     */
    private function cleanInput(string $input): string
    {
        if (!$input) {
            return '';
        }

        return mb_strtoupper(
            trim(
                html_entity_decode(
                    \preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x\1;', $input)
                )
            )
        );
    }

    /**
     * Determines whether or not a $value is of slavo-germanic orgin. A $value is
     * of slavo-germanic origin if it contians any of 'W', 'K', 'CZ', or 'WITZ'.
     */
    private function isSlavoGermanic(string $value): bool
    {
        return
            mb_strpos($value, 'W') !== false
            || mb_strpos($value, 'K') !== false
            || mb_strpos($value, 'CZ') !== false
            || mb_strpos($value, 'WITZ') !== false;
    }

    //-- BEGIN HANDLERS --//

    /**
     * Determines whether the $value starts with a silent letter.  It will
     * return <code>true</code> if the $value starts with any of 'GN', 'KN', 'PN', 'WR' or 'PS'.
     */
    private function isSilentStart(string $value): bool
    {
        foreach (self::SILENT_START as $silent) {
            if(0 === mb_strpos($value, $silent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handles 'A', 'E', 'I', 'O', 'U', and 'Y' cases
     */
    private function handleAEIOUY(DoubleMetaphoneResult $result, int $index): int
    {
        if ($index === 0) {
            $result->append('A');
        }

        return $index + 1;
    }

    /**
     * Gets the character at $index <code>$index</code> if available, otherwise
     * it returns <code>\u0000</code> so that there is a default
     */
    protected function charAt(string $value, int $index): string
    {
        if (($index < 0) || !($index < mb_strlen($value))) {
            return '\u0000';
        }

        return mb_substr($value, $index, 1);
    }

    /**
     * Handles 'C' cases
     */
    private function handleC(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->conditionC0($value, $index)) {  // very confusing, moved out
            $result->append('K');
            $index += 2;
        } else if ($index === 0 && $this->contains($value, $index, 6, "CAESAR")) {
            $result->append('S');
            $index += 2;
        } else if ($this->contains($value, $index, 2, "CH")) {
            $index = $this->handleCH($value, $result, $index);
        } else if ($this->contains($value, $index, 2, "CZ") && !$this->contains($value, $index - 2, 4, "WICZ")) {
            //-- "Czerny" --//
            $result->append('S', 'X');
            $index += 2;
        } else if ($this->contains($value, $index + 1, 3, "CIA")) {
            //-- "focaccia" --//
            $result->append('X');
            $index += 3;
        } else {
            if ($this->contains($value, $index, 2, "CC") && !($index === 1 && $this->charAt($value, 0) === 'M')) {
                //-- double "cc" but not "McClelland" --//
                return $this->handleCC($value, $result, $index);
            }

            if ($this->contains($value, $index, 2, "CK", "CG", "CQ")) {
                $result->append('K');
                $index += 2;
            } else if ($this->contains($value, $index, 2, "CI", "CE", "CY")) {
                //-- Italian vs. English --//
                if ($this->contains($value, $index, 3, "CIO", "CIE", "CIA")) {
                    $result->append('S', 'X');
                } else {
                    $result->append('S');
                }
                $index += 2;
            } else {
                $result->append('K');
                if ($this->contains($value, $index + 1, 2, " C", " Q", " G")) {
                    //-- Mac Caffrey, Mac Gregor --//
                    $index += 3;
                } else if ($this->contains($value, $index + 1, 1, "C", "K", "Q")
                    && !$this->contains($value, $index + 1, 2, "CE", "CI")) {
                    $index += 2;
                } else {
                    $index++;
                }
            }
        }

        return $index;
    }

    /**
     * Complex condition 0 for 'C'
     */
    private function conditionC0(string $value, int $index): bool
    {
        if ($this->contains($value, $index, 4, "CHIA")) {
            return true;
        }

        if ($index <= 1) {
            return false;
        }

        if ($this->isVowel($this->charAt($value, $index - 2))) {
            return false;
        }

        if (!$this->contains($value, $index - 1, 3, "ACH")) {
            return false;
        }

        $c = $this->charAt($value, $index + 2);

        return ($c !== 'I' && $c !== 'E')
            || $this->contains($value, $index - 2, 6, "BACHER", "MACHER");
    }

    /**
     * Determines whether <code>$value</code> contains any of the criteria starting
     * at $index <code>start</code> and matching up to length <code>length</code>
     *
     * @param string $value
     * @param int    $start
     * @param int    $length
     * @param        ...$criteria
     *
     * @return bool
     */
    protected function contains(string $value, int $start, int $length, ...$criteria): bool
    {
        if ($start >= 0 && ($start + $length) <= mb_strlen($value)) {
            $target = mb_substr($value, $start, /*$start + */$length);
            if(in_array($target, $criteria, true)){
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether a character is a vowel or not
     */
    private function isVowel(string $ch): bool
    {
        return mb_strpos(self::VOWELS, $ch) !== false;
    }

    /**
     * Handles 'CH' cases
     */
    private function handleCH(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($index > 0 && $this->contains($value, $index, 4, "CHAE")) {   // Michael
            $result->append('K', 'X');

            return $index + 2;
        }

        if ($this->conditionCH0($value, $index)) {
            //-- Greek roots ("chemistry", "chorus", etc.) --//
            $result->append('K');

            return $index + 2;
        }

        if ($this->conditionCH1($value, $index)) {
            //-- Germanic, Greek, or otherwise 'ch' for 'kh' sound --//
            $result->append('K');

            return $index + 2;
        }

        if ($index > 0) {
            if ($this->contains($value, 0, 2, "MC")) {
                $result->append('K');
            } else {
                $result->append('X', 'K');
            }
        } else {
            $result->append('X');
        }

        return $index + 2;
    }

    /**
     * Complex condition 0 for 'CH'
     */
    private function conditionCH0(string $value, int $index): bool
    {
        if ($index !== 0) {
            return false;
        }

        if (!$this->contains($value, $index + 1, 5, "HARAC", "HARIS")
            && !$this->contains($value, $index + 1, 3, "HOR", "HYM", "HIA", "HEM")) {
            return false;
        }

        if ($this->contains($value, 0, 5, "CHORE")) {
            return false;
        }

        return true;
    }

    /**
     * Complex condition 1 for 'CH'
     */
    private function conditionCH1(string $value, int $index): bool
    {
        return
            (
                ($this->contains($value, 0, 4, "VAN ", "VON ") || $this->contains($value, 0, 3, "SCH"))
                ||
                $this->contains($value, $index - 2, 6, "ORCHES", "ARCHIT", "ORCHID")
                ||
                $this->contains($value, $index + 2, 1, "T", "S")
                ||
                (
                    ($this->contains($value, $index - 1, 1, "A", "O", "U", "E") || $index === 0)
                    &&
                    (
                        $this->contains($value, $index + 2, 1, ...self::L_R_N_M_B_H_F_V_W_SPACE)
                        || $index + 1 === mb_strlen($value) - 1
                    )
                )
            );
    }

    /**
     * Handles 'CC' cases
     */
    private function handleCC(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->contains($value, $index + 2, 1, "I", "E", "H") && !$this->contains($value, $index + 2, 2, "HU")) {
            //-- "bellocchio" but not "bacchus" --//
            if (($index === 1 && $this->charAt($value, $index - 1) === 'A')
                || $this->contains(
                    $value, $index - 1, 5, "UCCEE", "UCCES"
                )) {
                //-- "accident", "accede", "succeed" --//
                $result->append("KS");
            } else {
                //-- "bacci", "bertucci", other Italian --//
                $result->append('X');
            }
            $index += 3;
        } else {    // Pierce's rule
            $result->append('K');
            $index += 2;
        }

        return $index;
    }

    /**
     * Handles 'D' cases
     */
    private function handleD(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->contains($value, $index, 2, "DG")) {
            //-- "Edge" --//
            if ($this->contains($value, $index + 2, 1, "I", "E", "Y")) {
                $result->append('J');
                $index += 3;
                //-- "Edgar" --//
            } else {
                $result->append("TK");
                $index += 2;
            }
        } else {
            $result->append('T');
            if ($this->contains($value, $index, 2, "DT", "DD")) {
                $index += 2;
            } else {
                $index++;
            }
        }

        return $index;
    }

    /**
     * Handles 'G' cases
     */
    private function handleG(string $value, DoubleMetaphoneResult $result, int $index, bool $slavoGermanic): int
    {
        if ($this->charAt($value, $index + 1) === 'H') {
            $index = $this->handleGH($value, $result, $index);
        } else if ($this->charAt($value, $index + 1) === 'N') {
            if (!$slavoGermanic && $index === 1 && $this->isVowel($this->charAt($value, 0))) {
                $result->append("KN", "N");
            } else if (!$slavoGermanic && !$this->contains($value, $index + 2, 2, "EY") && $this->charAt($value, $index + 1) !== 'Y') {
                $result->append("N", "KN");
            } else {
                $result->append("KN");
            }
            $index += 2;
        } else if (!$slavoGermanic && $this->contains($value, $index + 1, 2, "LI")) {
            $result->append("KL", "L");
            $index += 2;
        } else if ($index === 0
            && ($this->charAt($value, $index + 1) === 'Y'
                || $this->contains($value, $index + 1, 2, ...self::ES_EP_EB_EL_EY_IB_IL_IN_IE_EI_ER))) {
            //-- -ges-, -gep-, -gel-, -gie- at beginning --//
            $result->append('K', 'J');
            $index += 2;
        } else if (
            !$this->contains($value, 0, 6, "DANGER", "RANGER", "MANGER")
            && !$this->contains($value, $index - 1, 1, "E", "I")
            && !$this->contains($value, $index - 1, 3, "RGY", "OGY")
            && ($this->contains($value, $index + 1, 2, 'ER') || $this->charAt($value, $index + 1) === 'Y')
        ) {
            //-- -ger-, -gy- --//
            $result->append('K', 'J');
            $index += 2;
        } else if ($this->contains($value, $index + 1, 1, "E", "I", "Y")
            || $this->contains(
                $value, $index - 1, 4, "AGGI", "OGGI"
            )) {
            //-- Italian "biaggi" --//
            if (($this->contains($value, 0, 4, "VAN ", "VON ") || $this->contains($value, 0, 3, "SCH"))
                || $this->contains($value, $index + 1, 2, "ET")) {
                //-- obvious germanic --//
                $result->append('K');
            } else if ($this->contains($value, $index + 1, 3 , "IER")) {
                $result->append('J');
            } else {
                $result->append('J', 'K');
            }
            $index += 2;
        } else {
            if ($this->charAt($value, $index + 1) === 'G') {
                $index += 2;
            } else {
                $index++;
            }
            $result->append('K');
        }

        return $index;
    }

    /**
     * Handles 'GH' cases
     */
    private function handleGH(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($index > 0 && !$this->isVowel($this->charAt($value, $index - 1))) {
            $result->append('K');
        } else if ($index === 0) {
            if ($this->charAt($value, $index + 2) === 'I') {
                $result->append('J');
            } else {
                $result->append('K');
            }
        } else if (($index <= 1 || !$this->contains($value, $index - 2, 1, "B", "H", "D"))
            && ($index <= 2
                || !$this->contains(
                    $value, $index - 3, 1, "B", "H", "D"
                ))
            && ($index <= 3 || !$this->contains($value, $index - 4, 1, "B", "H"))) {
            if ($index > 2 && $this->charAt($value, $index - 1) === 'U'
                && $this->contains(
                    $value, $index - 3, 1, "C", "G", "L", "R", "T"
                )) {
                //-- "laugh", "McLaughlin", "cough", "gough", "rough", "tough"
                $result->append('F');
            } else if ($index > 0 && $this->charAt($value, $index - 1) !== 'I') {
                $result->append('K');
            }
        }
        $index += 2;

        return $index;
    }

    /**
     * Handles 'H' cases
     */
    private function handleH(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        //-- only keep if first & before vowel or between 2 vowels --//
        if (($index === 0 || $this->isVowel($this->charAt($value, $index - 1)))
            && $this->isVowel($this->charAt($value, $index + 1))) {
            $result->append('H');
            $index += 2;
            //-- also takes care of "HH" --//
        } else {
            $index++;
        }

        return $index;
    }

    /**
     * Handles 'J' cases
     */
    private function handleJ(string $value, DoubleMetaphoneResult $result, int $index, bool $slavoGermanic): int
    {
        if ($this->contains($value, $index, 4, "JOSE") || $this->contains($value, 0, 4, "SAN ")) {
            //-- obvious Spanish, "Jose", "San Jacinto" --//
            if ((($index === 0 && ($this->charAt($value, $index + 4) === ' '))
                    || mb_strlen($value) === 4)
                || $this->contains($value, 0, 4, "SAN ")) {
                $result->append('H');
            } else {
                $result->append('J', 'H');
            }
            $index++;
        } else {
            if ($index === 0 && !$this->contains($value, $index, 4, "JOSE")) {
                $result->append('J', 'A');
            } else if (!$slavoGermanic && $this->isVowel($this->charAt($value, $index - 1))
                && ($this->charAt($value, $index + 1) === 'A' || $this->charAt($value, $index + 1) === 'O')) {
                $result->append('J', 'H');
            } else if ($index === mb_strlen($value) - 1) {
                $result->append('J', ' ');
            } else if (!$this->contains($value, $index + 1, 1, ...self::L_T_K_S_N_M_B_Z)
                && !$this->contains($value, $index - 1, 1, "S", "K", "L")) {
                $result->append('J');
            }
            if ($this->charAt($value, $index + 1) === 'J') {
                $index += 2;
            } else {
                $index++;
            }
        }

        return $index;
    }

    /**
     * Handles 'L' cases
     */
    private function handleL(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->charAt($value, $index + 1) === 'L') {
            if ($this->conditionL0($value, $index)) {
                $result->appendPrimary('L');
            } else {
                $result->append('L');
            }
            $index += 2;
        } else {
            $index++;
            $result->append('L');
        }

        return $index;
    }

    /**
     * Complex condition 0 for 'L'
     */
    private function conditionL0(string $value, int $index): bool
    {
        if ($index === mb_strlen($value) - 3 && $this->contains($value, $index - 1, 4, "ILLO", "ILLA", "ALLE")) {
            return true;
        }

        if (($this->contains($value, \mb_strlen($value) - 2, 2, "AS", "OS") ||
                $this->contains($value, mb_strlen($value) - 1, 1, "A", "O")) &&
                $this->contains($value, $index - 1, 4, "ALLE")) {
            return true;
        }

        return false;
    }
    //-- BEGIN CONDITIONS --//

    /**
     * Complex condition 0 for 'M'
     */
    private function conditionM0dleL(string $value, int $index): bool
    {
        if ($this->charAt($value, $index + 1) === 'M') {
            return true;
        }

        return $this->contains($value, $index - 1, 3, "UMB")
            && (($index + 1) === mb_strlen($value) - 1
                || $this->contains( $value,$index + 2, 2, "ER"));
    }

    /**
     * Handles 'P' cases
     */
    private function handleP(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->charAt($value, $index + 1) === 'H') {
            $result->append('F');
            $index += 2;
        } else {
            $result->append('P');
            $index = $this->contains($value, $index + 1, 1, "P", "B") ? $index + 2 : $index + 1;
        }

        return $index;
    }

    /**
     * Handles 'R' cases
     */
    private function handleR(string $value, DoubleMetaphoneResult $result, int $index, bool $slavoGermanic): int
    {
        if (!$slavoGermanic && $index === mb_strlen($value) - 1 && $this->contains($value, $index - 2, 2, "IE")
            && !$this->contains($value, $index - 4, 2, "ME", "MA")) {
            $result->appendAlternate('R');
        } else {
            $result->append('R');
        }

        return $this->charAt($value, $index + 1) === 'R' ? $index + 2 : $index + 1;
    }

    /**
     * Handles 'S' cases
     */
    private function handleS(string $value, DoubleMetaphoneResult $result, int $index, bool $slavoGermanic): int
    {
        if ($this->contains($value, $index - 1, 3, "ISL", "YSL")) {
            //-- special cases "island", "isle", "carlisle", "carlysle" --//
            $index++;
        } else if ($index === 0 && $this->contains($value, $index, 5, "SUGAR")) {
            //-- special case "sugar-" --//
            $result->append('X', 'S');
            $index++;
        } else if ($this->contains($value, $index, 2, "SH")) {
            if ($this->contains(
                $value, $index + 1, 4,
                "HEIM", "HOEK", "HOLM", "HOLZ"
            )) {
                //-- germanic --//
                $result->append('S');
            } else {
                $result->append('X');
            }
            $index += 2;
        } else if ($this->contains($value, $index, 3, "SIO", "SIA") || $this->contains($value, $index, 4, "SIAN")) {
            //-- Italian and Armenian --//
            if ($slavoGermanic) {
                $result->append('S');
            } else {
                $result->append('S', 'X');
            }
            $index += 3;
        } else if (($index === 0 && $this->contains($value, $index + 1, 1, "M", "N", "L", "W"))
            || $this->contains(
                $value, $index + 1, 1, "Z"
            )) {
            //-- german & anglicisations, e.g. "smith" match "schmidt" //
            // "snider" match "schneider" --//
            //-- also, -sz- in slavic language although in hungarian it //
            //   is pronounced "s" --//
            $result->append('S', 'X');
            $index = $this->contains($value, $index + 1, 1, "Z") ? $index + 2 : $index + 1;
        } else if ($this->contains($value, $index, 2, "SC")) {
            $index = $this->handleSC($value, $result, $index);
        } else {
            if ($index === mb_strlen($value) - 1
                && $this->contains($value, $index - 2, 2, "AI", "OI")) {
                //-- french e.g. "resnais", "artois" --//
                $result->appendAlternate('S');
            } else {
                $result->append('S');
            }
            $index = $this->contains($value, $index + 1, 1, "S", "Z") ? $index + 2 : $index + 1;
        }

        return $index;
    }

    /**
     * Handles 'SC' cases
     */
    private function handleSC(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->charAt($value, $index + 2) === 'H') {
            //-- Schlesinger's rule --//
            if ($this->contains($value, $index + 3,  2, "OO", "ER", "EN", "UY", "ED", "EM")) {
                //-- Dutch origin, e.g. "school", "schooner" --//
                if ($this->contains($value, $index + 3, 2, "ER", "EN")) {
                    //-- "schermerhorn", "schenker" --//
                    $result->append("X", "SK");
                } else {
                    $result->append("SK");
                }
            } else if ($index === 0 && !$this->isVowel($this->charAt($value, 3)) && $this->charAt($value, 3) !== 'W') {
                $result->append('X', 'S');
            } else {
                $result->append('X');
            }
        } else if ($this->contains($value, $index + 2, 1, "I", "E", "Y")) {
            $result->append('S');
        } else {
            $result->append("SK");
        }

        return $index + 3;
    }

    /**
     * Handles 'T' cases
     */
    private function handleT(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->contains($value, $index, 4, "TION")) {
            $result->append('X');
            $index += 3;
        } else if ($this->contains($value, $index, 3, "TIA", "TCH")) {
            $result->append('X');
            $index += 3;
        } else if ($this->contains($value, $index, 2, "TH")
            || $this->contains(
                $value, $index,
                3, "TTH"
            )) {
            if ($this->contains($value, $index + 2, 2, "OM", "AM")
                || //-- special case "thomas", "thames" or germanic --//
                $this->contains($value, 0, 4, "VAN ", "VON ")
                || $this->contains($value, 0, 3, "SCH")) {
                $result->append('T');
            } else {
                $result->append('0', 'T');
            }
            $index += 2;
        } else {
            $result->append('T');
            $index = $this->contains($value, $index + 1, 1, "T", "D") ? $index + 2 : $index + 1;
        }

        return $index;
    }

    /**
     * Handles 'W' cases
     */
    private function handleW(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($this->contains($value, $index, 2, "WR")) {
            //-- can also be in middle of word --//
            $result->append('R');
            $index += 2;
        } else if ($index === 0
            && ($this->isVowel($this->charAt($value, $index + 1))
                || $this->contains(
                    $value, $index, 2, "WH"
                ))) {
            if ($this->isVowel($this->charAt($value, $index + 1))) {
                //-- Wasserman should match Vasserman --//
                $result->append('A', 'F');
            } else {
                //-- need Uomo to match Womo --//
                $result->append('A');
            }
            $index++;
        } else if (($index === mb_strlen($value) - 1 && $this->isVowel($this->charAt($value, $index - 1)))
            || $this->contains($value, $index - 1, 5, "EWSKI", "EWSKY", "OWSKI", "OWSKY")
            || $this->contains($value, 0, 3, "SCH")) {
            //-- Arnow should match Arnoff --//
            $result->appendAlternate('F');
            $index++;
        } else if ($this->contains($value, $index, 4, "WICZ", "WITZ")) {
            //-- Polish e.g. "filipowicz" --//
            $result->append("TS", "FX");
            $index += 4;
        } else {
            $index++;
        }

        return $index;
    }

    /**
     * Handles 'X' cases
     */
    private function handleX(string $value, DoubleMetaphoneResult $result, int $index): int
    {
        if ($index === 0) {
            $result->append('S');
            $index++;
        } else {
            if (!(($index === mb_strlen($value) - 1)
                && ($this->contains($value, $index - 3, 3, "IAU", "EAU")
                    || $this->contains(
                        $value, $index - 2, 2, "AU", "OU"
                    )))) {
                //-- French e.g. breaux --//
                $result->append("KS");
            }
            $index = $this->contains($value, $index + 1, 1, "C", "X") ? $index + 2 : $index + 1;
        }

        return $index;
    }

    /**
     * Handles 'Z' cases
     */
    private function handleZ(string $value, DoubleMetaphoneResult $result, int $index, bool $slavoGermanic): int
    {
        if ($this->charAt($value, $index + 1) === 'H') {
            //-- Chinese pinyin e.g. "zhao" or Angelina "Zhang" --//
            $result->append('J');
            $index += 2;
        } else {
            if ($this->contains($value, $index + 1, 2, "ZO", "ZI", "ZA")
                || ($slavoGermanic
                    && ($index > 0
                        && $this->charAt(
                            $value, $index - 1
                        ) !== 'T'))) {
                $result->append("S", "TS");
            } else {
                $result->append('S');
            }
            $index = $this->charAt($value, $index + 1) === 'Z' ? $index + 2 : $index + 1;
        }

        return $index;
    }

    /**
     * Returns the maxCodeLen.
     *
     * @return int
     */
    public function getMaxCodeLen(): int
    {
        return $this->maxCodeLen;
    }

    /**
     * Sets the maxCodeLen.
     *
     * @param int maxCodeLen The maxCodeLen to set
     */
    public function setMaxCodeLen(int $maxCodeLen): void
    {
        $this->maxCodeLen = $maxCodeLen;
    }

    protected function doubleMetaphoneResult(string $value): DoubleMetaphoneResult
    {
        $result      = new DoubleMetaphoneResult($value, $this->maxCodeLen);
        $slavoGermanic = $this->isSlavoGermanic($value);
        $index         = $this->isSilentStart($value) ? 1 : 0;

        $valueLength = mb_strlen($value) - 1;
        while (!$result->isComplete() && $index <= $valueLength) {
            switch ($this->charAt($value, $index)) {
                case 'A':
                case 'E':
                case 'I':
                case 'O':
                case 'U':
                case 'Y':
                    $index = $this->handleAEIOUY($result, $index);
                    break;
                case 'B':
                    $result->append('P');
                    $index = $this->charAt($value, $index + 1) === 'B' ? $index + 2 : $index + 1;
                    break;
                /*case '\u00C7':
                    // A C with a Cedilla
                    $result->append('S');
                    $index++;
                    break;*/
                case 'C':
                    $index = $this->handleC($value, $result, $index);
                    break;
                case 'D':
                    $index = $this->handleD($value, $result, $index);
                    break;
                case 'F':
                    $result->append('F');
                    $index = $this->charAt($value, $index + 1) === 'F' ? $index + 2 : $index + 1;
                    break;
                case 'G':
                    $index = $this->handleG($value, $result, $index, $slavoGermanic);
                    break;
                case 'H':
                    $index = $this->handleH($value, $result, $index);
                    break;
                case 'J':
                    $index = $this->handleJ($value, $result, $index, $slavoGermanic);
                    break;
                case 'K':
                    $result->append('K');
                    $index = $this->charAt($value, $index + 1) === 'K' ? $index + 2 : $index + 1;
                    break;
                case 'L':
                    $index = $this->handleL($value, $result, $index);
                    break;
                case 'M':
                    $result->append('M');
                    $index = $this->conditionM0dleL($value, $index) ? $index + 2 : $index + 1;
                    break;
                case 'N':
                    $result->append('N');
                    $index = $this->charAt($value, $index + 1) === 'N' ? $index + 2 : $index + 1;
                    break;
                /*case '\u00D1':
                    // N with a tilde (spanish ene)
                    $result->append('N');
                    $index++;
                    break;*/
                case 'P':
                    $index = $this->handleP($value, $result, $index);
                    break;
                case 'Q':
                    $result->append('K');
                    $index = $this->charAt($value, $index + 1) === 'Q' ? $index + 2 : $index + 1;
                    break;
                case 'R':
                    $index = $this->handleR($value, $result, $index, $slavoGermanic);
                    break;
                case 'S':
                    $index = $this->handleS($value, $result, $index, $slavoGermanic);
                    break;
                case 'T':
                    $index = $this->handleT($value, $result, $index);
                    break;
                case 'V':
                    $result->append('F');
                    $index = $this->charAt($value, $index + 1) === 'V' ? $index + 2 : $index + 1;
                    break;
                case 'W':
                    $index = $this->handleW($value, $result, $index);
                    break;
                case 'X':
                    $index = $this->handleX($value, $result, $index);
                    break;
                case 'Z':
                    $index = $this->handleZ($value, $result, $index, $slavoGermanic);
                    break;
                /**
                 * multi-byte cases
                 */
                case 'Ç': // see '\u00C7'
                    $result->append('S');
                    ++$index;
                    break;
                case 'Ñ': // see '\u00D1'
                    $result->append('N');
                    ++$index;
                    break;
                default:
                    $index++;
                    break;
            }
        }

        return $result;
    }

}
