#!/bin/bash
set -e

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud LEGRAND <arnaud@ulteo.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#

unset LANG

DRBD_RESOURCE="sm0"
DRBD_CONF=/etc/drbd.d/$DRBD_RESOURCE.res
DRBD_DEVICE=/dev/drbd0
DRBD_MOUNT_DIR=/var/cache/ulteo/ha/drbd

HEARTBEAT_CONF_DIR=/etc/ha.d
HEARTBEAT_CRM_DIR=/var/lib/heartbeat/crm

HA_CONF_DIR=/etc/ulteo/ovd/ha
HA_VARLIB_DIR=/var/lib/ulteo/ovd
HA_LOG=/var/log/ha_install.log

SM_LOG_DIR=/var/log/ulteo/sessionmanager
SM_SPOOL_DIR=/var/spool/ulteo/sessionmanager
SM_DATA_DIR=/usr/share/ulteo/sessionmanager

MYSQL_DB=/var/lib/mysql

AUTH_KEY=`date '+%m%d%y%H%M%S'`
GATEWAY=`route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}'`

# load util functions
. ./utils.sh

rm -rf $HA_CONF_DIR $HA_VARLIB_DIR
mkdir -p $HA_CONF_DIR $HA_VARLIB_DIR

function drbd_install()
{
	modprobe drbd

	info "stop all services"
	service mysql stop >> $HA_LOG 2>&1 || true
	service apache2 stop >> $HA_LOG 2>&1 || true
	service heartbeat stop >> $HA_LOG 2>&1 || true

	# Create a virtual block device of 250M
	info "Create a virtual block device of 250 MBytes"
	dd if=/dev/zero of=$HA_VARLIB_DIR/vbd0.bin count=500k 2>> $HA_LOG

	DRBD_LOOP=$(losetup -f)
	losetup $DRBD_LOOP $HA_VARLIB_DIR/vbd0.bin
	info "Connect $HA_VARLIB_DIR/vbd0.bin to $DRBD_LOOP"

	# Create conf /etc/drbd.d/sm0.res
	info "Create conf $DRBD_CONF"
	sed "s/%RESOURCE%/$DRBD_RESOURCE/" conf/$DRBD_RESOURCE.res | \
		sed "s,%DEVICE%,$DRBD_DEVICE," | sed "s,%LOOP%,$DRBD_LOOP," | \
		sed "s/%AUTH_KEY%/$AUTH_KEY/"  | sed "s/%HOSTNAME%/$HOSTNAME/" | \
		sed "s/%NIC_ADDR%/$NIC_ADDR/" > $DRBD_CONF

	# prepare and clean drbd
	umount $DRBD_DEVICE 2>> $HA_LOG || true
	execute "drbdadm down $DRBD_RESOURCE"

	# create drbd resource
	execute "drbdadm create-md $DRBD_RESOURCE"
	execute "drbdadm up $DRBD_RESOURCE" || true

	if [ $1 == "M" ]; then
		# Check if overwrite of peer is necessary
		execute "drbdadm -- --overwrite-data-of-peer primary $DRBD_RESOURCE"

		# Create ext3 FS
		execute "mkfs.ext3 $DRBD_DEVICE"

		# Copy MySQL DB to VDB0
		mkdir -p $DRBD_MOUNT_DIR
		mount $DRBD_DEVICE $DRBD_MOUNT_DIR
		cp -a $MYSQL_DB $SM_SPOOL_DIR $DRBD_MOUNT_DIR
		umount $DRBD_MOUNT_DIR

	elif [ $1 == "S" ]; then
		execute "drbdadm adjust $DRBD_RESOURCE" || true

		#Synchronize data
		var_role=`drbdadm role $DRBD_RESOURCE | grep -E 'Secondary/Primary|Secondary/Secondary' || true`
		if [ -n "$var_role" ]; then
			info "Master connected, synchronizing $DRBD_RESOURCE data..."
			execute "drbdadm invalidate-remote $DRBD_RESOURCE"
			local t=0
			while [ $t -lt 120 ]; do
				var_isfinish=`drbdadm dstate $DRBD_RESOURCE`
				[ "$var_isfinish" -eq "UpToDate/UpToDate" ] && break
				let t++
				sleep 2
			done
		fi
	fi
	execute "drbdadm down $DRBD_RESOURCE"
}


