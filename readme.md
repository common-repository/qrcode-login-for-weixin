=== 微信二维码登陆 ===

Contributors: baialaps
Tags: weixin,wechat,微信,二维码登陆,带参数二维码
Requires at least: 5.0
Tested up to: 5.2
Requires PHP: 7.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

请注意： 
必须使用认证过的微信服务号！ 
必须使用认证过的微信服务号！ 
必须使用认证过的微信服务号！

如果实在没有认证微信服务号，请到<a href="https://mp.weixin.qq.com/debug/cgi-bin/sandbox?t=sandbox/login">点击这里</a>，申请一个微信debug账号使用。

基于公司内部的网站系统，同事老是记不住密码，也不习惯使用浏览器保存等行为，估开发出这个工具方便各种不记密码党。

有问题，请加开发者微信进入讨论群：markchenlife

== Installation ==

1. 上传到 `/wp-content/plugins/` 目录
2. 在后台插件菜单激活该插件
3. 在设置页面里面输入微信公众号的 appid和密匙，令牌请自己填写一个长度低于32位的随机字条串即可
4. 在微信公众号后台 服务器配置->服务器地址(URL) 修改为  https://example.com?qrcode=1 请根据网站实际情况使用http或https， 把example.com修改为你自己的网站域名.
5. 消息加解密方式暂时只支持明文模式

== Changelog ==

1.3  居然有同学不知道在哪里设置，我加了几张截图，方便大家了解