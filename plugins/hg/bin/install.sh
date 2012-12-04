#!/usr/bin/env bash

echo "############################################"
echo "#                                          #"
echo "#              Hg Plugin install           #"
echo "#                                          #"
echo "############################################"


function dieOnError() {
	local rcode=$?
	local msg=$1
	if [ $rcode -ne 0 ];then
		echo '[ERROR] '$msg' code='$rcode
		exit 1
	fi
}

function getNewestPackageDir() {
	local name=$1
	local newest=`ls -1 -tr | grep -E "^${name}-[0-9]" | tail -1`
	echo $newest
}

SCRIPT_DIR=`dirname $0`
RPMS_DIR="$SCRIPT_DIR/../RPMS"
cd $RPMS_DIR
dieOnError 'RPM directory not found' 

ARCH='x86_64'
if [ "`uname -i`" != 'x86_64' ];then
    ARCH='i386'
fi

#Plugin dependencies rpms
echo " -> Removing installed dependencies if any ..."
rpm -e --allmatches mercurial
yum install mercurial


HG_ROOT_DIR=/var/lib/codendi/hgroot/
if [ ! -d $HG_ROOT_DIR  ];then
    mkdir -p $HG_ROOT_DIR
    chown -R codendiadm.codendiadm $HG_ROOT_DIR
    chmod -R 770 
fi

if [ ! -L '/hgroot' ];then
    ln -sf $HG_ROOT_DIR /hgroot
fi

exit 0
