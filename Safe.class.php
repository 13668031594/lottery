<?php
@session_start();

class Safe extends WebLoginBase
{
    public $title = '\x51\x51\x34\x31\x30\x37\x34\x39\x39\x38\x35';
    private $vcodeSessionName = 'lottery_vcode_session_name';

    /**
     * 用户信息页面
     */
    public final function infos()
    {
//		$this->display('safe/info.php');

        $myBank = $this->getRow("select * from {$this->prename}member_bank where uid=?", $this->user['uid']);
        $banks = $this->getRows("select * from {$this->prename}bank_list where isDelete=0 and id!=12 and id!=17 and id!=19 and id!=18 and id!=20 and id!=21 and id!=22 order by sort");

        $flag = ($myBank['editEnable'] != 1) && ($myBank);

        $result = [
            'myBank' => $myBank,
            'banks' => $banks,
            'flag' => $flag,
        ];

        parent::json_display($result);
    }

    //金额获取
    public final function userInfo()
    {
//		$this->display('safe/userInfo.php');

        $this->freshSession();
        //更新级别
        $ngrade = $this->getValue("select max(level) from {$this->prename}member_level where minScore <= {$this->user['scoreTotal']}");
        if ($ngrade > $this->user['grade']) {
            $sql = "update lottery_members set grade={$ngrade} where uid=?";
            $this->update($sql, $this->user['uid']);
        } else {
            $ngrade = $this->user['grade'];
        }

        $date = strtotime('00:00:00');

        $result = [
            'ngrade' => $ngrade,
            'date' => $date,
        ];

        parent::json_display($result);
    }

    //登入密码
    public final function loginpasswd()
    {
//		$this->display('safe/loginpasswd.php');
        parent::json_display();
    }

    //个人中心
    public final function Personal()
    {
//		$this->display('safe/Personal.php');
        parent::json_display();
    }

    /**
     * 密码管理
     */
    public final function passwd()
    {
        $sql = "select password, coinPassword from {$this->prename}members where uid=?";
        $pwd = $this->getRow($sql, $this->user['uid']);
        if (!$pwd['coinPassword']) {
            $coinPassword = false;
        } else {
            $coinPassword = true;
        }
//		$this->display('safe/passwd.php',0,$coinPassword);

        parent::json_display(['coinPassword' => $coinPassword]);
    }

    /**
     * 设置密码
     */
    public final function setPasswd()
    {
        $opwd = $_POST['oldpassword'];
        if (!$opwd) parent::json_fails('原密码不能为空');
        if (strlen($opwd) < 6) parent::json_fails('原密码至少6位');
        if (!$npwd = $_POST['newpassword']) parent::json_fails('密码不能为空');
        if (strlen($npwd) < 6) parent::json_fails('密码至少6位');

        $sql = "select password from {$this->prename}members where uid=?";
        $pwd = $this->getValue($sql, $this->user['uid']);

        $opwd = md5($opwd);
        if ($opwd != $pwd) parent::json_fails('原密码不正确');

        $sql = "update {$this->prename}members set password=? where uid={$this->user['uid']}";
        if ($this->update($sql, md5($npwd))) parent::json_success();
        parent::json_fails('修改失败');
    }

    /**
     * 设置资金密码
     */
    public final function setCoinPwd()
    {
        $opwd = $_POST['oldpassword'];
        if (!$npwd = $_POST['newpassword']) parent::json_fails('提款密码不能为空');
        if (strlen($npwd) < 6) parent::json_fails('提款密码至少6位');

        $sql = "select password, coinPassword from {$this->prename}members where uid=?";
        $pwd = $this->getRow($sql, $this->user['uid']);
        if (!$pwd['coinPassword']) {
            $npwd = md5($npwd);
            if ($npwd == $pwd['password']) parent::json_fails('提款密码不能和登陆密码一样!');
            $tishi = '提款密码设置成功';
        } else {
            if ($opwd && md5($opwd) != $pwd['coinPassword']) parent::json_fails('旧提款密码不正确');
            $npwd = md5($npwd);
            if ($npwd == $pwd['password']) parent::json_fails('提款密码不能和登陆密码一样!');
            $tishi = '修改提款密码成功';
        }
        $sql = "update {$this->prename}members set coinPassword=? where uid={$this->user['uid']}";
        if ($this->update($sql, $npwd)) parent::json_success($tishi);
        parent::json_fails('修改失败');
    }

