<?php
include 'includes/db_connect.php';
include 'includes/functions.php';
error_reporting(0);
	// Include database connection and functions here.
sec_session_start();
if(login_check($mysqli) == true) {

   // Add your protected page content here!

} else {
   header('Location: ./login.php');
}
 ?>


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Post Edit</title>
<link rel="icon" href="http://irishjoy.com/panel/images/favicon.png" type="image/x-icon">
	<link rel="stylesheet" type="text/css" href="css/css_panel.css" />
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script>
		$("#custom_menu_button").ready(function(){
		  $("#custom_menu1").hide();
		    $("#custom_menu2").hide();
		    $("#custom_menu3").hide();
		  $("#custom_menu_button").click(function(){
		    $("#custom_menu1").fadeToggle(400);
		    $("#custom_menu2").fadeToggle(600);
		    $("#custom_menu3").fadeToggle(800);
		  });
		});
	</script>



</head>



<body>
<div id="head">	 <?php head_custom_menu(); ?>	</div>





</div>

 		<div id="container">
		 		<div id="content">

 			<?php
	 			$id=$_GET['p_id'];
	 			view_post_menu($mysqli,$id);
			 ?>

 			<p><b>Make the changes you want at this post:</br></b></p>


						<form method="post"  action="" enctype="multipart/form-data">

							<label>Title:</label>
							<input type="text" name="post_title" style="width:90%"
							value="<?php $title=get_post_title($mysqli,$id); echo"$title"; ?>"	/>

								</br></br>	</br></br>

							<label>New Image Category:</label>
							<select name="category">
	  							<option selected="true" style="display:none;"
	  								value="<?php $cat=get_post_category($mysqli,$id); echo"$cat"; ?>" >
	  								<?php echo"$cat"; ?></option>
								    <?php
											$query_select_categ = "SELECT id, category_name FROM category" ;
											$result_categ= mysqli_query($mysqli , $query_select_categ);
											while($row_cat = mysqli_fetch_array($result_categ))
												  {
													  echo "<option value=\"" . $row_cat['category_name'] . "\">"
													  						  . $row_cat['category_name'] . "</option>";
												  }

									?>
							</select>

								</br></br></br>


						 	</br></br>
							Make the post:
						     	<h id="visible" style="color:green; cursor: pointer ;">Visible(1)</h> &nbsp;&nbsp;&nbsp;
						     	<h id="notvisible" style="color:red; cursor: pointer ;">Not Visible(0)</h>
						     	<input type="text" name="visible" id="visibility" value="1"
						 			   style="width: 80px; text-align: center; background: none; float:float;"/>
						     </br> </br></br>

		 					<hr>
		 					</br>
		 					<div style="float:right;">
					 			<button class="content" type="submit">Publish</button>
							</div>
							<a href="post-view.php?p_id=<?php echo"$id";?>" >
							<img style="width:15px; margin-bottom:-3px; height:auto;"
							src="images/left_arrow.png">Cancel editing</img></a>
							</a>
					</br> </br>


					 </form>


		 		</div>

				<div id="sidebar_right">

					<div id="menu_bar">
						<?php show_panel() ?>





					</div>

				</div>

 		</div>



</body>
</html>