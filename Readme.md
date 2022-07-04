# 用于[开黑啦(现KOOK)](https://www.kookapp.cn/)的最终幻想XIV(Final Fantasy XIV)机器人

本项目实现了基于 Swoole 的 PHP 机器人。目前可以实现对游戏内任务信息的查询，并返回伴有制定 wiki 链接的卡片式消息。

由于需要采用 Swoole 进行与开黑啦服务器的 Websocket 通信，因而请确保工作环境中的 PHP 已经配置好了相关拓展。

使用方法：

- 完成环境的配置（包括 Swoole 拓展的启用与数据库服务器的配置导入）
- 安装所需的 composer 包
- 以脚本形式运行 main.php 文件