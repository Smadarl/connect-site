<?php

class Word {
    public $id;
    public $word;
    public $frequency;

    static public function increment($word) {
        $query = "UPDATE words SET frequency = frequency + 1 WHERE word = '$word'";
        $res = ConnectDB::getInstance()->exec($query);
    }
}