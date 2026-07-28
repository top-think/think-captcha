# think-captcha

> ThinkPHP 验证码扩展

本扩展提供 **两种验证码实现**，按你的项目场景选择：

| 类 | 存储策略 | 适用场景 |
|---|---|---|
| `CaptchaService`（**推荐**） | `Cache + Key` | 前后端分离、跨域、SPA、小程序、App、分布式多节点 |
| `Captcha`（传统类） | `Session` | 单体项目、传统服务端渲染、和 ThinkPHP Session 深度绑定 |

`CaptchaService` 只做三件事 —— 生成、校验、删除 —— **返回纯数据**，路由 / 响应格式 / CORS / 限流 均由你在业务控制器里自行掌控，灵活度最高。

---

## 安装

```bash
composer require watsonhaw/think-captcha
```

> 环境要求：
> - PHP >= 7.4（推荐 PHP 8+）
> - 启用 GD 扩展（`ext-gd`）、Mbstring（`ext-mbstring`）
> - ThinkPHP 6.0 / 8.0 通用

安装后会自动注册服务（`extra.think`），配置文件会被发布到 `config/captcha.php`。

---

## 5 分钟上手

### 1. 在你自己的业务控制器里写两个方法

```php
<?php
namespace app\controller;

use think\captcha\CaptchaService;
use think\Request;
use think\Response;

class Captcha
{
    /**
     * 获取验证码
     * GET  /api/captcha
     *      /api/captcha/:scene   (scene 对应 config 子配置，如 login / register)
     */
    public function index(Request $request, ?string $scene = null): Response
    {
        return json([
            'code' => 1,
            'msg'  => 'success',
            'data' => CaptchaService::generate($scene),
            // data:
            //   key    : 唯一标识，提交校验时原样带回
            //   img    : data:image/png;base64,...  直接丢给 <img :src>
            //   expire : 有效秒数
        ])->header([
            'Access-Control-Allow-Origin'      => $request->header('origin', '*'),
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }

    /**
     * 校验验证码（独立接口；更多时候你会把校验直接写在 login/register 里）
     * POST /api/captcha/check
     * body: { key: "...", captcha: "a3b9" }
     */
    public function check(Request $request): Response
    {
        $ok = CaptchaService::validate(
            (string)$request->param('key'),
            (string)$request->param('captcha')
        );
        return json([
            'code' => $ok ? 1 : 0,
            'msg'  => $ok ? 'ok' : '验证码错误或已过期',
        ]);
    }

    /**
     * 刷新验证码（可选：传旧 key 立即失效）
     * GET /api/captcha/refresh?key=xxx
     */
    public function refresh(Request $request, ?string $scene = null): Response
    {
        $old = (string)$request->param('key');
        if ($old !== '') CaptchaService::remove($old);
        return json(['code' => 1, 'data' => CaptchaService::generate($scene)]);
    }
}
```

### 2. 在登录/注册等接口里校验

```php
public function login(Request $request)
{
    $key     = (string)$request->post('key');
    $captcha = (string)$request->post('captcha');
    $account = (string)$request->post('account');
    $pass    = (string)$request->post('password');

    // ⬇ 先校验验证码
    if (!CaptchaService::validate($key, $captcha)) {
        return json(['code' => 0, 'msg' => '验证码错误或已过期']);
    }

    // 再校验账号密码...
}
```

### 3. 前端（Vue / Axios 示例）

```vue
<template>
  <div>
    <img :src="captcha.img" @click="refresh" style="cursor:pointer" alt="captcha" />
    <input v-model="input.captcha" placeholder="请输入验证码" />
    <button @click="submit">登录</button>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import axios from 'axios'

const captcha = reactive({ key: '', img: '', expire: 120 })
const input   = reactive({ account: '', password: '', captcha: '' })

async function load(scene = 'login') {
  const { data } = await axios.get(`/api/captcha/${scene}`)
  if (data.code === 1) Object.assign(captcha, data.data)
}
async function refresh() {
  const { data } = await axios.get('/api/captcha/refresh', {
    params: { key: captcha.key }
  })
  if (data.code === 1) Object.assign(captcha, data.data)
}
async function submit() {
  await axios.post('/api/login', {
    ...input,
    key: captcha.key,   // ⚠️ 一定要把 key 一起回传
  })
}

onMounted(load)
</script>
```

---

## 三种调用风格，任挑一种（均走 `CaptchaService` 底层）

### ✅ 静态类（推荐，最清晰）

```php
use think\captcha\CaptchaService;

$data = CaptchaService::generate();            // 生成
$ok   = CaptchaService::validate($key, $code); // 校验
CaptchaService::remove($key);                  // 删除
```

### ✅ Facade

```php
use think\captcha\facade\Captcha;

$data = Captcha::generate('login');
$ok   = Captcha::validate($key, $code);
Captcha::remove($key);
```

### ✅ 助手函数

```php
$data = captcha_generate('register');          // 生成
$ok   = captcha_validate($key, $userInput);    // 校验
captcha_remove($key);                          // 删除
```

---

## 传统 `Captcha` 类用法（Session 存储，单体项目）

