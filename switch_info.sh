#!/bin/bash

ip=$1
community=cisco

sysName=$(snmpwalk -v 2c -c $community $ip sysName | awk -F ':' '{print $4}')
sysDescr=$(snmpwalk -v 2c -c $community $ip sysDescr)

ifDescr=$(snmpwalk -v 2c -c $community $ip ifDescr | cut -d' ' -f4 | tr '\n' ',')
arrDescr=()
IFS=',' read -r -a arrDescr <<< "$ifDescr"

ifAdminStatus=$(snmpwalk -v 2c -c $community $ip ifAdminStatus | cut -d' ' -f4 | tr '\n' ',')
arrAdminStatus=()
IFS=',' read -r -a arrAdminStatus <<< "$ifAdminStatus"

ifAlias=$(snmpwalk -v 2c -c $community $ip ifAlias | cut -d' ' -f4 | tr '\n' ',')
arrAlias=()
IFS=',' read -r -a arrAlias <<< "$ifAlias" 

arr=()
len=${#arrAlias[@]}
for (( c=0; c<$len; c++ ));
do
    arr+=("<td>" ${arrDescr[$c]} "</td><td>" ${arrAdminStatus[$c]} "</td><td>" ${arrAlias[$c]} "</td></tr><tr allign='center'>")
done
echo -e "[ $ip ] <b>$sysName</b><br/>"
echo -e "$sysDescr"
echo -e "<table border='1' cellpadding='5' cellspacing='2'><tr allign='center'>"
echo -e "${arr[@]}"
echo -e "</tr></table>"
