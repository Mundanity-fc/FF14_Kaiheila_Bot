# 用于[KOOK(即`开黑啦`)](https://www.kookapp.cn/)的《最终幻想14》 (Final Fantasy XIV) 机器人

本项目实现了基于 Swoole 协程的 PHP 机器人。目前可以实现对游戏内任务信息的查询、价格查询等，并返回伴有指定 wiki 链接的卡片式消息。

由于需要采用 Swoole 进行与开黑啦服务器的 Websocket 通信，因而请确保工作环境中的 PHP 已经配置好了相关拓展。

使用方法：

- 完成环境的配置（包括 Swoole 拓展的启用与数据库服务器的配置导入）
- 安装所需的 composer 包
- 以脚本形式运行 main.php 文件（Linux 下也可以运行 `bash installService.sh` 脚本安装 systemd 服务，然后以服务方式启动脚本 `systemctl start FF14Bot`，Windows 及 MAC 请自信准备后台执行方案）

卸载方法：

- 没有安装 systemd 服务的直接清除项目文件夹
- 安装了 systemd 服务的先执行 `bash uninstallService.sh`，之后再移除项目文件夹

已知问题：

- 任务目标中包含判断条件道具信息等内容时，会以原样式返回（需要对 XIVAPI 返回的文本进行大量的处理，暂时摸了，反正没人看任务目标）

Todo：

- [x] 完成 Universalis 查询功能
- [x] 实现任务信息的英/日文搜索（服务有国际服需求的玩家）
- [x] 本地数据库实现字段翻译，逐渐摆脱对 [FFCafe CafeMaker](https://github.com/thewakingsands/cafemaker) 的依赖（由于
  XIVAPI 没有中文字段内容，因而采用 FFCafe 提供的 API，其优势为可以提供全中文内容的 json 数据，但是经过用户测试和本地测试发现
  CafeMaker 的链接稳定性不甚理想，考虑到其所属团体的公益性质，因予以充分理解，目前 任务名/道具名/技能名
  可以实现本地数据库翻译查询，但是对于完整的任务信息还需要 任务类型/所属分类/NPC名称/所在地区/~~任务目标~~ 等字段的翻译）
- [ ] 提示主线任务进度（这个不在 XIVAPI 中，需要对本地数据库新增字段来实现）
- [ ] 完成道具搜索功能
- [ ] 稳定性测试（基于 PHP 脚本的 Swoole 协程稳定性未知，且测试阶段出现过 Websocket 产生 pingpong 但是无法接收到频道消息的情况，未来会与 Webhook 进行对比测试）
- [ ] 大流量测试
- [x] HTTP Timeout 复写（Saber 建立的链接当出现超时错误后会直接停止脚本运行，由于 Systemd 中 Restart=on-failure 字段的存在，因而脚本会自动重启，但在用户处没有感知）
- [ ] FF Logs 查询

---

## 常见错误

>具体情况还请确认 PHP 的报错信息。若存在无法解决情况，可以尝试提交 issue

#### 运行完 `php ./main.php` 后无任何反应直接结束

请检查是否正常配置了运行环境，composer 包是否都全部安装

#### 运行至 `state getGateWay` 后程序自动退出

请检查 swoole 是否正常编译安装（编译过程中是否在配置阶段启用了 openssl 选项）
