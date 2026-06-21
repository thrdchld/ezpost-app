<?php

namespace App\Services;

class ThreadsSplitter
{
    /**
     * Splits a long text into chunks of maximum 490 characters to comply with Threads limits.
     * It splits by paragraphs first, and if a paragraph exceeds 490 characters, it splits by words.
     *
     * @param string $text The input content.
     * @return array Array of text chunks.
     */
    public function split(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $threads = [];
        foreach ($paragraphs as $p) {
            $trimmed = trim($p);
            if (empty($trimmed)) continue;
            
            if (mb_strlen($trimmed) > 490) {
                $words = explode(' ', $trimmed);
                $currentChunk = '';
                foreach ($words as $word) {
                    // Check if adding the next word exceeds 490 characters
                    if (mb_strlen($currentChunk . $word) > 490) {
                        $threads[] = trim($currentChunk);
                        $currentChunk = $word . ' ';
                    } else {
                        $currentChunk .= $word . ' ';
                    }
                }
                if (trim($currentChunk) !== '') {
                    $threads[] = trim($currentChunk);
                }
            } else {
                $threads[] = $trimmed;
            }
        }
        return empty($threads) ? [""] : $threads;
    }
}
