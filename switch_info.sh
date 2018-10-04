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

ifAlias=$(snmpwalk -v 2c -c $community $ip ifAlias | grep -o ':\s.*' | grep -o '\s.*' | tr '\n' ',')
arrAlias=()
IFS=',' read -r -a arrAlias <<< "$ifAlias" 

arr=()
len=${#arrAlias[@]}
arr+=("<tr allign='center'><td> <b>interface</b> </td><td> <b>status</b> </td><td> <b>description</b> </td></tr>")
for (( c=0; c<$len; c++ ));
do
    arr+=("<tr allign='center'><td>" ${arrDescr[$c]} "</td><td>" ${arrAdminStatus[$c]} "</td><td>" ${arrAlias[$c]} "</td></tr>")
done
echo -e "[ $ip ] <b>$sysName</b><br/>"
echo -e "${sysDescr//$'\n'/<br/>}"
echo -e "<table border='1' cellpadding='5'>"
echo -e "${arr[@]}"
echo -e "</table>"
