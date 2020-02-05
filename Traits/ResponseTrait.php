<?php

namespace App\Traits;

trait ResponseTrait
{

    /**
     * @param null $data
     * @param null $message
     * @param null $status
     * @param null $statusCode
     * @param $exp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnResponse($data=null, $message=null, $status=null, $statusCode=null, $exp=0)
    {
        if ($exp == 1){
            $msg = self::genericMessage($message);
        }else{
            $msg = $message;
        }
        $response = [
            'data' => $data,
            'message' => $msg,
            'devMessage' => $message,
            'status' => $status,
            'statusCode' => $statusCode
        ];
        
        return response()->json($response, 200);
    }

    public function genericMessage($message){
        
        if (env('APP_ENV') === 'prod') {
            $messages = 'It seems we have encountered a problem!';
        }else{
            $messages = $message;
        }
        
        return $messages;
    }
    
}