<?php


namespace App\Controllers;


class ErrorController
{
    /**
     * 404 not found error
     *
     * @return void
     */
    public static function not_found($message = 'Resource not found!')
    {
        http_response_code(404);
        load_view('error', [
            'status' => '404',
            'message' => $message
        ]);
    }

    /**
     * 403 unauthorized error
     *
     * @return void
     */
    public static function unauthorized($message = 'You are not authorized to view this resource!')
    {
        http_response_code(403);
        load_view('error', [
            'status' => '403',
            'message' => $message
        ]);
    }
}