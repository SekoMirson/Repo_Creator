<?php
session_start();

define('GITHUB_CLIENT_ID', getenv('GITHUB_CLIENT_ID') ?: '');
define('GITHUB_CLIENT_SECRET', getenv('GITHUB_CLIENT_SECRET') ?: '');

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
        die(json_encode(['error' => 'Invalid state parameter. CSRF detected.']));
    }

    $tokenResponse = file_get_contents('https://github.com/login/oauth/access_token', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => json_encode([
                'client_id'     => GITHUB_CLIENT_ID,
                'client_secret' => GITHUB_CLIENT_SECRET,
                'code'          => $code,
            ]),
        ],
    ]));

    $tokenData = json_decode($tokenResponse, true);

    if (isset($tokenData['access_token'])) {
        $_SESSION['github_token'] = $tokenData['access_token'];

        $userResponse = file_get_contents('https://api.github.com/user', false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$tokenData['access_token']}\r\nUser-Agent: PHP-GitHub-Repo-Creator\r\nAccept: application/vnd.github+json\r\n",
            ],
        ]));

        $userData = json_decode($userResponse, true);
        $_SESSION['github_user'] = $userData;

        header('Location: index.php');
        exit;
    } else {
        $_SESSION['auth_error'] = $tokenData['error_description'] ?? 'Authentication failed.';
        header('Location: index.php');
        exit;
    }
}

header('Location: index.php');
exit;
