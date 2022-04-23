<?php
/**
 * User: CharleyChan
 * Date: 2022/4/23
 * Time: 10:59 下午
 **/
if (!function_exists('responseJson')) {

    /**
     * @param int $code
     * @param string $message
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    function responseJson(int $code, string $message, array $data = [], bool $status = true)
    {
        return response()->json([
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
