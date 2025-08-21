<?php

    /*
     * for execute app enter this commond in terminal:
     * php lan.php "Your LanSet" input.txt
     */

    // ...................... Language Definitions ......................

    function isInL1($str) {
        if (!preg_match('/^a+b*$/', $str)) {
            return false;
        }
        $n = substr_count($str, 'a');
        $m = substr_count($str, 'b');
        if ($m == 0) {
            return $n > 0;
        }
        return $m < $n && $n <= 3 * $m;
    }

    function isInL2($str) {
        if (!preg_match('/^a*b*c*$/', $str)) {
            return false;
        }
        $n_a = substr_count($str, 'a');
        $n_b = substr_count($str, 'b');
        return $n_a === $n_b;
    }

    function isInL3($str) {
        if (!preg_match('/^a*b*c*$/', $str)) {
            return false;
        }
        $n_b = substr_count($str, 'b');
        $n_c = substr_count($str, 'c');
        return $n_b === $n_c;
    }

    function isInL4($str) {
        if (!preg_match('/^[ab]*$/', $str)) {
            return false;
        }
        $n_a = substr_count($str, 'a');
        $n_b = substr_count($str, 'b');
        return $n_a === $n_b;
    }

    // ...................... Operation Handlers ......................

    function handleUnion($lang1, $lang2) {
        return function($str) use ($lang1, $lang2) {
            return $lang1($str) || $lang2($str);
        };
    }

    function handleConcat($lang1, $lang2) {
        return function($str) use ($lang1, $lang2) {
            $len = strlen($str);
            for ($i = 0; $i < $len; $i++) {
                $part1 = substr($str, 0, $i);
                $part2 = substr($str, $i);
                if ($lang1($part1) && $lang2($part2)) {
                    return true;
                }
            }
            return false;
        };
    }


    function handleStar($lang) {
        return function($str) use ($lang) {
            if ($str === '') {
                return true;
            }
            $len = strlen($str);
            for ($i = 1; $i <= $len; $i++) {
                $part = substr($str, 0, $i);
                if ($lang($part)) {
                    $remaining = substr($str, $i);
                    if (($langStar = handleStar($lang))($remaining)) {
                        return true;
                    }
                }
            }
            return false;
        };
    }

    // ...................... Parser ......................

    function parseLanguageExpression($expr) {
        static $langMap = [
            'L1' => 'isInL1',
            'L2' => 'isInL2',
            'L3' => 'isInL3',
            'L4' => 'isInL4'
        ];
        static $temp = 100;
        $expr = preg_replace('/\s+/', '', $expr);

        // Handle parentheses first
        while (preg_match('/\(([^()]+)\)/', $expr, $matches)) {
            $subExpr = $matches[1];
            $parsed = parseLanguageExpression($subExpr);
            $tempKey = "L$temp";
            $langMap[$tempKey] = $parsed;
            $expr = str_replace("($subExpr)", $tempKey, $expr);
            $temp++;
        }

        // Handle Kleene Star
        while (preg_match('/(L\d+)\*/', $expr, $matches)) {
            $langKey = $matches[1];
            $tempKey = "L$temp";
            $langMap[$tempKey] = handleStar($langMap[$langKey]);
            $expr = str_replace("$langKey*", $tempKey, $expr);
            $temp++;
        }

        // Handle Concatenation (.)
        while (preg_match('/(L\d+)\.(L\d+)/', $expr, $matches)) {
            $lang1 = $matches[1];
            $lang2 = $matches[2];
            $tempKey = "L$temp";
            $langMap[$tempKey] = handleConcat($langMap[$lang1], $langMap[$lang2]);
            $expr = str_replace("$lang1.$lang2", $tempKey, $expr);
            $temp++;
        }

        // Handle Union (+)
        while (preg_match('/(L\d+)\+(L\d+)/', $expr, $matches)) {
            $lang1 = $matches[1];
            $lang2 = $matches[2];
            $tempKey = "L$temp";
            $langMap[$tempKey] = handleUnion($langMap[$lang1], $langMap[$lang2]);
            $expr = str_replace("$lang1+$lang2", $tempKey, $expr);
            $temp++;
        }

        if (!isset($langMap[$expr])) {
            die("Error: Invalid expression '$expr'\n");
        }

        return $langMap[$expr];
    }

    // ...................... Main Program ......................

    function processFile($expression, $filePath) {
        if (!file_exists($filePath)) {
            die("Error: File not found\n");
        }

        $checker = parseLanguageExpression($expression);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $i => $line) {
        $line = trim($line);
        if ($line === '') continue;
        $result = $checker($line) ? '✅ Yes' : '❌ No';
        echo "Line " . ($i + 1) . ": $line => $result\n";
    }

    }

    // Command-line execution
    if (php_sapi_name() === 'cli') {
        global $argc, $argv;
        if ($argc != 3) {
            die("Usage: php lang_checker.php \"<expression>\" <file_path>\n");
        }
        $expression = $argv[1];
        $filePath = $argv[2];
        processFile($expression, $filePath);
    }
?>
