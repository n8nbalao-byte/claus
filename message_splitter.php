<?php
/**
 * splitMessages($text)
 *
 * Splits an AI response into natural short messages,
 * like WhatsApp multi-bubble behaviour.
 *
 * Rules:
 *  - Split on double newlines first
 *  - Then split on sentence endings (. ! ?) followed by a space + uppercase or digit-start word
 *  - NEVER split inside decimal numbers like 1.500,00 or 3.14
 *  - Merge fragments shorter than 15 chars with the next one
 *  - Trim each piece; skip blank pieces
 */
function splitMessages(string $text): array {
    if (empty(trim($text))) return [];

    // 1. Split on double newlines
    $paras = preg_split('/\n{2,}/', $text);
    $parts = [];

    foreach ($paras as $para) {
        $para = trim($para);
        if ($para === '') continue;

        // 2. Split on sentence boundaries:
        //    A period/!/? followed by a space and an uppercase or common word-start char.
        //    But NOT when the period is between digits (decimal numbers).
        //    Regex: look-behind for [.!?] that is NOT preceded by a digit-dot-digit pattern,
        //    then look-ahead for space + uppercase letter.
        $sentences = preg_split(
            '/(?<![0-9])([.!?])(?=["\s]+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ\d])/u',
            $para,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // preg_split with DELIM_CAPTURE interleaves delimiters, reassemble
        $buf = '';
        $count = count($sentences);
        for ($i = 0; $i < $count; $i++) {
            $tok = $sentences[$i];
            // If this is a captured delimiter (single punctuation char), attach to previous
            if (preg_match('/^[.!?]$/', $tok)) {
                $buf .= $tok;
            } else {
                if ($buf !== '') {
                    $parts[] = trim($buf);
                }
                $buf = $tok;
            }
        }
        if (trim($buf) !== '') $parts[] = trim($buf);
    }

    // 3. Merge tiny fragments (< 20 chars) with the next piece
    $merged = [];
    $carry  = '';
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if ($carry !== '') {
            $p = $carry . ' ' . $p;
            $carry = '';
        }
        if (mb_strlen($p) < 20 && count($merged) === 0) {
            // Too short as first message — carry forward
            $carry = $p;
        } else {
            $merged[] = $p;
        }
    }
    if ($carry !== '') {
        if (!empty($merged)) {
            $merged[count($merged)-1] .= ' ' . $carry;
        } else {
            $merged[] = $carry;
        }
    }

    // 4. Fallback: if splitting produced nothing useful, return original
    if (empty($merged)) return [trim($text)];

    return $merged;
}
