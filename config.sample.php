<?php
/*
 * Kaiheila API Setting
 * 开黑啦 API 相关设置
 */

// The API Token Provided at the Bot Detail Information Page
// 机器人管理界面提供的 Token
const TOKEN = '';

// HTTP Request Base URL (No need to change)
// 开黑啦 HTTP 请求的基本 URL（默认无需更改）
const BASE_URL = "https://www.kaiheila.cn";

/*
 * DataBase Setting
 * 数据库设置部分
 */

// Target Database Type: MySQL, PostgreSQL(Default)
// 设置欲连接的数据库的种类，可选：MySQL, PostgreSQL(默认)
const DbType = 'PostgreSQL';

// Database IP Address (or Domain Name)
// 数据库链接的 IP 地址（或域名）
const DbHost = '127.0.0.1';

// Database Work Port
// 数据库工作端口
const DbPort = '5432';

// Authorized Username
// 有读写权限的用户名
const DbUsername = 'postgres';

// User Password
// 对应用户的密码
const DbPassword = '';

// Database Name
// 欲使用的数据库名
const DbName = 'FF14Bot';