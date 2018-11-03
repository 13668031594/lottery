<?php
@session_start();

class Team extends WebLoginBase
{
    public $pageSize = 14;
    private $vcodeSessionName = 'lottery_vcode_session_name';

    public function getMyUserCount1()
    {
        $this->getSystemSettings();
        $minFanDian = $this->user['fenhongbili'] - 10 * $this->settings['fenhongbiliDiff'];
        $sql = "select count(*) registerUserCount, fenhongbili from {$this->prename}members where parentId={$this->user['uid']} and fenhongbili>=$minFanDian and fenhongbili<{$this->user['fenhongbili']} group by fenhongbili order by fenhongbili desc";
        $data = $this->getRows($sql);
        $ret = array();
        $fenhongbili = $this->user['fenhongbili'];
        $i = 0;
        $set = explode(',', $this->settings['fenhongbiliUserCount']);
        while (($fenhongbili -= $this->settings['fenhongbiliDiff']) && ($fenhongbili >= $minFanDian)) {
            $arr = array();
            if ($data[0]['fenhongbili'] == $fenhongbili) {
                $arr = array_shift($data);
            } else {
                $arr['fenhongbili'] = $fenhongbili;
                $arr['registerUserCount'] = 0;
            }
            $arr['setting'] = $set[$i];
            //var_dump($fenhongbili);
            $ret["$fenhongbili"] = $arr;
            $i++;
        }
        return ($ret);
    }

    public function getMyUserCount()
    {
        if (!$set = $this->settings['fenhongbiliUserCount']) return array();
        $set = explode(',', $set);

        foreach ($set as $key => $var) {
            $set[$key] = explode('|', $var);
        }

        foreach ($set as $var) {
            if ($this->user['fenhongbili'] >= $var[1]) break;
            array_shift($set);
        }
    }

    public final function onlineMember()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//		$this->display('team/member-online-list.php');
        parent::json_display();
    }

    /*游戏记录*/
    public final function gameRecord()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getTypes();
        $this->getPlayeds();
        $this->action = 'searchGameRecord';
//		$this->display('team/record.php');

        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));
        $type = $_REQUEST['type'];
        $types = $this->types;

        $result = [
            'fromTime' => $fromTime,
            'toTime' => $toTime,
            'type' => $type,
            'types' => $types,
        ];

        parent::json_display($result);
    }

    public final function searchGameRecord()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getTypes();
        $this->getPlayeds();
