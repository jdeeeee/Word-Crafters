<?php
session_start();
require_once("db.php"); 

if (!isset($_SESSION["user_id"])) {
    header("Location: main.php");
    exit();
} else {
    $screen_name = isset($_SESSION["screen_name"]) ? $_SESSION["screen_name"] : '';
    $user_id = $_SESSION["user_id"];
    $avatar_url = $_SESSION["avatar_url"];
}

try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $errors = [];

    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($content)) {
        $errors[] = "Content is required.";
    }

    $imagePath = null;
    if ($_FILES['featured_image']['error'] == 0) {
        $imageFileType = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        if ($_FILES['featured_image']['size'] > 5000000) { 
            $errors[] = "The image file is too large.";
        }

        $imagePath = 'uploads/blog_posts/' . $user_id . '.temp.' . $imageFileType;
        if (empty($errors) && !move_uploaded_file($_FILES['featured_image']['tmp_name'], $imagePath)) {
            $errors[] = "Failed to upload the image.";
        }
    }

    if (empty($errors)) {
        $query = "INSERT INTO BlogPosts (user_id, title, content, featured_image_url, date_created) 
                  VALUES (:user_id, :title, :content, :featured_image, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':content' => $content,
            ':featured_image' => $imagePath ? $imagePath : null
        ]);

        $post_id = $db->lastInsertId();

        if ($imagePath) {
            $finalImagePath = 'uploads/blog_posts/' . $user_id . '.' . $post_id . '.' . $imageFileType;
            rename($imagePath, $finalImagePath); 
        }

        if ($imagePath) {
            $updateQuery = "UPDATE BlogPosts SET featured_image_url = :featured_image WHERE post_id = :post_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                ':featured_image' => $finalImagePath,
                ':post_id' => $post_id
            ]);
        }

        header("Location: manage.php"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Create New Post - Word Crafters</title>
    <link rel="stylesheet" href="css/style.css"/>
    <script src="jss/post.js" defer></script> 
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Word Crafters</h1>
            <nav>
                <ul>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Search</a></li>
                    <li>
                        <div class="user-info">
                            <img src="<?php echo $avatar_url; ?>" alt="User Avatar" class="avatar"/>
                            <span><?php echo htmlspecialchars($screen_name); ?></span>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <aside class="sidebar">
            <ul>
                <li><a href="main1.php">Home</a></li>
                <li><a href="manage.php">Manage Posts</a></li>
            </ul>
            <a href="logout.php">
                <button class="logout-btn">Logout</button>
            </a>
        </aside>
        <section class="create-post-section">
            <h2>Create New Post</h2>
            <form action="" method="POST" enctype="multipart/form-data" class="create-post-form">
                <label for="post-title">Title:</label>
                <input type="text" id="post-title" name="title" placeholder="Enter your post title" maxlength="100"/>
                <div id="title-error-message" style="color: red; display: none;"></div> 

                <label for="post-content">Content:</label>
                <textarea id="post-content" name="content" rows="10" placeholder="Type here..."></textarea>
                <div id="content-error-message" style="color: red; display: none;"></div> 
                
                <div id="char-count">Characters: 0/2000</div> 

                <label for="featured-image">Featured Image:</label>
                <input type="file" id="featured-image" name="featured_image" accept="image/*"/>
                <div id="image-error-message" style="color: red; display: none;"></div> 

                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='main1.php'" class="cancel-btn">Cancel</button>
                    <button type="submit" class="publish-btn">Publish</button>
                </div>
            </form>
        </section>
    </main>
    
    <footer>
        <p>Word Crafters</p>
    </footer>
</body>
</html>