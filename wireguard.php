<?php

class Wireguard
{

    // in the constructor function get the full adress
    public $config_file_address;

    // for saving raw wireguard config (ini format)
    public $wireguard_raw_config;

    // for saving parsed wireguard interface
    public $wireguard_interface;

    // for saving all peers each element contain PublicKey and AllowedIPs
    public $wireguard_peers;

    // analyze the host ip and saved the info to host_ip_info
    public $host_ip_info;

    // get full list of in used ip addresses
    public $reserved_ips;



    /**
     * Public method for manage wireguard interface
     */

    // get the full path address of wireguard config and saving to $config_file_address
    public function __construct($config_file_address)
    {
        $this->config_file_address = $config_file_address;
        $this->bootstrap();
    }

    // update current config From original file must be call after any change in config file
    public function update()
    {
        $this->bootstrap();
    }

    // find next available ip address for create a peer
    public function get_available_ip()
    {
        // get last in use ip address
        $ip_address = end($this->reserved_ips);

        // make next available ip
        while ($this->is_valid_ip_address($this->host_ip_info, $this->reserved_ips, $ip_address)['status'] === -3) {
            $ip_address = long2ip(ip2long($ip_address) + 1);
        }

        // check ip address validity
        if ($this->is_valid_ip_address($this->host_ip_info, $this->reserved_ips, $ip_address)['ok'] === true) {
            return $ip_address;
        }

        return false;
    }

    // call add peer script on server and return a config file
    public function add_peer($ip_address, $dns, $endpoint)
    {
        // check ip validity
        $ip_validity = $this->is_valid_ip_address($this->host_ip_info, $this->reserved_ips, $ip_address);

        if ($ip_validity['ok']) {
            $shell_command = sprintf("sudo ./add-peer.sh '%s/32' '%s' '%s'", $ip_address, $dns, $endpoint);
            $output = shell_exec($shell_command);

            return [
                'ok' => true,
                'result' => $output
            ];
        } else {
            return [
                'ok' => false,
                'result' => $ip_validity['desc']
            ];
        }
    }



    /**
     * Private Functions for read and parse config file
     */

    // this function run after make an object
    private function bootstrap()
    {
        $this->read_config_file();
        $this->get_interface();
        $this->get_peers();
        $this->get_host_ip_info();
        $this->get_reserved_ips();
    }

    // read the config file and save it to wireguard_raw_config
    private function read_config_file()
    {
        $this->wireguard_raw_config = shell_exec('sudo cat ' . $this->config_file_address);
    }

    // find the all peers and parsed, then saving it to $peers each peers contain PublicKey and AllowedIPs
    private function get_peers()
    {
        $peers = $this->find_the_match('/^\[Peer\]$\s\S.*\s\S.*/m', $this->wireguard_raw_config);
        foreach ($peers as $peer) {
            $this->wireguard_peers[] = parse_ini_string($peer, false, INI_SCANNER_RAW);
        }
    }

    // find the Interface section on config file parsed it and saved in to $wireguard_interface
    private function get_interface()
    {
        $interface = $this->find_the_match('/^\[Interface\]$\s\S.*\s\S.*\s\S.*/m', $this->wireguard_raw_config);
        $this->wireguard_interface = parse_ini_string($interface[0], false, INI_SCANNER_RAW);
    }

    // calculate network, hostMax, hostMin and etc saved to $host_ip_info
    private function get_host_ip_info()
    {
        $this->host_ip_info = $this->get_ip_info($this->wireguard_interface['Address']);
    }

    // make a full list of in use addresses and saved to reserved_ips
    private function get_reserved_ips()
    {
        $reserved_ips = [];

        // get host ip as a reserved ip
        $reserved_ips[] = explode('/', $this->wireguard_interface['Address'])[0];

        // get Peers reserved ip
        foreach ($this->wireguard_peers as $peer) {
            $reserved_ips[] =  explode('/', $peer['AllowedIPs'])[0];
        }

        $this->reserved_ips = $reserved_ips;
    }



    /**
     * Helper Functions
     */

    // analyze ip address
    private function get_ip_info($ip)
    {
        $ip = explode('/', $ip);

        $long_ip = ip2long($ip[0]);
        $cidr = $ip[1];

        $long_network = $long_ip & (-1 << (32 - (int)$cidr));
        $long_broadcast = $long_network + pow(2, (32 - (int)$cidr)) - 1;

        return [
            'ip' => $ip[0],
            'cidr' => $cidr,
            'network' => long2ip($long_network),
            'broadcast' => long2ip($long_broadcast),
            'hostMin' => long2ip($long_network + 1),
            'hostMax' => long2ip($long_broadcast - 1),
            'long' => [
                'ip' => $long_ip,
                'network' => $long_network,
                'broadcast' => $long_broadcast,
                'hostMin' => $long_network + 1,
                'hostMax' => $long_broadcast - 1
            ]
        ];
    }

    // get regex and string, return matches item
    private function find_the_match($regex, $content)
    {
        preg_match_all($regex, $content, $matches);
        return $matches[0];
    }

    // check ip for valid format, in range and not in used.
    public function is_valid_ip_address($host_ip_info, $reserved_ips, $ip)
    {
        // in case this not valid ip
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'ok' => false,
                'status' => -1,
                'desc' => 'invalid format.'
            ];
        }

        // in case of ip address is not in host range
        if (ip2long($ip) < $host_ip_info['long']['hostMin'] || ip2long($ip) > $host_ip_info['long']['hostMax']) {
            return [
                'ok' => false,
                'status' => -2,
                'desc' => 'out of range.'
            ];
        }

        // in case of existing ip address
        if (in_array($ip, $reserved_ips)) {
            return [
                'ok' => false,
                'status' => -3,
                'desc' => 'in used.'
            ];
        }

        // valid ip address
        return [
            "ok" => true,
            'status' => 1,
            "desc" => "valid"
        ];
    }
}
