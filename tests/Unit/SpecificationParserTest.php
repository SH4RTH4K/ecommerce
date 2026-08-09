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
}
