<?php
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        echo "FATAL ERROR: \n";
        print_r($error);
    }
});
require 'vendor/bin/pest';
