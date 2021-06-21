#!/bin/bash

# Force to run as root
[[ $UID == 0 ]] || { echo "You must be root to run this."; exit 1; }

# SERVER VAR
SERVER_INTERFACE="wg0"
SERVER_PORT=$(wg show "${SERVER_INTERFACE}" | sed -n 's/^.*listening port: //p')
SERVER_PUBLICKEY=$(wg show "${SERVER_INTERFACE}" | sed -n 's/^.*public key: //p')

# CLIENT VAR
CLEINT_ALLOWEDIPS="0.0.0.0/0"
CLIENT_PRIVATEKEY=$(wg genkey)
CLIENT_PUBCLIEKEY=$(wg pubkey <<< $CLIENT_PRIVATEKEY)

# GET FROM USER INPUT
CLIENT_ADDRESS=$1
CLIENT_DNS=$2
SERVER_ENDPOINT=$3

CONFIG="[Interface]\nPrivateKey = ${CLIENT_PRIVATEKEY}\nAddress = ${CLIENT_ADDRESS}\nDNS = ${CLIENT_DNS}\n\n[Peer]\nPublicKey = ${SERVER_PUBLICKEY}\nAllowedIPs = ${CLEINT_ALLOWEDIPS}\nEndpoint = ${SERVER_ENDPOINT}:${SERVER_PORT}\n"
echo -e $CONFIG

# Add peer to wireguard interface
wg set "${SERVER_INTERFACE}" peer "${CLIENT_PUBCLIEKEY}" allowed-ips "${CLIENT_ADDRESS}"

# Save new peer to interface file
printf "\n[Peer]\nPublicKey = ${CLIENT_PUBCLIEKEY}\nAllowedIPs = ${CLIENT_ADDRESS}\n" >> /etc/wireguard/${SERVER_INTERFACE}.conf

