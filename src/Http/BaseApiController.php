<?php
/**
 * User: CharleyChan
 * Date: 2022/4/23
 * Time: 10:50 下午
 **/

namespace Charleychan\Laratool\Http;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class BaseApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param array $data
     * @param array $codeResponse
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(array $data = [], array $codeResponse = HttpCodeResponse::HTTP_SUCCESS)
    {
        list($code, $msg) = $codeResponse;
        return responseJson($code, $msg, $data);
    }

    /**
     * @param array $codeResponse
     * @param string $message
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail(array $codeResponse = HttpCodeResponse::HTTP_FAIL, string $message = '', array $data = [])
    {
        list($code, $msg) = $codeResponse;
        return responseJson($code, $message ?: $msg, $data, false);
    }
}
