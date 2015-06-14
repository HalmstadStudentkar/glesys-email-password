<?php
/**
    GleSYS Email password updater.

    Copyright © 2015 Halmstad studentkår <kaos@karen.hh.se>
    Copyright © 2015 Martin Bagge <brother@bsnet.se>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Takes the new password in two copies provided by user. Checks for
 * apparent errors and makes sure it follows the standard defined.
 *
 * @param string $np1 The password to be changed into.
 * @param string $np2 The repeating of the password. To avoid spelling
 *                    errors mostly.
 *
 * @return int
 */
function validatepasswords ($np1,$np2)
{
    if ($np1 === $np2) {
        if (strlen($np1) > 7) {
            return 0;
        } else {
            return -2;
        }
    } else {
        return -4;
    }
}

/**
 * Does a verification login to IMAP server to make sure the user is
 * the owner of the account.
 *
 * @param string $u Username for accessing IMAP server.
 * @param string $p Password (current) to access IMAP server.
 *
 * @return bool
 */
function logincheck ($u,$p)
{
    try {
        imap_open("{mail.glesys.se:993/imap/ssl}", "$u", "$p", OP_HALFOPEN, 1);
        return true;
    } catch (Exception $e) {
        unset ($_POST);
        return false;
    }

}

/**
 * If the login checking is accepted the password will be changed at
 * provider.
 *
 * @param string $u  Username to be used when accessing IMAP.
 * @param string $np Proposed new password.
 *
 * @return bool
 */
function changepassword ($u,$np)
{
    global $glesysmail;
    return $glesysmail->email_editaccount($u, array("password" => $np));
}


/**
 * When no username is sent using POST the form to start the password
 * update process is shown. Will check the provided passwords for
 * errors and do a login to the mailbox. If all succeeds the password
 * is changed and the user is brought back here.
 *
 * @param string $msg Maybe maybe? errors and so on....
 *
 * @return void
 */
function paintform ($msg=false)
{
    echo "
<html>
<head>
<title>hejsan</title>
</head>
<body>";

        if (isset($msg[0])) {
            echo "<div class=\"".$msg[0]."\">".$msg[1]."</div>";
        }

        echo "
<form name=\"loginchange\" method=\"post\">
username: <input type=\"text\" name=\"username\"/><br/>
password: <input type=\"password\" name=\"mailpassword\"/><br/>
<hr/>
new password1: <input type=\"password\" name=\"newpass1\"/><br/>
new password2: <input type=\"password\" name=\"newpass2\"/><br/>
<input type=\"submit\" value=\"Change password\" />
</form>
&copy ".date("Y")." Halmstad studentk&aringr (KAOS)
</body>
</html>
";
}



// Start of actual page logic.

if (!file_exists("../local.config.php")) {
    exit("<h1>local config not set. ABORTED.</h1>");
} else {
    require "../local.config.php";
    require $config["api_path"]."/PHP/api_classes/glesys_email.php";
}

// Make sure GleSYS API is actually sort of working before going further.
try {
    $glesysmail = new glesys_email($config["api_user"], $config["api_key"]);
} catch (Exception $e) {
    echo "Connection error: ".$e->getMessage();
    exit("ABORTED");
}

if (empty($_POST["username"])) {
    paintform();
} else {
    $pwerr=0;
    $pwerr=validatepasswords($_POST["newpass1"], $_POST["newpass2"]);
    $msg = array();
    if ($pwerr === 0) {
        if (logincheck($_POST["username"], $_POST["mailpassword"])) {
            if (changepassword(
                $_POST["username"],
                $_POST["newpass1"]
            )
            ) {
                $msg = array("OK", "Password upated.");
            } else {
                $msg = array("ERROR", "Password not changed.");
            }
        } else {
            $msg = array(
                "ERROR",
                "Inccorect current username or password provided."
            );
        }
    } elseif ($pwerr === -2) {
        $msg = array(
            "WARNING",
            "Password not long enough (must be at least 8 characters)."
        );
    } elseif ($pwerr === -4) {
        $msg = array(
            "WARNING",
            "The new password can not be verified. Provide the same string twice."
        );
    } else {
        $msg = array(
            "WARNING",
            "Unknown problem. Please tell us about this. Return code was: $pwerr"
        );
    }
    paintform($msg);
}
?>