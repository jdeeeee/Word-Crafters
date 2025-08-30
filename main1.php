<?php
session_start();
require_once("db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: main.php");
    exit();
}

$screen_name = $_SESSION["screen_name"] ?? "User";
$email = $_SESSION["email"] ?? "";
$user_id = $_SESSION["user_id"];
$avatar_url = $_SESSION["avatar_url"] ?? "images/default-avatar.jpg";

try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "
        SELECT 
            p.post_id, p.title, p.content, p.featured_image_url, p.date_created, 
            u.screen_name, u.avatar_url, 
            COUNT(c.comment_id) AS comment_count
        FROM BlogPosts p
        LEFT JOIN Users u ON p.user_id = u.user_id
        LEFT JOIN Comments c ON p.post_id = c.post_id
        GROUP BY p.post_id
        ORDER BY p.date_created DESC
        LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching posts: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Crafters - Main Page (Logged In)</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="jss/main1.js" defer></script>
</head>
<body class="logged-in">
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
                    <li>
                        <div class="user-info">
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>"alt="User Avatar" class="avatar">
                            <span class="screen-name"><?php echo htmlspecialchars($screen_name); ?></span>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <aside class="sidebar">
            <ul>
                <li><a href="manage.php">Manage Posts</a></li>
                <li><a href="post.php">Create Posts</a></li>
            </ul>
            <a href="logout.php">
                <button class="logout-btn">Logout</button>
            </a>
        </aside>

        <section class="recent-posts">
            <h2>Recent Posts</h2>
            <ul>
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <li class="post-item">
                            <div class="post-avatar">
                                <img src="<?php echo htmlspecialchars($post['avatar_url'] ?? 'images/default-avatar.jpg'); ?>" alt="User Avatar" class="avatar">
                            </div>
                            <div class="post-info">
                                <span class="screen-name"><?php echo htmlspecialchars($post['screen_name'] ?? 'Unknown User'); ?></span>
                                <time datetime="<?php echo htmlspecialchars($post['date_created']); ?>">
                                    <?php echo date("jS M, Y, g:i A", strtotime($post['date_created'])); ?>
                                </time>
                                <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                                <span class="comment-count">
                                    <?php echo $post['comment_count'] ?? 0; ?> comments
                                </span>
                            </div>
                            <div class="blog-image">
                                <img src="<?php echo htmlspecialchars($post['featured_image_url'] ?? 'images/blog.png'); ?>" alt="Blog Image">
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
