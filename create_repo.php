<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['github_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated. Please login with GitHub.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$name        = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$private     = isset($input['private']) ? (bool)$input['private'] : false;
$autoInit    = isset($input['auto_init']) ? (bool)$input['auto_init'] : false;

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Repository name is required.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Repository name can only contain letters, numbers, hyphens, underscores, and dots.']);
    exit;
}

$payload = [
    'name'        => $name,
    'description' => $description,
    'private'     => $private,
    'auto_init'   => $autoInit,
];

$token = $_SESSION['github_token'];

$response = file_get_contents('https://api.github.com/user/repos', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Authorization: Bearer $token\r\nUser-Agent: PHP-GitHub-Repo-Creator\r\nAccept: application/vnd.github+json\r\nContent-Type: application/json\r\n",
        'content' => json_encode($payload),
        'ignore_errors' => true,
    ],
]));

$responseHeaders = $http_response_header ?? [];
$statusCode = 201;
foreach ($responseHeaders as $header) {
    if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
        $statusCode = (int)$matches[1];
    }
}

$data = json_decode($response, true);

if ($statusCode === 201) {
    echo json_encode([
        'success' => true,
        'repo' => [
            'name'        => $data['name'],
            'full_name'   => $data['full_name'],
            'description' => $data['description'],
            'private'     => $data['private'],
            'html_url'    => $data['html_url'],
            'clone_url'   => $data['clone_url'],
            'ssh_url'     => $data['ssh_url'],
            'created_at'  => $data['created_at'],
        ],
    ]);
} else {
    http_response_code($statusCode);
    echo json_encode([
        'error'   => $data['message'] ?? 'Failed to create repository.',
        'details' => $data['errors'] ?? [],
    ]);
}
