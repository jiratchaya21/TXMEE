<?php
    include "Datasql.php"; 
    $F_name = $_POST['F_name'];
    $L_name = $_POST['L_name'];
    $Email_C = $_POST['Email_C'];
    $Phone = $_POST['Phone'];
    $Password_C = $_POST['Password_C'];
    $sql ="insert into customer(F_name,L_name,Email_C,Phone,Password_C) 
    values ('$F_name','$L_name','$Email_C','$Phone','$Password_C')";
    $conn->query($sql);
    header('Location:Login.php');
?>