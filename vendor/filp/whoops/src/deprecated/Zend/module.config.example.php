<?php
/**
 * ZF2 Integration for Whoops
 * @author Balázs Németh <zsilbi@zsilbi.hu>
 *
 * Example controller configuration
 */

return [
    'view_manager' => [
        'editor'                   => 'sublime',
        'display_not_found_reason' => TRUE,
        'display_exceptions'       => TRUE,
        'json_exceptions'          => [
            'display'    => TRUE,
            'ajax_only'  => TRUE,
            'show_trace' => TRUE,
        ],
    ],
];
