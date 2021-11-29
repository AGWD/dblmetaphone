<?php
/**
 * DoubleMetaphoneResult
 *
 * @package     Doublemetaphone
 * @author      Adrian Green
 * @copyright   Copyright (c) 2021
 */
declare(strict_types=1);

namespace AdrianGreen\Phonetic;

use AdrianGreen\String\StringBuffer;

/**
 * Class for storing results, since there is the optional alternate encoding.
 */
class DoubleMetaphoneResult
{
    private StringBuffer $primary;
    private StringBuffer $alternate;
    private string       $value;
    private int          $maxLength;

    public function __construct(string $value = '', int $maxLength = 4)
    {
        $this->maxLength = $maxLength;
        $this->value     = $value;
        $this->primary   = new StringBuffer('');
        $this->alternate = new StringBuffer('');
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function append(string $primary, string $alternate = ''): void
    {
        $this->appendPrimary($primary);
        if ($alternate) {
            $this->appendAlternate($alternate);
        } else {
            $this->appendAlternate($primary);
        }

    }

    public function appendPrimary(string $value)
    {
        if ($this->primary->length() < $this->maxLength) {
            $this->primary->append($value);
        }
    }

    public function appendAlternate(string $value): void
    {
        $addChars = $this->maxLength - $this->alternate->length();
        if (\strlen($value) <= $addChars) {
            $this->alternate->append($value);
        } else {
            $this->alternate->append(substr($value, 0, $addChars));
        }
    }

    public function isComplete(): bool
    {
        return $this->primary->length() >= $this->maxLength
            && $this->alternate->length() >= $this->maxLength;
    }

    /**
     * @throws \JsonException
     */
    public function toJson(): string
    {

        return \json_encode([
            'value'     => $this->value,
            'primary'   => $this->getPrimary(),
            'alternate' => $this->getAlternate()
        ], JSON_THROW_ON_ERROR);
    }

    public function getPrimary(): string
    {
        return \mb_substr($this->primary->toString(), 0, $this->maxLength);
    }

    public function getAlternate(): string
    {
        return \mb_substr($this->alternate->toString(), 0, $this->maxLength);
    }
}

