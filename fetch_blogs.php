<?php
session_start();
require_once("db.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "User not logged in."]);
    exit();
}

$last_post_id = $_GET['last_post_id'] ?? 0;

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
        WHERE p.post_id > :last_post_id
        GROUP BY p.post_id
        ORDER BY p.date_created DESC";
        
    $stmt = $db->prepare($query);
    $stmt->bindParam(':last_post_id', $last_post_id, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($posts);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
