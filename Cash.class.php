<?php
@session_start();

class Cash extends WebLoginBase
{
    public $pageSize = 20;
    private $vcodeSessionName = 'lottery_vcode_session_name';

    public final function toCash()
    {
//		$this->display('cash/to-cash.php');

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

        $bank = $this->getRow("select m.*,b.logo logo, b.name bankName from {$this->prename}member_bank m, {$this->prename}bank_list b where b.isDelete=0 and m.bankId=b.id and m.uid=? limit 1", $this->user['uid']);

        if($bank['bankId']) {

            $bank = $this->getRow("select m.*,b.logo logo, b.name bankName from {$this->prename}member_bank m, {$this->prename}bank_list b where b.isDelete=0 and m.bankId=b.id and m.uid=? limit 1", $this->user['uid']);
            $this->freshSession();
            $date = strtotime('00:00:00');
            $date2 = strtotime('00:00:00');
            $time = strtotime(date('Y-m-d', $this->time));
            $cashAmout = 0;
            $rechargeAmount = 0;
            $rechargeTime = strtotime('00:00');
            if ($this->settings['cashMinAmount']) {
                $cashMinAmount = $this->settings['cashMinAmount'] / 100;
                $gRs = $this->getRow("select sum(case when rechargeAmount>0 then rechargeAmount else amount end) as rechargeAmount from {$this->prename}member_recharge where  uid={$this->user['uid']} and state in (1,2,9) and isDelete=0 and rechargeTime>=" . $rechargeTime);
                if ($gRs) {
                    $rechargeAmount = $gRs["rechargeAmount"];
                }
            }
            $cashAmout = $this->getValue("select sum(mode*beiShu*actionNum) from {$this->prename}bets where isDelete=0 and actionTime>={$rechargeTime} and uid={$this->user['uid']}");
            $times = $this->getValue("select count(*) from {$this->prename}member_cash where actionTime>=$time and uid=?", $this->user['uid']);
        }
        $result = [
            'bank' => $bank,
            'cashAmout' => isset($cashAmout) ? $cashAmout : null,
            'cashMinAmount' => isset($cashMinAmount) ? $cashMinAmount : null,
            'rechargeAmount' => isset($rechargeAmount) ? $rechargeAmount : null,
            'ngrade' => $ngrade,
            'times' => isset($times) ? $times : null,
        ];

        parent::json_display($result);
    }

