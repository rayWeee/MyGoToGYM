<?php
include 'api/db.php'; 

$name    = 'Raivis Ločmelis';
$email   = 'raivislo03@gmail.com';
$phone   = '+37120492446';
$address = 'Brīvības gat. 321, Rīga';
$mapUrl  = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
$mapEmbedUrl = 'https://maps.google.com/maps?output=embed&q=' . urlencode($address);

include 'views/contacts.html';