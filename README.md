## 后端代码生成器(Hogen\laravel-generator)
---
### Introduction
1. 根据目前框架生成默认代码
2. `model + filter + request + resource + service + controller + migration`
3. 自动读取同名数据库填充 `model + resource + migration`的字段
4. 模板样式改造*.stub
---
### Installation
    composer require xxxx
---
### Demo
```bash
## name : 必填，短横式命名的资源名称}
## --module= : 必填，指定三级模块(大小写规范) 如：GasStation/MainCard/Balance
## --prefix= : 指定二级前缀(大小写规范) 默认：AdminApi
## --baseDir= : 指定一级目录(大小写规范) 默认：Http
## --force : 覆盖已存在文件
## --test : 生成控制器测试类

例子：
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
 * 选择需要生成的组件
 * 有先后顺序之分
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
 * inBaseDir决定是否在Http内
 * prefix决定是否有二级前缀
---
```php
protected $createFilterHelper = true;
protected $baseFilterHelperPath = "Models\Traits\Filter";
```
* 在此修改是否需要新建trait特征 filter
* 对应 BASEDIR\\Models\\Traits\\Filter
* 目前stub代码格式默认使用filter
---
### Generator\\stubs\\*.stub
* 参考各stub配置自定义默认格式
* EmptyResource为我的代码习惯，可自行去掉
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


