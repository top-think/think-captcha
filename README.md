# xuezhitech\captcha
thinkphp5.1 验证码类库 适用于接口调用

## 控制器里使用
~~~
use xuezhitech\captcha\Captcha;
初始化
$captcha = new Captcha();
return $captcha->entry();
返回
captcha_key 验证码key
src         验证图片(base64)

验证
$captcha_key 验证码key
$code        用户输入的code
$captcha = new Captcha();
if( !$captcha->check($captcha_key,$code) ){
    //验证失败
}
~~~



