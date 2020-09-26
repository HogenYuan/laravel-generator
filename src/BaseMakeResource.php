<?php

namespace Hogen\Generator;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BaseMakeResource extends GeneratorCommand
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
     *
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
     * 当前正在生成的类型
     *
     * @var string
     */
    protected $nowType;
    /**
     * 一级目录 app\Http\
     *
     * @var string
     */
    protected $baseDir = 'Http';
    /**
     * 二级前缀 app\baseDir\prefix
     *
     * @var string
     */
    protected $prefix = 'AdminApi';
    /**
     * 三级模块 app\baseDir\prefix\module
     *
     * @var string
     */
    protected $module;
    /**
     * 是否强制覆盖
     *
     * @var bool
     */
    protected $forceCreate = false;
    /**
     * 是否创建测试
     *
     * @var bool
     */
    protected $createTest = false;
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
     * 各类型对应的namespaceBasePaths
     *
     * @var array
     */
    protected $namespaceBasePaths = [];
    /**
     * 各类型对应的完整的类名
     *
     * @var array
     */
    protected $classes = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getOptionValue();
        $this->addNamespaceBasePath();
        return $this->makeBackend();
    }

    /**
     * 获取用户的设置
     */
    protected function getOptionValue()
    {
        $this->module       = $this->option('module');
        $this->prefix       = $this->option('prefix');
        $this->baseDir      = $this->option('baseDir');
        $this->forceCreate  = $this->option('force');
        $this->createTest   = $this->option('test');
        $this->createFilter = $this->option('filter');
    }

    /**
     * 添加namespace的基础路径
     */
    protected function addNamespaceBasePath()
    {
        $baseDir = $this->baseDir;
        $prefix  = $this->prefix;
        foreach ($this->pathFormat as $type => $typeFormat) {
            $namespaceBasePath = Str::ucfirst(self::APP_PATH);
            if ($typeFormat['inBaseDir'] && $baseDir) {
                $namespaceBasePath = $namespaceBasePath . "\\" . $baseDir;
            }
            if ($typeFormat['prefix'] && $prefix) {
                $namespaceBasePath = $namespaceBasePath . "\\" . $prefix;
            }
            $this->namespaceBasePaths[$type] = $namespaceBasePath . "\\" . Str::plural(Str::ucfirst($type));
        }
    }

    /**
     * 自动生成后端
     *
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function makeBackend()
    {
        //初始化参数
        if (!$this->module) {
            $this->error('请指定模块(大小写规范) 例：--module=GasStation');
            return false;
        }

        //初始化模块路径格式
        foreach ($this->pathFormat as $k => $v) {
            if ($v['prefix'] === true) {
                $this->pathFormat[$k]['prefix'] = $this->prefix;
            }
        }

        //针对每个模块
        foreach ($this->types as $type) {
            $this->nowType = $type;
            $this->type    = Str::ucfirst($type);

            if (($type === 'test') && (!$this->createTest)) {
                continue;
            }

            if ($this->createFile() === false) {
                return false;
            }
            $this->classes[$type] = $this->qualifyClass($this->getNameInput());
        }

        return true;
    }

    /**
     * 创建文件替代stub
     *
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createFile(): bool
    {
        //获取拼接命名空间后的全称
        $name = $this->qualifyClass($this->getNameInput());

        //获取文件的实际存储路径
        $path = $this->getPath($name);

        //若文件已存在，直接给出错误提示而不会覆盖原来的文件
        if (!$this->forceCreate && $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type . ' already exists!');
            return false;
        }

        //新建filterHelpers
        if ($this->createFilter && $this->nowType == 'model') {
            $stubName             = 'filter';                 //读取filterHelpers文件名
            $baseFilterHelperPath = $this->baseFilterHelperPath;
            $this->createBaseFile($stubName, $baseFilterHelperPath, 'DummyFilterHelpersNamespace');
        }

        //生成文件
        if ($this->nowType == 'migration') {
            $stubName = 'migration';               //stub读取文件名
            $this->createMigrationFile($stubName);
        } else {
            $this->makeDirectory($path);
            //适当地替换模版文件中的数据，如命名空间和类名
            $this->files->put($path, $this->sortImports($this->buildClass($name)));
        }

        //控制台提示
        $this->info($this->type . ' created successfully.');
        return true;
    }

    /**
     * 规范化名
     *
     * @inheritDoc
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * 修改命名空间
     *
     * @inheritDoc
     */
    protected function rootNamespace()
    {
        $namespace = Str::ucfirst(self::APP_PATH) . "\\";
        $namespace = $this->getNamespaceByType($namespace);
        $namespace .= $this->module;
        $namespace = str_replace('/', '\\', $namespace);
        return $namespace;
    }

    /**
     *  根据当前type获取命名空间
     *
     * @param $namespace
     *
     * @return string
     */
    protected function getNamespaceByType($namespace): string
    {
        $inBaseDir = $this->pathFormat[$this->nowType]['inBaseDir'];
        $prefix    = $this->pathFormat[$this->nowType]['prefix'];
        if ($inBaseDir) {
            $namespace = $namespace . $this->baseDir . '/';
        }
        $namespace .= Str::ucfirst(Str::plural($this->nowType)) . '/';
        if ($prefix) {
            $namespace = $namespace . $prefix . '/';
        }
        return $namespace;
    }

    /**
     * 获取命名空间
     *
     * @inheritDoc
     */
    protected function getNameInput()
    {
        $name = Str::studly(trim($this->argument('name')));

        if ($this->nowType == 'test') {
            $name = 'Feature\\' . $name . 'ControllerTest';
        } elseif ($this->nowType != 'model') {
            $name .= $this->type;
        }

        return $name;
    }

    /**
     * 根据type去改变位置path，且添加传入参数$this->module改变stub句柄的修改值
     *
     * @inheritDoc
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        $path = $this->getPathByType($name);
        $path = str_replace('/', '\\', $path);
        return $path;
    }

    /**
     * 根据type获取路径
     *
     * @param $name
     *
     * @return string
     */
    protected function getPathByType($name)
    {
        $inBaseDir = $this->pathFormat[$this->nowType]['inBaseDir'];
        $prefix    = $this->pathFormat[$this->nowType]['prefix'];
        $path      = $this->laravel['path'] . '/';
        if ($inBaseDir) {
            $path = $path . $this->baseDir . '/';
        }
        $path .= Str::ucfirst(Str::plural($this->nowType)) . '\\';
        if ($prefix) {
            $path = $path . $prefix . '/';
        }
        $path .= $this->module . str_replace('\\', '/', $name) . '.php';
        return $path;
    }

    /**
     * 初始化filterHelpers文件
     *
     * @param $stubName
     * @param $basePath
     * @param $DummyNamespace
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createBaseFile($stubName, $basePath, $DummyNamespace): void
    {
        $fullPath  = '';
        $namespace = str::ucfirst(self::APP_PATH);
        if ($this->pathFormat['model']['inBaseDir']) {
            //命名空间
            $namespace = $namespace . "/" . $this->baseDir;
        }
        $filePath  = str_replace('/', '\\', $this->laravel['path'] . '\\' . $fullPath . $basePath);
        $fullPath  = $filePath . '.php';
        $namespace = $namespace . "\\" . Str::ucfirst(Str::plural($this->nowType)) . '\\Traits';
        $namespace = str_replace('/', '\\', $namespace);
        if (!$this->files->exists($fullPath)) {
            $this->files->ensureDirectoryExists(rtrim($filePath, 'Filter'));
            $stub = $this->files->get(app_path("Console/Commands/Generator/stubs")."/hasFilter/{$stubName}.stub");
            $stub = str_replace($DummyNamespace, $namespace, $stub);
            $this->files->put($fullPath, $this->sortImports($stub));
            $this->info($this->type . "[{$stubName}] created successfully.");
        }
    }

    /**
     * 初始化Migration文件
     *
     * @param $stubName
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createMigrationFile($stubName): void
    {
        $now       = now();
        $class     = Str::studly(trim($this->argument('name')));
        $tableName = Str::plural(Str::snake($class));
        $fullPath  = rtrim($this->laravel['path'], 'app') . 'database\\migrations\\';
        $fullPath  .= "{$now->year}_{$now->month}_{$now->day}_000000_create_{$tableName}_table.php";
        $fullPath  = str_replace('/', '\\', $fullPath);
        if (!$this->files->exists($fullPath)) {
            $stub = $this->files->get(app_path("Console/Commands/Generator/stubs"). "/{$stubName}.stub");
            $stub = $this->DummyMigration($stub);
            $this->files->put($fullPath, $this->sortImports($stub));
            $this->info($this->type . "[{$stubName}] created successfully.");
        }
    }

    /**
     * 替换migration内的Dummy
     *
     * @param string $stub
     *
     * @return string
     */
    protected function DummyMigration(string $stub): string
    {
        $class          = Str::studly(trim($this->argument('name')));
        $table          = Str::plural(Str::snake($class));
        $stub           = str_replace('DummyTable', $table, $stub);
        $stub           = str_replace('DummyClass', $class, $stub);
        $dummyMigration = null;
        if (Schema::hasTable($table)) {
            $dummyMigration .= '$table->commonColumns();' . "\r\n            ";

            $columns = Schema::getColumnListing($table);
            foreach ($columns as $column) {
                $type = Schema::getColumnType($table, $column);
                if ($type == 'boolean') {
                    $type = 'tinyInteger';
                }
                if ($type == 'datetime') {
                    $type = 'timestamp';
                }
                $type           = Str::Ucfirst($type);
                $dummyMigration .= '$table->' . "{$type}('{$column}')->nullable()->comment('');\r\n            ";
            }
        }
        $stub = str_replace('DummyMigration', $dummyMigration, $stub);
        return $stub;
    }

    /**
     * 新建类
     *
     * @inheritDoc
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * 获取Stub源文件
     *
     * @inheritDoc
     */
    protected function getStub()
    {
        $stubPath = app_path("Console/Commands/Generator/stubs");
        if ($this->createFilter) {
            $stubPath = $stubPath . "/hasFilter/{$this->nowType}.stub";
        } else {
            $stubPath = $stubPath . "/{$this->nowType}.stub";
        }
        return $stubPath;
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
        $stub = parent::replaceClass($stub, $name);
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
                $baseFilterHelperPath = self::APP_PATH;
                if ($this->pathFormat['model']['inBaseDir']) {
                    $baseFilterHelperPath = $baseFilterHelperPath . "/" . $this->baseDir;
                }
                $baseFilterHelperPath = str_replace("/", "\\", Str::ucfirst($baseFilterHelperPath . "/" . $this->baseFilterHelperPath));
                $stub = str_replace('BaseNamespaceFilter', $baseFilterHelperPath, $stub);
                $stub = $this->replaceDummyModel($stub);
                break;
            case "resource":
                $stub = $this->DummyResourceReturn($stub);
                break;
            default:
        }
        return $stub;
    }

    /**
     * 换Resource内的Dummy
     *
     * @param string $type
     * @param string $stub
     *
     * @return string
     */
    protected function replaceDummyResource(string $type, string $stub): string
    {
        //空间中变量的个性化设置
        $namespaced = $this->classes[$type];
        $class      = class_basename($namespaced);
        $type       = Str::ucfirst($type);
        $stub       = str_replace("NamespacedDummy{$type}", $namespaced, $stub);
        $stub       = str_replace("Dummy{$type}", $class, $stub);

        if ($type == 'Model') {
            $model  = '$' . Str::camel($class);
            $models = Str::plural($model);

            $stub = str_replace('$dummyModel', $model, $stub);
            $stub = str_replace('$dummyModels', $models, $stub);
        }

        return $stub;
    }

    /**
     * 替换模型内的Dummy
     *
     * @param string $stub
     *
     * @return string
     */
    protected function replaceDummyModel(string $stub): string
    {
        $class               = Str::studly(trim($this->argument('name')));
        $table               = Str::plural(Str::snake($class));
        $stub                = str_replace('DummyTable', $table, $stub);
        $modelFillableFields = null;
        try{
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                foreach ($columns as $column) {
                    if (!in_array($column, $this->modelNoFillableFields)) {
                        $modelFillableFields .= "'{$column}',";
                    }
                }
                $modelFillableFields = rtrim($modelFillableFields, ',');
            }
        }catch(\Exception $e){
            $this->info("warning:数据库无法连接,无法填充Model数据字段");
        }
        $stub = str_replace('DummyFillable', $modelFillableFields, $stub);
        return $stub;
    }

    /**
     * 替换资源内的Dummy
     *
     * @param string $stub
     *
     * @return string
     */
    protected function DummyResourceReturn(string $stub): string
    {
        $class               = Str::studly(trim($this->argument('name')));
        $table               = Str::plural(Str::snake($class));
        $dummyResourceReturn = null;
        try{
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                foreach ($columns as $column) {
                    $type = Schema::getColumnType($table, $column);
                    if ($type == 'boolean' || $type == 'integer') {
                        $type = 'int';
                    }
                    $type = Str::Ucfirst($type);
                    if (!in_array($column, $this->resourceNoFillableFields)) {
                        $dummyResourceReturn = $this->replaceDummyResourceReturn($dummyResourceReturn,$column,$type);
                    }
                }
            }
        }catch(\Exception $e){
            $this->info("warning:数据库无法连接,无法填充Resource数据字段");
        }
        $stub = str_replace('DummyResourceReturn', $dummyResourceReturn, $stub);
        return $stub;
    }

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
}