//		$this->display('team/record-list.php');
        $para = $_GET;

        if ($para['state'] == 5) {
            $whereStr = ' and b.isDelete=1 ';
        } else {
            $whereStr = ' and  b.isDelete=0 ';
        }
        // 彩种限制
        if ($para['type'] = intval($para['type'])) {
            $whereStr .= " and b.type={$para['type']}";
        }

        // 时间限制
        if ($para['fromTime'] && $para['toTime']) {
            $whereStr .= ' and b.actionTime between ' . strtotime($para['fromTime']) . ' and ' . strtotime($para['toTime']);
        } elseif ($para['fromTime']) {
            $whereStr .= ' and b.actionTime>=' . strtotime($para['fromTime']);
        } elseif ($para['toTime']) {
            $whereStr .= ' and b.actionTime<' . strtotime($para['toTime']);
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) {
                $whereStr .= ' and b.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
            }
        }

        // 投注状态限制
        if ($para['state']) {
            switch ($para['state']) {
                case 1:
                    // 已派奖
                    $whereStr .= ' and b.zjCount>0';
                    break;
                case 2:
                    // 未中奖
                    $whereStr .= " and b.zjCount=0 and b.lotteryNo!='' and b.isDelete=0";
                    break;
                case 3:
                    // 未开奖
                    $whereStr .= " and b.lotteryNo=''";
                    break;
                case 4:
                    // 追号
                    $whereStr .= ' and b.zhuiHao=1';
                    break;
                case 5:
                    // 撤单
                    $whereStr .= ' and b.isDelete=1';
                    break;
            }
        }

        // 模式限制
        if ($para['mode'] = floatval($para['mode'])) $whereStr .= " and b.mode={$para['mode']}";

        //单号
        if ($para['betId'] && $para['betId'] != '输入单号') {
            $para['betId'] = wjStrFilter($para['betId']);
            if (!ctype_alnum($para['betId'])) parent::json_fails('单号包含非法字符,请重新输入');
            $whereStr .= " and b.wjorderId='{$para['betId']}'";
        }


        // 用户名限制
        if ($para['username'] && $para['username'] != '用户名') {
            $para['username'] = wjStrFilter($para['username']);
            if (!ctype_alnum($para['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $whereStr .= " and u.username like '%{$para['username']}%' and concat(',',u.parents,',') like '%,{$this->user['uid']},%'";
        }
        switch ($para['utype']) {
            case 1:
                //我自己
                $whereStr .= " and b.uid={$this->user['uid']}";
                break;
            case 2:
                //直属下线
                $whereStr .= " and u.parentId={$this->user['uid']}";
                break;
            case 3:
                // 所有下级
                $whereStr .= " and concat(',',u.parents,',') like '%,{$this->user['uid']},%' and u.uid!={$this->user['uid']}";
                break;
            default:
                // 所有人
                $whereStr .= " and concat(',',u.parents,',') like '%,{$this->user['uid']},%'";
                break;
        }
        $sql = "select b.*, u.username from {$this->prename}bets b, {$this->prename}members u where b.uid=u.uid";
        $sql .= $whereStr;
        $sql .= ' order by id desc, actionTime desc';

        $data = $this->getPage($sql, $this->page, $this->pageSize);
        //print_r($data);
        $params = http_build_query($para, '', '&');

        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘'/*,'1.000'=>'1元'*/);

        $result = [
            'data' => $data,
            'params' => $params,
            'modelName' => $modeName,
        ];

        parent::json_display($result);
    }
    /*游戏记录 结束*/

    /*团队报表*/
    public final function report()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->action = 'searchReport';
//		$this->display('team/report.php');

        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));

        $result = [
            'fromTime' => $fromTime,
            'toTime' => $toTime,
        ];

        parent::json_display($result);
    }

    public final function searchReport()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//		$this->display('team/report-list.php');
        $para = $_GET;

        // 时间限制
        if ($para['fromTime'] && $para['toTime']) {
            $fromTime = strtotime($para['fromTime']);
            $toTime = strtotime($para['toTime']) + 24 * 3600;
            $betTimeWhere = "and actionTime between $fromTime and $toTime";
            $cashTimeWhere = "and c.actionTime between $fromTime and $toTime";
            $rechargeTimeWhere = "and r.actionTime between $fromTime and $toTime";
            $fanDiaTimeWhere = "and actionTime between $fromTime and $toTime";
            $fanDiaTimeWhere2 = "and l.actionTime between $fromTime and $toTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        } elseif ($para['fromTime']) {
            $fromTime = strtotime($para['fromTime']);
            $betTimeWhere = "and b.actionTime >=$fromTime";
            $cashTimeWhere = "and c.actionTime >=$fromTime";
            $rechargeTimeWhere = "and r.actionTime >=$fromTime";
            $fanDiaTimeWhere = "and actionTime >= $fromTime";
            $fanDiaTimeWhere2 = "and l.actionTime >= $fromTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        } elseif ($para['toTime']) {
            $toTime = strtotime($para['toTime']) + 24 * 3600;
            $betTimeWhere = "and b.actionTime < $toTime";
            $cashTimeWhere = "and c.actionTime < $toTime";
            $rechargeTimeWhere = "and r.actionTime < $toTime";
            $fanDiaTimeWhere = "and actionTime < $toTime";
            $fanDiaTimeWhere2 = "and l.actionTime < $toTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        } else {
            $toTime = strtotime('00:00:00');
            $betTimeWhere = "and b.actionTime > $toTime";
            $cashTimeWhere = "and c.actionTime > $toTime";
            $rechargeTimeWhere = "and r.actionTime > $toTime";
            $fanDiaTimeWhere = "and actionTime > $toTime";
            $fanDiaTimeWhere2 = "and l.actionTime > $toTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        }

        // 用户限制
        $uid = $this->user['uid'];
        if ($para['parentId'] = intval($para['parentId'])) {
            // 用户ID限制
            $userWhere = "and u.parentId={$para['parentId']}";
            $uid = $para['parentId'];
        } elseif ($para['uid'] = intval($para['uid'])) {
            // 用户ID限制
            $uParentId = $this->getValue("select parentId from {$this->prename}members where uid=?", $para['uid']);
            $userWhere = "and u.uid=$uParentId";
            $uid = $uParentId;
        } elseif ($para['username'] && $para['username'] != '用户名') {
            // 用户名限制
            $para['username'] = wjStrFilter($para['username']);
            if (!ctype_alnum($para['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $uid = $this->getValue("select uid from {$this->prename}members where username=? and concat(',',parents,',') like '%,{$this->user['uid']},%'", $para['username']);
            $userWhere = "and u.username='{$para['username']}' and concat(',', u.parents, ',') like '%,{$this->user['uid']},%'";
        } else {
            $userWhere = "and (u.parentId={$uid} or u.uid={$uid}) ";
        }
        $userWhere3 = "and concat(',', u.parents, ',') like '%,$uid,%'";

        //没有账变的不显示
        $userWhere .= " and u.uid in(select uid from {$this->prename}coin_log where 1=1 $logTimeWhere)";

        $sql = "select u.username, u.coin, u.uid, u.parentId, sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount, (select sum(c.amount) from {$this->prename}member_cash c where c.`uid`=u.`uid` and c.state=0 $cashTimeWhere) cashAmount,(select sum(r.amount) from {$this->prename}member_recharge r where r.`uid`=u.`uid` and r.state in(1,2,9) $rechargeTimeWhere) rechargeAmount, (select sum(l.coin) from {$this->prename}coin_log l where l.`uid`=u.`uid` and l.liqType in(50,51,52,53,56) $brokerageTimeWhere) brokerageAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere where 1 $userWhere";
        //echo $sql;exit;

        $this->pageSize -= 1;
        if ($this->action != 'searchReport') $this->action = 'searchReport';
        $list = $this->getPage($sql . ' group by u.uid', $this->page, $this->pageSize);
        if (!$list['total']) {
            $uParentId2 = $this->getValue("select parentId from {$this->prename}members where uid=?", $para['parentId']);
            $list = array(
                'total' => 1,
                'data' => array(array(
                    'parentId' => $uParentId2,
                    'uid' => $para['parentId'],
                    'username' => '没有用户'
                ))
            );
            $noChildren = true;
        }
        $params = http_build_query($_REQUEST, '', '&');
        $count = array();
        $sql = "select sum(coin) from {$this->prename}coin_log where uid=? and liqType in(2,3) $fanDiaTimeWhere";

        $rel = "/index.php/{$this->controller}/{$this->action}";

        if ($list['data']) foreach ($list['data'] as $var) {

            if ($var['username'] != '没有用户') {
                $var['fenhongbiliAmount'] = $this->getValue($sql, $var['uid']);
                //echo $sql.$var['uid'];
                $pId = $var['uid'];
            }
            $count['betAmount'] += $var['betAmount'];
            $count['zjAmount'] += $var['zjAmount'];
            $count['fenhongbiliAmount'] += $var['fenhongbiliAmount'];
            $count['brokerageAmount'] += $var['brokerageAmount'];
            $count['cashAmount'] += $var['cashAmount'];
            $count['coin'] += $var['coin'];
            $count['rechargeAmount'] += $var['rechargeAmount'];
        }

        if ($para['userType'] == 1 || ($para['userType'] == 0 && !$para['parentId']) || ($para['username'] && $para['username'] != '用户名')) {
            $sql2 = "select sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere $userWhere3";
            $all = $this->getRow($sql2);
            $all['fenhongbiliAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType between 2 and 3 and l.uid=u.uid $fanDiaTimeWhere2 $userWhere3", $var['uid']);
            $all['brokerageAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType in(50,51,52,53,56) and l.uid=u.uid $brokerageTimeWhere $userWhere3", $var['uid']);
            $all['rechargeAmount'] = $this->getValue("select sum(r.amount) from {$this->prename}member_recharge r, {$this->prename}members u where r.state in (1,2,9) and r.uid=u.uid $rechargeTimeWhere $userWhere3", $var['uid']);
            $all['cashAmount'] = $this->getValue("select sum(c.amount) from {$this->prename}member_cash c, {$this->prename}members u  where c.state=0 and c.uid=u.uid $cashTimeWhere $userWhere3", $var['uid']);
            $all['coin'] = $this->getValue("select sum(u.benjin) coin from {$this->prename}members u where 1 $userWhere3", $var['uid']);
        } else {
            $sql2 = "select sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere $userWhere";
            $all = $this->getRow($sql2);
            $all['fenhongbiliAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType between 2 and 3 and l.uid=u.uid $fanDiaTimeWhere2 $userWhere", $var['uid']);
            $all['brokerageAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType in(50,51,52,53,56) and l.uid=u.uid $brokerageTimeWhere $userWhere", $var['uid']);
            $all['rechargeAmount'] = $this->getValue("select sum(r.amount) from {$this->prename}member_recharge r, {$this->prename}members u where r.state in (1,2,9) and r.uid=u.uid $rechargeTimeWhere $userWhere", $var['uid']);
            $all['cashAmount'] = $this->getValue("select sum(c.amount) from {$this->prename}member_cash c, {$this->prename}members u  where c.state=0 and c.uid=u.uid $cashTimeWhere $userWhere", $var['uid']);
            $all['coin'] = $this->getValue("select sum(u.benjin) coin from {$this->prename}members u where 1 $userWhere", $var['uid']);
        }

        if (intval($para['userType']) != 3) {
            $sql2 = "select sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere $userWhere3";
            $all2 = $this->getRow($sql2);
            $all2['fenhongbiliAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log l, {$this->prename}members u where l.liqType between 2 and 3 and l.uid=u.uid $fanDiaTimeWhere2 $userWhere3", $var['uid']);
            $all2['brokerageAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log l, {$this->prename}members u where l.liqType in(50,51,52,53,56) and l.uid=u.uid $brokerageTimeWhere $userWhere3", $var['uid']);
            $all2['rechargeAmount'] = $this->getValue("select sum(r.amount) from {$this->prename}member_recharge r, {$this->prename}members u where r.state in (1,2,9) and r.uid=u.uid $rechargeTimeWhere $userWhere3", $var['uid']);
            $all2['cashAmount'] = $this->getValue("select sum(c.amount) from {$this->prename}member_cash c, {$this->prename}members u  where c.state=0 and c.uid=u.uid $cashTimeWhere $userWhere3", $var['uid']);
            $all2['coin'] = $this->getValue("select sum(u.coin) coin from {$this->prename}members u where 1 $userWhere3", $var['uid']);

            $all2['rechargeAmount'] = $this->ifs($all['rechargeAmount'], '--');
            $all2['cashAmount'] = $this->ifs($all['cashAmount'], '--');
            $all2['betAmount'] = $this->ifs($all['betAmount'], '--');
            $all2['zjAmount'] = $this->ifs($all['zjAmount'], '--');
            $all2['fenhongbiliAmount'] = $this->ifs($all['fenhongbiliAmount'], '--');
            $all2['brokerageAmount'] = $this->ifs($all['brokerageAmount'], '--');
            $all2['all'] = $this->ifs($all['zjAmount'] - $all['betAmount'] + $all['fenhongbiliAmount'] + $all['brokerageAmount'], '--');
        }

        $all['rechargeAmount'] = $this->ifs($all['rechargeAmount'], '--');
        $all['cashAmount'] = $this->ifs($all['cashAmount'], '--');
        $all['betAmount'] = $this->ifs($all['betAmount'], '--');
        $all['zjAmount'] = $this->ifs($all['zjAmount'], '--');
        $all['fenhongbiliAmount'] = $this->ifs($all['fenhongbiliAmount'], '--');
        $all['brokerageAmount'] = $this->ifs($all['brokerageAmount'], '--');
        $all['all'] = $this->ifs($all['zjAmount'] - $all['betAmount'] + $all['fenhongbiliAmount'] + $all['brokerageAmount'], '--');


        $count['rechargeAmount'] = $this->ifs($count['rechargeAmount'], '--');
        $count['cashAmount'] = $this->ifs($count['cashAmount'], '--');
        $count['betAmount'] = $this->ifs($count['betAmount'], '--');
        $count['zjAmount'] = $this->ifs($count['zjAmount'], '--');
        $count['fenhongbiliAmount'] = $this->ifs($count['fenhongbiliAmount'], '--');
        $count['brokerageAmount'] = $this->ifs($count['brokerageAmount'], '--');
        $count['all'] = $this->ifs($count['zjAmount'] - $count['betAmount'] + $count['fenhongbiliAmount'] + $count['brokerageAmount'], '--');

        $result = [
            'userWhere' => $userWhere,
            'userWhere3' => $userWhere3,
            'list' => $list,
            'noChildren' => isset($noChildren) ? $noChildren : null,
            'count' => $count,
            'rel' => $rel,
            'all' => $all,
            'all2' => isset($all2) ? $all2 : null,
        ];

        parent::json_display($result);
    }
    /*团队报表 结束*/

    /*帐变列表*/
    public final function coin()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->action = 'searchCoin';

        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));

        $result = [
            'fromTime' => $fromTime,
            'toTime' => $toTime,
        ];

        parent::json_display($result);
    }

    public final function searchCoin()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->display('team/coin-log.php');
    }

    public final function searchCoinBenjin()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getTypes();
        $this->getPlayeds();

        // 日期限制
        if ($_REQUEST['fromTime'] && $_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime between ' . strtotime($_REQUEST['fromTime']) . ' and ' . strtotime($_REQUEST['toTime']);
        } elseif ($_REQUEST['fromTime']) {
            $timeWhere = ' and l.actionTime >=' . strtotime($_REQUEST['fromTime']);
        } elseif ($_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime <' . strtotime($_REQUEST['toTime']);
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $timeWhere = ' and l.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }

        // 帐变类型限制
        if ($_REQUEST['liqType'] = intval($_REQUEST['liqType'])) {
            $liqTypeWhere = ' and liqType=' . $_REQUEST['liqType'];
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }

        // 用户类型限制
        if ($_REQUEST['username'] && $_REQUEST['username'] != '用户名') {
            $_REQUEST['username'] = wjStrFilter($_REQUEST['username']);
            if (!ctype_alnum($_REQUEST['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $userWhere = " and u.parents like '%,{$this->user['uid']},%' and u.username like '%{$_REQUEST['username']}%'";
        }
        //$userWhere3="concat(',',u.parents,',') like '%,{$this->user['uid']},%'"; //所有人
        if ($_REQUEST['userType']) {
            switch ($_REQUEST['userType']) {
                case 1:
                    $userWhere = " and u.uid={$this->user['uid']}";
                    break;
                case 2:
                    $userWhere = " and u.parentId={$this->user['uid']}";
                    break;
                case 3:
                    $userWhere = "and concat(',', u.parents, ',') like '%,{$this->user['uid']},%'  and u.uid!={$this->user['uid']}";
                    break;

            }
        } else {
            $userWhere = " and u.parentId={$this->user['uid']}";
        }

        // 冻结查询
        if ($this->action == 'fcoinModal') {

            $fcoinModalWhere = 'and l.fcoin!=0';
        } else {

            $fcoinModalWhere = '';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_benjin l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘'/*,'1.000'=>'1元'*/);
        $liqTypeName = array(
            '账户类' => array(
                55 => '注册奖励',
                1 => '用户充值',
                9 => '系统充值',
                54 => '充值奖励',
                12 => '上级转款',

                700 => '分红提现',//new
                6 => '中奖奖金',
                702 => '盈利转出',//new
            ),
            '游戏类' => array(
                101 => '投注扣款',
                108 => '开奖扣除',
//            6 => '中奖奖金',
                7 => '撤单返款',
                102 => '追号投注',
                5 => '追号撤单',
                //11  => '合买收单',old
                255 => '未开奖返还',
            ),
            /*
            '抢庄类' => array(
                100 => '抢庄冻结',
                10  => '撤庄返款',
                103 => '抢庄分红比例',
                104 => '抢庄抽水',
                105 => '抢庄赔付',
            ),
            */
            '代理类' => array(
//            3 => '代理分红',
//            52 => '充值佣金',
//            53 => '消费佣金',
//            56 => '亏损佣金',
                13 => '转款给下级',
            ),
            '活动类' => array(
                50 => '签到赠送',
//            120 => '幸运大转盘',
                121 => '积分兑换',
            ),
        );

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName,
        ];

        parent::json_display($result);
    }

    public final function searchCoinFenhong()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getTypes();
        $this->getPlayeds();

        // 日期限制
        if ($_REQUEST['fromTime'] && $_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime between ' . strtotime($_REQUEST['fromTime']) . ' and ' . strtotime($_REQUEST['toTime']);
        } elseif ($_REQUEST['fromTime']) {
            $timeWhere = ' and l.actionTime >=' . strtotime($_REQUEST['fromTime']);
        } elseif ($_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime <' . strtotime($_REQUEST['toTime']);
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $timeWhere = ' and l.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }

        // 帐变类型限制
        if ($_REQUEST['liqType'] = intval($_REQUEST['liqType'])) {
            $liqTypeWhere = ' and liqType=' . $_REQUEST['liqType'];
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }

        // 用户类型限制
        if ($_REQUEST['username'] && $_REQUEST['username'] != '用户名') {
            $_REQUEST['username'] = wjStrFilter($_REQUEST['username']);
            if (!ctype_alnum($_REQUEST['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $userWhere = " and u.parents like '%,{$this->user['uid']},%' and u.username like '%{$_REQUEST['username']}%'";
        }
        //$userWhere3="concat(',',u.parents,',') like '%,{$this->user['uid']},%'"; //所有人
        if ($_REQUEST['userType']) {
            switch ($_REQUEST['userType']) {
                case 1:
                    $userWhere = " and u.uid={$this->user['uid']}";
                    break;
                case 2:
                    $userWhere = " and u.parentId={$this->user['uid']}";
                    break;
                case 3:
                    $userWhere = "and concat(',', u.parents, ',') like '%,{$this->user['uid']},%'  and u.uid!={$this->user['uid']}";
                    break;

            }
        } else {
            $userWhere = " and u.parentId={$this->user['uid']}";
        }

        // 冻结查询
        if ($this->action == 'fcoinModal') {

            $fcoinModalWhere = 'and l.fcoin!=0';
        } else {

            $fcoinModalWhere = '';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_fenhong l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘'/*,'1.000'=>'1元'*/);
        $liqTypeName = array(
            '账户类' => array(
//            2 => '下级分红比例',
                669 => '转移到到余额',//new
                106 => '提现冻结',
                8 => '提现失败返还',
                107 => '提现成功扣除',
                51 => '绑定银行奖励',
                666 => '盈利转入',//new
            ),
            '游戏类' => array(
                668 => '亏损分红',//new
            ),
        );

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName,
        ];


        parent::json_display($result);
    }

    public final function searchCoinYingli()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getTypes();
        $this->getPlayeds();

        // 日期限制
        if ($_REQUEST['fromTime'] && $_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime between ' . strtotime($_REQUEST['fromTime']) . ' and ' . strtotime($_REQUEST['toTime']);
        } elseif ($_REQUEST['fromTime']) {
            $timeWhere = ' and l.actionTime >=' . strtotime($_REQUEST['fromTime']);
        } elseif ($_REQUEST['toTime']) {
            $timeWhere = ' and l.actionTime <' . strtotime($_REQUEST['toTime']);
        } else {

            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $timeWhere = ' and l.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }

        // 帐变类型限制
        if ($_REQUEST['liqType'] = intval($_REQUEST['liqType'])) {
            $liqTypeWhere = ' and liqType=' . $_REQUEST['liqType'];
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }

        // 用户类型限制
        if ($_REQUEST['username'] && $_REQUEST['username'] != '用户名') {
            $_REQUEST['username'] = wjStrFilter($_REQUEST['username']);
            if (!ctype_alnum($_REQUEST['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $userWhere = " and u.parents like '%,{$this->user['uid']},%' and u.username like '%{$_REQUEST['username']}%'";
        }
        //$userWhere3="concat(',',u.parents,',') like '%,{$this->user['uid']},%'"; //所有人
        if ($_REQUEST['userType']) {
            switch ($_REQUEST['userType']) {
                case 1:
                    $userWhere = " and u.uid={$this->user['uid']}";
                    break;
                case 2:
                    $userWhere = " and u.parentId={$this->user['uid']}";
                    break;
                case 3:
                    $userWhere = "and concat(',', u.parents, ',') like '%,{$this->user['uid']},%'  and u.uid!={$this->user['uid']}";
                    break;

            }
        } else {
            $userWhere = " and u.parentId={$this->user['uid']}";
        }

        // 冻结查询
        if ($this->action == 'fcoinModal') {

            $fcoinModalWhere = 'and l.fcoin!=0';
        } else {

            $fcoinModalWhere = '';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_yingli l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘'/*,'1.000'=>'1元'*/);
        $liqTypeName = array(
            '账户类' => array(
                667 => '盈利日转'//new
            ),
            '游戏类' => array(
                701 => '盈利',
            ),
        );

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName,
        ];

        parent::json_display($result);
    }

    /*帐变列表 结束*/

    public final function coinall()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->freshSession();
//        $this->display('team/coinall.php');

        $teamAll = $this->getRow("select sum(u.benjin) benjin, count(u.uid) count from {$this->prename}members u where u.isDelete=0 and concat(',', u.parents, ',') like '%,{$this->user['uid']},%'");
        $teamAll2 = $this->getRow("select count(u.uid) count from {$this->prename}members u where u.isDelete=0 and u.parentId={$this->user['uid']}");

        $home_uid = $this->user['uid'];
        $childrenarr = $this->getRows("SELECT username,benjin,parents,uid FROM {$this->prename}members where concat(',',parents,',') like '%,{$home_uid},%' and uid!=?", $home_uid);

        $onlineNum = 0;
        $index = null;
        foreach ($childrenarr as $var) {
            $login = $this->getRow("select * from {$this->prename}member_session where uid=? order by id desc limit 1", $var['uid']);
            if ($login['isOnLine'] && ($this->time - $login['accessTime'] < 900)) {
                $parents = explode(',', $var['parents']);
                rsort($parents);
                $index = 1;
                foreach ($parents as $key => $var2) {
                    $index++;
                }
                $onlineNum++;
            }
        }

        $result = [
            'teamAll' => $teamAll,
            'teamAll2' => $teamAll2,
            'onlineNum' => $onlineNum,
//            'index' => $index,
        ];

        parent::json_display($result);
    }

    public final function addMember()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/add-member.php');

        parent::json_display();
    }

    public final function userUpdate($id)
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/update-menber.php', 0, intval($id));

        $sql = "select * from {$this->prename}members where uid=?";
        $userData = $this->getRow($sql, $id);

        if ($userData['parentId']) {
            $parentData = $this->getRow("select fenhongbili from {$this->prename}members where uid=?", $userData['parentId']);
        } else {
            $this->getSystemSettings();
            $parentData['fenhongbili'] = $this->settings['fenhongbiliMax'];
        }

        $parent = $this->getValue("select username from {$this->prename}members where uid={$userData['parentId']} ");

        $result = [
            'userData' => $userData,
            'parentUseranem' => $parent,
        ];

        parent::json_display($result);
    }

    public final function userUpdate2($id)
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/menber-recharge.php', 0, intval($id));

        $sql = "select * from {$this->prename}members where uid=?";
        $userData = $this->getRow($sql, intval($id));
        $parent = $this->getValue("select username from {$this->prename}members where uid={$userData['parentId']}");

        $result = [
            'userData' => $userData,
            'parentUseranem' => $parent,
        ];

        parent::json_display($result);

    }

    public final function memberList()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/member-list.php');

        parent::json_display();
    }

    public final function cashRecord()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/cash-record.php');

        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));

        $result = [
            'fromTime' => $fromTime,
            'toTime' => $toTime,
        ];

        parent::json_display($result);
    }

    public final function searchCashRecord()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/cash-record-list.php');

        $sql = "select c.*, b.name bankName, u.username from {$this->prename}members u, {$this->prename}bank_list b, {$this->prename}member_cash c where c.uid=u.uid and b.isDelete=0 and c.bankId=b.id";

        // 时间条件限制
        if ($_GET['fromTime'] && $_GET['toTime']) {
            $sql .= ' and c.actionTime between ' . strtotime($_GET['fromTime']) . ' and ' . strtotime($_GET['toTime']);
        } elseif ($_GET['fromTime']) {
            $sql .= ' and c.actionTime>=' . strtotime($_GET['fromTime']);
        } elseif ($_GET['toTime']) {
            $sql .= ' and c.actionTime<' . strtotime($_GET['toTime']);
        } else {
            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) $sql .= ' and c.actionTime between ' . $GLOBALS['fromTime'] . ' and ' . $GLOBALS['toTime'] . ' ';
        }

        // 用户名限制
        if ($_GET['username'] && $_GET['username'] != '用户名') {
            $_GET['username'] = wjStrFilter($_GET['username']);
            if (!ctype_alnum($_GET['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
            $sql .= " and u.username like '%{$_GET['username']}%' and concat(',',u.parents,',') like '%,{$this->user['uid']},%'";
        } else {
            // 从属关系限制
            unset($_GET['username']);
            switch ($_GET['type'] = intval($_GET['type'])) {
                case 1:
                    //我自己
                    $sql .= " and uid={$this->user['uid']}";
                    break;
                case 2:
                    //直属下线
                    if (!$_GET['uid']) $_GET['uid'] = $this->user['uid'];
                    $sql .= " and u.parentId={$_GET['uid']}";
                    break;
                case 3:
                    // 所有下级
                    $sql .= "concat(',',u.parents,',') like '%,{$this->user['uid']},%' and u.uid!={$this->user['uid']}";
                    break;
                default:
                    // 所有人
                    $sql .= " and concat(',',u.parents,',') like '%,{$this->user['uid']},%'";
                    break;
            }
        }
        //echo $sql;
        if ($_GET['uid'] = $this->user['uid']) unset($_GET['uid']);
        $data = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_GET, '', '&');

        $stateName = array('已到帐', '正在办理', '已取消', '已支付', '失败');

        $result = [
            'data' => $data,
            'params' => $params,
            'stateName' => $stateName,
        ];

        parent::json_display($result);
    }

    public final function addLink()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/add-link.php');

        parent::json_display();
    }

    public final function linkDelete($lid)
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/delete-link.php', 0, intval($lid));

        $sql = "select * from {$this->prename}links where lid=?";
        $linkData = $this->getRow($sql, intval($lid);

        if ($linkData['uid']) {
            $parentData = $this->getRow("select fenhongbili, username from {$this->prename}members where uid=?", $linkData['uid']);
        } else {
            $this->getSystemSettings();
            $parentData['fenhongbili'] = $this->settings['fenhongbiliMax'];
        }

        $result = [
            'linkData' => $linkData,
            'parentData' => $parentData,
        ];

        parent::json_display($result);
    }

    public final function linkList()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/link-list.php');

        parent::json_display();
    }

    public final function getLinkCode($lid)
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/get-linkcode.php', 0, intval($lid));

        $sql = "select * from {$this->prename}links where lid=?";
        if (!$linkData = $this->getRow($sql, intval($lid))) {
            $parentData = null;
        } else {
            $pd = "select * from {$this->prename}members where uid=?";
            $parentData = $this->getRow($pd, $linkData['uid']);
        }

        $result = [
            'parentData' => $parentData,
        ];

        parent::json_display($result);
    }

    public final function advLink()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        parent::json_display();
//        $this->display('team/link-list.php');
    }

    public final function insertLink()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->getSystemSettings();
        if (!$_POST) parent::json_fails('提交数据出错，请重新操作');

        $update['uid'] = intval($_POST['uid']);
        $update['type'] = intval($_POST['type']);
        $update['fenhongbili'] = floatval($_POST['fenhongbili']);
        $update['regIP'] = $this->ip(true);
        $update['regTime'] = $this->time;

        if ($update['fenhongbili'] < 0) parent::json_fails('分红比例不能小于0');
        if ($update['fenhongbili'] > $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'])) parent::json_fails('分红比例不能大于' . $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff']));
        if ($update['type'] != 0 && $update['type'] != 1) parent::json_fails('类型出错，请重新操作');
        if ($update['uid'] != $this->user['uid']) parent::json_fails('只能增加自己的推广链接!');

        // 查检分红比例设置
        if ($update['fenhongbili']) {
            $this->getSystemSettings();
            if ($update['fenhongbili'] % $this->settings['fenhongbiliDiff']) parent::json_fails(sprintf('分红比例只能是%.1f%的倍数', $this->settings['fenhongbiliDiff']));
        } else {
            $update['fenhongbili'] = 0.0;
        }
        $this->beginTransaction();
        try {
            $sql = "select fenhongbili from {$this->prename}links where uid={$update['uid']} and fenhongbili=? ";
            if ($this->getValue($sql, $update['fenhongbili'])) parent::json_fails('此链接已经存在');
            if ($this->insertRow($this->prename . 'links', $update)) {
                $id = $this->lastInsertId();
                $this->commit();
//                return '添加链接成功';
                parent::success('添加链接成功');
            } else {
                parent::json_fails('添加链接失败');
            }

        } catch (Exception $e) {
            $this->rollBack();
//            throw $e;
            parent::json_fails();
        }
    }

    /*编辑注册链接*/
    public final function linkUpdate($id)
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/update-link.php', 0, intval($id));

        $sql = "select * from {$this->prename}links where lid=?";
        $linkData = $this->getRow($sql, intval($id));
        if ($linkData['uid']) {
            $parentData = $this->getRow("select fenhongbili from {$this->prename}members where uid=?", $linkData['uid']);
        } else {
            $this->getSystemSettings();
            $parentData['fenhongbili'] = $this->settings['fenhongbiliMax'];
        }
    }

    public final function linkUpdateed()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        if (!$_POST) parent::json_fails('提交数据出错，请重新操作');

        $update['lid'] = intval($_POST['lid']);
        $update['type'] = intval($_POST['type']);
        $update['fenhongbili'] = floatval($_POST['fenhongbili']);
        $update['updateTime'] = $this->time;
        $lid = $update['lid'];

        if ($update['fenhongbili'] < 0) parent::json_fails('分红比例不能小于0');
        if ($update['fenhongbili'] > $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'])) parent::json_fails('分红比例不能大于' . $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff']));
        if ($uid = $this->getvalue("select uid from {$this->prename}links where lid=?", $lid)) {
            if ($uid != $this->user['uid']) parent::json_fails('只能修改自己的推广链接!');
        } else {
            parent::json_fails('此注册链接不存在');
        }

        if (!$_POST['fenhongbili']) {
            unset($_POST['fenhongbili']);
            unset($update['fenhongbili']);
        }
        if ($update['fenhongbili'] == 0) $update['fenhongbili'] = 0.0;

        if ($this->updateRows($this->prename . 'links', $update, "lid=$lid")) {
            echo '修改成功';
        } else {
            parent::json_fails('未知出错');
        }

    }

    /*删除注册链接*/
    public final function linkDeleteed()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $lid = intval($_POST['lid']);
        if ($uid = $this->getvalue("select uid from {$this->prename}links where lid=?", $lid)) {
            if ($uid != $this->user['uid']) parent::json_fails('只能删除自己的推广链接!');
        } else {
            parent::json_fails('此注册链接不存在');
        }
        $sql = "delete from {$this->prename}links where lid=?";
        if ($this->update($sql, $lid)) {
            return '删除成功';
        } else {
            parent::json_fails('未知出错');
        }
    }

    public final function searchMember()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->display('team/member-search-list.php');
    }

    public final function memberlistlist()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $this->display('team/member-list-list.php');
    }

    public final function insertMember()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        if (!$_POST) parent::json_fails('提交数据出错，请重新操作');

        //过滤未知字段
        $update['username'] = wjStrFilter($_POST['username']);
        $update['qq'] = wjStrFilter($_POST['qq']);
        $update['fenhongbili'] = floatval($_POST['fenhongbili']);
        $update['password'] = $_POST['password'];
        $update['type'] = intval($_POST['type']);

        //接收参数检查
        //if(strtolower($_POST['vcode'])!=$_SESSION[$this->vcodeSessionName]) parent::$this->json_fails('验证码不正确。');
        //清空验证码session
        //  unset($_SESSION[$this->vcodeSessionName]);
        if ($update['fenhongbili'] < 0) parent::json_fails('分红比例不能小于0');
        if ($update['fenhongbili'] > $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] <= 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'])) parent::json_fails('分红比例不能大于' . $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff']));
        if (!$update['username']) parent::json_fails('用户名不能为空，请重新操作');
        if ($update['type'] != 0 && $update['type'] != 1) parent::json_fails('类型出错，请重新操作');

        if (!ctype_alnum($update['username'])) parent::json_fails('用户名包含非法字符,请重新输入');
        if (!ctype_digit($update['qq'])) parent::json_fails('QQ包含非法字符');

        $userlen = strlen($update['username']);
        $passlen = strlen($update['password']);
        $qqlen = strlen($update['qq']);

        if ($userlen < 4 || $userlen > 16) parent::json_fails('用户名长度不正确,请重新输入');
        if ($passlen < 6) parent::json_fails('密码至少六位,请重新输入');
        if ($qqlen < 5 || $qqlen > 11) parent::json_fails('QQ号为5-11位,请重新输入');

        $update['parentId'] = $this->user['uid'];
        $update['parents'] = $this->user['parents'];
        $update['password'] = md5($update['password']);
        $update['source'] = 1;

        $update['regIP'] = $this->ip(true);
        $update['regTime'] = $this->time;

        if (!$_POST['nickname']) $update['nickname'] = '未设昵称';
        if (!$_POST['name']) $update['name'] = $update['username'];

        // 查检分红比例设置
        if ($update['fenhongbili']) {
            $this->getSystemSettings();
            if ($update['fenhongbili'] % $this->settings['fenhongbiliDiff']) parent::json_fails(sprintf('分红比例只能是%.1f%的倍数', $this->settings['fenhongbiliDiff']));

            $count = $this->getMyUserCount();
            $sql = "select userCount, (select count(*) from {$this->prename}members m where m.parentId={$this->user['uid']} and m.fenhongbili=s.fenhongbili) registerCount from {$this->prename}params_fandianset s where s.fenhongbili={$update['fenhongbili']}";
            $count = $this->getRow($sql);

            if ($count && $count['registerCount'] >= $count['userCount']) parent::json_fails(sprintf('对不起分红比例为%.1f的下级人数已经达到上限', $update['fenhongbili']));
        } else {
            $update['fenhongbili'] = 0.0;
        }

        $this->beginTransaction();
        try {
            $sql = "select username from {$this->prename}members where username=?";
            if ($this->getValue($sql, $update['username'])) parent::json_fails('用户“' . $update['username'] . '”已经存在');
            if ($this->insertRow($this->prename . 'members', $update)) {
                $id = $this->lastInsertId();
                $sql = "update {$this->prename}members set parents=concat(parents, ',', $id) where `uid`=$id";
                $this->update($sql);

                $this->commit();

                return '添加会员成功';
            } else {
                parent::json_fails('添加会员失败');
            }

        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public final function userUpdateed()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        if (!$_POST) parent::json_fails('提交数据出错，请重新操作');

        //过滤未知字段
        $update['type'] = intval($_POST['type']);
        $update['uid'] = intval($_POST['uid']);
        $update['fenhongbili'] = floatval($_POST['fenhongbili']);
        $uid = $update['uid'];

        if ($update['fenhongbili'] < 0) parent::json_fails('分红比例不能小于0');
        $fandian = $this->getvalue("select fenhongbili from {$this->prename}members where uid=?", $update['uid']);
        if ($update['fenhongbili'] < $fandian) parent::json_fails('分红比例不能降低!');
        if ($update['fenhongbili'] > $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'])) parent::json_fails('分红比例不能大于' . $this->iff($this->user['fenhongbili'] - $this->settings['fenhongbiliDiff'] < 0, 0, $this->user['fenhongbili'] - $this->settings['fenhongbiliDiff']));
        if ($update['type'] != 0 && $update['type'] != 1) parent::json_fails('类型出错，请重新操作');

        if ($uid == $this->user['uid']) parent::json_fails('不能修改自己的分红比例');
        if (!$parentId = $this->getvalue("select parentId from {$this->prename}members where uid=?", $uid)) parent::json_fails('此会员不存在!');
        if ($parentId != $this->user['uid']) parent::json_fails('此会员不是你的直属下线，无法修改');

        if (!$_POST['fenhongbili']) {
            unset($_POST['fenhongbili']);
            unset($update['fenhongbili']);
        }
        if ($update['fenhongbili'] == 0) $update['fenhongbili'] = 0.0;

        if ($this->updateRows($this->prename . 'members', $update, "uid=$uid")) {
            echo '修改成功';
        } else {
            parent::json_fails('未知出错');
        }

    }

    /*额度转移*/
    public final function userUpdateed2()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        if (!$para = $_POST) parent::json_fails('提交数据出错，请重新操作');
        $this->getSystemSettings();
        if ($this->settings['recharge'] != 1) parent::json_fails('上级充值功能已经关闭！');

        $uid = intval($para['uid']);
        $amount = floatval($para['coin']);
        $coinpwd = wjStrFilter('safepass', '', 1);
        if (!$uid) {
            parent::json_fails('用户ID不正确');
        }
        $amount = floatval(abs($amount));
        if ($amount <= 0) parent::json_fails('充值金额不能为负值');
        if (!$coinpwd) parent::json_fails('请输入资金密码');
        $this->freshSession();
        if ($this->user['coinPassword'] != md5($coinpwd)) parent::json_fails('资金密码不正确');
        $data = array(
            'amount' => $amount,
            'actionUid' => $this->user['uid'],
            'actionIP' => $this->ip(true),
            'actionTime' => $this->time,
            'rechargeTime' => $this->time
        );
        $this->beginTransaction();
        try {
            $user = $this->getRow("select uid, username, coin, fcoin from {$this->prename}members where uid=$uid");
            if (!$user) parent::json_fails('用户不存在');
            // 查询用户可用资金
            $userAmount = $this->getValue("select coin from {$this->prename}members where uid={$this->user['uid']}");
            if ($userAmount < $amount) parent::json_fails('您的可用资金不足');
            $data['uid'] = $user['uid'];
            $data['coin'] = $user['coin'];
            $data['fcoin'] = $user['fcoin'];
            $data['username'] = $user['username'];
            $data['info'] = '上级[' . $this->user['username'] . ']充值';
            $data['state'] = 2;
            $data['flag'] = 1;

            $sql = "select id from {$this->prename}member_recharge where rechargeId=?";
            do {
                $data['rechargeId'] = mt_rand(100000, 999999);
            } while ($this->getValue($sql, $data['rechargeId']));

            if ($this->insertRow($this->prename . 'member_recharge', $data)) {
                $dataId = $this->lastInsertId();
                $this->addCoin(array(
                    'uid' => $user['uid'],
                    'typea' => 1,
                    'liqType' => 12,
                    'coin' => $amount,
                    'extfield0' => $dataId,
                    'extfield1' => $data['rechargeId'],
                    'info' => '上级充值'
                ));

                //扣除
                $this->addCoin(array(
                    'uid' => $this->user['uid'],
                    'typea' => 1,
                    'liqType' => 13,
                    'coin' => -$amount,
                    'extfield0' => $dataId,
                    'extfield1' => $data['rechargeId'],
                    'info' => '上级充值成功扣款'
                ));
            }
            $this->commit();
//            echo "充值成功";
            parent::json_success("充值成功");
        } catch (Exception $e) {
            $this->rollBack();
            parent::json_fails('未知出错');
        }
    }

    public final function shareBonus()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/share-bonus.php');
        parent::json_display();
    }

    public final function shareBonusInfo()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
