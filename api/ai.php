<?php
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$bio = trim($data['bio'] ?? '');
$style = trim($data['style'] ?? 'professional');

if ($bio === '') {
    echo json_encode(['error' => 'Bio is required']);
    exit;
}

function normalizeText(string $text): string {
    $text = trim($text);
    $text = preg_replace('/\s+/', ' ', $text);
    return $text;
}

function sentenceCase(string $text): string {
    $text = trim($text);
    if ($text === '') return $text;
    $text = ucfirst($text);
    if (!preg_match('/[.!?]$/', $text)) {
        $text .= '.';
    }
    return $text;
}

function improveProfessional(string $text): string {
    $text = normalizeText($text);

    $replacements = [
        '/\bmyself\b/i' => 'I am',
        '/\bi am\b/i' => 'I am',
        '/\bi have done\b/i' => 'I have completed',
        '/\bi know\b/i' => 'I am proficient in',
        '/\bgood at\b/i' => 'skilled in',
        '/\binterested in\b/i' => 'passionate about',
        '/\bmaking\b/i' => 'building',
        '/\bwebsite\b/i' => 'web applications',
        '/\bwebsites\b/i' => 'web applications',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    $text = sentenceCase($text);

    if (!preg_match('/developer|engineer|student|designer/i', $text)) {
        $text = 'I am a motivated developer. ' . $text;
    }

    if (!preg_match('/passionate|focused|dedicated|enthusiastic/i', $text)) {
        $text .= ' I am passionate about building efficient, user-focused, and scalable solutions.';
    }

    return $text;
}

function improveConcise(string $text): string {
    $text = normalizeText($text);
    $text = preg_replace('/\bmyself\b/i', 'I am', $text);
    $text = preg_replace('/\bgood at\b/i', 'skilled in', $text);
    $text = sentenceCase($text);

    if (strlen($text) > 160) {
        $text = substr($text, 0, 157) . '...';
    }

    if (!preg_match('/developer|student|engineer/i', $text)) {
        $text = 'I am a developer with a strong interest in practical and efficient solutions.';
    }

    return $text;
}

function improveCreative(string $text): string {
    $text = normalizeText($text);
    $text = preg_replace('/\bmyself\b/i', 'I am', $text);
    $text = sentenceCase($text);

    if (!preg_match('/developer|creator|builder/i', $text)) {
        $text = 'I am a curious builder who enjoys turning ideas into meaningful digital experiences.';
    }

    if (!preg_match('/creative|innovative|modern|impact/i', $text)) {
        $text .= ' I enjoy creating modern, impactful, and user-friendly solutions that blend creativity with functionality.';
    }

    return $text;
}

switch ($style) {
    case 'concise':
        $improved = improveConcise($bio);
        break;
    case 'creative':
        $improved = improveCreative($bio);
        break;
    case 'professional':
    default:
        $improved = improveProfessional($bio);
        break;
}

echo json_encode([
    'success' => true,
    'bio' => $improved,
    'mode' => 'fallback-ai',
    'style' => $style
]);