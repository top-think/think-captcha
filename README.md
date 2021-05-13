# think-captcha

thinkphp6 验证码类库

## 安装
> composer require topthink/think-captcha



## 使用

### 在控制器中输出验证码

在控制器的操作方法中使用

~~~
public function captcha($id = '')
{
	return captcha($id);
}
~~~
然后注册对应的路由来输出验证码


### 模板里输出验证码

首先要在你应用的路由定义文件中，注册一个验证码路由规则。

~~~
\think\facade\Route::get('captcha/[:id]', "\\think\\captcha\\CaptchaController@index");
~~~

然后就可以在模板文件中使用
~~~
<div>{:captcha_img()}</div>
~~~
或者
~~~
<div><img src="{:captcha_src()}" alt="captcha" /></div>
~~~
> 上面两种的最终效果是一样的


### 控制器里验证

使用TP的内置验证功能即可
~~~
$this->validate($data,[
    'captcha|验证码'=>'require|captcha'
]);
~~~
或者手动验证
~~~
if(!captcha_check($captcha)){
 //验证失败
};
~~~

### 在前端分类API接口输出验证码

在API接口方法中使用
~~~
use think\captcha\facade\Captcha;

/**
* @desc: 获取验证码
* @return Response
*/
public function captcha(): Response
{
    $res = Captcha::createBase64();
    return json(['msg'=>'ok','captcha'=>$res]);
}
~~~
然后注册对应的路由来输出验证码