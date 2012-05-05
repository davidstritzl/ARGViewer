<!DOCTYPE html>
<head>
    <title>ARGViewer</title>
    <!-- ARGViewer by koekje00 2012 -->
</head>
<body>
    <div id="container"></div>
    <canvas id="canvas"></canvas>
    <script>
    var xPos = 0, yPos = 0;

    var canvas = document.getElementById('canvas');
    var ctx = canvas.getContext('2d');
    var width = 1024, height = 32, scale = 2;
    canvas.width = width * scale;
    canvas.height = height * scale;

    function addData(data) {
        for(var i=0; i<data.length; i++)
            addChunk(data[i]);
    }

    function addChunk(value) {
        if(yPos >= height)
            return;
            
        if(value != null) {
            var c = 255 - value * 255;
            ctx.fillStyle = 'rgb('+c+','+c+','+c+')';
            ctx.fillRect(xPos*scale, yPos*scale, scale, scale);

            xPos++;
            if(xPos == width) {
                xPos = 0;
                yPos++;
            }
        }
    }
    </script>
</body>
</html>

<?php

function get_raw_data_from_url($url) {
    $raw_input_data = file_get_contents($url);
    if($raw_input_data == false)
        die("Input file could not be found!");

    preg_match_all('/(?:[^+0-9])([0-9A-F]{4})(?=[^-0-9A-Za-z])/', $raw_input_data, $matches);
    if(count($matches[0]) == 0)
        die("Incorrect input file!");

    return $matches[0];
}

function get_data_offset($raw_data_array) {
    for($i=0; $i<512; $i+=4) {
        for($n=0; $n<4; $n++) {
            $value =  hexdec($raw_data_array[$i+$n  ]);
            $value += hexdec($raw_data_array[$i+$n+1]);
            $value += hexdec($raw_data_array[$i+$n+2]);
            $value += hexdec($raw_data_array[$i+$n+3]);

            $value /= 4 * 256;
            $value = round(abs($value - 128));
            $total_value[$n] += $value;
        }
    }

    $offset_for_highest_value = 0;
    $highest_value = 0;
    for($i=0; $i<4; $i++) {
        if($total_value[$i] > $highest_value) {
            $offset_for_highest_value = $i;
            $highest_value = $total_value[$i];
        }
    }

    return $offset_for_highest_value;
}

function process_raw_data($raw_data_array, $offset) {
    for($i=0; $i<count($raw_data_array); $i+=4) {
        $value =  hexdec($raw_data_array[$i+$offset  ]);
        $value += hexdec($raw_data_array[$i+$offset+1]);
        $value += hexdec($raw_data_array[$i+$offset+2]);
        $value += hexdec($raw_data_array[$i+$offset+3]);

        $value /= 4 * 256;

        if(round($value) >= 128) $bit = 0; else $bit = 1;

        $bit_array[$i/4] = $bit;
    }

    return $bit_array;
}

function get_counter_offset($bit_array) {
    for($i=0; $i<count($bit_array); $i++) {
        $counter_found = true;

        for($n=0; $n<32; $n++) {
            if($bit_array[$i+$n] != 0)
                $counter_found = false;
        }

        if($bit_array[$i+32] != 1)
            $counter_found = false;

        if($counter_found)
            return $i;
    }

    return -1;
}

function get_data_without_counter($bit_array, $counter_offset) {
    $remove_n_begin_bits = $counter_offset%16;
    $remove_n_end_bits = (count($bit_array)-($counter_offset%16))%16;

    $n = 0;
    for($i=$remove_n_begin_bits; $i<(count($bit_array)-$remove_n_end_bits); $i++) {
        if($i % 1024 == $counter_offset)
            $i += 48;

        $counterless_bit_array[$n] = $bit_array[$i];
        $n++;
    }

    return $counterless_bit_array;
}

function generate_js_array_string($bit_array) {
    $js_array = '[';
    for($i=0; $i<count($bit_array); $i++) {
        $js_array .= $bit_array[$i];
        if($i != count($bit_array)-1)
            $js_array .= ',';
    }
    $js_array .= ']';

    return $js_array;
}

function get_string_from_data($counterless_bit_array, $verbose = false) {
    $string = '';
    for($i=0; $i<count($counterless_bit_array); $i+=16) {
        $letter = 0;

        for($n=0; $n < 16; $n++) {
            $letter += $counterless_bit_array[$i+$n] << $n;
        }

        switch($letter) {
            case 0x1225: case 0x3225: $string .= 'a'; break;
            case 0x0b89: case 0x4997: $string .= 'b'; break;
            case 0x3e38: $string .= 'c'; break;
            case 0x5819: $string .= 'd'; break;
            case 0x22f2: $string .= 'e'; break;
            case 0xcf34: $string .= 'f'; break;
            case 0x4079: $string .= 'g'; break;
            case 0x9036: $string .= 'h'; break;
            case 0xbdd4: $string .= 'i'; break;
            case 0x8b0e: $string .= 'j'; break;
            case 0x919a: $string .= 'k'; break;
            case 0x5bcd: $string .= 'l'; break;
            case 0x906b: $string .= 'm'; break;
            case 0x0817: $string .= 'n'; break;
            case 0x29d3: $string .= 'o'; break;
            case 0x3e81: $string .= 'p'; break;
            case 0x2726: $string .= 'q'; break;
            case 0xaa44: $string .= 'r'; break;
            case 0xc831: case 0xc931: $string .= 's'; break;
            case 0x77ee: case 0x77ae: $string .= 't'; break;
            case 0x06df: $string .= 'u'; break;
            case 0xd8ec: $string .= 'v'; break;
            case 0xedf9: $string .= 'w'; break;
            case 0xa6a5: $string .= 'x'; break;
            case 0xf6de: $string .= 'y'; break;
            case 0x0590: $string .= ' '; break;
            default: if($verbose) $string .= '['.dechex($letter).']'; break;
        }
    }

    return $string;
}

$raw_data = get_raw_data_from_url('http://etc.firefly.nu/tmp/arg-1/dump-alexer.js');
//$raw_data = get_raw_data_from_url('http://pastebin.com/raw.php?i=wbDfhdWq');

$data_offset = get_data_offset($raw_data);

$bit_data = process_raw_data($raw_data, $data_offset);

echo "<script>addData(" . generate_js_array_string($bit_data) . ");</script>";

$counter_offset = get_counter_offset($bit_data);
if($counter_offset < 0) die("Counter could not be found!");

$counterless_data = get_data_without_counter($bit_data, $counter_offset);

$output_string = get_string_from_data($counterless_data);

echo $output_string;

?>
