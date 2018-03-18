<?php
/**
 * Simple mysql blog post, no error checking.
 */

$conn = new mysqli('localhost', 'username', 'password');

$sql = <<< SQL
    SET sql_notes = 0;
    CREATE DATABASE IF NOT EXISTS `blog` CHARACTER SET utf8 COLLATE utf8_general_ci;
    CREATE TABLE IF NOT EXISTS `post`(id MEDIUMINT NOT NULL AUTO_INCREMENT, post BLOB NOT NULL, createdt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id));
SQL;

if(!empty($_POST) && array_key_exists('blogPost', $_POST)){
    $post = $conn->real_escape_string($_POST['blogPost']);
    $sql .= <<< SQL
    INSERT INTO `blog.post`(post) VALUES ({$post}});
SQL;
}

$sql .= <<< SQL
SELECT * FROM `blog.post` ORDER BY createdt ASC LIMIT 10;
SQL;

$res = $conn->query($sql);
$posts = [];
while($row = $res->mysqli_fetch_assoc()){
    $posts = $row;
}
?>

<!doctype html>
<html>
    <head>
        <title>Test Blog</title>
        <style>
            textarea{
                width: 60%;
            }
            button{
                display:block;
            }
        </style>
    </head>
    <body>
        <h1>Simple blog</h1>
        <p>Enter in some text you'd like to keep.</p>
        <form action="/" method="post">
            <textarea name="blogPost"></textarea>
            <button type="submit">Submit</button>
        </form>

        <?php if(count($posts)): ?>
            <h1>Posts</h1>
            <table>
                <thead>
                    <tr>
                        <td>Post</td>
                        <td>Date</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($posts as $post): ?>
                        <tr>
                            <td><?php echo $post['post']; ?></td>
                            <td><?php echo $post['createdt']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table> 
        <?php endif; ?>
        
    </body>
</html>
