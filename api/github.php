<?php
header('Content-Type: application/json; charset=utf-8');

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    echo json_encode(['error' => 'Username required']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9-]+$/', $username)) {
    echo json_encode(['error' => 'Invalid GitHub username']);
    exit;
}

$url = "https://api.github.com/users/" . rawurlencode($username) . "/repos?sort=updated&per_page=10";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Portfolio-App\r\nAccept: application/vnd.github+json\r\n",
        "timeout" => 15
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Failed to fetch repos from GitHub']);
    exit;
}

$repos = json_decode($response, true);

if (!is_array($repos)) {
    echo json_encode(['error' => 'Invalid response from GitHub']);
    exit;
}

if (isset($repos['message'])) {
    echo json_encode(['error' => $repos['message']]);
    exit;
}

$result = [];

foreach ($repos as $repo) {
    if (!is_array($repo)) {
        continue;
    }

    $result[] = [
        'name' => $repo['name'] ?? '',
        'description' => $repo['description'] ?? '',
        'url' => $repo['html_url'] ?? ''
    ];
}

echo json_encode($result);