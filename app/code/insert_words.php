<?php

require_once('../config.php');

$db = ConnectDB::getInstance();
$stmt = $db->prepare('INSERT INTO words (word, frequency) VALUES (:word, 0)');

$words = file('../word_cards.txt');
foreach($words as $word) {
    if (!trim($word)) {
        continue;
    }
    $stmt->execute(['word' => trim($word)]);
}