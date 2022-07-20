#!/bin/bash
path="`pwd`/main.php"
error="`pwd`/FF14BotErr.log"
service="/lib/systemd/system/FF14Bot.service"
if test -e $service
then
  echo "文件已存在，请执行 uninstallSercive.sh 脚本将文件删除后再进行操作"
  exit
fi
echo "[Unit]" >> $service
echo "Description=FF14Bot Service" >> $service
echo "Documentation=https://github.com/Mundanity-fc/FF14_Kaiheila_Bot" >> $service
echo "" >> $service
echo "[Service]" >> $service
echo "Type=simple" >> $service
echo "User=root" >> $service
echo "LimitNPROC=500" >> $service
echo "LimitNOFILE=1000000" >> $service
echo "ExecStart=/usr/bin/php $path" >> $service
echo "Restart=on-failure" >> $service
echo "StandardError=append:$errlog" >> $service
echo "" >> $service
echo "[Install]" >> $service
echo "WantedBy=multi-user.target" >> $service
if test -e $service
then
  echo "已创建服务，请运行 systemctl start FF14Bot 执行该服务"
  exit
else
  echo "服务创建失败，请确认脚本是否拥有权限（以 root 用户执行或采用 sudo 命令）"
  exit
fi
