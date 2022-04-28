<?php
/*
Plugin Name:second page
Plugin URI: http://wordpress.org/plugins/your-text/
Description: This is my first plugin who i do .
Author: Aicha 
Version: 4.7.2

*/

add_shortcode('greeting', 'wpb_demo_shortcode');
function plugin_form()
{
    add_menu_page('Forms', 'Form Items', 'manage_options', 'plugin_form', 'plugin_form_main', 'dashicons-feedback', 2);
   
}
add_action('admin_menu', 'plugin_form');

function plugin_form_main()
{

    $activefn = '';
    $disablefn = '';
    $activeln = '';
    $disableln = '';
    $activeful = '';
    $disableful = '';
    $activeml = '';
    $disableml = '';
    $activephon = '';
    $disablephon = '';
    $activemssg = '';
    $disablemssg = '';
    $activedt = '';
    $disabledt = '';
    if (get_option('wp_fname') == 1) {
        $activefn = 'checked';
    } else {
        $disablefn = 'checked';
    }
    if (get_option('wp_lastname') == 1) {
        $activeln = 'checked';
    } else {
        $disableln = 'checked';
    }
    if (get_option('wp_fulname') == 1) {
        $activeful = 'checked';
    } else {
        $disableful = 'checked';
    }
    if (get_option('wp_email') == 1) {
        $activeml = 'checked';
    } else {
        $disableml = 'checked';
    }
    if (get_option('wp_nember') == 1) {
        $activephon = 'checked';
    } else {
        $disablephon = 'checked';
    }
    if (get_option('wp_messg') == 1) {
        $activemssg = 'checked';
    } else {
        $disablemssg = 'checked';
    }
    if (get_option('wp_date') == 1) {
        $activedt = 'checked';
    } else {
        $disabledt = 'checked';
    }
    if (isset($_POST["submit"])) {
        echo '<div class ="updated"> The operation was completed successfully!!!! </div>';
    }
    echo '<form action="" method="POST">

       <h4> Fist Name :</h4>
       <input type ="text" placeholder= ".....">
       Active <input type="radio" name="fname"  value="1"' . $activefn . '>
       disable <input type="radio"name="fname" value ="0" ' . $disablefn . '>
       <h4> Last Name:</h4>
       <input type ="text" placeholder= ".....">
        Active <input type="radio"name="lastname" value="1"' . $activeln . '>
        disable <input type="radio"name="lastname" value="0" ' . $disableln . '>
        
       <h4> Full Name :</h4>
       <input type ="text" placeholder= ".....">
        Active <input type="radio"name="fulname"value="1"' . $activeful . '>
        disable<input type="radio"name="fulname"value="0" ' . $disableful . '>
        
        <h4>Email :</h4>
        <input type ="email" placeholder= ".....">
        Active<input type="radio"name="email"value="1"' . $activeml . '>
        disable <input type="radio"name="email"value="0"b' . $disableml . ' >
        
        <h4>Phone  :</h4>
        <input type ="phone" placeholder= "....."name="hiho">
        Active<input type="radio"name="phone"value="1"' . $activephon . '>
        disable<input type="radio"name="phone"value="0"' . $disablephon . '>
        
        
       <h4> message :</h4>
       <input type ="text" placeholder= ".....">
        Active<input type="radio"name="mssg"value="1"' . $activemssg . '>
        disable<input type="radio"name="mssg"value="0"' . $disablemssg . ' >
      <h4>  Date :</h4>
      <input type ="date" placeholder= ".....">
        Active <input type="radio"name="date"value="1" ' . $activedt . '>
        disable <input type="radio"name="date"value="0" ' . $disabledt . '>
        
        <button name="submit" style ="background: #2271B1; border-radius: 8px;width:81px;margin-left:50px; margin-top:10px; height: 30px; color:white;">Submit</button>';
    }
  

$option = 'wp_fname';
if (isset($_POST["submit"])) {
    $value = $_POST['fname'];
    update_option($option, $value);
}


$option = 'wp_lastname';
if (isset($_POST["submit"])) {


    $value = $_POST['lastname'];
    update_option($option, $value);
}

$option = 'wp_fulname';
if (isset($_POST["submit"])) {

    $value = $_POST['fulname'];
    update_option($option, $value);
}

$option = 'wp_email';
if (isset($_POST["submit"])) {
    $value = $_POST['email'];
    update_option($option, $value);
}

$option = 'wp_nember';
if (isset($_POST["submit"])) {

    $value = $_POST['phone'];
    update_option($option, $value);
}

$option = 'wp_messg';
if (isset($_POST["submit"])) {

    $value = $_POST['mssg'];
    update_option($option, $value);
}
$option = 'wp_date';
if (isset($_POST["submit"])) {
    $value = $_POST['date'];
    update_option($option, $value);
}

function form(){
    $form_added = false; 
    if(get_option("wp_fname")){
        echo '<p> FirstName :</p><input id="inputes" type="text" id="lname" name="lastname" placeholder="  ...............">';
        $form_added = true;
    }
    if(get_option("wp_lastname")){
        echo '	<p>LastName :</p><input id="inputes" type="text" id="lname" name="lastname" placeholder="..........." >';
        $form_added = true;
    }
    if(get_option("wp_fulname")){
        echo '<p>Full Name :</p><input id="inputes" type="text" id="fullname" name="FullName" placeholder=".............">';
        $form_added = true;
    }
    if(get_option("wp_email")){
        echo '	<p>First Name :</p><input  id="inputes"type="email" id="email" name="Email" placeholder="..................">';
        $form_added = true;
    }
    if(get_option("wp_nember")){
        echo '	<p>Phone :</p><input id="inputes" type="number" id="phone" name="phone" placeholder="...........">';
        $form_added = true;
    }
    if(get_option("wp_messg")){
        echo '	<p>Message :</p><input id="inputes" type="text"placeholder= "............">';
        $form_added = true;
    }
 
    if(get_option("wp_date")){
        echo '	<p>Date :</p><input id="inputes" type="date">';
        $form_added = true;
    }
    
    if($form_added){

        echo '<br><br><button style= "background-color:#00d084; margin-left:40em;  border-radius: 10px;" >Done</button>';
    }
}
add_shortcode('inputs','form');

?>

</form>
<style>
    #inputes{
        margin-left: 30px;
          padding: 0 8px;
        /* line-height: 2; */
        /* min-height: 30px; */
        border-radius: 10px;
       
        margin-left:20em;
       
       
    
     
      
    
<?php

?>