function heartbeat_install()
{
	# create logs files
	mkdir -p $SM_LOG_DIR
	touch $SM_LOG_DIR/ha.log $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log
	chown www-data:www-data  $SM_LOG_DIR/ha.log
	chown hacluster:haclient $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log

	info "generate ha.cf file"
	sed "s/%GATEWAY%/$GATEWAY/" conf/ha.cf | \
		sed "s/%NIC_NAME%/$NIC_NAME/" | sed "s/%NIC_ADDR%/$NIC_ADDR/" | \
		sed "s/%HOSTNAME%/$HOSTNAME/" | sed "s,%LOGDIR%,$SM_LOG_DIR," \
		    > $HEARTBEAT_CONF_DIR/ha.cf

	info "generate authkeys file"
	echo -e "auth 1\n1 sha1 $AUTH_KEY" > $HEARTBEAT_CONF_DIR/authkeys
	chmod 600 $HEARTBEAT_CONF_DIR/authkeys

	# Copy resource for OCF manager
	cp conf/mysql-ocf /usr/lib/ocf/resource.d/heartbeat

	# Delete old cibs [HEARTBEAT]
	[ -e $HEARTBEAT_CRM_DIR/cib.xml ] && rm -f $HEARTBEAT_CRM_DIR/*

	service heartbeat start >> $HA_LOG 2>&1
}


function heartbeat_cib_install()
{
	echo -ne "\033[36;1m[INFO] \033[0m Waiting connection to CRM. It may take some time."
	local t=0
	while [ -z "$var_cib_node" ]; do
		var_cib_node=`crm_mon -1 | grep -E "[1-9] Nodes configured" || true`
		[ $t -gt 360 ] && die "Connection timeout to the cluster"
		echo -n "."
		let t+=5
		sleep 5
	done
	echo -e "\n\033[34;1m[OK] \033[0m Connection to CRM done."

	execute "crm node standby"

	info " submit resource configurations"
	sed "s,%MOUNT_DIR%,$DRBD_MOUNT_DIR," conf/crm.conf | \
		sed "s,%MYSQL_DB%,$MYSQL_DB," | sed "s,%SM_SPOOL_DIR%,$SM_SPOOL_DIR," | \
		sed "s/%DRBD_RESOURCE%/$DRBD_RESOURCE/" | sed "s/%VIP%/$VIP/" | \
		crm configure 2>> $HA_LOG
}


function hashell_install()
{
	info "install HAshell"
	make install -C shell >> $HA_LOG
}


# update-rc.d -f remove mysql/apache2 necessary
function set_init_script()
{
	info "install init script"
	cp ulteo_ha /etc/init.d/
	update-rc.d ulteo_ha defaults >> $HA_LOG
}


# Slave only : register to Master host
function set_ha_register_to_master()
{
	info "register server to the SM"
	tmp=$(mktemp)
	execute "wget --no-check-certificate -O $tmp --post-data='action=register&hostname=$HOSTNAME' https://$MIP/ovd/admin/ha/registration.php"
	response=$(cat $tmp)
	[ -z "$response" -o "$response" = "2" ] && die "request to master refused"
}

###############################################################################
# BEGINING
##

dpkg -l ulteo-ovd-session-manager > $HA_LOG
[ $? -eq 0 ] || die "package ulteo-ovd-session-manager is required"
[ -x $(which mysql) ] || die "mysql is required"
[ -x $(which drbdadm) ] || die "drbd is required"
[ -e /etc/init.d/heartbeat ] || die "hearbeat is required"
[ -z "$HOSTNAME" ] && die "No Hostname found"
[ -z "$GATEWAY" ] && die "No gateway found"

# choose MASTER/SLAVE
while true; do
	echo -n "Install this session manager as master or slave [m/s]: " && read CHOICE
	CHOICE=$(echo $CHOICE | tr 'A-Z' 'a-z')

	case $CHOICE in
		master | m)
			info "the host will become the master"

			set_netlink
			set_virtual_ip $NIC_MASK $NIC_ADDR
			drbd_install "M"
			heartbeat_install
			heartbeat_cib_install
			hashell_install
			set_init_script
			crm_attribute --type nodes --node $HOSTNAME --name standby --update off

			echo -e "\n\033[37;1mYou You must enable the HA module in configuration before !\033[0m"
			echo -e "\033[37;1mThen you can get web interface at: https://$VIP/ovd/admin/ha/status.php\033[0m"
		;;

		slave | s)
			info "the host will become a slave"

			set_netlink
			set_master_ip
			drbd_install "S"
			heartbeat_install
			hashell_install
			set_ha_register_to_master
			set_init_script
		;;

		*)
			echo -e "\033[31;1mYour response is not valid\033[0m"
			continue
		;;
	esac
	break
done
echo -e "\n\033[31;1mINSTALLATION SUCCESSFULL\033[0m\n"
