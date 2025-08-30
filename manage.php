<?php
session_start();
require_once("db.php");


if (!isset($_SESSION["user_id"])) {
    header("Location: main.php");
    exit();
} else {
    $screen_name = $_SESSION["screen_name"] ?? 'Guest';
    $user_id = $_SESSION["user_id"];
    $avatar_url = $_SESSION["avatar_url"];
}


try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

$query = "SELECT 
              BlogPosts.post_id, 
              BlogPosts.title, 
              BlogPosts.content, 
              BlogPosts.date_created, 
              BlogPosts.featured_image_url
          FROM BlogPosts
          WHERE BlogPosts.user_id = :user_id
          ORDER BY BlogPosts.date_created DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);


function fetchComments($db, $post_id) {
    $comment_query = "SELECT 
                          Comments.comment_id,
                          Comments.content AS comment_content, 
                          Comments.date_created AS comment_date, 
                          Users.screen_name, 
                          Users.avatar_url, 
                          SUM(CASE WHEN Votes.vote_type = 1 THEN 1 ELSE 0 END) AS upvotes, 
                          SUM(CASE WHEN Votes.vote_type = -1 THEN 1 ELSE 0 END) AS downvotes
                      FROM Comments
                      LEFT JOIN Users ON Comments.user_id = Users.user_id
                      LEFT JOIN Votes ON Comments.comment_id = Votes.comment_id
                      WHERE Comments.post_id = :post_id
                      GROUP BY Comments.comment_id
                      ORDER BY Comments.date_created DESC";

    $comment_stmt = $db->prepare($comment_query);
    $comment_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $comment_stmt->execute();
    return $comment_stmt->fetchAll(PDO::FETCH_ASSOC);



    $comment_stmt = $db->prepare($comment_query);
    $comment_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $comment_stmt->execute();
    return $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Blog Management - Word Crafters</title>
    <link rel="stylesheet" href="css/style.css"/>
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
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="User Avatar" class="avatar"/>
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
                <li><a href="post.php">Create Posts</a></li>
            </ul>
            <a href="logout.php">
                <button class="logout-btn">Logout</button>
            </a>
        </aside>

        <section class="posts-section">
            <h2>Manage Posts</h2>
            <h3>Published Posts:</h3>
            <ul class="post-list">
                <?php foreach ($posts as $post): ?>
                <li class="post-item">
                    <div class="post-header">
                        <span class="post-date">Posted on: <?php echo date('d M, Y, H:i', strtotime($post['date_created'])); ?></span>
                    </div>
                    <div class="post-content-wrapper">
                        <div class="post-content">
                            <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                        </div>
                        <?php if ($post['featured_image_url']): ?>
                        <div class="featured-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image_url']); ?>" alt="Featured Image"/>
                        </div>
                        <?php endif; ?>

                        <!-- Comments Section -->
                        <div class="comments-section">
                            <h4>Comments:</h4>
                            <?php 
                            $comments = fetchComments($db, $post['post_id']);
                            if (!empty($comments)): ?>
                                <ul class="comment-list">
                                    <?php foreach ($comments as $comment): ?>
                                    <li class="comment-item">
                                        <div class="comment-header">
                                            <img src="<?php echo htmlspecialchars($comment['avatar_url'] ?? 'images/default-avatar.png'); ?>" alt="User Avatar" class="avatar"/>
                                            <span><?php echo htmlspecialchars($comment['screen_name']); ?></span>
                                            <time datetime="<?php echo htmlspecialchars($comment['comment_date']); ?>">
                                                <?php echo date('d M, Y, H:i', strtotime($comment['comment_date'])); ?>
                                            </time>
                                        </div>
                                        <div class="comment-content">
                                            <p><?php echo htmlspecialchars($comment['comment_content']); ?></p>
                                        </div>
                                        <div class="comment-votes">
                                            <span class="upvotes">⬆ <?php echo $comment['upvotes'] ?? 0; ?></span>
                                            <span class="downvotes">⬇ <?php echo $comment['downvotes'] ?? 0; ?></span>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No comments on this post.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="view-details">
                        <button onclick="window.location.href='last.php?id=<?php echo $post['post_id']; ?>'">View Details</button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>

    <footer>
        <p>Word Crafters</p>
    </footer>
</body>
</html>
