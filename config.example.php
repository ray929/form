<?php
/**
 * Forms — 配置文件模板
 * 部署时复制为 config.php 并填入实际值
 */
return [
    // Resend 邮件发送
    'resend_api_key' => 're_xxxxxxxxxxxx',

    // Cloudflare Turnstile
    'turnstile_site_key'   => '1x00000000000000000000AA',
    'turnstile_secret_key' => '1x0000000000000000000000000000000AA',

    // 默认收件邮箱（新表单未指定收件人时使用）
    'default_recipient' => 'form@542500.xyz',

    // 发件人信息
    'from_email' => 'hello@form.542500.xyz',
    'from_name'  => 'Form Messenger',

    // SQLite 数据库路径（相对于本文件）
    'db_path' => __DIR__ . '/db/forms.db',

    // htpasswd 文件路径
    'htpasswd_path' => __DIR__ . '/.htpasswd',

    // 站点名称
    'site_name' => 'Forms',

    // 默认语言（en / zh）
    'default_lang' => 'en',
];
