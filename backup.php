<?php

define('WIREGUARD_FILENAME', '/etc/wireguard/wg0.conf');

/**
 * Ecryption data
 */

// Store the cipher method
$ciphering = "AES-128-CTR";

// Use OpenSSl Encryption method
$iv_length = openssl_cipher_iv_length($ciphering);
$options = 0;

// Non-NULL Initialization Vector
$iv = 'wireguard';

// Store the  key
$key = "wireguard";


// user request for download config file
if (isset($_GET['getBackup']) && !empty($_GET['getBackup'])) {

    $config_str = shell_exec('sudo cat ' . WIREGUARD_FILENAME);

    // Use openssl_encrypt() function to encrypt the data
    $encrypted_data = encrypt_config($config_str);

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=wireguard-backup-' . time() . '.conf');

    echo $encrypted_data;

    exit(0);
}

// upload backup file
if (isset($_FILES['backupfile']) && $_FILES['backupfile']['error'] === 0) {
    $config = get_decrypted_config($_FILES['backupfile']);

    if ($config['ok'] && file_put_contents('./wireguard-backup-upload/wg0.conf', $config['data'])) {
        $backupfile_status = ['ok' => true, 'desc' => 'Backup file saved, Ask the administrator to confirm that.'];
    } else {
        $backupfile_status = $config;
    }
}

// get uploaded file, decrypt and check for validity
function get_decrypted_config($file)
{
    // check for size
    if ($file['size'] < 100 || $file['size'] > 50000) {
        return ['ok' => false, 'desc' => 'File is too large'];
    }

    // check for file mime
    if (mime_content_type($file['tmp_name']) !== 'text/plain') {
        return ['ok' => false, 'desc' => 'Undefined file'];
    }

    // read the file
    if (!$encrypted_data = file_get_contents($file['tmp_name'])) {
        return ['ok' => false, 'desc' => "Something went wrong! Can't read file."];
    }

    // decrypt file
    if (!$data = decrypt_config($encrypted_data)) {
        return ['ok' => false, 'desc' => "Decryption processes failed."];
    }

    // check for config validity
    if (strpos($data, '[Interface]') === false || strpos($data, '[Peer]') === false) {
        return ['ok' => false, 'desc' => "Configuration file is not valid."];
    }

    return [
        'ok' => true,
        'desc' => "File is valid",
        'data' => $data
    ];
}

function encrypt_config($data)
{
    // required data for encrypt
    global $ciphering;
    global $key;
    global $options;
    global $iv;

    return openssl_encrypt($data, $ciphering, $key, $options, $iv);
}

function decrypt_config($data)
{
    // required data for decrypt
    global $ciphering;
    global $key;
    global $options;
    global $iv;

    return openssl_decrypt($data, $ciphering, $key, $options, $iv);
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wireguard - Backup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
</head>

<body>
    <nav class="navbar is-link" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>
            <div id="navbarBasicExample" class="navbar-menu">
                <div class="navbar-start">
                    <a class="navbar-item" href="./index.php">
                        Add Client
                    </a>
                    <a class="navbar-item is-active" href="./backup.php">
                        Backup and Restore
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <section class="section">
        <div class="container">

            <?php if ($backupfile_status) : ?>
                <div class="columns">
                    <div class="column">
                        <article class="message <?= $backupfile_status['ok'] ? 'is-success' : 'is-danger' ?>">
                            <div class="message-body">
                                <?= $backupfile_status['desc'] ?>
                            </div>
                        </article>
                    </div>
                </div>
            <?php endif; ?>

            <div class="columns">

                <div class="column">

                    <div class="box p-6">
                        <h3 class="title is-3 has-text-centered is-capitalized pb-6">get backup file</h3>
                        <div class="has-text-centered">
                            <a href="./backup.php?getBackup=true" class="button is-link is-large">Get Backup</a>
                        </div>
                    </div>

                </div>

                <div class="column">

                    <div class="box p-6">
                        <h3 class="title is-3 has-text-centered is-capitalized pb-6">import backup file</h3>
                        <form id="form-backupfile" action="<?= ltrim($_SERVER['PHP_SELF'], '/'); ?>" method="POST" enctype="multipart/form-data">
                            <div class="file is-centered is-link is-boxed">
                                <label class="file-label">
                                    <input class="file-input" type="file" accept=".conf" id="backupfile" name="backupfile">
                                    <span class="file-cta">
                                        <span class="file-label">
                                            Choose a file…
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </section>
    <script>
        document.querySelector("#backupfile").addEventListener('change', (e) => {
            document.querySelector("form#form-backupfile").submit();
        });
    </script>
</body>

</html>