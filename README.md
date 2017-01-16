依赖：

1. Laravel \DB 类
2. 数据库短信发送记录表
3. 验证发送类

ddphp/sms_code

SMSCode\Verify

发送验证码
getCode($phone);  // 1234 or false

核对验证码
check($phone, $code);  // return bool

获取错误信息

getError();  StdClass  code/msg/data

model($tableName, $idField);

	private $model;
	private $currentTime;  // 当前系统时间戳

	private $MaxNum;  // 最大获取次数
	private $IntervalTime;  // 获取频率 s
	$verifyTimes = 3;
	$yxTime = 10 * 60;

1. 验证码获取次数限制  1
2. 验证码获取频率限制  2
3. 验证码有效期限制    3
4. 验证码验证次数限制  4


## 初始化

清空过期记录


