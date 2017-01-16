<?php
namespace SMSCode;

class Verify
{
    private $tableName;
    private $idField;
    private $idValue;

    private $phone;

    private $timestamp;  // 当前系统时间戳
    private $error;

    private $sentMaxTimes = 3;  // 最大获取次数
    private $sentIntervalTime = 60;  // 获取频率 s
    private $verifyTimes = 3;
    private $yxTime = 600;  // 有效时间 s

    public function __construct()
    {
        $this->timestamp = time();

        $this->error = new \StdClass;
    }

    public function setModel($tableName, $idField, $idValue)
    {
        $this->tableName = $tableName;
        $this->idField = $idField;
        $this->idValue = $idValue;

        $currDate = date('Y-m-d');
        if (\Cache::get('sms_code_'.$tableName, '') !== $currDate) {
            \DB::table($tableName)->where('created_time', '<>', $currDate)->delete();
            \Cache::put('sms_code_'.$tableName, $currDate);
        }

        return $this;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    public function setSentMaxTimes($times)
    {
        $this->sentMaxTimes = $times;
        return $this;
    }

    public function setSentIntervalTime($time)
    {
        $this->sentIntervalTime = $time;
        return $this;
    }

    public function setVerifyTimes($times)
    {
        $this->verifyTimes = $times;
        return $this;
    }

    public function setYxTime($time)
    {
        $this->yxTime = $time;
        return $this;
    }

    public function getCode()
    {
        $code = rand(1000, 9999);
        $date = date('Y-m-d');
        $dateTime = date('Y-m-d H:i:s');

        $smsModel = \DB::table('sms_binds')->where($this->idField, $this->idValue)->first();
        if ($smsModel) {
            if ($smsModel->sent_times >= $this->sentMaxTimes) {
                $this->error->code = 1;
                $this->error->msg = '验证码获取次数限制';
                return false;
            }

            $updatedTimestamp = strtotime($smsModel->updated_time);
            $passedTime = $this->timestamp - $updatedTimestamp;
            if ($passedTime < $this->sentIntervalTime) {
                $this->error->code = 2;
                $this->error->msg  = '验证码获取频率限制';
                $this->error->data = ['passed_time' => $passedTime];
                return false;
            }

            $insertSms = \DB::table($this->tableName)
                ->where($this->idField, $this->idValue)
                ->where('created_time', $date)
                ->update([
                    'phone' => $this->phone,
                    'code' => $code,
                    'sent_times' => $smsModel->sent_times + 1,
                    'verify_times' => 0,
                    'updated_time' => $dateTime
                ]);
        } else {
            $insertSms = \DB::table($this->tableName)->insert([
                $this->idField => $this->idValue,
                'phone' => $this->phone,
                'code' => $code,
                'sent_times' => 1,
                'verify_times' => 0,
                'created_time' => $date,
                'updated_time' => $dateTime
            ]);
        }

        if ($insertSms) {
            return $code;
        } else {
            $this->error->code = 3;
            $this->error->msg  = '保存验证码失败';
            return false;
        }
    }

    public function check($code)
    {
        $smsModel = \DB::table($this->tableName)
            ->where($this->idField, $this->idValue)
            ->where('created_time', date('Y-m-d'))
            ->first();

        if (!$smsModel) {
            $this->error->code = 4;
            $this->error->msg  = '请先获取验证码';
            return false;
        }

        if ($this->timestamp - strtotime($smsModel->updated_time) > $this->yxTime) {
            $this->error->code = 5;
            $this->error->msg  = '验证码有效期限制';
            return false;
        }

        if ($smsModel->verify_times >= $this->verifyTimes) {
            $this->error->code = 6;
            $this->error->msg  = '验证码验证次数限制';
            return false;
        }

        \DB::table($this->tableName)
            ->where($this->idField, $this->idValue)
            ->where('created_time', date('Y-m-d'))
            ->update([
                'verify_times' => $smsModel->verify_times + 1
            ]);

        $verify = ($smsModel->phone === $this->phone) && ($smsModel->code === $code);

        if ($verify) {
            return true;
        } else {
            $this->error->code = 7;
            $this->error->msg  = '验证码验证失败';
            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }
}