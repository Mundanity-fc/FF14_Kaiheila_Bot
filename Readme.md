# 用于[开黑啦(现KOOK)](https://www.kookapp.cn/)的最终幻想XIV(Final Fantasy XIV)机器人

本项目实现了基于 Swoole 的 PHP 机器人。目前可以实现对游戏内任务信息的查询，并返回伴有指定 wiki 链接的卡片式消息。

由于需要采用 Swoole 进行与开黑啦服务器的 Websocket 通信，因而请确保工作环境中的 PHP 已经配置好了相关拓展。

使用方法：

- 完成环境的配置（包括 Swoole 拓展的启用与数据库服务器的配置导入）
- 安装所需的 composer 包
- 以脚本形式运行 main.php 文件

已知问题：

- 任务目标中包含判断条件道具信息等内容时，会以原样式返回

Todo：

- [x] 修复Bug
- [ ] 完成 Universalis 查询功能
- [ ] 完成道具搜索功能
- [ ] 实现任务信息的英/日文搜索（服务有国际服需求的玩家）
- [x] 稳定性测试

---

## 常见错误

#### 运行完 `php ./main.php` 后无任何反应直接结束

请检查是否正常配置了运行环境，composer 包是否都全部安装

#### 运行至 `state getGateWay` 后程序自动退出

请检查 swoole 是否正常编译安装（编译过程中是否在配置阶段启用了 openssl 选项）