<?php
/**
 * Get MAC addresses and their IPv4 addresses from `arp -a` output
 *
 * `-a` option used as it can be used across Linux, Windows and Mac OS.
 * Note that `shell_exec()` is disabled when PHP is running in safe mode.
 *
 * `arp` only lists hosts that the current host has connected to. To populate it with all the live hosts on the same
 * LAN subnet, the following command can be run on Linux with nmap installed:
 *     nmap -sn $(ip --oneline route get to 8.8.8.8 | sed -n 's/.*src \([0-9.]\+\).*/\1/p' | awk '{print $1"/24"}')
 *
 * @link   https://github.com/zionsg/standalone-php-scripts/tree/master/getMacIpFromArp
 * @param  string $arpOutput Output of `arp -a` command, retrieved from current host if not specified
 * @return array MAC-IP pairs with IP addresses converted to lowercase and dashes converted to colons
 */
function getMacIpFromArp($arpOutput = null)
{
    if (null === $arpOutput) {
        $arpOutput = shell_exec('arp -a');
    }

    // The regex matches an invalid MAC address with 7 pairs of hex digits - kept this way to keep the regex simple
    $result = [];
    $regex = '/([0-9]{1,3}(\.[0-9]{1,3}){3}).*([0-9a-f]{2}([:\-][0-9a-f]{2}){5,7})/i';
    $lines = explode("\n", $arpOutput);

    // Parse arp output
    foreach ($lines as $line) {
        if (preg_match($regex, $line, $matches)) {
            $ip = strtolower(str_replace('-', ':', $matches[3]));
            $result[$ip] = $matches[1];
        }
    }

    return $result;
}
