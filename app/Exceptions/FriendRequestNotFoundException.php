<?php

namespace App\Exceptions;

use Exception;

class FriendRequestNotFoundException extends Exception
{
    //
    public function render($request)
    {
        //

        return response()->json([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locat the user with the given information',

            ]
        ],404);
    }
}
