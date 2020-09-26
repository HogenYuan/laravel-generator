<?php

namespace App\Console\Commands\Generator;

use Illuminate\Support\Str;
use Hogen\Generator\BaseMakeResource;

class MakeResource extends BaseMakeResource
{
    /**
     * APP_PATH
     */
    const APP_PATH = "app";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        admin:make-resource
        {name : 必填，短横式命名的资源名称}
        {--module= : 必填，指定三级模块(大小写规范) 如：GasStation/MainCard/Balance}
        {--prefix= : 指定二级前缀(大小写规范) 默认：AdminApi}
        {--baseDir= : 指定一级目录(大小写规范) 默认：Http}
        {--force : 覆盖已存在文件}
        {--filter : 使用filter筛选类}
        {--test : 生成控制器测试类}
    ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '添加一个资源，包含各种相关文件';
    /**
     * 手动配置
     * 选择需要生成的组件
     * 有先后顺序之分
     * test需要用--test开启
     *
     * @var array
     */
    protected $types = [
        'model',
        'request',
        'resource',
        'service',
        'controller',
        'test',
        'migration',
    ];
    /**
     * 手动配置
     * 是否需要使用filter筛选器
     *
     * @var boolean
     */
    protected $createFilter = false;
    /**
     * 手动配置
     * 生成的filter基类的路径 例: App/Models/Traits/Filter.php
     * 路径生成只遵循$pathFormat中model的inBaseDir规则，不遵循prefix，避免多个trait的生成
     * 
     * @var string
     */
    protected $baseFilterHelperPath = "Models/Traits/Filter";
    /**
     * 手动配置
     * 在此修改各模块的路径规则设置
     *
     * inBaseDir决定是否在Http内
     * prefix决定是否有二级前缀
     *
     * @var array
     */
    protected $pathFormat = [
        'model'      => ['inBaseDir' => true, 'prefix' => ''],
        'service'    => ['inBaseDir' => true, 'prefix' => ''],
        'test'       => ['inBaseDir' => true, 'prefix' => true],
        'request'    => ['inBaseDir' => true, 'prefix' => true],
        'resource'   => ['inBaseDir' => true, 'prefix' => true],
        'controller' => ['inBaseDir' => true, 'prefix' => true],
        'migration'  => ['inBaseDir' => true, 'prefix' => ''],
    ];
    /**
     * 手动配置
     * resource文件中不需要添加到 $fillable 的字段
     *
     * @var string[]
     */
    protected $resourceNoFillableFields = [
        'update_time',
        'updated_time',
        'delete_time',
        'deleted_time',
    ];

    /**
     * 手动配置
     * model文件中不需要添加到 $fillable 的字段
     *
     * @var string[]
     */
    protected $modelNoFillableFields = [
        'id',
        'create_time',
        'created_time',
        'update_time',
        'updated_time',
        'delete_time',
        'deleted_time',
    ];
    
    /**
     * 自定义替换资源内的Dummy
     *
     * @param string $dummyResourceReturn
     * @param string $column
     * @param string $type
     *
     * @return string
     */
    protected function replaceDummyResourceReturn(string $dummyResourceReturn,string $column ,string $type): string{
        $dummyResourceReturn .= "'{$column}' => " . '$this->' . "{$column},\r\n            ";
        //$dummyResourceReturn .= "'{$column}' => static::prop{$type}('{$column}'),\r\n            ";
        return $dummyResourceReturn;
    }



    /**
     * 替换类中的dummy替换符
     *
     * @inheritDoc
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $stub  = str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
        //替换namespace根路径
        foreach ($this->namespaceBasePaths as $type => $namespaceBasePath) {
            $stub = str_replace(
                'BaseNamespace' . str::ucfirst($type),
                $namespaceBasePath,
                $stub
            );
        }
        switch ($this->nowType) {
            case "test":
                $stub = str_replace('NamespacedDummyModel', $this->classes['model'], $stub);
                $stub = str_replace('dummy-resource-name', Str::plural($this->argument('name')), $stub);
                break;
            case "controller":
                //仅在controller.stub中添加替代各个控件的变量
                foreach (['request', 'resource', 'model', 'service'] as $type) {
                    if (in_array($type, $this->types)) {
                        $stub = $this->replaceDummyResource($type, $stub);
                    }
                }
                break;
            case "service":
                foreach (['model'] as $type) {
                    $stub = $this->replaceDummyResource($type, $stub);
                }
                break;
            case "model":
                $stub = $this->replaceDummyModel($stub);
                break;
            case "resource":
                $stub = $this->DummyResourceReturn($stub);
                break;
            default:
        }
        return $stub;
    }
}
