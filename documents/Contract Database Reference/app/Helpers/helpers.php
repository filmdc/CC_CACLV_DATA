<?php
// app/Helpers/helpers.php

if (!function_exists('get_file_icon_class')) {
    function get_file_icon_class($extension) {
        $icon_classes = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive',
            'txt' => 'fa-file-alt',
            'mp3' => 'fa-file-audio',
            'mp4' => 'fa-file-video',
            // Add more file types and their corresponding icons here
        ];

        return isset($icon_classes[$extension]) ? $icon_classes[$extension] : 'fa-file';
    }
}
