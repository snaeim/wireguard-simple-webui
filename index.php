<?php

/**
 * 
 */
define('WIREGUARD_FILENAME', '/etc/wireguard/wg0.conf');
require_once('./wireguard.php');

$wg = new Wireguard(WIREGUARD_FILENAME);

/**
 * 
 */
if (isset($_POST['address']) && isset($_POST['dns']) && isset($_POST['endpoint'])) {
    $res = $wg->add_peer($_POST['address'], $_POST['dns'], $_POST['endpoint']);
    if ($res['ok']) {
        $output = $res['result'];
    } else {
        $err_txt = $res['result'];
    }
    $wg->update();
}

/**
 * 
 */
$ip_address = $wg->get_available_ip();


?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wireguard - Add peer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <style>
        .select,
        .select select {
            width: 100%;
        }

        label {
            user-select: none;
        }
    </style>
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
                    <a class="navbar-item is-active" href="./index.php">
                        Add Client
                    </a>
                    <a class="navbar-item" href="./backup.php">
                        Backup and Restore
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <section class="section">
        <div class="container">

            <?php if (isset($err_txt)) :  ?>
                <div class="columns">
                    <div class="column">
                        <article class="message is-danger">
                            <div class="message-body">
                                <?= $_POST['address'] . ' is ' . $err_txt; ?>
                            </div>
                        </article>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($output)) :  ?>
                <div class="columns">
                    <div class="column">
                        <article class="message is-success">
                            <div class="message-body">
                                Client successfully created, make sure to take config file.
                            </div>
                        </article>
                    </div>
                </div>
            <?php endif; ?>

            <div class="columns">

                <div class="column">

                    <form class="box" action="<?= ltrim($_SERVER['PHP_SELF'], '/'); ?>" method="POST">

                        <div class="field">
                            <label id="enable-address-edit" class="label">Address</label>
                            <div class="control">
                                <input class="input" name="address" readonly required type="text" value="<?= $ip_address === false ? 'No IP available.' : $ip_address ?>">
                            </div>
                            <p class="help">Max host address: <?= $wg->host_ip_info['hostMax']; ?></p>
                        </div>

                        <div class="field">
                            <label class="label">DNS</label>
                            <div class="select">
                                <select name="dns">
                                    <option value="8.8.8.8, 8.8.4.4">Google</option>
                                    <option value="1.1.1.1, 1.0.0.1">Cloudflare</option>
                                    <option value="9.9.9.9, 149.112.112.112">Quad9</option>
                                    <option value="208.67.222.222, 208.67.220.220">OpenDNS</option>
                                    <option value="94.140.14.14, 94.140.15.15">AdGuard</option>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label">Endpoint</label>
                            <div class="control">
                                <input class="input" name="endpoint" required placeholder="e.g. domain.com" type="text">
                            </div>
                        </div>

                        <div class="field">
                            <div class="control">
                                <button class="button is-link">Submit</button>
                            </div>
                        </div>

                    </form>

                </div>

                <div class="column">

                    <form class="box" action="#" method="POST">

                        <div class="field">
                            <label class="label">Result</label>
                            <div class="control">
                                <textarea class="textarea" rows="8" id="result" <?= isset($output) ? 'readonly' : '' ?>><?= $output ?? '' ?></textarea>
                            </div>
                        </div>

                        <p id="config-action" class="buttons">
                            <button id="config-save" class="button is-link" <?= isset($output) ? '' : 'disabled' ?>>
                                <span class="material-icons">save_alt</span>
                            </button>
                            <button id="config-qrcode" class="button is-link" <?= isset($output) ? '' : 'disabled' ?>>
                                <span class="material-icons">qr_code_2</span>
                            </button>
                            <button id="config-copy" class="button is-link" <?= isset($output) ? '' : 'disabled' ?>>
                                <span class="material-icons">content_copy</span>
                            </button>
                        </p>

                    </form>

                </div>
            </div>
        </div>
    </section>
    <div id="modal-qrcode" class="modal">
        <div class="modal-background"></div>
        <div class="modal-content">
            <p class="image is-square">
                <img src="./loading.gif" alt="">
            </p>
        </div>
        <button class="modal-close is-large" aria-label="close"></button>
    </div>

    <script src="./script.js"></script>

</body>

</html>