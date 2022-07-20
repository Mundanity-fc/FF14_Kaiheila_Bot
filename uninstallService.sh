#!/bin/bash
service="/lib/systemd/system/FF14Bot.service"
if test -e $service
then
  echo "正在删除服务……"
else
  echo "未找到服务，请确认是否正常安装"
  exit
fi
rm -f $service
if test -e $service
then
  echo "删除失败，请确认脚本是否拥有权限（以 root 用户执行或采用 sudo 命令）"
  exit
else
  systemctl daemon-reload
  echo "删除成功"
  exit
fi