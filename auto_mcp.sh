#!/bin/bash

time=`date +%Y%m%d%H%M%S`

function copy()
{
expect<<EOF
    set timeout 30
    spawn scp root@10.10.0.141:${1}/${2} /home/work/mcp/bin
    expect "*assword"
    send "1a2s3dqwe\r"
    expect "100%"
EOF
}


bin=( "mcp")
bin_amount=${#bin[@]};
path=( "/home/zhengxie/mcp/trunk/")

if [ "$#" -eq "0" ]
then
    for((i=0;i<$bin_amount;++i))
    do
        mv bin/${bin[$i]} bak/${bin[$i]}_${time} 2>/dev/null
        copy ${path[$i]} ${bin[$i]}

        #restart ${bin[$i]}
        killall ${bin[$i]}
        nohup bin/${bin[$i]} &
    done
else
    for arg in $*
    do
        for((i=0;i<$bin_amount;++i))
        do
            if [ "$arg" == "${bin[$i]}" ]
            then
                mv bin/${bin[$i]} bak/${bin[$i]}_${time} 2>/dev/null
                copy ${path[$i]} ${bin[$i]}

                #restart ${bin[$i]}
                killall ${bin[$i]}
                nohup bin/${bin[$i]} &
            fi
        done
    done
fi

