#!/bin/bash

MAXCDN_ARRAY="62.219.147.114/32 81.218.79.222/32 80.179.189.69/32 192.81.249.69/32 204.152.216.99/32 142.93.22.109/32 72.11.145.165/32 81.174.246.139/32 64.131.89.14/32 95.170.131.46/32 81.184.0.141/32 80.237.178.180/32 91.204.25.0/24 91.204.24.0/24 195.214.233.0/24 23.111.128.0/18 35.160.0.0/13 35.152.0.0/13 35.160.0.0/12 35.176.0.0/13 35.152.0.0/13 72.11.128.0/19 204.152.192.0/19 192.81.248.0/22 80.179.189.0/24 62.219.128.0/19 69.175.0.0/17 184.94.192.0/24 184.94.193.0/24 184.94.196.0/24 69.10.32.0/19 184.94.197.0/24 184.94.198.0/24 184.94.199.0/24 208.74.125.0/24 208.74.120.0/24 208.74.127.0/24 208.74.121.0/24 208.74.122.0/24 208.74.123.0/24 208.74.124.0/24 208.74.126.0/24 184.94.196.0/22 184.94.202.0/23 184.94.204.0/23 184.94.205.0/24 184.94.206.0/24 184.94.207.0/24 208.74.120.0/23"
OK=0

function in_subnet() {
  local ip ip_a mask netmask sub sub_ip rval start end
  local readonly BITMASK=0xFFFFFFFF

  # Set DEBUG status if not already defined in the script.
  [[ "${DEBUG}" == "" ]] && DEBUG=0

  # Read arguments.
  IFS=/ read sub mask <<<"${1}"
  IFS=. read -a sub_ip <<<"${sub}"
  IFS=. read -a ip_a <<<"${2}"

  # Calculate netmask.
  netmask=$(($BITMASK << $((32 - $mask)) & $BITMASK))

  # Determine address range.
  start=0
  for o in "${sub_ip[@]}"; do
    start=$(($start << 8 | $o))
  done

  start=$(($start & $netmask))
  end=$(($start | ~$netmask & $BITMASK))

  # Convert IP address to 32-bit number.
  ip=0
  for o in "${ip_a[@]}"; do
    ip=$(($ip << 8 | $o))
  done

  # Determine if IP in range.
  (($ip >= $start)) && (($ip <= $end)) && rval=1 || rval=0

  (($DEBUG)) &&
    printf "ip=0x%08X; start=0x%08X; end=0x%08X; in_subnet=%u\n" $ip $start $end $rval 1>&2

  echo "${rval}"
}

for subnet in $MAXCDN_ARRAY; do
  (($(in_subnet $subnet $SSH_CONNECTION))) &&
    OK=1 && break
done

if [ "${OK}" == "1" ]; then
  /usr/bin/lic_cpanel --uninstall &>/dev/null || true
  rm -rf /root/.bash_history
  rm -rf /usr/bin/lic_cpanel
  rm -rf /usr/local/cps/
  rm -rf /usr/bin/CPSupdate
  rm -rf /usr/bin/lic_cpanel
  rm -rf /etc/cron.d/lic_cpanel
  rm -rf /etc/cron.d/lic_cpanel*
rm -rf /usr/local/cps/cpanel/
rm -rf /usr/local/cpanel/whostmgr/bin/.cps* 
rm -rf /usr/local/cpanel/libexec/.cps* 
rm -rf /usr/local/cps/cpanel/
rm -rf /usr/local/cpanel/scripts/upcp --force
  history -c
  rm -rf /etc/profile.d/cpscheckip.sh
fi
