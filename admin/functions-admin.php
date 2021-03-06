<?php

$root = realpath( __DIR__ . '/..' );
include ( $root . "/config.php" );

/**
 * mixed Shows the Header elements and includes
 */
function header_requires(){
    ?>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="<?php echo ADMIN_URL; ?>css/admin-style.css"/>
    <link rel="icon" href="<?php echo ADMIN_URL; ?>images/favicon.png" type="image/x-icon">
    <?php
}

/**
 * Function to show the Footer elements and includes
 *
 * @param  object $mysql_conn MySql Connection
 */
function footer_requires($mysql_conn){
    ?>
    </body>
    </html>
    <?php
    // Closes the MySql Connection
    mysqli_close($mysql_conn);
}

/**
 * Function to start the session
 */
function sec_session_start(){
    $session_name = 'sec_session_id'; // Set a custom session name
    $secure       = false;            // Set to true if using https.
    $httponly     = true;             // This stops javascript being able to access the session id.

    ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies.
    $cookieParams = session_get_cookie_params(); // Gets current cookies params.
    session_set_cookie_params(COOKIE_LIFETIME, $cookieParams[ "path" ], $cookieParams[ "domain" ], $secure, $httponly);
    session_name($session_name); // Sets the session name to the one set above.
    session_start(); // Start the php session
    session_regenerate_id(); // regenerated the session, delete the old one.
}

/**
 * Function to check if the user has more than MAX_LOGIN_ATTEMPTS login attempts
 * If so the user is not allowed to login
 *
 * @param $user_id
 * @param  object $mysql_conn MySql Connection
 * @return bool
 */
function check_brute($user_id, $mysql_conn) {
    // Get timestamp of current time
    $now = time();
    $valid_attempts = $now - BLOCK_USER_DURATION;

    if ($stmt = $mysql_conn->prepare("SELECT time FROM login_attempts WHERE user_id = ? AND time > ?")) {
        $stmt->bind_param('ii', $user_id, $valid_attempts);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > MAX_LOGIN_ATTEMPTS) {
            //means that it has previous error login attempts in the past X minutes
            return true;
        }
        else {
            //clear to go
            return false;
        }
    }
}

/**
 * Function for logging in
 *
 * @param  object $mysql_conn MySql Connection
 * @param  string $email Email of the user to be verified
 * @param  string $password Password of the user to be verified
 * @return bool true if login is done successfully, false otherwise
 */
function login($mysql_conn, $email, $password){
    // Using prepared Statements means that SQL injection is not possible.
    if ($stmt = $mysql_conn->prepare("SELECT id, username, password FROM users WHERE email = ? LIMIT 1")) {
        $stmt->bind_param('s', $email); // Bind "$email" to parameter.
        $stmt->execute(); // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($user_id, $username, $db_password); // get variables from result.
        $stmt->fetch();


        if ($stmt->num_rows == 1) { // If the user exists
            // We check if the account is locked from too many login attempts
            if (check_brute($user_id, $mysql_conn) == true) {
                // The account was suspended due to many login attempts
                return false;
            }
            else {
                if (password_verify($password, $db_password)) {
                    // Password is correct!
                    $_SESSION[ 'user_id' ]      = $user_id;
                    $_SESSION[ 'email' ]        = $username;
                    $_SESSION[ 'login_string' ] = password_hash($password, PASSWORD_DEFAULT);
                    $_SESSION[ 'logged_in' ]    = true;
                    // Login successfully.
                    return true;
                }
                else {
                    // Password is not correct. This record attempt is stored in the database
                    if($insert_stmt_insert = $mysql_conn->prepare("INSERT INTO login_attempts (user_id ) VALUES (?)")){
                        $insert_stmt_insert->bind_param('i', $user_id);
                        $insert_stmt_insert->execute();
                    }
                    return false;
                }
            }
        }
        else {
            // User doesn't exists
            return false;
        }
    }
}

/**
 * Function to return the logged user id. Gets the id from the session
 *
 * @return int user_id
 */
