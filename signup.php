<?php
require_once("db.php");

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); 
    return $data;
}

$errors = array();
$email = "";
$screen_name = "";
$password = "";
$dob = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = test_input($_POST["email"]);
    $screen_name = test_input($_POST["screen_name"]);
    $password = test_input($_POST["password"]);
    $dob = test_input($_POST["dob"]);;

    $emailRegex = "/^[\w.%+-]+@[\w.-]+\.[a-zA-Z]{2,}$/";
    $unameRegex = "/^[a-zA-Z0-9_]+$/";
    $passwordRegex = "/^.{8}$/";
    $dobRegex = "/^\d{4}[-]\d{2}[-]\d{2}$/";
 
    if (!preg_match($emailRegex, $email)) {
        $errors["email"] = "Invalid Email Address";
    }
    if (!preg_match($unameRegex, $screen_name)) {
        $errors["screen_name"] = "Invalid Username";
    }
    if (!preg_match($passwordRegex, $password)) {
        $errors["password"] = "Invalid Password";
    }
    if (!preg_match($dobRegex, $dob)) {
        $errors["dob"] = "Invalid DOB";
    }

    $target_file = "";
    try {
        $db = new PDO($attr, $db_user, $db_pwd);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        die ("PDO Error >> " . $e->getMessage() . "\n<br />");
    }

    $query = "SELECT email from Users where email = '$email'";

    $result = $db->query($query);

    $match = $result->fetch();

    if ($match) {
        $errors["Email Taken"] = "A user with that email address already exists.";
    }

    $query = "SELECT screen_name from Users where screen_name = '$screen_name'";

    $result = $db->query($query);

    $match = $result->fetch();

    if ($match) {
        $errors["Username Taken"] = "A user with that username already exists.";
    }

    if (empty($errors)) {

        $query = "INSERT INTO Users (email,screen_name,password_hash,date_of_birth,avatar_url) VALUES ('$email', '$screen_name','$password','$dob','avatar_stub')";
        $result = $db->exec($query);

if (!$result) {
            $errors["Database Error:"] = "Failed to insert user";
        } else {
            $target_dir = "uploads/avatars";
            $uploadOk = TRUE;

            $imageFileType = strtolower(pathinfo($_FILES["avatar"]["name"],PATHINFO_EXTENSION));

            $uid = $db->lastInsertId();

            $target_file = "uploads/avatars/" . $uid . "." . $imageFileType;

            if (file_exists($target_file)) {
                $errors["avatar"] = "Sorry, file already exists. ";
                $uploadOk = FALSE;
            }

            if ($_FILES["avatar"]["size"] > 1000000) {
                $errors["avatar"] = "File is too large. Maximum 1MB. ";
                $uploadOk = FALSE;
            }

            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                $errors["avatar"] = "Bad image type. Only JPG, JPEG, PNG & GIF files are allowed. ";
                $uploadOk = FALSE;
            }

            if ($uploadOk) {
                $fileStatus = move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file);

                if (!$fileStatus) {
                    $errors["Server Error"] = "Could not upload the avatar's image.";
                    $uploadOK = FALSE;
                }
            }

            if (!$uploadOk)
            {
                $query = "DELETE FROM Users WHERE user_id = '$uid'";
                $result = $db->exec($query);
                if (!$result) {
                    $errors["Database Error"] = "could not delete user when avatar upload failed";
                }
                $db = null;
            } else {
                $query =  "UPDATE Users SET avatar_url = '$target_file' WHERE user_id = $uid";
                $result = $db->exec($query);

                if (!$result) {
                    $errors["Database Error:"] = "could not update avatar_url";
                } else {
                    $db = null;
                    header("Location: main.php");
                    exit;
                }
            } 
        } 
    } 

if (!empty($errors)) {
        foreach($errors as $type => $message) {
            print("$type: $message \n<br />");
        }
    }

} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Word Crafters</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="branding">
            <h1>Word Crafters</h1>
            <p>Where words are shaped by the collective voice</p>
        </div>
    </header>

    <main>
        <section class="signup-form">
            <h2>Create Your Account</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="signupForm" action="signup.php" method="POST" enctype="multipart/form-data">
                <label for="avatar">Avatar:</label>
                <input type="file" id="avatar-upload" name="avatar" accept="image/*">
                    
                <label for="screen_name">Username:</label>
                <input type="text" id="screen_name" name="screen_name" required>

                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>

                <label for="dob">Date of Birth (YYYY-MM-DD):</label>
                <input type="date" id="dob" name="dob" required>

                <label for="password">Enter Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the Terms and Conditions</label>
                </div>

                <button type="submit">Create Account</button>
                <p class="login-link">Already have an account? <a href="main.php">Login</a></p>
            </form>
        </section>
    </main>

    <footer>
        <p>Word Crafters</p>
    </footer>
</body>
</html>
