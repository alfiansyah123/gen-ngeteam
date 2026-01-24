<?php
// Router for PHP built-in server
// Run with: php -S localhost:8000 router.php

// Adjust path handling for subdirectories (e.g. /gen/)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_path = dirname($_SERVER['SCRIPT_NAME']); // returns /gen or /

// Remove the subfolder path from the URI to get the clean slug
if ($script_path !== '/' && strpos($request_uri, $script_path) === 0) {
    $slug = substr($request_uri, strlen($script_path));
} else {
    $slug = $request_uri;
}

$slug = ltrim($slug, '/'); // remove leading slash

if (empty($slug)) {
    // If root /, serve index.html or just show message
    // Since we are running separate frontend, maybe just 404 or message
    echo "Link Generator Backend Running. Access frontend via Vite.";
    exit;
}

// Debugging
ini_set('display_errors', 0); // Disable display errors to not mess up HTML output
error_reporting(E_ALL);

function is_bot() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bots = [
        'facebookexternalhit', 'twitterbot', 'whatsapp', 'linkedinbot', 
        'pinterest', 'slackbot', 'telegrambot', 'discordbot', 'googlebot', 
        'bingbot', 'yandex', 'duckduckgo'
    ];
    
    foreach ($bots as $bot) {
        if (strpos($userAgent, $bot) !== false) {
            return true;
        }
    }
    return false;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM links WHERE slug = ?");
    $stmt->execute([$slug]);
    $link = $stmt->fetch();

    if ($link) {
        $target = $link['original_url'];
        
        // Ensure protocol exists
        if (!preg_match('#^https?://#', $target)) {
            $target = 'https://' . $target;
        }

        // Get Metadata
        $title = htmlspecialchars($link['title'] ?? 'Link Preview');
        $description = htmlspecialchars($link['description'] ?? 'Click to view this link');
        $image = htmlspecialchars($link['image_url'] ?? '');

        // CLOAKING LOGIC
        if (is_bot()) {
            // SERVE SAFE PREVIEW FOR BOTS (No Redirect)
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title><?= $title ?></title>
                <meta name="description" content="<?= $description ?>">
                
                <!-- Open Graph / Facebook -->
                <meta property="og:type" content="website">
                <meta property="og:title" content="<?= $title ?>">
                <meta property="og:description" content="<?= $description ?>">
                <?php if ($image): ?>
                <meta property="og:image" content="<?= $image ?>">
                <?php endif; ?>

                <!-- Twitter -->
                <meta property="twitter:card" content="summary_large_image">
                <meta property="twitter:title" content="<?= $title ?>">
                <meta property="twitter:description" content="<?= $description ?>">
                <?php if ($image): ?>
                <meta property="twitter:image" content="<?= $image ?>">
                <?php endif; ?>
            </head>
            <body>
            </body>
            </html>
            <?php
            exit;
        } else {
            // SERVE SAFE LANDING FOR HUMANS (JS Redirect)
            // This breaks the redirect chain for scanners
            header('Referrer-Policy: no-referrer');
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta name="referrer" content="no-referrer">
                <title>Waiting for you...</title>
                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400&display=swap" rel="stylesheet">
                <style>
                    body {
                        background-color: #111827;
                        background-image: url('<?= $image ?>');
                        background-size: cover;
                        background-position: center;
                        background-blend-mode: overlay;
                        color: #ffffff;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 100vh;
                        margin: 0;
                        font-family: 'Outfit', sans-serif;
                    }
                    /* Add a dark overlay so text is visible */
                    body::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        z-index: -1;
                    }
                    .loader {
                        border: 3px solid rgba(255, 255, 255, 0.1);
                        border-left-color: #f97316;
                        border-radius: 50%;
                        width: 50px;
                        height: 50px;
                        animation: spin 1s linear infinite;
                        margin-bottom: 20px;
                    }
                    h2 {
                        font-weight: 300;
                        letter-spacing: 1px;
                        font-size: 1.2rem;
                        margin: 0;
                        opacity: 0.9;
                        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
                    }
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                </style>
            </head>
            <body>
                <div class="loader"></div>
                <h2>Waiting for you...</h2>
                <script>
                    setTimeout(() => {
                        window.location.replace("<?= $target ?>");
                    }, 1000); // 1 second delay
                </script>
            </body>
            </html>
            <?php
            exit;
        }

    } else {
        // 404 Not Found
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>Link invalid or expired.</p>";
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Internal Server Error";
}
?>
