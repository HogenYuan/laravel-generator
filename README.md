# Laravel-generator 代码数据生成包
<a href="https://packagist.org/packages/hogen/laravel-generator" title="Latest Version on Packagist"><img src="https://img.shields.io/packagist/v/hogen/laravel-generator.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/hogen/laravel-generator" title="Total Downloads"><img src="https://img.shields.io/packagist/dt/hogen/laravel-generator.svg?style=flat-square"></a>
<a href="LICENSE.md" title="MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square"></a>
### Introduction
1. 根据自定义框架目录,修改*.stub生成自定义的初始代码
2. 支持`model + filter + request + resource + service + controller + migration + test`
3. 自动读取同名数据表并填充到 `model + resource + migration` 的字段
---
### Installation
    composer require hogen\laravel-generator
    php artisan vendor:public --tag=generator

* config/app.php
```php
'providers' => [
    ···
    App\Console\Commands\Generator\GeneratorServiceProvider::class
];
```
---
### Run
* 请先根据自己的框架目录和代码格式修改默认代码格式stub文件
```bash
## name : 必填，短横式命名的资源名称}
## --module= : 必填，指定三级模块(大小写规范) 如：GasStation/MainCard/Balance
## --prefix= : 指定二级前缀(大小写规范) 默认：AdminApi
## --baseDir= : 指定一级目录(大小写规范) 默认：Http
## --force : 覆盖已存在文件

例子：
//有大小写规范

* Path: app\Http\Controller\AdminApi\User\Example 
php artisan admin:make-resource testExample --force --baseDir=Http --prefix=AdminApi --module=User\Example

* Path: app\Admin\Controller\User\Example
php artisan admin:make-resource testExample --force --baseDir=Admin  --module=User\Example
```
---
### Deployment自定义配置
**Generator\\MakeResource.php**

```php
protected $types = [
    'filter', 'model', 'request', 'resource', 'service', 'controller', 'test', 'migration'
];
```
 * 选择需要生成的组件,filter和test默认不开启
 * 有先后顺序之分，需按照上图顺序填写
---

```php
protected $pathFormat = [
    'model'      => ['inBaseDir' => false, 'prefix' => ''],
    'service'    => ['inBaseDir' => false, 'prefix' => ''],
    'test'       => ['inBaseDir' => false, 'prefix' => true],
    'filter'     => ['inBaseDir' => true, 'prefix' => true],
    'request'    => ['inBaseDir' => true, 'prefix' => true],
    'resource'   => ['inBaseDir' => true, 'prefix' => true],
    'controller' => ['inBaseDir' => true, 'prefix' => true],
    'migration'  => ['inBaseDir' => false, 'prefix' => ''],
];
```
 * 默认
 * 在此修改各模块的路径规则设置
 * inBaseDir决定是否在BaseDir内，默认```Http```
 * prefix决定是否在二级前缀内
---
```php
protected $createFilterHelper = false;
protected $baseFilterHelperPath = "Models\Traits\Filter";
```
* filter默认不开启
* 在此修改是否需要新建trait特征 filter
---
### Code Format修改默认代码格式
* 参考各stub配置自定义默认格式
* 以下stub均为我的代码习惯，可自行修改

Generator\\stubs\\*.stub
```php
<?php

namespace DummyNamespace;

use NamespacedDummyModel;
use NamespacedDummyRequest;
use NamespacedDummyResource;
use NamespacedDummyService;
use BaseNamespaceResource\EmptyResource;
use BaseNamespaceController\Controller;

/**
 * 方法
 *
 * Class DummyClass
 *
 * @package DummyNamespace
 */
class DummyClass extends Controller
{
    /**
     * 列表
     *
     * @param \NamespacedDummyRequest $request
     *
     * @return \NamespacedDummyResource[]
     */
    public function index(DummyRequest $request){
        $validated = $request->validated();
        $dummyModels = DummyModel::query()
            ->filter($validated)
            ->orderByDesc('id')
            ->paginate();
        return DummyResource::collection($dummyModels);
    }
}
```
---
### TODO
1. test自动测试需指令--test，尚未测试完善
2. 前端根据组件一键生成代码

