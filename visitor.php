<?php

header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/visitors.json';

function readVisitors($file)
{
    if (!is_file($file)) {
        return [];
    }

    $content = file_get_contents($file);

    if ($content === false || trim($content) === '') {
        return [];
    }

    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
}

function saveVisitors($file, $visitors)
{
    file_put_contents(
        $file,
        json_encode(
            $visitors,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ),
        LOCK_EX
    );
}

$visitors = readVisitors($file);

/*
 * Création d'une nouvelle demande
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    $visitorId =
        trim((string)($input['visitor_id'] ?? ''));

    if ($visitorId === '') {
        http_response_code(400);

        echo json_encode([
            'error' => 'Visitor ID missing'
        ]);

        exit;
    }

    $found = false;

    foreach ($visitors as &$visitor) {

        if ($visitor['id'] === $visitorId) {

            $visitor['last_seen'] = time();
            $found = true;

            break;
        }
    }

    unset($visitor);

    if (!$found) {

        $visitors[] = [
            'id' => $visitorId,
            'created_at' => time(),
            'last_seen' => time(),
            'redirect' => null
        ];
    }

    saveVisitors(
        $file,
        $visitors
    );

    echo json_encode([
        'success' => true
    ]);

    exit;
}


/*
 * Vérification du statut
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $visitorId =
        trim((string)($_GET['id'] ?? ''));

    if ($visitorId === '') {

        http_response_code(400);

        echo json_encode([
            'error' => 'Visitor ID missing'
        ]);

        exit;
    }

    foreach ($visitors as $visitor) {

        if ($visitor['id'] === $visitorId) {

            echo json_encode([
                'redirect' =>
                    $visitor['redirect'] ?? null
            ]);

            exit;
        }
    }

    echo json_encode([
        'redirect' => null
    ]);

    exit;
}

http_response_code(405);

echo json_encode([
    'error' => 'Method not allowed'
]);