//        $this->display('team/share-bonus-info.php');

        $sql = 'select * from {$this->prename}bonus_log where uid=' . $this->user['uid'] . ' and bonusStatus = 0 order by id DESC Limit 1';
        $lastBonus = $this->getRow($sql);
        if ($lastBonus) {
        } else {
            $dayDate = date('d', time());
            if (1 <= $dayDate && $dayDate < 11) {
                $startTime = date('Y-m-1', time()) . ' 03:00:00';
                $endTime = date('Y-m-11', time()) . ' 03:00:00';
            } elseif (11 <= $dayDate && $dayDate < 21) {
                $startTime = date('Y-m', time()) . '-11 03:00:00';
                $endTime = date('Y-m', time()) . '-21 03:00:00';
            } elseif (21 <= $dayDate) {
                $startTime = date('Y-m', time()) . '-21 03:00:00';
                $endTime = date('Y-m-1', strtotime('+1 month')) . ' 03:00:00';
            }
        }

        $lossAmoutCount = $this->getValue("select sum(lossAmount) as lossAmount from {$this->prename}bonus_log_benjin where uid=? and bonusStatus = 1", $this->user['uid']);
        $lossAmoutCount = $this->ifs($lossAmoutCount, sprintf('%.2f', 0)) . '元';

        $bonusAmoutCount = $this->getValue("select sum(bonusAmount) as bonusAmount from {$this->prename}bonus_log_benjin where uid=? and bonusStatus = 1", $this->user['uid']);
        $bonusAmoutCount = $this->ifs($bonusAmoutCount, sprintf('%.2f', 0)) . '元';

        $bonusCount = $this->getValue("select count(*) from {$this->prename}bonus_log where uid=? and bonusStatus = 1", $this->user['uid']);
        $bonusCount = $bonusCount . '次';

        $result = [
            'lastBonus' => $lastBonus,
            'startTime' => isset($startTime) ? $startTime : null,
            'endTime' => isset($endTime) ? $endTime : null,
            'lossAmoutCount' => $lossAmoutCount,
            'bonusAmoutCount' => $bonusAmoutCount,
            'bonusCount' => $bonusCount,
        ];

        parent::json_display($result);
    }

    public final function getShareBonus()
    {
        if (!$this->user['type']) parent::json_fails('非法操作!');
        $uid = $this->user['uid'];
        if (!$uid) die('参数出错');
        $sql = 'select * from {$this->prename}bonus_log where uid=' . $this->user['uid'] . ' and bonusStatus = 0 order by id DESC Limit 1';
        $lastBonus = $this->getRow($sql);
        if ($lastBonus) {
            //直接将用户分红提现，提现信息提交至后台
            $bank = $this->getRow("select * from {$this->prename}member_bank where uid=? limit 1", $this->user['uid']);
            if ($bank['bankId']) {
                $para['username'] = $bank['username'];
                $para['account'] = $bank['account'];
                $para['bankId'] = $bank['bankId'];
                $this->beginTransaction();
                try {
                    $this->freshSession();
                    // 插入提现请求表
                    $para['actionTime'] = $this->time;
                    $para['uid'] = $this->user['uid'];
                    $para['info'] = '分红提现';
                    $para['amount'] = $lastBonus['bonusAmount'];
                    if (!$this->insertRow($this->prename . 'member_cash', $para)) parent::json_fails('领取分红请求出错');
                    if (!$this->updateRows($this->prename . 'bonus_log', array('bonusStatus' => 1), 'id=' . $lastBonus['id'])) parent::json_fails('领取分红请求出错');
                    $id = $this->lastInsertId();

                    $this->commit();
                    echo '分红提现成功，分红提现将在10分钟内到帐，如未到账请联系在线客服。';

                } catch (Exception $e) {
                    $this->rollBack();
                    throw $e;
                }
            } else {
                die('您还没有设置银行账户，不可领取分红！！！');
            }
        } else {
            die('您本期没有可分红金额或者您已经领取了本期分红！！！');
        }
    }
}