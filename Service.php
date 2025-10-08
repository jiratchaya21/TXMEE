<?php
    include "Datasql.php"; 
    $ServiceType = $_POST['ServiceType'];
    $Detail_P = $_POST['Detail_P'];
    $Float_P = $_POST['Float_P'];
    $TimeSetup = $_POST['TimeSetup'];
    $Servicetime = $_POST['Servicetime'];
    $Space = $_POST['Space'];
    $sql ="insert into service(ServiceType,Detail_P,Float_P,TimeSetup,Servicetime,Space) 
    values ('$ServiceType','$Detail_P','$Float_P','$TimeSetup','$Servicetime','$Space')";
    $conn->query($sql);
    header('Location:ServiceAdmin.php');
?>