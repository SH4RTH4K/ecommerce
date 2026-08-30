<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductDescriptionHtmlTest extends TestCase
{
    public function test_word_style_tables_and_inline_formatting_are_preserved(): void
    {
        $input = <<<'HTML'
<table style="width:100%;border-collapse:collapse" onclick="bad()">
    <tr><td colspan="2" style="background-color:#f5f6fb;color:#3749bb;font-weight:700">General Information</td></tr>
    <tr><td style="color:#666">Image Sensor</td><td><span style="font-weight:bold">3MP</span></td></tr>
</table>
HTML;

        $result = product_description_html($input);

        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('colspan="2"', $result);
        $this->assertStringContainsString('background-color:#f5f6fb', $result);
        $this->assertStringContainsString('font-weight:bold', $result);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_unsafe_tags_urls_and_css_are_removed(): void
    {
        $input = <<<'HTML'
<p style="position:absolute;background-image:url(javascript:bad)">Safe copy</p>
<script>alert(1)</script><a href="javascript:bad()">Unsafe link</a>
HTML;

        $result = product_description_html($input);

        $this->assertStringContainsString('Safe copy', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('position:', $result);
        $this->assertStringNotContainsString('javascript:', $result);
    }
}
