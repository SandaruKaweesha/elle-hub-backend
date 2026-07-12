<?php
require_once __DIR__ . '/app/service/UserService.php';
require_once __DIR__ . '/app/model/User.php';
require_once __DIR__ . '/app/model/Team.php';

$svc = new UserService();
$team = new Team();
$team->setEmail('test@test.com');
$team->setPassword('password');
$team->setRole('TEAM');
$team->setTeamName('My Team');

$result = $svc->registerUser($team);
var_dump($result);
