# .bashrc

# User specific aliases and functions

# Source global definitions
if [ -f /etc/bashrc ]; then
	. /etc/bashrc
fi

#PS1='[\u@\h \w \A #\#]\$ '
PS1="\e[1;46m[\u@\h:\w#\#]\$ \e[0m"
#export $PS1
#source ~/.git-completion.bash

set -o vi
export TERM=xterm-256color
alias php='/home/work/webserver/php/bin/php'
alias l='ls --color=tty'
alias dataxml='vi /home/work/www/bdapp/all/data.xml'
alias generatexml='bash /home/zhengxie/push/generateXML.sh'
alias pushxml='bash /home/zhengxie/push/push.sh'
alias j='jobs'
alias m2='/home/work/server/mongodb/bin/mongo -port 27021'
alias m3='/home/work/server/mongodb/bin/mongo -port 30000'
alias m35='/home/work/server/mongodb/bin/mongo -port 30005'
alias r='/home/work/server/redis1/bin/redis-cli -p 6382 -n 3'
alias r101='/home/work/server/redis1/bin/redis-cli -h 10.10.1.101 -p 6384 -n 3'
alias lsc='ls -lR|grep "^-"|wc -l'
alias jython='/home/zhengxie/jython/jython'
alias rmapk='rm -f *.apk'
alias seigeurl='siege -c 100 -r 10 url'
alias tarbz2='tar -vxjf '
#alias vi='/home/zhengxie/vim74/bin/vim '
#alias pip='/home/zhengxie/python/bin/pip '
#alias python='/home/zhengxie/python/bin/python '
alias upmongodb='bash /home/zhengxie/upmongodb/upmongodb.sh'
alias upstar='bash /home/zhengxie/upmongodb/upstar.sh'
alias vimcp='vim /home/zhengxie/mcp/trunk/mcp.idl'
#alias git='/home/zhengxie/program/bin/git'
alias svnmcp='svn co http://10.10.0.156/svn/mcp/mcp/'
alias gitlog='git log --pretty=format:"[%h] %ae, %ar: %s" --stat'
alias svn='/usr/local/bin/svn'
alias vb='vi ~/.bashrc'
alias v='vi'
alias s='source ~/.bashrc'
alias h='history'
alias g='grep'
