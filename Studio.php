<?php
    include "Datasql.php"; 
    $Des_Stu = $_POST['Des_Stu'];
    $Price_Stu = $_POST['Price_Stu'];
    $Status_Stu = $_POST['Status_Stu'];
    $sql ="insert into studio(Des_Stu,Price_Stu,Status_Stu) 
    values ('$Des_Stu','$Price_Stu','$Status_Stu')";
    $conn->query($sql);
    header('Location:AddStudio.php');
?>