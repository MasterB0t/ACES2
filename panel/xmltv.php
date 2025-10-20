<?php

$Protocol = isset($_SERVER['HTTPS']) ? "https" : "http";
header("Location: {$Protocol}://{$_SERVER['HTTP_HOST']}/guide/{$_GET['username']}/{$_GET['password']}/guide.xml");

