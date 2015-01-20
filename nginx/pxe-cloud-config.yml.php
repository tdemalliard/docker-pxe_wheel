<?php
$hostfile = 'hosts.json';
$customip = 1;

# load current config
$hosts = @json_decode(file_get_contents($hostfile), true);

# get dhcp ip
$ip = $_SERVER['REMOTE_ADDR'];

# mac address is not yet in config file, aka 1st time we see it
if (!isset($hosts['hosts'][$ip])) {
  # 1st time we have a node
  if (!isset($hosts['nodes'])) {
    $hosts['nodes'] = 1;
  } else {
    $hosts['nodes']++;
  }
  # set cluster ip and cluster hostname
  if ($customip == 1) {
    $hosts['hosts'][$ip]['cluster_ip'] = sprintf('172.16.1.1%02d', $hosts['nodes']);
  } else {
    $hosts['hosts'][$ip]['cluster_ip'] = $ip;
  }
  $hosts['hosts'][$ip]['name'] = sprintf('n%04d', $hosts['nodes']);

  # create shares
  #`mkdir /export/$hosts['hosts'][$ip]['name']/etcd`
}

# build peers string
$peers = '';
foreach ($hosts['hosts'] as $key => $value) {
  $peers .= $value['cluster_ip'] . ':7001,';
}
$peers = rtrim($peers, ",");

# save current config if ip is from cluster
if (strpos($ip, '172.16.1') !== FALSE) {
  file_put_contents($hostfile, json_encode($hosts));
}
?>
#cloud-config
coreos:
  etcd:
    name: <?php print $hosts['hosts'][$ip]['name'] ?> 
    # generate a new token for each unique cluster from https://discovery.etcd.io/new
    discovery: https://discovery.etcd.io/24de2182254fd76edc9431b724986e18
    # multi-region and multi-cloud deployments need to use $public_ipv4
    addr: <?php print $hosts['hosts'][$ip]['cluster_ip'] ?>:4001
    peer-addr: <?php print $hosts['hosts'][$ip]['cluster_ip'] ?>:7001
    # peers: "<?php print $peers ?>"
  units:
<?php if ($customip == 1) { ?>
    - name: 00-eth0.network
      runtime: true
      content: |
        [Match]
        Name=eno16777728

        [Network]
        DNS=172.16.1.1
        Address=<?php print $hosts['hosts'][$ip]['cluster_ip'] ?>/24
        Gateway=172.16.1.1
<?php } ?>
    - name: var-lib-etcd.mount
      command: start
      content: |
        [Unit]
        Description=NFS etcd
        After=network.target
        Before=local-fs.target umount.target etcd.service fleet.service
        Requires=network.target
        
        [Mount]
        What=172.16.1.1:/export/<?php print $hosts['hosts'][$ip]['name'] ?>/etcd
        Where=/var/lib/etcd
        Type=nfs
        Options=rw,hard,async,intr,rsize=49152,wsize=49152
    - name: etcd.service
      command: start
    - name: fleet.service
      command: start



ssh_authorized_keys:
  - ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDl38xoZjilX4XKiIG4mnFGll4WcXIk14Og/tI7u78lZ3i2gwM7hG6xntdjfgd7ZS1388GlpBIRzvQGNExqK8eajRYce6xt/29WvAyoYLBEiUjbCFtUzQjE0qKy3MlRlW5Pn7J89uQfxFzfEeHkKH7NBkNkQinKbBLpmPs9BAj3UiGzC/y7GXtxPTB6GUuVFMlaxLETQZLGur5vhD7ZwJImsp1vK4NNRcoQkpC8AUrfjYLDCnFNi86OmGgg6A2CauG+rUIqkDGCNcLg/Aejr0GHY8Yw74kZ4VQbweYuS0O6T4OVFwT/SnDHy8WxE+liJ2VNq9sT4rlWYk1AUPocNh6t
