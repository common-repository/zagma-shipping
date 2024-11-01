<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>برچسب#</title>
    <style>
        body {
            font-family: <?php echo PW()->get_options( 'wooi_font' ); ?> !important;
            color: <?php echo PW()->get_options( 'wooi_font_color' ); ?>;
        }

        .content.factor th {
            background: <?php echo PW()->get_options( 'wooi_bg_color' ); ?>;
        }

        .content.factor table {
            margin-bottom: 0;
        }

        .content.factor {
            padding: 0;
            margin: 0;
        }

        #data, #data td {
            border: none;
        }
    </style>
</head>
<body>
<div class="tickets">