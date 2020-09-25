<?php

namespace App\Console\Commands\Generator;

use Illuminate\Http\Resources\Json\JsonResource;

class EmptyResource extends JsonResource
{
    /**
     * Options for encoding data to JSON.
     *
     * @var int
     */
    protected $encodingOptions = 0;

    /**
     * EmptyResource constructor.
     *
     * @param bool $isArray
     */
    public function __construct($isArray = false)
    {
        parent::__construct(null);

        $this->encodingOptions = $isArray ? 0 : JSON_FORCE_OBJECT;
    }

    /**
     * 资源描述
     *
     * @return array
     */
    public static function schema(): array
    {
        return ['description' => '操作成功', 'statusCode' => 200];
    }


    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [];
    }
}
