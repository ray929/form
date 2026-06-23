<?php
/**
 * Header partial — Admin pages (with sidebar)
 */
$current = $current ?? '';
$currentLang = $currentLang ?? App::langCode();
?><!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(App::lang()['site_name']) ?> — <?= h($title ?? '') ?></title>
<link rel="stylesheet" href="/css/app.css?v=1">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
<body>
<nav class="sidebar">
  <div class="sidebar-logo"><?= h(App::lang()['site_name']) ?></div>
  <a href="/admin" class="<?= $current === 'dashboard' ? 'active' : '' ?>"><?= h(App::lang()['dashboard']) ?></a>
  <a href="/admin/forms" class="<?= $current === 'forms' ? 'active' : '' ?>"><?= h(App::lang()['forms']) ?></a>
  <a href="/admin/submissions" class="<?= $current === 'submissions' ? 'active' : '' ?>"><?= h(App::lang()['submissions']) ?></a>
  <a href="/admin/embed" class="<?= $current === 'embed' ? 'active' : '' ?>"><?= h(App::lang()['embed_code']) ?></a>
  <a href="/admin/logout" style="margin-top:auto;"><?= h(App::lang()['logout']) ?></a>
</nav>
<main class="main">
  <div class="topbar">
    <h1><?= h($title ?? '') ?></h1>
  </div>