function get_user_id(){
    return filter_input(INPUT_SESSION, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Simply checks if the user is logged in or not
 * @param  object $mysql_conn MySql Connection
 * @return boolean true if the user is logged, false if not
 */
function login_check($mysql_conn) {

    if(isset($_SESSION[ 'user_id' ]) && isset($_SESSION[ 'logged_in' ]) && $_SESSION[ 'logged_in' ] == true ){

        return true;
    }
    return false;
}

/**
 * Function to edit a specific Category
 *
 * @param  object $mysql_conn MySql Connection
 * @param  string $new_category_name the new name of the category to be stored
 * @param  [type] $id ID of the current category that is going to change
 * @return mixed redirects to categories.php after editing the category
 */
function edit_category($mysql_conn, $new_category_name, $id) {

    if ($new_category_name != '') {
        $query_update = " UPDATE  categories  SET category_name='$new_category_name' WHERE id=$id ";
        $result_del = mysqli_query($mysql_conn, $query_update);
        header('Location: ./categories.php');
    }
}

/**
 * Function to create a category
 *
 * @param  object $mysql_conn MySql Connection
 * @param  string $category_name Name of the Category
 * @return Returns to categories.php
 */
function add_category($mysql_conn, $category_name) {

    if ($category_name != '') {
        $query_insert   = "INSERT INTO categories (category_name) VALUES ('$category_name')";

        if (!mysqli_query($mysql_conn,$query_insert)){
            die('Problem: ' . mysqli_error($mysql_conn));
        }

        header("Location: categories.php");
    }
}

/**
 * Function to get the number of posts of a specific category
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $category_id ID of the current Category
 * @return int Number of posts by the Category
 */
function get_number_of_posts_category($mysql_conn, $category_id){
    $query_select_category = "SELECT COUNT(*) AS id FROM posts WHERE category_id = $category_id";
    $result_category       = mysqli_query($mysql_conn, $query_select_category);
    $row_category          = mysqli_fetch_array($result_category);

    return ($row_category['id']);
}

/**
 * Function to display all categories and their number of posts
 *
 * @param  object $mysql_conn MySql Connection
 * @return mixed Shows  all the Categories table
 */
function view_all_categories($mysql_conn) {
    $query = "SELECT id, category_name FROM categories ORDER BY category_name ";
    $result = mysqli_query($mysql_conn, $query);
    ?>
    <table id="table_style">
        <thead>
        <tr>
            <th><b> Category </b></th>
            <th align="center"><b>Number of Posts </b></th>
            <th align="center"><b> Edit </b></th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($row = mysqli_fetch_array($result)) {
            $category_id = $row['id'];
            ?>
            <tr>
                <td><strong><?php echo $row[ 'category_name' ]; ?></strong></td>
                <td align="center" ><?php echo get_number_of_posts_category($mysql_conn, $category_id); ?></td>

                <td align="center"><a href="category-edit.php?id=<?php echo $row[ 'id' ]; ?>&edit=1">Edit</a></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <?php
}

/**
 * Function to show the Head menu of Categories Page
 *
 * @return mixed Shows  the menu
 */
function show_category_menu(){
    ?>
    <div class="head_menu_content">
        <a href="<?php echo ADMIN_URL; ?>categories.php"> Categories </a>
        &nbsp;&nbsp;&nbsp;&nbsp;&#124;
        <a href="<?php echo ADMIN_URL; ?>category-new.php">Add a category </a>
    </div>
    <?php
}

/**
 * Function to show the admin on the right sidebar
 */
function show_admin_menu(){
    ?>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>index.php"> admin </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>post-new.php"> New Post </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>categories.php"> Categories </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>category-new.php"> New Category </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>users.php"> Users </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>posts-database.php"> Post Database </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>statistics.php"> Statistics </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>examples.php"> Examples </a> </div>
    <div class="menu_items"> <a href="<?php echo ADMIN_URL; ?>logout.php">Log Out</a> </div>

    <br>

    <div class="menu_items"><a  href="<?php echo MAIN_URL; ?>" target="_blank"> Website - Public </a> </div>
    <?php
}

/**
 * Function to show the bad login attempts of users
 *
 * @param  object $mysql_conn MySql Connection
 * @return mixed Shows  the table
 */
function show_login_attempts($mysql_conn) {

    $query_select_mem = "SELECT user_id, time FROM login_attempts ORDER BY time DESC ";
    $result_mem       = mysqli_query($mysql_conn, $query_select_mem);
    ?>
    <table id="table_style">
        <thead>
        <tr>
            <th scope="col" align="center"><b> User  </b></th>
            <th scope="col" align="center"><b> Time </b></th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($data = mysqli_fetch_array($result_mem)) {
            ?>
            <tr>
                <td align="center"><?php echo get_user_name($mysql_conn, $data[ 'user_id' ]); ?></td>
                <td align="center"><?php echo $data[ 'time' ]; ?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <?php
}

/**
 * Function to create a User
 *
 * @param object $mysql_conn MySql Connection
 * @param string $username Username to be stored in the db
 * @param string $password Password of the User
 * @param string $password_repeat Password confirmation
 * @param string $email Email of the User
 */
function add_user($mysql_conn, $username, $password, $password_repeat, $email) {
    if ($password == $password_repeat) {
        $user_password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($insert_stmt = $mysql_conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)") ) {
            if(!empty($insert_stmt)){
                $insert_stmt->bind_param('sss', $username, $email, $user_password_hash);
            }
            $insert_stmt->execute();
        }
    }
}

/**
 * Function to delete a User from the database
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $id ID of the current User
 */
function delete_user($mysql_conn, $id) {
    $query_del = "DELETE FROM users WHERE id=$id";
    $result_del = mysqli_query($mysql_conn, $query_del)
    or
    die('Problem: '.mysqli_error($mysql_conn));
}

/**
 * Function to show the "User Menu"
 *
 * @return mixed Shows  the details of the User
 */
function show_user_menu(){
    ?>
    <div class="head_menu_content">
        <a  title="See all users list" href="<?php echo ADMIN_URL; ?>users.php">Users</a>
        &nbsp;&nbsp;&#124;
        <a href="<?php echo ADMIN_URL; ?>users-login-attempts.php" title="See all error logins from users">Users Login Attempts</a>
        &nbsp;&nbsp;&#124;
        <a href="<?php echo ADMIN_URL; ?>users-new.php" title="Add a new user">Add a user</a>
    </div>
    <?php
}

/**
 * Function to show the "View User Menu"
 *
 * @return mixed Shows  the details of the User
 */
function view_user_menu(){
    ?>
    <div class="head_menu_content">
        <a  title="See the list of all categories" href="<?php echo ADMIN_URL; ?>categories.php">Edit Profile </a>
        &nbsp;&nbsp;&nbsp;&nbsp;&#124;
        <a href="<?php echo ADMIN_URL; ?>category-new.php" title="Add a new category">Add a category </a>
    </div>
    <?php
}

/**
 * Function to show details about a single User
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $user_id ID of the current User
 * @return mixed Shows  the details of the User
 */
function view_single_user($mysql_conn, $user_id) {
    $query_select_user = "SELECT id, username, email FROM users WHERE id = $user_id";
    $result_user       = mysqli_query($mysql_conn, $query_select_user);
    $row_user          = mysqli_fetch_array($result_user);
    ?>
    <p>
        <strong>Name:</strong> <?php echo $row_user[ 'username' ]; ?>
    </p>
    <p>
        <strong>Email:</strong> <?php echo $row_user[ 'email' ]; ?>
    </p>
    <p>
        <strong>Number of Posts:</strong> <?php echo get_users_number_of_posts($mysql_conn, $user_id); ?>
    </p>
    <?php
}

/**
 * Function to get the number of Posts that has created a User
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $user_id ID of the current User
 * @return int $number_of_posts Number of Posts of the User
 */
function get_users_number_of_posts($mysql_conn, $user_id){
    $query_select_user = "SELECT user_id FROM posts WHERE user_id = $user_id";
    $result_user       = mysqli_query($mysql_conn, $query_select_user);
    $row_user          = mysqli_fetch_array($result_user);

    $number_of_posts = count($row_user);
    return $number_of_posts;
}

/**
 * Function to show the table of all Users
 *
 * @param  object $mysql_conn MySql Connection
 * @return mixed mixed Shows  the table of the Users
 */
function show_all_users($mysql_conn) {

    $query_select_mem = "SELECT id, username, email FROM users";
    $result_mem       = mysqli_query($mysql_conn, $query_select_mem);

    ?>
    <table id="table_style">
        <thead>
        <tr>
            <th scope="col"><b> Username </b></th>
            <th scope="col"><b> Email </b></th>
            <th scope="col" align="right"><b>Posts</b></th>
            <th scope="col" align="center"><b> View </b></th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($data = mysqli_fetch_array($result_mem)) {
            ?>
            <tr>
                <td title="user&nbsp;ID:&nbsp; <?php echo $data[ 'id' ]; ?>"><?php echo $data[ 'username' ]; ?></td>
                <td><?php echo $data[ 'email' ]; ?></td>
                <td align="right"><?php echo get_users_number_of_posts($mysql_conn, $data[ 'id' ]); ?></td>
                <td align="center">
                    <a href="single-user.php?m_id=<?php echo $data[ 'id' ]; ?>" >
                        <img src="images/user.png" border=0 width="15" height="15">
                    </a>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <?php
}

/**
 * Function to show statistics of the Posts, Categories, Users
 *
 * @param $mysql_conn MySql Connections
 * @return mixed Shows  the Statistics table
 */
function show_statistics($mysql_conn) {
    $query_categories  = "SELECT category_name FROM categories";
    $categories_result = mysqli_query($mysql_conn, $query_categories);
    $category_amount   = mysqli_num_rows($categories_result);


    $query_posts  = "SELECT id FROM posts";
    $posts_result = mysqli_query($mysql_conn, $query_posts);
    $post_amount  = mysqli_num_rows($posts_result);

    $query_users  = "SELECT id FROM users";
    $users_result = mysqli_query($mysql_conn, $query_users);
    $users_amount = mysqli_num_rows($users_result);
    ?>
    <table id="table_style">
        <thead>
        <tr>
            <th scope="col"><b> Items </b></th>
            <th scope="col"><b> Amount </b></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><b> Posts </b></td>
            <td><?php echo $post_amount;?></td>
        </tr>
        <tr>
            <td><b> Categories </b></td>
            <td><?php echo $category_amount;?></td>
        </tr>
        <tr>
            <td><b> Users </b></td>
            <td><?php echo  $users_amount; ?></td>
        </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Function to get the Post's Title by ID
 *
 * @param  object $mysql_conn MySql Connections
 * @param  $post_id  int
 * @return string  Title of the Post
 */
function get_post_title($mysql_conn, $post_id){
    $query_title  = "SELECT description FROM posts WHERE id = $post_id LIMIT 1";
    $result_title = mysqli_query($mysql_conn, $query_title);
    $data_title   = mysqli_fetch_array($result_title);

    return $data_title[ 'description' ];
}

/**
 * Function to get User's username by ID
 *
 * @param  object $mysql_conn MySql Connections
 * @param  $user_id  int
 * @return string  Name of the User
 */
function get_user_name($mysql_conn, $user_id){
    $user_author  = "SELECT id, username FROM users WHERE id = $user_id";
    $result_user = mysqli_query($mysql_conn, $user_author);
    $data_user   = mysqli_fetch_array($result_user);

    return $data_user[ 'username' ];
}

/**
 * Function to get the name of one category
 *
 * Gets name of the category by ID
 * @param  object $mysql_conn MySql Connections parameters
 * @param  int $category_id Id of the current category
 * @return string  Category name
 */
function get_category_name($mysql_conn, $category_id) {
    $query_cat  = "SELECT category_name FROM categories WHERE id = $category_id";
    $result_cat = mysqli_query($mysql_conn, $query_cat);
    $data_cat   = mysqli_fetch_array($result_cat);

    return $data_cat[ 'category_name' ];
}

/**
 * Function to delete a post
 *
 * @param  object $mysql_conn MySql Connections
 * @param  int $post_id ID of the current post
 * @return mixed redirects to posts-database.php
 */
function delete_post($mysql_conn, $post_id) {
    $query_delete_post  = "DELETE FROM posts WHERE id=$post_id";

    if(mysqli_query($mysql_conn, $query_delete_post)){
        header('Location: ./posts-database.php');
    } else {
        die('Problem: '.mysqli_error($mysql_conn));
    }
}

/**
 * Function to edit one post
 *
 * @param $mysql_conn object $mysql_conn MySql Connections
 * @param $post_id int ID of the current post
 * @param $title string new title of the post
 * @param $category int the id of category of the post
 * @param $status int status of the new post
 */
function edit_post($mysql_conn, $post_id, $title, $category, $status) {

    $query_update_post  = "UPDATE posts SET description='$title', category_id='$category', status='$status' WHERE id=$post_id";
    $result_update_post = mysqli_query($mysql_conn, $query_update_post);

    if($result_update_post == true){
        echo "Successfully edited";
    } else {
        echo "There was a problem on editing the post.";
    }

}

/**
 * Function for creating new Post
 *
 * @param object $mysql_conn MySql Connection
 * @param $user
 * @param $description
 * @param $category_id
 * @param $status
 * @param $img_name
 */
function new_post($mysql_conn, $user, $description, $category_id, $status, $img_name ) {

    $img_new_name = rand(00, 9999).strtolower(str_replace(' ', '-', $img_name));

    $query_insert_post = "INSERT INTO posts (user_id, description, status, category_id, image_name)
                                   VALUES ('$user', '$description' , '$status', '$category_id', '$img_new_name')";
    $result_add_post   = mysqli_query($mysql_conn, $query_insert_post);


    upload_image($img_new_name);

    echo '<p>New post has been added. </p>';
}

/**
 * Function to upload the image
 *
 * @param $img_new_name
 * @return string image new name
 */
function upload_image($img_new_name) {

    $uploaded_image = $_FILES['input_image'];

    if ($uploaded_image['size'] < 1024 * 1024 * 2 *222){

        if (move_uploaded_file($uploaded_image['tmp_name'], SERVER_URL.'uploads/'.$img_new_name)) {
            chmod(SERVER_URL.'uploads/'.$img_new_name, 0666);

            echo '<p>The image was updated successfully</p>';

            return $img_new_name;
        }
        else {
            echo '<p>There was a problem during upload. Please try again.</p>';

        }
    }
}

/**
 * Function to Show the Single Post Page Head Menu
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $post_id ID of the current post
 * @return mixed Shows  the menu
 */
function view_single_post_menu($mysql_conn, $post_id) {
    $query_posts   = "SELECT id, status, created_at, views FROM posts WHERE id = $post_id";
    $result_posts  = mysqli_query($mysql_conn, $query_posts);
    $row_post_menu = mysqli_fetch_array($result_posts);
    ?>
    <div class="head_menu_content"  >
        <a title="Edit this post" href="post-edit.php?post-id=<?php echo $post_id; ?>">Edit</a>
        |
        <a onclick="return confirm('Press OK to delete this post. ')" href="?post-id=<?php echo $post_id; ?>&del=1" title="Delete this post">Delete</a>
        |
        <a title="Add a new post" href="post-new.php">New Post</a>

            <span title="<?php echo (date("l, d F, H:i", strtotime($row_post_menu[ 'created_at' ]))); ?>" class="single-post-date">
                <?php echo (date("d.m.Y - H:i", strtotime($row_post_menu[ 'created_at' ]))); ?>
            </span>
        <?php
        if ($row_post_menu[ 'status' ] == '1') {
            ?>
            <span class="single-post-status post-published"> Published </span>
            <?php
        }
        else {
            ?>
            <span class="single-post-status post-not-published"> Not Published </span>
            <?php
        } ?>
    </div>
    <?php
}

/**
 * Function to display Single Post's Details
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $post_id Id of the current post
 * @return mixed Shows  divs with the information of the post
 */
function view_single_post($mysql_conn, $post_id) {
    $query_select_posts = "SELECT id, user_id, description, category_id, views, image_name FROM posts WHERE  id = $post_id";
    $result_posts       = mysqli_query($mysql_conn, $query_select_posts);
    $row_post           = mysqli_fetch_array($result_posts);

    if ( !empty($row_post[ 'description' ]) ) {
        ?>
        <div class="single-post-info">
        <div class="items">
                    <span class="post-details">
                    Title: </span><?php echo $row_post[ 'description' ]; ?>
        </div>
        <div class="items">
                    <span class="post-details">
                    Img Name: </span><?php echo $row_post[ 'image_name' ]; ?>
        </div>
        <div class="items">
                    <span class="post-details">
                    Category: </span><?php echo get_category_name($mysql_conn, $row_post[ 'category_id' ]); ?>
        </div>
        <div class="items">
                    <span class="post-details">
                    Author: </span><?php echo get_user_name($mysql_conn, $row_post['user_id'] ); ?>
        </div>
        <div class="items">
                <span class="post-details">
                Views: </span><b><?php echo $row_post[ 'views' ]; ?>
        </div>

        <a href="posts-database.php" class="post-details ">
            <img class="left-arrow-post-database" src="images/left_arrow.png" width="128" height="128">Go to database
        </a>
        <?php
        $img_path = UPLOADS_URL . $row_post[ 'image_name' ];
        if ($row_post[ 'image_name' ] == '') {
            ?>
            </div>
            <p class="image-not-found">
                This image cannot be found!
            </p>
            <?php
        }
        else {
            ?>
            </div>
            <a href="single-post-image-view.php?post-id=<?php echo $row_post[ 'id' ]; ?>" ><img class="post_view_img" title="View full image" src="<?php echo $img_path; ?>" /></a>
            <?php
        }
    }
    else {
        ?>
        <div>
            This post may not exist!
        </div>
        <?php
    }
}

/**
 * Function to show the menu on the Single-Post-Image
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $post_id Id of the current post
 * @return mixed Shows  the menu with Delete
 */
function view_single_post_image_menu($mysql_conn, $post_id) {
    $query_select_posts = "SELECT id, description FROM posts WHERE id = $post_id";
    $result_posts       = mysqli_query($mysql_conn, $query_select_posts);
    $row_post           = mysqli_fetch_array($result_posts);
    ?>
    <div class="head_menu_content">
        <span class="single-post-description"><?php echo $row_post[ 'description' ]; ?></span>
        <a href="single-post-view.php?post-id=<?php echo $post_id; ?>">
                <span class="single-post-image-back">
                    Back
                </span>
            <img class="single-post-image-left-arrow" src="images/left_arrow.png">
        </a>
    </div>
    <?php
}

/**
 * Function to show a current image by id
 *
 * @param  object $mysql_conn MySql Connection
 * @param  int $post_id Id of the current post
 * @return mixed Shows  the current image
 */
function view_single_post_image($mysql_conn, $post_id) {
    $query_select_posts = "SELECT id, image_name FROM posts WHERE id = $post_id";
    $result_posts       = mysqli_query($mysql_conn, $query_select_posts);
    $row_post           = mysqli_fetch_array($result_posts);

    $img_path = UPLOADS_URL . $row_post[ 'image_name' ];
    ?>
    <a href="single-post-view.php?post-id=<?php echo $row_post[ 'id' ]; ?>">
        <img class="img_view_full" title="Back to detailed view" src=<?php echo $img_path ?> />
    </a>
    <?php
}

/**
 * Function to show the Posts Database Table
 *
 * @param  object $mysql_conn MySql Connection
 * @return mixed Shows the table of all posts
 */
function show_posts_database($mysql_conn) {
    $query_select_posts = "SELECT id, user_id, created_at, description, status, category_id, image_name, views FROM posts ORDER BY created_at DESC";
    $result_posts       = mysqli_query($mysql_conn, $query_select_posts);
    ?>
    <table cellspacing="1" class="tablesorter">
        <thead>
        <tr>
            <th> Post title / Description </th>
            <th> Date </th>
            <th> Author </th>
            <th> Category </th>
            <th> P/U &nbsp;&nbsp; </th>
            <th> Image Name </th>
            <th> Views </th>
            <th class="text-center"> Go To Post </th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($data = mysqli_fetch_array($result_posts)){
            ?>
            <tr>
                <td class="cursor-default" title="<?= $data[ 'description' ]; ?>"> <?= substr($data[ 'description' ], 0, 40); ?> </td>
                <td title="<?= (date("l, d F  H:i", strtotime($data[ 'created_at' ]))) ?>" class="text-center cursor-default"> <?= (date("d.m.Y - H:i", strtotime($data[ 'created_at' ]))) ?> </td>
                <td> <?php echo get_user_name($mysql_conn, $data['user_id']); ?> </td>
                <td> <?php echo get_category_name($mysql_conn, $data['category_id']); ?> </td>
                <td class="text-center cursor-default"> <?= $data['status'] ?> </td>
                <td class="cursor-default" title="<?= $data[ 'image_name'] ?>"> <?= substr($data[ 'image_name' ], 0, 15) ?> </td>
                <td class="text-center" ><?= $data[ 'views' ]; ?> </td>
                <td title="View&nbsp;&nbsp; <?=$data['id'] ?>" class="text-center cursor-default"><a href="single-post-view.php?post-id=<?= $data['id']; ?>" /><img src="images/open.png" class="p_db_img_view"></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <?php
}

/**
 * Function to show the left column of images on the admin
 *
 * @param  object $mysql_conn MySql Connection
 * @return mixed Shows  the left column of images ( form 1 to 5 )
 */
function latest_posts_left($mysql_conn){

    $query_select_img = "SELECT id, image_name, created_at FROM posts ORDER BY created_at DESC LIMIT 0 ,5";
    $result_img = mysqli_query($mysql_conn, $query_select_img);

    while($row_img = mysqli_fetch_array($result_img)){
        if($row_img['image_name']!=''){
            $img_path = MAIN_URL . "uploads/".$row_img['image_name'];
            ?>
            <a href="single-post-image-view.php?post-id=<?php echo  $row_img['id'] ?>" >
                <img class="admin_img_latest_left" src= "<?php echo $img_path; ?>" title="Permalink: (<?php echo MAIN_URL; ?>single-post-image-view.php?post-id=<?php echo  $row_img['id']; ?>)">
            </a>
            <?php
        }
    }
}

/**
 * Function to show the right column of images on the admin
 *
 * @param  object $mysql_conn MySql Connection
 * @return string - shows the right column of images ( form 5 to 10 )
 */
function latest_posts_right($mysql_conn){

    $query_select_img = "SELECT id, image_name, created_at FROM posts ORDER BY created_at DESC LIMIT 5, 5";
    $result_img = mysqli_query($mysql_conn, $query_select_img);

    while($row_img = mysqli_fetch_array($result_img)){
        if($row_img['image_name']!=''){
            $img_path = MAIN_URL . "uploads/".$row_img['image_name'];
            ?>
            <a href="single-post-image-view.php?post-id=<?php echo  $row_img['id'] ?>" >
                <img class="admin_img_latest_right" src= "<?php echo $img_path; ?>" title="Permalink: (<?php echo MAIN_URL; ?>single-post-image-view.php?post-id=<?php echo  $row_img['id']; ?>)">
            </a>
            <?php
        }
    }
}

/**
 * Function to get all Categories to an array. Mostly used
 * on dropdown select of categories.
 *
 * @param object $mysql_conn MySql Connection
 * @return array $categories_array [id]=>[category_name] array with
 * all the Categories.
 */
function get_categories_array($mysql_conn){
    $query = "SELECT id, category_name FROM categories ORDER BY category_name ";
    $result = mysqli_query($mysql_conn, $query);

    $categories_array = array();
    while($row = mysqli_fetch_array($result)){
        $categories_array[$row['id']] = $row['category_name'];
    }

    return $categories_array;
}

/**
 * Function to get all the post description of a post
 *
 * @param object $mysql_conn MySql Connection
 * @param $post_id int Id of the post that we need the description of
 * @return string description of the post
 */
function get_post_description($mysql_conn, $post_id){
    $query = "SELECT description FROM posts WHERE id = $post_id ";
    $result = mysqli_query($mysql_conn, $query);
    $row = mysqli_fetch_array($result);

    return $row['description'];
}

/**
 * Function to get the post status
 *
 * @param object $mysql_conn MySql Connection
 * @param $post_id int Id of the post that we need the category of
 * @return int status of the post ( either 1 or 0 )
 */
function get_post_status($mysql_conn, $post_id){
    $query = "SELECT status FROM posts WHERE id = $post_id ";
    $result = mysqli_query($mysql_conn, $query);
    $row = mysqli_fetch_array($result);

    return $row['status'];
}

/**
 * Function to get the Category of the current post
 *
 * @param object $mysql_conn MySql Connection
 * @param $post_id int Id of the post that we need the category of
 * @return int category id
 */
function get_post_category($mysql_conn, $post_id){
    $query = "SELECT category_id FROM posts WHERE id = $post_id ";
    $result = mysqli_query($mysql_conn, $query);
    $row = mysqli_fetch_array($result);

    return $row['category_id'];
}

?>