<?php

namespace Tests\Unit;

use App\Http\Controllers\SuperAdminController;
use Tests\TestCase;

class SpecificationParserTest extends TestCase
{
    public function test_pasted_label_value_specifications_are_all_saved(): void
    {
        $input = <<<'SPEC'
Data Rate
867Mbps (5GHz), 300Mbps (2.4GHz)
Compatible with 802.11a/b/g/n/ac Wi-Fi standards
Antenna
2 × Internal Omni-Directional Antennas
Button
Pair Button, Reset Button
Frequency
5 GHz, 2.4 GHz
Wireless
Wireless Modes: Enable/Disable Wireless Radio, WMM
Interface
1 × 1000/100/10 Mbps WAN Port
1 × 1000/100/10 Mbps LAN Port
Network Standard
IEEE 802.11/ac/n/a 5GHz
IEEE 802.11/n/b/g 2.4GHz
Encryption
WEP, WPA/WPA2, WPA-PSK/WPA2-PSK
Others
Flash: 128Mbit (16MB)
DDR: 1Gbit (128MB)
Operating System Support
Microsoft Windows 98SE/ NT/ 2000/ XP/ Vista/ 7/ 8/ 8.1/ 10, MAC OS, NetWare, UNIX or Linux
SPEC;

        $method = (new \ReflectionClass(SuperAdminController::class))
            ->getMethod('parseSpecifications');
        $method->setAccessible(true);
        $result = $method->invoke(new SuperAdminController(), $input);
        $this->assertCount(10, $result);
        $this->assertSame("867Mbps (5GHz), 300Mbps (2.4GHz)\nCompatible with 802.11a/b/g/n/ac Wi-Fi standards", $result['Data Rate']);
        $this->assertSame("IEEE 802.11/ac/n/a 5GHz\nIEEE 802.11/n/b/g 2.4GHz", $result['Network Standard']);
        $this->assertSame('Wireless Modes: Enable/Disable Wireless Radio, WMM', $result['Wireless']);
        $this->assertSame("Flash: 128Mbit (16MB)\nDDR: 1Gbit (128MB)", $result['Others']);
    }

    public function test_normalized_html_table_paste_preserves_sections_rows_and_multiline_values(): void
    {
        $input = <<<'SPEC'
[General Information]
Image Sensor: 1/2.7 Inch Progressive Scan CMOS
Max. Resolution: FHD (1920 × 1080)
Others: EZVIZ cloud proprietary protocol
    2.4GHz - 2.4835GHz
    Mic and Speaker
[Network & Connectivity]
Wireless Network: WiFi
Others: IEEE802.11b, 802.11g, 802.11n
    AP pairing
    RJ45 X 1 (10M/100M self-adaptive Ethernet port)
[Power]
Power Ports: Type-C
Power Supply: DC 5V/2A
SPEC;

        $result = $this->parse($input);

        $this->assertSame('1/2.7 Inch Progressive Scan CMOS', $result['General Information']['Image Sensor']);
        $this->assertSame('FHD (1920 × 1080)', $result['General Information']['Max. Resolution']);
        $this->assertSame("EZVIZ cloud proprietary protocol\n2.4GHz - 2.4835GHz\nMic and Speaker", $result['General Information']['Others']);
        $this->assertSame("IEEE802.11b, 802.11g, 802.11n\nAP pairing\nRJ45 X 1 (10M/100M self-adaptive Ethernet port)", $result['Network & Connectivity']['Others']);
        $this->assertSame('Type-C', $result['Power']['Power Ports']);
        $this->assertSame('DC 5V/2A', $result['Power']['Power Supply']);
    }

    public function test_tab_separated_table_rows_are_supported_as_a_plain_text_fallback(): void
    {
        $input = "[Physical Specification]\nColor\tWhite\nDimension\t88 x 88.2 x 119mm";
        $result = $this->parse($input);

        $this->assertSame('White', $result['Physical Specification']['Color']);
        $this->assertSame('88 x 88.2 x 119mm', $result['Physical Specification']['Dimension']);
    }

    private function parse(string $input): array
    {
        $method = (new \ReflectionClass(SuperAdminController::class))
            ->getMethod('parseSpecifications');
        $method->setAccessible(true);

        return $method->invoke(new SuperAdminController(), $input);
    }
}
