<?php
session_start();
require_once("db.php");


if (!isset($_SESSION["user_id"])) {
    header("Location: main.php");
    exit();
} else {
    $user_id = $_SESSION["user_id"];
    $screen_name = $_SESSION["screen_name"];
    $avatar_url = $_SESSION["avatar_url"];
}

try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage.php");
    exit();
}
$post_id = $_GET['id'];

$post_query = "SELECT title, content, featured_image_url, date_created FROM BlogPosts WHERE post_id = :post_id";
$post_stmt = $db->prepare($post_query);
$post_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
$post_stmt->execute();
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: manage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    $comment_text = trim($_POST['comment']);
    if (!empty($comment_text)) {
        $insert_comment_query = "INSERT INTO Comments (post_id, user_id, content, date_created) 
                                 VALUES (:post_id, :user_id, :comment_text, NOW())";
        $insert_comment_stmt = $db->prepare($insert_comment_query);
        $insert_comment_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $insert_comment_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insert_comment_stmt->bindParam(':comment_text', $comment_text, PDO::PARAM_STR);
        $insert_comment_stmt->execute();
        
        // Get the last inserted comment ID
        $comment_id = $db->lastInsertId();
    }
    // Redirect to the page and include the comment_id to highlight
    header("Location: last.php?id=" . $post_id . "&highlight_comment=" . $comment_id);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote'])) {
    $comment_id = $_POST['comment_id'] ?? null;
    $vote_type = $_POST['vote'] ?? null;

    if ($vote_type !== 'upvote' && $vote_type !== 'downvote') {
        die("Invalid vote type: " . htmlspecialchars($vote_type));
    }

    // First, delete any existing vote for this user on this comment
    $delete_vote_query = "DELETE FROM Votes WHERE user_id = :user_id AND comment_id = :comment_id";
    $delete_vote_stmt = $db->prepare($delete_vote_query);
    $delete_vote_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $delete_vote_stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $delete_vote_stmt->execute();

    // Insert the new vote
    $insert_vote_query = "INSERT INTO Votes (user_id, comment_id, vote_type, date_created) 
                          VALUES (:user_id, :comment_id, :vote_type, NOW())";
    $insert_vote_stmt = $db->prepare($insert_vote_query);
    $insert_vote_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $insert_vote_stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $insert_vote_stmt->bindParam(':vote_type', $vote_type, PDO::PARAM_STR);
    $insert_vote_stmt->execute();

    // Fetch the new vote score for the comment
    $vote_score_query = "SELECT 
                             COALESCE(SUM(CASE WHEN v.vote_type = 'upvote' THEN 1 WHEN v.vote_type = 'downvote' THEN -1 ELSE 0 END), 0) AS vote_score
                         FROM Votes v
                         WHERE v.comment_id = :comment_id";
    $vote_score_stmt = $db->prepare($vote_score_query);
    $vote_score_stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $vote_score_stmt->execute();
    $vote_score = $vote_score_stmt->fetch(PDO::FETCH_ASSOC)['vote_score'];

    // Return the updated vote score in JSON format
    echo json_encode(['vote_score' => $vote_score]);
    exit();
}


$comments_query = "SELECT c.comment_id, c.user_id, c.content, c.date_created, 
                          COALESCE(SUM(CASE WHEN v.vote_type = 'upvote' THEN 1 WHEN v.vote_type = 'downvote' THEN -1 ELSE 0 END), 0) AS vote_score, 
                          u.screen_name, u.avatar_url 
                   FROM Comments c
                   LEFT JOIN Votes v ON c.comment_id = v.comment_id
                   LEFT JOIN Users u ON c.user_id = u.user_id
                   WHERE c.post_id = :post_id
                   GROUP BY c.comment_id
                   ORDER BY vote_score DESC, c.date_created DESC";
$comments_stmt = $db->prepare($comments_query);
$comments_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
$comments_stmt->execute();
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Post Detail - Word Crafters</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="jss/last.js" defer></script>
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
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="User Avatar" class="avatar">
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
                <li><a href="post.php">Create Posts</a></li>
            </ul>
            <a href="logout.php">
                <button class="logout-btn">Logout</button>
            </a>
        </aside>
        <section class="post-detail-section">
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <div class="post-content-wrapper">
                <div class="post-content">
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                </div>
                <?php if ($post['featured_image_url']): ?>
                    <div class="featured-image">
                        <img src="<?php echo htmlspecialchars($post['featured_image_url']); ?>" alt="Featured Image">
                    </div>
                <?php endif; ?>
            </div>
            <div class="comments-section">
    <h3>Comments</h3>
    <?php if ($comments): ?>
        <ul class="comments-list">
        </ul>

    <?php else: ?>
        <p>No comments yet. Be the first to comment!</p>
    <?php endif; ?>
</div>

            <div class="new-comment-form">
    <h4>Leave a Comment</h4>
    <?php if (!isset($_SESSION["user_id"])): ?>
        <p>Please <a href="main.php">login</a> to leave a comment.</p>
    <?php else: ?>
        <form action="" method="POST">
            <textarea id="comment-textarea" name="comment" rows="4" placeholder="Write your comment here..." required></textarea>
            <button type="submit" class="publish-btn">Submit Comment</button>
        </form>
    <?php endif; ?>
</div>

        </section>
    </main>
    <footer>
        <p>Word Crafters</p>
    </footer>
</body>
</html>