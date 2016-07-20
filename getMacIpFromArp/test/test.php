<?php
include '../getMacIpFromArp.php';

// Test arp output from system
$result = getMacIpFromArp();
printf(
    "<pre>Using `arp -a` on current system\nIPs by MAC address: %s</pre>",
    var_export($result, true)
);

// Test outputs from different platforms
$arpOutputsByPlatform = [
    'Ubuntu 16.04 LTS' => '
my-router (192.168.1.1) at 00:11:22:33:44:55 [ether] on enp2s0
? (192.168.1.101) at 11:22:33:44:55:66 [ether] on enp2s0
? (192.168.1.102) at 22:33:44:55:66:77 [ether] on wlp3s0
    ',

    'Windows 10 Home' => '
Interface: 192.168.1.101 --- 0x5
  Internet Address      Physical Address      Type
  192.168.1.1           00-11-22-33-44-55     dynamic
  192.168.1.101         11-22-33-44-55-66     dynamic
  192.168.1.102         22-33-44-55-66-77     dynamic
  255.255.255.255       ff-ff-ff-ff-ff-ff     static
    ',

    'Mac OS X El Capitan' => '
my-router (192.168.1.1) at 00:11:22:33:44:55 on en0 ifscope [ethernet]
? (192.168.1.101) at 11:22:33:44:55:66 on en0 ifscope [ethernet]
? (192.168.1.102) at 22:33:44:55:66:77 on en0 ifscope [ethernet]
? (192.168.1.255) at ff:ff:ff:ff:ff:ff on en0 ifscope permanent [ethernet]
    ',
];

foreach ($arpOutputsByPlatform as $platform => $arpOutput) {
    $result = getMacIpFromArp($arpOutput);
    printf(
        "<pre>Platform: %s\nIPs by MAC address: %s</pre>",
        $platform,
        var_export($result, true)
    );
}