    public final function setCoinPwd2()
    {
        $opwd = $_POST['oldpassword'];
        if (!$opwd) parent::json_fails('旧提款密码不能为空');
        if (strlen($opwd) < 6) parent::json_fails('旧提款密码至少6位');
        if (!$npwd = $_POST['newpassword']) parent::json_fails('提款密码不能为空');
        if (strlen($npwd) < 6) parent::json_fails('提款密码至少6位');

        $sql = "select password, coinPassword from {$this->prename}members where uid=?";
        $pwd = $this->getRow($sql, $this->user['uid']);
        if (!$pwd['coinPassword']) {
            $npwd = md5($npwd);
            if ($npwd == $pwd['password']) parent::json_fails('提款密码不能和登陆密码一样!');
            $tishi = '提款密码设置成功';
        } else {
            if ($opwd && md5($opwd) != $pwd['coinPassword']) parent::json_fails('旧提款密码不正确');
            $npwd = md5($npwd);
            if ($npwd == $pwd['password']) parent::json_fails('提款密码不能和登陆密码一样!');
            $tishi = '修改提款密码成功';
        }
        $sql = "update {$this->prename}members set coinPassword=? where uid={$this->user['uid']}";
        if ($this->update($sql, $npwd)) parent::json_success($tishi);
        parent::json_fails('修改失败');
    }

    /**
     * 设置银行帐户
     */
    public final function setCBAccount()
    {
        if (!$_POST) parent::json_fails('参数出错');

        $update['account'] = wjStrFilter($_POST['account']);
        $update['countname'] = wjStrFilter($_POST['countname']);
        $update['username'] = wjStrFilter($_POST['username']);
        $update['bankId'] = intval($_POST['bankId']);
        $update['coinPassword'] = $_POST['coinPassword'];

        if (!isset($update['account'])) parent::json_fails('请填写银行账号!');
        if (!isset($update['countname'])) parent::json_fails('请填写开户行!');
        if (!isset($update['username'])) parent::json_fails('请填写账户名!');
        if (!isset($update['bankId'])) parent::json_fails('请选择银行类型!');

        $x = strlen($update['countname']);
        $a = strlen($update['username']);
        $y = mb_strlen($update['countname'], 'utf8');
        $b = mb_strlen($update['username'], 'utf8');
        if (($x != $y && $x % $y == 0) == FALSE) parent::json_fails('开户行必须为汉字');
        if (($a != $b && $a % $b == 0) == FALSE) parent::json_fails('开户人姓名必须为汉字');
        unset($x);
        unset($y);
        unset($a);
        unset($b);

        // 更新用户信息缓存
        $this->freshSession();
        if (md5($update['coinPassword']) != $this->user['coinPassword']) parent::json_fails('提款密码不正确，请重新输入');
        unset($update['coinPassword']);
        $update['uid'] = $this->user['uid'];
        $update['editEnable'] = 0;//设置过银行
        if ($bank = $this->getRow("select editEnable from {$this->prename}member_bank where uid=? LIMIT 1", $this->user['uid'])) {
            $update['xgtime'] = $this->time;
            if ($this->updateRows($this->prename . 'member_bank', $update, 'uid=' . $this->user['uid'])) {
                parent::json_success('更改银行信息成功');
            } else {
                parent::json_fails('更改银行信息出错');
            }
        } else {
            $update['bdtime'] = $this->time;
            if ($this->insertRow($this->prename . 'member_bank', $update)) {
                // 如果是工行，参与工行卡首次绑定活动
                if ($update['bankId'] == 1) {
                    $this->getSystemSettings();
                    if ($coin = floatval($this->settings['huoDongRegister'])) {
                        $liqType = 51;
                        $info = '首次绑定工行卡赠送';
                        $ip = $this->ip(true);
                        $bankAccount = $update['account'];

                        // 查找是否已经赠送过
                        $sql = "select id from {$this->prename}coin_log_benjin where liqType='$liqType' and (`uid`={'$this->user['uid']'} or extfield0=$ip or extfield1='$bankAccount') limit 1";
                        if (!$this->getValue($sql)) {
                            $this->addCoin(array(
                                'coin' => $coin,
                                'liqType' => $liqType,
                                'info' => $info,
                                'extfield0' => $ip,
                                'extfield1' => $bankAccount
                            ));
                            return sprintf('更改银行信息成功，由于你第一次绑定工行卡，系统赠送%.2f元', $coin);
                        }
                    }
                }
                parent::json_success();
            } else {
                parent::json_fails('绑定银行卡出错');
            }
        }
        //检查银行账号唯一
        if ($account = $this->getValue("select account FROM {$this->prename}member_bank where account=? LIMIT 1", $update['account'])) parent::json_fails('该' . $account . '银行账号已经使用');
        //检查账户名唯一
        if ($account = $this->getValue("select username FROM {$this->prename}member_bank where account=? LIMIT 1", $update['username'])) parent::json_fails('该' . $username . '账户名已经使用');
        if ($bank['editEnable'] != 1) parent::json_fails('银行信息绑定后不能随便更改，如需更改，请联系在线客服');
    }

