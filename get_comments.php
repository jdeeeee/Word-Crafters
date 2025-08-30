<?php
session_start();
require_once("db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: main.php");
    exit();
} else {
    $user_id = $_SESSION["user_id"];
}

$post_id = $_GET['post_id'] ?? null;

if (!isset($post_id) || !is_numeric($post_id)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Invalid post ID']);
    exit();
}

try {
    $db = new PDO($attr, $db_user, $db_pwd, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Fetch comments and their votes
if (isset($_GET['last_update_time'])) {
    $last_update_time = $_GET['last_update_time'];

    $comments_query = "SELECT c.comment_id, c.user_id, c.content, c.date_created, 
                              COALESCE(SUM(CASE WHEN v.vote_type = 'upvote' THEN 1 WHEN v.vote_type = 'downvote' THEN -1 ELSE 0 END), 0) AS vote_score, 
                              u.screen_name, u.avatar_url 
                       FROM Comments c
                       LEFT JOIN Votes v ON c.comment_id = v.comment_id
                       LEFT JOIN Users u ON c.user_id = u.user_id
                       WHERE c.post_id = :post_id AND c.date_created > :last_update_time
                       GROUP BY c.comment_id
                       ORDER BY vote_score DESC, c.date_created DESC";

    $comments_stmt = $db->prepare($comments_query);
    $comments_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $comments_stmt->bindParam(':last_update_time', $last_update_time, PDO::PARAM_STR);
    $comments_stmt->execute();
} else {
    // Fallback to full fetch for the first load
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
}

$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($comments);

?>
