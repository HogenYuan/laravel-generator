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
    php artisan vendor:publish --tag=generator
    # 如更新出现问题，执行 composer remove hogen\laravel-generator 并把 app\Console\Commmands\Generator 删除

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
## --filter : 使用filter筛选类
## --test : 生成测试文件

例子：
//有大小写规范

* Path: App\Http\Controller\AdminApi\User\Example 
php artisan admin:make-resource testExample --force --baseDir=Http --prefix=AdminApi --module=User\Example

* Path: App\Admin\Controller\User\Example
php artisan admin:make-resource testExample --force --filter --baseDir=Admin  --module=User\Example
```
---
### Deployment自定义配置

**Generator\\MakeResource.php**
```php
protected $types = [
    'model', 'request', 'resource', 'service', 'controller', 'test', 'migration'
];
```
 * 选择需要生成的组件,filter和test默认不开启
 * 有先后顺序之分，需按照上图顺序填写
---
##### 目录规则
```php
protected $pathFormat = [
    'model'      => ['inBaseDir' => false, 'prefix' => ''],
    'service'    => ['inBaseDir' => false, 'prefix' => ''],
    'test'       => ['inBaseDir' => false, 'prefix' => true],
    'request'    => ['inBaseDir' => true, 'prefix' => true],
    'resource'   => ['inBaseDir' => true, 'prefix' => true],
    'controller' => ['inBaseDir' => true, 'prefix' => true],
    'migration'  => ['inBaseDir' => false, 'prefix' => ''],
];
```
 * 在此修改各模块的路径规则设置，会影响各文件的命名空间和类名
 * inBaseDir决定是否在BaseDir内，默认```Http```
 * prefix决定是否在二级前缀内
---
##### Filter筛选器
```php
protected $createFilter = false;
protected $baseFilterHelperPath = "Models\Traits\Filter";
```
* 默认不开启
* 生成的filter基类的路径 例: App/Models/Traits/Filter.php
* 路径生成只遵循$pathFormat中model的inBaseDir规则，不遵循prefix，避免个trait的生成
---
#####  数据库字段填充
```php
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
```

---
### Code Format 修改默认代码格式
* 参考各stub配置自定义默认格式
* 以下stub为简化后的代码习惯，按需修改

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

class DummyClass extends Controller
{
    public function index(DummyRequest $request){
        $validated = $request->validated();
        $dummyModels = DummyModel::query()
            ->filter($validated)
            ->orderByDesc('id')
            ->paginate();
        return DummyResource::collection($dummyModels);
    }
    ···
}
```
---
### TODO
1. test自动测试需指令--test，尚未测试完善
2. 前端根据组件一键生成代码

