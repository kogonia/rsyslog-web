#!/bin/bash

snmp_sysName="1.3.6.1.2.1.1.5"
snmp_sysDescr="1.3.6.1.2.1.1.1"
snmp_bgpPeerTable="1.3.6.1.2.1.15.3.1.2"
snmp_bgpLocalAs="1.3.6.1.2.1.15.2"
snmp_ifDescr="1.3.6.1.2.1.2.2.1.2" 
snmp_ifAdminStatus="1.3.6.1.2.1.2.2.1.7"
snmp_ifAlias="1.3.6.1.2.1.31.1.1.1.18"

bgp_Array_status=(
    [1]="IDLE"
    [2]="CONNECT"
    [3]="ACTIVE"
    [4]="OPENSENT"
    [5]="OPENCONFIRM"
    [6]="ESTABLISHED")

##########################################
# Edit this block                        #
# ip_without_dots_N = 10.0.0.1=10001     #
# Neighbor_Name_N = text you want to see #
##########################################
bgp_Array_partner=(
    [ip_without_dots_1]="Neighbor_Name_1"
    [ip_without_dots_2]="Neighbor_Name_2")

function info {
    sysName=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_sysName} | awk -F ':' '{print $4}')
    sysDescr=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_sysDescr})

    ifDescr=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_ifDescr} | cut -d' ' -f4 | tr '\n' ',')
    arrDescr=()
    IFS=',' read -r -a arrDescr <<< "${ifDescr}"

    ifAdminStatus=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_ifAdminStatus} | cut -d' ' -f4 | tr '\n' ',')
    arrAdminStatus=()
    IFS=',' read -r -a arrAdminStatus <<< "${ifAdminStatus}"

    ifAlias=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_ifAlias} | grep -o ':\s.*' | grep -o '\s.*' | tr '\n' ',')
    arrAlias=()
    IFS=',' read -r -a arrAlias <<< "${ifAlias}"

    arr=()
    len=${#arrAlias[@]}
    arr+=("<tr align='center'><td> <b>interface</b> </td><td> <b>status</b> </td><td> <b>description</b> </td></tr>")
    for (( c=0; c<$len; c++ ));
    do
        arr+=("<tr align='center'><td>" ${arrDescr[$c]} "</td><td>" ${arrAdminStatus[$c]} "</td><td>" ${arrAlias[$c]} "</td></tr>")
    done
    echo -e "[ ${ip} ] <b>${sysName}</b><br/>"
    echo -e "${sysDescr//$'\n'/<br/>}"
    echo -e "<table border='1' cellpadding='5'>"
    echo -e "${arr[@]}"
    echo -e "</table>"
}

function name {
    sysName=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_sysName} | awk -F ':' '{print $4}')
    echo -e "${ip}<tab>${sysName}"
}

function bgp {
    bgp_ip=()
    bgp_value=()
    bgp_res=()
    my_AS=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_bgpLocalAs} | awk -F'INTEGER:' '{print $2}')
    bgp_out=$(snmpwalk -v 2c -c ${community} ${ip} ${snmp_bgpPeerTable} | awk -F'15.3.1.2.' '{print $2}' | awk '{print $1" "$4}')
    bgp_ip+=($(echo -e "${bgp_out}" | awk '{print $1}'))
    bgp_value+=($(echo -e "${bgp_out}" | awk '{print $2}'))
    len=${#bgp_ip[@]}
    bgp_res+=("<tr align='center'><td>ip</td><td>name</td><td>status</td></tr>")
    for (( c=0; c<$len; c++ ));
    do
        ip_convert "${bgp_ip[$c]}"
        bgp_res+=("<tr><td>" ${bgp_ip[$c]} "</td><td>" ${bgp_Array_partner[${res}]} "</td><td>" ${bgp_Array_status[${bgp_value[$c]}]} "</td></tr>")
    done
    echo -e "<table border='1' cellpadding='5' cellspacing='4'>"
    echo -e "<tr align='center'><td colspan='3'><b>${key} status [AS-${my_AS}]</b></td></tr>"
    echo -e "${bgp_res[@]}"
    echo -e "</table>"
}

function ip_convert {
    ip=$@
    res=$(echo "${ip}" | sed 's/\.//g')
}

key=$1
ip=$2
community="cisco"

case ${key} in
    "name")
        name;;
    "info")
        info;;
    "bgp_key1"|"bgp_key2")
        bgp;;
esac
