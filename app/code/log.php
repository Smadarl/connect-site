<?php

class Log {
    static private $handles;

    static private $levels = [
        'Error' => 1,
        'Warning' => 2,
        'Info' => 3,
        'Debug' => 4
    ];

    static public function checkLevel($level) {
        return self::$levels[$level] <= self::$levels[Config::get('log/level')];
    }

    static public function checkHandle($category) {
        if (!isset(self::$handles[$category])) {
            self::open($category);
        }
    }

    static private function open($category) {
        self::$handles[$category] = fopen("$category.log", 'a');
    }

    static public function write($level, $category, ...$args) {
        if (!self::checkLevel($level)) return false;
        self::checkHandle($category);
        // if (self::$levels[$level] > self::$levels[Config::get('log/level')]) {
        //     return false;
        // }
        // if (!isset(self::$handles[$category])) {
        //     self::open($category);
        // }
        foreach($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                fwrite(self::$handles[$category], $tmp = var_export($arg, true) . "\n", sizeof($tmp));
            } else {
                fwrite(self::$handles[$category], $tmp = "$arg\n", sizeof($tmp));
            }
        }
        return true;
    }

}
