#!/bin/bash
# Program
#	搜索百度多酷渠道脚本,将结果插入数据库check_baidu_app
# History
#	2013/04/07	zhengxie(zhengxie@duoku.com)	First release

LOGPATH="/home/zhengxie/checkapp/checkApp.log"
export LANG=zh_CN.UTF-8
date "+%Y/%B/%d %I:%M:%S || checkApp.php执行开始" >> $LOGPATH 
message=`/home/work/webserver/php/bin/php /home/zhengxie/checkapp/checkApp.php` 
date "+%Y/%B/%d %I:%M:%S || checkApp.php执行完毕" >> $LOGPATH

HOST="10.10.0.10"
PORT="4051"
USER="root"
PASS="duoku2012"

data="/home/zhengxie/checkapp/checkApp.csv" >> $LOGPATH

date "+%Y/%B/%d %I:%M:%S || 插入数据库开始" >> $LOGPATH
while read line;
do
	oldIFS=$IFS
	IFS=","
	values=($line)
	values[0]="''"
	values[2]="\"`echo ${values[2]}`\""
	values[6]="\"`echo ${values[6]}`\""
	query=`echo "${values[@]}"|tr ' ' ','`
	IFS=$oldIFS
	mysql -h$HOST -P$PORT -u$USER -p$PASS --default-character-set='utf8' 'MCP' <<EOF
	INSERT INTO \`check_baidu_app\` VALUES($query);
EOF
done< $data

date "+%Y/%B/%d %I:%M:%S || 成功插入数据库" >> $LOGPATH
echo "" >> $LOGPATH 
