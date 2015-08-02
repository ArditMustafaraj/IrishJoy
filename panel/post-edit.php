<?php
    require_once('functions.php');

    sec_session_start();
    if (login_check($mysqli) == false) {
         header('Location: ' . MAIN_URL);
    }
?>

<html>
    <head>
        <title>Post Edit</title>
        <?php header_requires(); ?>
    </head>
    <body>
        <div class="head"></div>
            <div class="container">
                <div class="content">
                    <?php
                        $id = $_GET[ 'p_id' ];
                        view_single_post_menu($mysqli, $id);
                    ?>
                    <p><b>Make the changes you want at this post:</br></b></p>
                    <form method="post" action="" enctype="multipart/form-data">
                       <div class="form-control">
                            <label class="label-post">Image Description or tags:</label>
                        </div>
                        <div class="form-control">
                            <textarea name="description" rows="6" cols="50"><?php echo get_post_description($mysqli, $id); ?></textarea>
                        </div>
                        <div class="form-control">
                        <label class="label-post">Image Category:</label>
                        <select name="category_id">
                            <option disabled selected>Select Category</option>
                            <?php 
                                $categories_array = get_categories_array($mysqli);
                                $post_category_id = get_post_category($mysqli, $id);
                                foreach($categories_array as $category_id => $category_name){
                                    ?>
                                    <option value="<?php echo $category_id; ?>" <?php if($post_category_id == $category_id) echo 'selected'; ?> >
                                        <?php echo $category_name; ?>
                                    </option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>

                        <div class="pull-right">
                            <button class="content_button" type="submit" name="post_edit">Edit</button>
                        </div>
                        <a href="single-post-view.php?p_id=<?php echo "$id"; ?>">
                            <img class="left-arrow" src="images/left_arrow.png">Cancel editing
                        </a>
                        </br> </br>
                    </form>
                    <?php

                        if (isset($_POST[ 'description' ]) && isset($_POST[ 'category_id' ])) {
                            $title    = $_POST[ 'description' ];
                            $category = $_POST[ 'category_id' ];
                            edit_post($mysqli, $id, $title, $category);
                            //header('Location: " ./single-post-view.php?p_id=".$post_id."&edit=success"');
                        }
                    ?>
                </div>
                <div class="sidebar_right">
                    <div class="menu_bar">
                        <?php show_panel_menu(); ?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>