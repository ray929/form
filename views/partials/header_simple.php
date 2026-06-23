<?php
/**
 * Simple header — for login pages and simple layouts (no sidebar)
 */
$currentLang = $currentLang ?? App::langCode();
$otherLang = $currentLang === 'zh' ? 'en' : 'zh';
$otherLangLabel = $currentLang === 'zh' ? 'EN' : '中';
?><!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? App::lang()['site_name']) ?></title>
<link rel="stylesheet" href="/css/app.css?v=1">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