如果你的项目是传统 MVC + Session，不做前后端分离，可以直接使用原版风格的 `Captcha` 类（配合 ThinkPHP Container 自动注入）：

```php
<?php
namespace app\controller;

use think\captcha\Captcha;
use think\Request;
use think\Response;

class Login
{
    // 直接输出验证码图片
    public function captcha(Captcha $captcha, ?string $config = null): Response
    {
        return $captcha->create($config);  // 返回 image/png Response
    }

    // 校验
    public function check(Captcha $captcha, Request $request): bool
    {
        return $captcha->check((string)$request->param('code'));
    }
}
```

> 💡 传统 `Captcha` 类额外支持：**中文验证码**、**背景透明度 alpha**、**指定字体文件 fontttf**、**API 模式（返回 [code+img] 数组）** 等特性，详见下文配置对比表。

---

## 配置（`config/captcha.php`）

### 全部配置项速查（两个类都支持的 12 项）

```php
<?php
return [
    // 验证码位数
    'length'      => 4,
    // 字符集合（默认已剔除 0/O/1/I 等易混淆字符）
    'codeSet'     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    // 过期时间（秒）
    'expire'      => 180,
    // 算术验证码 (true 时 length / codeSet 忽略)
    'math'        => false,
    // 画干扰点
    'useNoise'    => true,
    // 画干扰线
    'useCurve'    => true,
    // 使用 assets/bgs/ 下的图片做背景（平铺拉伸到验证码尺寸，true 时忽略 bg 颜色）
    'useImgBg'    => false,
    // 背景色 [R, G, B]（useImgBg=false 或背景图加载失败时生效）
    'bg'          => [243, 251, 254],
    // 图片尺寸（填 0 则根据 fontSize 自动计算）
    'imageH'      => 0,
    'imageW'      => 0,
    // 字号（px）
    'fontSize'    => 22,
    // 缓存键前缀（多项目共用 Redis 时改一改防冲突）
    'cachePrefix' => 'captcha_',
];
```

### ⚠️ 两个实现类的配置支持差异

| 参数 | CaptchaService（推荐） | Captcha（传统 Session 类） | 备注 |
|---|---|---|---|
| `length` | ✅ 支持，下限 1 | ✅ 支持 | - |
| `codeSet` | ✅ 空串自动回退默认 | ✅ 支持 | - |
| `expire` | ✅ 作为 Cache TTL，下限 10s | ✅ 配置读取但**实际未使用**（依赖 PHP Session 过期） | ⚠️ Captcha 类 expire 字段是"假支持" |
| `math` | ✅ `+ / - / ×` 三种运算 | ✅ 仅支持 `+` 加法 | ⚠️ 两者算法不同 |
| `useNoise` | ✅ 按面积计算干扰点密度 | ✅ 调用 `writeNoise()` | - |
| `useCurve` | ✅ 两段贝塞尔风格折线 | ✅ 正弦函数曲线 | 视觉效果不同 |
| `useImgBg` | ✅ JPG / PNG / GIF 均可 | ✅ 仅支持 **JPG** | ⚠️ Captcha 放 PNG/GIF 会被静默忽略 |
| `bg` | ✅ 少于 3 项自动回退 | ✅ 支持 | - |
| `imageH` | ✅ 自动计算 + 下限 24px | ✅ 自动计算 | 公式不同 |
| `imageW` | ✅ 自动计算 + 下限 60px | ✅ 自动计算 | 公式不同 |
| `fontSize` | ✅ 下限 10px | ✅ 支持 | - |
| `cachePrefix` | ✅ Cache Key 前缀 | ❌ 不使用（用 Session） | - |

### 🔧 Captcha 类**额外独有**的配置项（`config/captcha.php` 可追加）

以下 6 项只有传统 `Captcha` 类会读取，`CaptchaService` 不支持：

```php
return [
    // ... 上面 12 项 ...

    // 👇 以下仅 Captcha（Session 类）支持

    'useZh'   => false,                 // 启用中文验证码（true 时用 assets/zhttfs 字体 + zhSet 字符）
    'zhSet'   => '我们的祖国...',        // 中文字符集合（useZh=true 时生效）
    'fontttf' => '',                    // 指定字体文件名（如 '3.ttf'），空 = 从 assets/ttfs 随机
    'alpha'   => 0,                     // 背景透明度：0=完全不透明，127=完全透明
    'api'     => false,                 // create() 返回类型：false=Response；true=[code, img] 数组
    'interfere' => [                    // 自定义干扰项（高级用法），值为可调用 Closure
        // 'myCustom' => fn($im, $w, $h, $fs, $c) => /* 画点线 ... */,
    ],
];
```

### 🎯 场景化子配置示例（login / register 分组）

`CaptchaService::generate('login')` / `captcha_generate('register')` 会自动合并二级数组：