    public final function toCashLog()
    {
//        $this->display('cash/to-cash-log.php');
        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));

        parent::display(['fromTime' => $fromTime, 'toTime' => $toTime]);
    }

    public final function toCashResult()
    {
//        $this->display('cash/cash-result.php');
        $txcount = $this->getValue("select count(id) from {$this->prename}member_cash  where state=1");

        parent::json_display(['txcount' => $txcount]);
    }


    public final function recharge()
    {
//        $this->display('cash/recharge.php');

        $sql = "select * from {$this->prename}bank_list b, {$this->prename}sysadmin_bank m where m.admin=1 and m.enable=1 and b.isDelete=0 and b.id=m.bankId and b.id not in(12,0,0,0)";
        $banks = $this->getRows($sql);

        $set = $this->getSystemSettings();

        parent::json_display(['banks' => $banks, 'set' => $set]);
    }

    public final function rechargeLog()
    {
        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));

        parent::json_display(['fromTime' => $fromTime, 'toTime' => $toTime]);
    }

    public final function rechargelist()
    {
//        $this->display('cash/recharge-list.php');
        $sql = "select a.rechargeId,a.amount,a.rechargeAmount,a.info,a.state,a.actionTime,b.name as bankName from {$this->prename}member_recharge a left join {$this->prename}bank_list b on b.id=a.bankId where a.isDelete=0 and a.uid={$this->user['uid']}";
        if ($_GET['fromTime'] && $_GET['endTime']) {
            $fromTime = strtotime($_GET['fromTime']);
            $endTime = strtotime($_GET['endTime']);
            $sql .= " and a.actionTime between $fromTime and $endTime";
        } elseif ($_GET['fromTime']) {
            $sql .= ' and a.actionTime>=' . strtotime($_GET['fromTime']);
        } elseif ($_GET['endTime']) {
            $sql .= ' and a.actionTime<' . (strtotime($_GET['endTime']));
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $sql .= ' and a.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }
        $sql .= ' order by a.id desc';

        $pageSize = 10;

        $list = $this->getPage($sql, $this->page, $pageSize);

        parent::json_display(['list' => $list]);
    }


    public final function toCashlist()
    {
//        $this->display('cash/to-cash-list.php');

        $sql = "select c.*, b.name bankName from {$this->prename}member_cash c, {$this->prename}bank_list b where c.bankId=b.id and uid={$this->user['uid']} and b.isDelete=0 and c.isDelete=0";
        if ($_GET['fromTime'] && $_GET['endTime']) {
            $fromTime = strtotime($_GET['fromTime']);
            $endTime = strtotime($_GET['endTime']);
            $sql .= " and actionTime between $fromTime and $endTime";
        } elseif ($_GET['fromTime']) {
            $sql .= ' and actionTime>=' . strtotime($_GET['fromTime']);
        } elseif ($_GET['endTime']) {
            $sql .= ' and actionTime<' . (strtotime($_GET['endTime']));
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $sql .= ' and actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }

        $stateName = array('已到帐', '正在办理', '已取消', '已支付', '失败');

        $list = $this->getPage($sql, $this->page, $this->pageSize);

        parent::json_display(['stateName' => $stateName, 'list' => $list]);
    }

    /**
     * 点卡充值
     */
    public final function card()
    {
//        $this->display('cash/card.php');

        $ngrade = $this->getValue("select max(level) from {$this->prename}member_level where minScore <= {$this->user['scoreTotal']}");
        if ($ngrade > $this->user['grade']) {
            $sql = "update lottery_members set grade={$ngrade} where uid=?";
            $this->update($sql, $this->user['uid']);
        } else {
            $ngrade = $this->user['grade'];
        }

        $date = strtotime('00:00:00');

        parent::json_display(['ngrage' => $ngrade, 'date' => $date]);
    }

    public final function yanzheng()
    {
//        if (!$_POST['amount']) throw new Exception('提交数据出错，请重新操作!');
        if (!$_POST['amount']) parent::json_fails('提交数据出错，请重新操作!');

        //检查卡密是否正确
        $sql = "select * from {$this->prename}card where card_str=?";
        $isRight = $this->getRow($sql, $_POST['amount']);
//        if (!$isRight) throw new Exception('卡密不存在');
        if (!$isRight) parent::json_fails('卡密不存在');
//        if ($isRight['status'] == 1) throw new Exception('卡密已使用');
        if ($isRight['status'] == 1) parent::json_fails('卡密已使用');

        $update['id'] = $isRight['id'];
        $update['uid'] = $this->user['uid'];
        $update['useranme'] = $this->user['useranme'];
        $update['use_time'] = time();
        $update['status'] = 1;
        $sql = "update {$this->prename}card set uid={$this->user['uid']}, username='{$this->user['username']}', use_time={$update['use_time']}, status={$update['status']} where id={$isRight['id']}";
        $cardResult = $this->update($sql);

        $coinResult = $this->addCoin(array(
            //'uid'=>$this->user['uid'],
            'coin' => intval($isRight['price']),
            'liqType' => 111,
            'extfield0' => 0,
            'extfield1' => 0,
            'info' => "卡密充值-{$_POST['amount']}"
        ));


//        return '充值成功';
        parent::json_success('充值成功');

    }

    /**
     * 提现申请
     */
    public final function ajaxToCash()
    {
//        if (!$_POST) throw new Exception('参数出错');
        if (!$_POST) parent::json_fails('参数出错');

        $para['amount'] = $_POST['amount'];
        $para['coinpwd'] = $_POST['coinpwd'];
        $bank = $this->getRow("select username,account,bankId from {$this->prename}member_bank where uid=? limit 1", $this->user['uid']);
        $para['username'] = $bank['username'];
        $para['account'] = $bank['account'];
        $para['bankId'] = $bank['bankId'];
//        if (!ctype_digit($para['amount'])) throw new Exception('提现金额包含非法字符');
        if (!ctype_digit($para['amount'])) parent::json_fails('提现金额包含非法字符');
//        if ($para['amount'] <= 0) throw new Exception("提现金额只能为正整数");
        if ($para['amount'] <= 0) parent::json_fails("提现金额只能为正整数");
//        if ($para['amount'] > $this->user['coin']) throw new Exception("提款金额大于可用余额，无法提款");
        if ($para['amount'] > $this->user['fenhong']) parent::json_fails("提款金额大于可用分红，无法提款");
//        if ($this->user['coin'] <= 0) throw new Exception("可用余额为零，无法提款");
        if ($this->user['分红'] <= 0) throw new Exception("可用分红为零，无法提款");

        //提示时间检查
        $baseTime = strtotime(date('Y-m-d ', $this->time) . '06:00');
        $fromTime = strtotime(date('Y-m-d ', $this->time) . $this->settings['cashFromTime'] . ':00');
        $toTime = strtotime(date('Y-m-d ', $this->time) . $this->settings['cashToTime'] . ':00');
        if ($toTime < $baseTime) $toTime += 24 * 3600;
        if ($this->time < $baseTime) $fromTime -= 24 * 3600;
//        if ($this->time < $fromTime || $this->time > $toTime) throw new Exception("提现时间：从" . $this->settings['cashFromTime'] . "到" . $this->settings['cashToTime']);
        if ($this->time < $fromTime || $this->time > $toTime) parent::json_fails("提现时间：从" . $this->settings['cashFromTime'] . "到" . $this->settings['cashToTime']);

        //消费判断
        $cashAmout = 0;
        $rechargeAmount = 0;
        $rechargeTime = strtotime('00:00');
        if ($this->settings['cashMinAmount']) {
            $cashMinAmount = $this->settings['cashMinAmount'] / 100;
            $gRs = $this->getRow("select sum(case when rechargeAmount>0 then rechargeAmount else amount end) as rechargeAmount from {$this->prename}member_recharge where  uid={$this->user['uid']} and state in (1,2,9) and isDelete=0 and rechargeTime>=" . $rechargeTime);
            if ($gRs) {
                $rechargeAmount = $gRs["rechargeAmount"] * $cashMinAmount;
            }
            if ($rechargeAmount) {
                //消费总额
                $cashAmout = $this->getValue("select sum(mode*beiShu*actionNum) from {$this->prename}bets where actionTime>={$rechargeTime} and uid={$this->user['uid']} and isDelete=0");
//                if (floatval($cashAmout) < floatval($rechargeAmount)) throw new Exception("消费满" . $this->settings['cashMinAmount'] . "%才能提现");
            }
        }//消费判断结束
        $this->beginTransaction();
        try {
            $this->freshSession();
//            if ($this->user['coinPassword'] != md5($para['coinpwd'])) throw new Exception('资金密码不正确');
            if ($this->user['coinPassword'] != md5($para['coinpwd'])) parent::json_fails('资金密码不正确');
            unset($para['coinpwd']);

//            if ($this->user['coin'] < $para['amount']) throw new Exception('你帐户资金不足');
            if ($this->user['fenhong'] < $para['amount']) parent::json_fails('你帐户资金不足');

            // 查询最大提现次数与已经提现次数
            $time = strtotime(date('Y-m-d', $this->time));
            if ($times = $this->getValue("select count(*) from {$this->prename}member_cash where actionTime>=$time and uid=?", $this->user['uid'])) {
                $cashTimes = $this->getValue("select maxToCashCount from {$this->prename}member_level where level=?", $this->user['grade']);
//                if ($times >= $cashTimes) throw new Exception('对不起，今天你提现次数已达到最大限额，请明天再来');
                if ($times >= $cashTimes) parent::json_fails('对不起，今天你提现次数已达到最大限额，请明天再来');
            }

            // 插入提现请求表
            $para['actionTime'] = $this->time;
            $para['uid'] = $this->user['uid'];
//            if (!$this->insertRow($this->prename . 'member_cash', $para)) throw new Exception('提交提现请求出错');
            if (!$this->insertRow($this->prename . 'member_cash', $para)) parent::json_fails('提交提现请求出错');
            $id = $this->lastInsertId();

            // 流动资金
            $this->addCoin(array(
                'coin' => 0 - $para['amount'],
                'fcoin' => $para['amount'],
                'uid' => $para['uid'],
                'liqType' => 106,
                'info' => "提现[$id]资金冻结",
                'extfield0' => $id
            ));

            $this->commit();

//            return ('申请提现成功，提现将在10分钟内到帐，如未到账请联系在线客服。');
            parent::json_success('申请提现成功，提现将在10分钟内到帐，如未到账请联系在线客服。');
        } catch (Exception $e) {
            $this->rollBack();
            //return 9999;
//            throw $e;
            parent::json_fails('未知错误');
        }
    }

    /**
     * 确认提现到帐
     */
    public final function toCashSure($id)
    {
//        if (!$id = intval($id)) throw new Exception('参数出错');
        if (!$id = intval($id)) parent::json_fails('参数出错');

        $this->beginTransaction();
        try {

            // 查找提现请求信息
            if (!$cash = $this->getRow("select * from {$this->prename}member_cash where id=$id"))
//                throw new Exception('参数出错');
                parent::json_fails('参数出错');

//            if ($cash['uid'] != $this->user['uid']) throw new Exception('您不能代别人确认');
            if ($cash['uid'] != $this->user['uid']) throw new Exception('您不能代别人确认');
            switch ($cash['state']) {
                case 0:
//                    throw new Exception('提现已经确认过了');
                    parent::json_fails('提现已经确认过了');
                    break;
                case 1:
//                    throw new Exception("提现请求正在处理中...");
                    parent::json_fails("提现请求正在处理中...");
                    break;
                case 2:
//                    throw new Exception("该提现请求已经取消，冻结资金已经解除冻结\r\n如需要提现请重新申请");
                    parent::json_fails("该提现请求已经取消，冻结资金已经解除冻结\r\n如需要提现请重新申请");
                    break;
                case 3:

                    break;
                case 4:
//                    throw new Exception("该提现请求已经失败，冻结资金已经解除冻结\r\n如需要提现请重新申请");
                    parent::json_fails("该提现请求已经失败，冻结资金已经解除冻结\r\n如需要提现请重新申请");
                    break;
                default:
//                    throw new Exception('系统出错');
                    parent::json_fails('系统出错');
                    break;
            }

            if ($this->update("update {$this->prename}member_cash set state=0 where id=$id"))
                $this->addCoin(array(
                    'liqType' => 12,
                    'uid' => $this->user['uid'],
                    'info' => "提现[$id]资金确认",
                    'extfield0' => $id
                ));

        } catch (Exception $e) {
            $this->rollBack();
//            throw $e;
            parent::json_fails('未知错误');
        }
    }

    /* 进入充值，生产充值订单 */
    public final function inRecharge()
    {

        if (!$_POST) parent::json_fails('参数出错');
//        if (!$_POST) throw new Exception('参数出错');
        $para['mBankId'] = intval($_POST['mBankId']);
        $para['amount'] = floatval($_POST['amount']);

        if ($para['amount'] <= 0) parent::json_fails('充值金额错误，请重新操作');
//        if ($para['amount'] <= 0) throw new Exception('充值金额错误，请重新操作');
        if ($id = $this->getValue("select bankId from {$this->prename}sysadmin_bank where id=?", $para['mBankId'])) {
            if ($id == 0 || $id == 0) {
                if ($para['amount'] < $this->settings['rechargeMin1'] || $para['amount'] > $this->settings['rechargeMax1']) parent::json_fails('支付宝/财付通充值最低' . $this->settings['rechargeMin1'] . '元，最高' . $this->settings['rechargeMax1'] . '元');
//                if ($para['amount'] < $this->settings['rechargeMin1'] || $para['amount'] > $this->settings['rechargeMax1']) throw new Exception('支付宝/财付通充值最低' . $this->settings['rechargeMin1'] . '元，最高' . $this->settings['rechargeMax1'] . '元');
            } else {
                if ($para['amount'] < $this->settings['rechargeMin'] || $para['amount'] > $this->settings['rechargeMax']) parent::json_fails('银行卡充值最低' . $this->settings['rechargeMin1'] . '元，最高' . $this->settings['rechargeMax1'] . '元');
//                if ($para['amount'] < $this->settings['rechargeMin'] || $para['amount'] > $this->settings['rechargeMax']) throw new Exception('银行卡充值最低' . $this->settings['rechargeMin1'] . '元，最高' . $this->settings['rechargeMax1'] . '元');
            }
        } else {
            parent::json_fails('充值银行不存在，请重新选择');
//            throw new Exception('充值银行不存在，请重新选择');
        }

        //if(strtolower($_POST['vcode'])!=$_SESSION[$this->vcodeSessionName]){
        //	throw new Exception('验证码不正确。');
        //}else{
        // 插入充值请求表
        unset($para['coinpwd']);
        $para['rechargeId'] = $this->getRechId();
        $para['actionTime'] = $this->time;
        $para['uid'] = $this->user['uid'];
        $para['username'] = $this->user['username'];
        $para['actionIP'] = $this->ip(true);
        $para['info'] = '用户充值';
        $para['bankId'] = $id;

        if ($this->insertRow($this->prename . 'member_recharge', $para)) {
//            $this->display('cash/recharge-copy.php', 0, $para);
            parent::json_display($para);
        } else {
            parent::json_fails('充值订单生产请求出错');
//            throw new Exception('充值订单生产请求出错');
        }
    }
    //清空验证码session
    // unset($_SESSION[$this->vcodeSessionName]);
    //}

    public final function getRechId()
    {
        $rechargeId = mt_rand(100000, 999999);
        if ($this->getRow("select id from {$this->prename}member_recharge where rechargeId=$rechargeId")) {
            getRechId();
        } else {
            return $rechargeId;
        }
    }

    //充值提现详细信息弹出
    public final function rechargeModal($id)
    {
        $this->getTypes();
        $this->getPlayeds();
//        $this->display('cash/recharge-modal.php', 0, $id);

        $sql = "select r.* from {$this->prename}member_recharge r where r.id=?";
        $rechargeInfo = $this->getRow($sql, $id);
        if ($rechargeInfo['mBankId']) {
            $sql = "select mb.username accountName, mb.account account, b.name bankName from {$this->prename}members u,{$this->prename}member_bank mb, {$this->prename}bank_list b where b.isDelete=0 and u.uid={$rechargeInfo['uid']} and mb.id={$rechargeInfo['mBankId']} and mb.bankId=b.id";
            $bankInfo = $this->getRow($sql);
        } else {

            $bankInfo = null;
        }

        parent::json_display(['rechargeInfo' => $rechargeInfo, 'bankInfo' => $bankInfo]);
    }

    public final function cashModal($id)
    {
        $this->getTypes();
        $this->getPlayeds();
//        $this->display('cash/cash-modal.php', 0, $id);

        $sql = "select c.*, u.username user, u.coin coin, b.name bankName from {$this->prename}member_cash c,{$this->prename}members u, {$this->prename}bank_list b where b.isDelete=0 and c.id={$id} and b.id=c.bankId and c.uid=u.uid";
        $cashInfo = $this->getRow($sql, $id);

        parent::json_display(['cashInfo' => $cashInfo]);
    }

    //充值演示
    public final function paydemo($id)
    {
//        $this->display('cash/paydemo.php', 0, $id);
        parent::json_display(['id' => $id]);
    }
}