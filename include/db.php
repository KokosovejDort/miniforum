<?php
  $db = new PDO('mysql:host=127.0.0.1;dbname=dbname;charset=utf8', 'dbname', 'password');

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