```php
return [
    'length'   => 4,
    'expire'   => 180,
    'fontSize' => 22,

    // 登录：4 位数字 + 长过期（减少输入失败）
    'login' => [
        'length'  => 4,
        'codeSet' => '0123456789',
        'expire'  => 300,
    ],

    // 注册：6 位混合字符 + 短过期（防垃圾注册）
    'register' => [
        'length'  => 6,
        'useNoise'=> true,
        'useCurve'=> true,
        'expire'  => 60,
    ],

    // 后台：算术 + 更大字体
    'admin' => [
        'math'     => true,
        'fontSize' => 26,
    ],
];
```

---

## 设计说明

| 关注点 | 做法 |
|---|---|
| **前后端解耦** | 不使用 Session，改为 `Cache` 存验证码，用 `key` 关联；图片直接返回 `data:image/png;base64` |
| **一次性** | 校验通过后自动从 Cache 删除，防重放 |
| **分布式** | 只要 TP 项目的 `cache.php` 配的是共享缓存（Redis/Memcached），任意节点都能校验 |
| **无副作用** | `CaptchaService` 不注册路由、不发响应、不加 CORS 头，完全不侵入项目结构 |
| **安全** | 用户输入与缓存值做 **大小写不敏感** 比对，体验更友好 |
| **图片资源** | 优先使用 `assets/ttfs` 下的 TTF/OTF 字体（中文则用 `assets/zhttfs`），找不到自动降级到 GD 内置字体 |

---

## 旧函数兼容性（从原版 `think-captcha` 升级的同学看这里）

原版 `captcha()` / `captcha_check()` / `captcha_src()` / `captcha_img()` 依然可用，只是底层换成了 `CaptchaService`：

```php
// 原版直接出图片（不推荐前后端分离项目用，但保留了）
return captcha();            // Response: image/png binary

// 原版校验：额外传 key 即可
captcha_check($code, $key);  // $key 可省略，省略时会尝试从 request()->param('key') 取值
```

> ⚠️ `captcha_src()` / `captcha_img()` 依赖你自己注册 `/captcha/[:config]` 路由（本扩展不再自动注册）。

---

## 常见问题

**Q: 为什么不直接把验证码字符串塞到响应里？**
A: 那等于把答案告诉前端，完全失去校验意义。所以前端只拿 **图片 + key**，key 只是一个随机索引，不包含答案信息。

**Q: 刷新时旧 key 的验证码还能被撞库吗？**
A: 调 `CaptchaService::remove($oldKey)` 就会立刻失效；不调的话等到 `expire` 秒后缓存也会自动过期。

**Q: 支持中文验证码吗？**
A: ✅ 两个类都内置中文字体。使用方式不同：
- 传统 `Captcha` 类：`config('captcha.useZh', true)` 即可（自动启用 `assets/zhttfs/1.otf` + 内置大字符串 `zhSet`）
- `CaptchaService`：将 `codeSet` 直接设为任意中文字符串即可，例如 `'codeSet' => '天地玄黄宇宙洪荒日月盈昃辰宿列张'`

**Q: `useImgBg=true` 放了 PNG 背景为什么没生效？**
A: 传统 `Captcha` 类只支持 **JPG**（`background()` 里写死 `substr($file,-4) == '.jpg'`），放 PNG/GIF 会静默降级到纯色 `bg`；改用 `CaptchaService` 类即支持 JPG/PNG/GIF 三种。

**Q: `math=true` 为什么 Captcha 和 CaptchaService 出来的不一样？**
A: 两者算法独立实现：传统 `Captcha` 只有 `X + Y =` 加法；`CaptchaService` 会随机出 `+ / - / ×`，且会自动把减法调成非负数、乘法控制在 1-5 防答案过大。

**Q: 验证码存储在 Cache/Session 会不会泄露？**
A: 不会。存的不是明文，`Captcha` 用 `password_hash()` 加盐 bcrypt 哈希；`CaptchaService` 虽然存明文小写字符串，但 key 是 `md5(uniqid+mt_rand)` 的随机串，暴力猜对 key 的概率忽略不计，而且校验通过后会立即删除（一次性）。

---

## 开发 & 贡献

本仓库自带完整的 PHPUnit 12 单元测试、PHPStan 静态分析、PHP-CS-Fixer 代码风格检查。

```bash
# 运行全部测试（39 tests / 60 assertions）
composer test
vendor/bin/phpunit

# 运行单个测试文件
vendor/bin/phpunit tests/CaptchaServiceTest.php
vendor/bin/phpunit tests/CaptchaTest.php
vendor/bin/phpunit tests/HelperTest.php
vendor/bin/phpunit tests/CaptchaFacadeTest.php

# 静态分析（PHPStan level 1）
composer analyze

# 代码风格检查 + 自动修复
composer check-style
composer fix-style
```

测试覆盖范围：
- `CaptchaServiceTest`：generate / validate / remove 全流程、数学验证码、大小写不敏感、缓存删除策略、子配置合并（10 tests）
- `CaptchaTest`：check 校验（成功/失败/大小写/一次性删除）、create() API 模式 PNG data URI 验证、生成-校验完整闭环（10 tests）
- `HelperTest`：captcha_generate/validate/src/img/check/remove 15 个辅助函数（15 tests）
- `CaptchaFacadeTest`：Facade 代理到 CaptchaService 的正确转发（4 tests）