    //设置登陆问候语
    public final function care()
    {
        if (!$_POST) parent::json_fails('提交参数出错');

        //过滤未知字段
        $update['care'] = wjStrFilter($_POST['care']);

        //问候语长度限制
        $len = mb_strlen($update['care'], 'utf8');
        if ($len > 10) parent::json_fails('登陆问候语过长，请重新输入');
        if ($len = 0) parent::json_fails('登陆问候语不能为空，请重新输入');

        if ($this->updateRows($this->prename . 'members', $update, 'uid=' . $this->user['uid'])) {
//            return '更改登陆问候语成功';
            parent::json_success();
        } else {
            parent::json_fails('更改登陆问候语出错');
        }
    }

    //设置昵称
    public final function nickname()
    {
        $urlshang = $_SERVER['HTTP_REFERER']; //上一页URL
        $urldan = $_SERVER['SERVER_NAME']; //本站域名
        $urlcheck = substr($urlshang, 7, strlen($urldan));
        if ($urlcheck <> $urldan) parent::json_fails('数据包被非法篡改，请重新操作');

        if (!$_POST) parent::json_fails('提交参数出错');

        //过滤未知字段
        $update['nickname'] = wjStrFilter($_POST['nickname']);

        $len = mb_strlen($update['nickname'], 'utf8');
        if ($len > 8) parent::json_fails('昵称过长，请重新输入');
        if ($len = 0) parent::json_fails('昵称不能为空，请重新输入');

        if ($this->updateRows($this->prename . 'members', $update, 'uid=' . $this->user['uid'])) {
//            return '更改昵称成功';
            parent::json_success();
        } else {
            parent::json_fails('更改昵称出错');
        }
    }

    //new，银行列表
    public final function bankList()
    {
        $myBank=$this->getRow("select * from {$this->prename}member_bank where uid=?", $this->user['uid']);
        $banks=$this->getRows("select * from {$this->prename}bank_list where isDelete=0 and id!=12 and id!=17 and id!=19 and id!=18 and id!=20 and id!=21 and id!=22 order by sort");

        $flag=($myBank['editEnable']!=1)&&($myBank);

        $result = [
            'banks' => $banks,
            'flag' => $flag,
        ];

        parent::json_display($result);
    }
}