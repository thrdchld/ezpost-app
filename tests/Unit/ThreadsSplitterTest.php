<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ThreadsSplitter;

class ThreadsSplitterTest extends TestCase
{
    private ThreadsSplitter $splitter;

    protected function setUp(): void
    {
        $this->splitter = new ThreadsSplitter();
    }

    public function test_it_does_not_split_short_text(): void
    {
        $text = "Halo! Ini adalah teks pendek.";
        $result = $this->splitter->split($text);

        $this->assertCount(1, $result);
        $this->assertEquals("Halo! Ini adalah teks pendek.", $result[0]);
    }

    public function test_it_returns_array_with_empty_string_for_empty_input(): void
    {
        $result = $this->splitter->split("");
        $this->assertCount(1, $result);
        $this->assertEquals("", $result[0]);
    }

    public function test_it_splits_by_paragraphs_first(): void
    {
        $text = "Paragraf pertama yang cukup pendek.\n\nParagraf kedua yang juga pendek.";
        $result = $this->splitter->split($text);

        $this->assertCount(2, $result);
        $this->assertEquals("Paragraf pertama yang cukup pendek.", $result[0]);
        $this->assertEquals("Paragraf kedua yang juga pendek.", $result[1]);
    }

    public function test_it_splits_long_paragraph_by_words(): void
    {
        // Create a word of 10 chars, repeat it 50 times to get 500 chars (exceeding 490)
        $wordsArray = array_fill(0, 50, "abcdeabcde"); // 50 * 10 = 500 chars + spaces
        $longParagraph = implode(" ", $wordsArray);
        
        $result = $this->splitter->split($longParagraph);

        $this->assertCount(2, $result);
        // Each part should be less than or equal to 490 characters
        $this->assertLessThanOrEqual(490, mb_strlen($result[0]));
        $this->assertLessThanOrEqual(490, mb_strlen($result[1]));
        
        // Reassembled should contain all words
        $reassembled = implode(" ", $result);
        // Clean multiple spaces and compare lengths or check words
        $this->assertStringContainsString("abcdeabcde", $result[0]);
        $this->assertStringContainsString("abcdeabcde", $result[1]);
    }
}
