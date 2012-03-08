#!/bin/bash

if [ ! $# == 0 ]; then
	MAJOR=$1
	MINOR=$2
	PATCH=$3
	CODENAME=$4
	EXTRA=$5
	# set default minor
	if [ -e $MINOR ]; then
		MINOR=0
	fi
	# set default patch
	if [ -e $PATCH ]; then
		PATCH=0
	fi
	# write to the '.version' file
	echo $((($MAJOR*1000000)+($MINOR*1000)+($PATCH-1)))$CODENAME$EXTRA  > .version
	
fi

if [ \! -f .version ]; then
	echo 0  > .version
fi

# Update the version number in the '.version' file.
# Separate number from additional alpha/beta/etc marker
MARKER=`cat .version | sed 's/[0-9.]//g'`
# Bump the number
VN=`cat .version | sed 's/[^0-9.]//g'`
# Reassemble and write back out
VN=$(($VN + 1))
rm -f .version.old
mv .version .version.old
chmod +w .version.old
echo $VN$MARKER > .version
RELEASE="$(($VN/1000000)).$(( ($VN/1000)%1000 )).$(( $VN%1000 ))$MARKER"
echo "Bumping sources to release $RELEASE"
grep -rl "%RELEASE%" *.php|xargs sed -i "" "s/%RELEASE%/$RELEASE/g"