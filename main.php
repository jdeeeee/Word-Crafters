<?php
session_start();
require_once("db.php");

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); 
    return $data;
}

try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

try {
    $query = "
        SELECT 
            p.post_id, 
            p.title, 
            p.content, 
            p.featured_image_url, 
            p.date_created, 
            u.screen_name, 
            COALESCE(u.avatar_url, 'images/default-avatar.jpg') AS avatar_url, 
            COUNT(c.comment_id) AS comment_count 
        FROM BlogPosts p
        LEFT JOIN Users u ON p.user_id = u.user_id
        LEFT JOIN Comments c ON p.post_id = c.post_id
        GROUP BY p.post_id
        ORDER BY p.date_created DESC
        LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching posts: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];
    $screen_name = test_input($_POST['screen_name'] ?? '');
    $password = test_input($_POST['password'] ?? '');

    if (empty($screen_name) || empty($password)) {
        $errors[] = "Username and password are required.";
    }

    if (empty($errors)) {
        try {
            $query = "
                SELECT user_id, screen_name, avatar_url 
                FROM Users 
                WHERE screen_name = :screen_name 
                AND password_hash = :password";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':screen_name', $screen_name, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['screen_name'] = $user['screen_name'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                header("Location: main1.php"); 
                exit();
            } else {
                $errors[] = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Crafters - Main Page</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="jss/main.js" defer></script>
</head>
<body class="<?php echo isset($_SESSION['user_id']) ? 'logged-in' : 'logged-out'; ?>">
    <header>
        <div class="header-container">
        <div class="branding">
            <h1>Word Crafters</h1>
            <p>Where words are shaped by the collective voice.</p>
        </div>
            <nav>
                <ul>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Search</a></li>
                    <li><a href="signup.php" class="signup-btn">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Login Form -->
        <aside class="login-form">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <form action="" method="POST">
                    <h2>Login</h2>
                    <?php if (!empty($errors)): ?>
                        <ul style="color: red;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <label for="screen_name">Username:</label>
                    <input type="text" id="screen_name" name="screen_name" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <a href="#">Forgot Password?</a>
                    <button type="submit">Login</button>
                </form>
            <?php else: ?>
                <div class="user-info">
                    <img src="<?php echo htmlspecialchars($_SESSION['avatar_url']); ?>" alt="Avatar" class="avatar">
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['screen_name']); ?>!</p>
                    <a href="logout.php">Logout</a>
                </div>
            <?php endif; ?>
        </aside>

        <section class="recent-posts">
            <h2>Recent Posts</h2>
            <?php if (isset($posts) && count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <li class="post-item">
                            <div class="post-avatar">
                            <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" alt="User Avatar" class="avatar"/>
                            </div>
                            <div class="post-info">
                                <span class="screen-name"><?php echo htmlspecialchars($post['screen_name'] ?? 'Unknown User'); ?></span>
                                <time datetime="<?php echo htmlspecialchars($post['date_created']); ?>"><?php echo date("jS M, Y, g:i A", strtotime($post['date_created'])); ?></time>
                                <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                                <span class="comment-count">
                                    <?php echo isset($post['comment_count']) ? $post['comment_count'] : 0; ?> comments
                                </span>
                            </div>
                            <div class="blog-image">
                                <img src="<?php echo htmlspecialchars($post['featured_image_url'] ?? 'images/blog.png'); ?>" alt="Blog Image"/>
                            </div>
                            <div class="view-details">
                            <button onclick="window.location.href='last.php?id=<?php echo $post['post_id']; ?>'">View Details</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recent posts available.</p>
                <?php endif; ?>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Word Crafters</p>
    </footer>
</body>
</html>
