<?php

class Report extends WebLoginBase
{
    public $type;
    public $pageSize = 14;

    // �ʱ��б�
    public final function coin($type = 0)
    {
        $this->type = intval($type);
        $this->action = 'coinlog';
        $this->display('report/coin.php');
    }

    public final function membercoin($type = 0)
    {
        $this->type = intval($type);
        $this->action = 'coinlog';
        $this->display('report/membercoin.php');
    }

    public final function coinlog($type = 0)
    {
        $this->type = intval($type);
//        $this->display('report/coin-log.php');


    }

    // �ܽ����ѯ
    public final function count()
    {
        $this->action = 'countSearch';
        $this->display('report/count.php');
    }

    public final function countSearch()
    {
//		$this->display('report/count-list.php');
        $para = $_GET;

        // 时间限制
        if ($para['fromTime'] && $para['toTime']) {
            $fromTime = strtotime($para['fromTime']);
            $toTime = strtotime($para['toTime']);
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
            $toTime = strtotime($para['toTime']);
            $betTimeWhere = "and b.actionTime < $toTime";
            $cashTimeWhere = "and c.actionTime < $toTime";
            $rechargeTimeWhere = "and r.actionTime < $toTime";
            $fanDiaTimeWhere = "and actionTime < $toTime";
            $fanDiaTimeWhere2 = "and l.actionTime < $toTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        } else {
            if ($GLOBALS['fromTime'] && $GLOBALS['toTime']) {
                $betTimeWhere = "and actionTime between {$GLOBALS['fromTime']} and {$GLOBALS['toTime']}";
                $cashTimeWhere = "and c.actionTime between {$GLOBALS['fromTime']} and {$GLOBALS['toTime']}";
                $rechargeTimeWhere = "and r.actionTime between {$GLOBALS['fromTime']} and {$GLOBALS['toTime']}";
                $fanDiaTimeWhere = "and actionTime between {$GLOBALS['fromTime']} and {$GLOBALS['toTime']}";
                $fanDiaTimeWhere2 = "and l.actionTime between {$GLOBALS['fromTime']} and {$GLOBALS['toTime']}";
                $brokerageTimeWhere = $fanDiaTimeWhere2;
            }
        }

        // 用户限制
        $uid = $this->user['uid'];
        $userWhere = "and u.uid=$uid";
        $userWhere3 = "and concat(',', u.parents, ',') like '%,$uid,%'";

//        $sql = "select u.username, u.benjin, u.uid, u.parentId, sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount, (select sum(c.amount) from {$this->prename}member_cash c where c.`uid`=u.`uid` and c.state=0 $cashTimeWhere) cashAmount,(select sum(r.amount) from {$this->prename}member_recharge r where r.`uid`=u.`uid` and r.state in(1,2,9) $rechargeTimeWhere) rechargeAmount, (select sum(l.coin) from {$this->prename}coin_log_benjin l where l.`uid`=u.`uid` and l.liqType in(50,51,52,53,56) $brokerageTimeWhere) brokerageAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere where 1 $userWhere";
        $sql = "select u.username, u.benjin, u.uid, u.parentId, sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount, (select sum(c.amount) from {$this->prename}member_cash c where c.`uid`=u.`uid` and c.state=0 $cashTimeWhere) cashAmount,(select sum(r.amount) from {$this->prename}member_recharge r where r.`uid`=u.`uid` and r.state in(1,2,9) $rechargeTimeWhere) rechargeAmount, (select sum(l.coin) from {$this->prename}coin_log_benjin l where l.`uid`=u.`uid`  and l.liqType in(50,51,52,53) $brokerageTimeWhere) brokerageAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere where 1 $userWhere";
        //echo $sql;exit;

        $this->pageSize -= 1;
        if ($this->action != 'countSearch') $this->action = 'countSearch';
        $list = $this->getPage($sql . ' group by u.uid', $this->page, $this->pageSize);
        if (!$list['total']) {
            $uParentId2 = $this->getValue("select parentId from {$this->prename}members where uid=?", intval($para['parentId']));
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
        $sql = "select sum(coin) from {$this->prename}coin_log_benjin where uid=? and liqType in(2,3) $fanDiaTimeWhere";

        $rel = "/index.php/{$this->controller}/{$this->action}";

        if ($list['data']) foreach ($list['data'] as $var) {

            if ($var['username'] != '没有用户') {
                $var['fanDianAmount'] = $this->getValue($sql, $var['uid']);
                //echo $sql.$var['uid'];
                $pId = $var['uid'];
            }
            $count['betAmount'] += $var['betAmount'];
            $count['zjAmount'] += $var['zjAmount'];
            $count['fanDianAmount'] += $var['fanDianAmount'];
            $count['brokerageAmount'] += $var['brokerageAmount'];
            $count['cashAmount'] += $var['cashAmount'];
            $count['coin'] += $var['coin'];
            $count['rechargeAmount'] += $var['rechargeAmount'];
        }

        $result = [
            'params' => $params,
            'count' => $count,
            'rel' => $rel,
            'list' => $list,
        ];

        parent::json_display($result);
    }

    public final function dateList()
    {
        $para = $_GET;
        //echo $para['uid'];
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
            $toTime = strtotime('00:00');
            $betTimeWhere = "and b.actionTime > $toTime";
            $cashTimeWhere = "and c.actionTime > $toTime";
            $rechargeTimeWhere = "and r.actionTime > $toTime";
            $fanDiaTimeWhere = "and actionTime > $toTime";
            $fanDiaTimeWhere2 = "and l.actionTime > $toTime";
            $brokerageTimeWhere = $fanDiaTimeWhere2;
        }

        // 用户限制
        $amountTitle = '全部总结';
        if ($para['parentId'] = intval($para['parentId'])) {
            // 用户ID限制
            $userWhere = "and u.parentId={$para['parentId']}";
            $parentIdWhere = "and u.parentId={$para['parentId']}";
            $uid = $para['parentId'];
            $userWhere3 = "and concat(',', u.parents, ',') like '%,$uid,%'";
            $amountTitle = '团队统计';
        }
        if ($para['uid'] = intval($para['uid'])) {
            // 用户ID限制
            $uParentId = $this->getValue("select parentId from {$this->prename}members where uid=?", $para['uid']);
            $userWhere = "and u.uid=$uParentId";
            $uid = $uParentId;
            $userWhere3 = "and concat(',', u.parents, ',') like '%,$uid,%'";
            $amountTitle = '团队统计';
        }
        if ($para['username'] && $para['username'] != '用户名') {
            $para['username'] = wjStrFilter($para['username']);
            if (!ctype_alnum($para['username'])) throw new Exception('用户名包含非法字符,请重新输入');
            // 用户名限制
            $userWhere = "and u.username='{$para['username']}'";
            $uid = $this->getValue("select uid from {$this->prename}members where username='{$para['username']}'");
            $userWhere3 = "and concat(',', u.parents, ',') like '%,$uid,%'";
            $amountTitle = '团队统计';
        }

        $sql = "select u.username, u.benjin,u.fenhong,u.yingli, u.uid, u.parentId, sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount, (select sum(c.amount) from {$this->prename}member_cash c where c.`uid`=u.`uid` and c.state=0 $cashTimeWhere) cashAmount,(select sum(r.amount) from {$this->prename}member_recharge r where r.`uid`=u.`uid` and r.state in(1,2,9) $rechargeTimeWhere) rechargeAmount, (select sum(l.coin) from {$this->prename}coin_log_benjin l where l.`uid`=u.`uid` and l.liqType in(50,51,52,53) $brokerageTimeWhere) brokerageAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere where 1 $userWhere";
        //echo $sql;exit;

        $this->pageSize -= 1;
        if ($this->action != 'betDate') $this->action = 'betDate';
        $list = $this->getPage($sql . ' group by u.uid', $this->page, $this->pageSize);
        if (!$list['total']) {
            $uParentId2 = $this->getValue("select parentId from {$this->prename}members where uid=?", $para['parentId']);
            $list = array(
                'total' => 1,
                'data' => array(array(
                    'parentId' => $uParentId2,
                    'uid' => $para['parentId'],
                    'username' => '没有下级了'
                ))
            );
            $noChildren = true;
        }
        $params = http_build_query($_REQUEST, '', '&');
        $count = array();

        //$sql2="select sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount, (select sum(c.amount) from {$this->>$this->prename}member_cash c where c.`uid`=u.`uid` and c.state=0 $cashTimeWhere) cashAmount,(select sum(r.amount) from {$this->>$this->prename}member_recharge r where r.`uid`=u.`uid` and r.state in(1,2,9) $rechargeTimeWhere) rechargeAmount from {$this->>$this->prename}members u left join {$this->>$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere $parentIdWhere";
        $sql2 = "select sum(b.mode * b.beiShu * b.actionNum) betAmount, sum(b.bonus) zjAmount from {$this->prename}members u left join {$this->prename}bets b on u.uid=b.uid and b.isDelete=0 $betTimeWhere $userWhere3";

        $all = $this->getRow($sql2);
        $all['brokerageAmount'] = $this->getValue("select sum(l.coin) from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType in(50,51,52,53) and l.uid=u.uid $brokerageTimeWhere $userWhere3", $var['uid']);
        $all['rechargeAmount'] = $this->getValue("select sum(r.amount) from {$this->prename}member_recharge r, {$this->prename}members u where r.state in (1,2,9) and r.uid=u.uid $rechargeTimeWhere $userWhere3", $var['uid']);
        $all['cashAmount'] = $this->getValue("select sum(c.amount) from {$this->prename}member_cash c, {$this->prename}members u  where c.state=0 and c.uid=u.uid $cashTimeWhere $userWhere3", $var['uid']);
        $all['benjin'] = $this->getValue("select sum(u.benjin) benjin from {$this->prename}members u where 1 $userWhere3", $var['uid']);
        $all['yingli'] = $this->getValue("select sum(u.yingli) yingli from {$this->prename}members u where 1 $userWhere3", $var['uid']);
        $all['fenhong'] = $this->getValue("select sum(u.fenhong) fenhong from {$this->prename}bets u where 1 $userWhere3", $var['uid']);

        if ($list['data']) foreach ($list['data'] as $var) {

            if ($var['username'] != '没有下级了') {
                $pId = $var['uid'];
                $var['teamwin'] = $this->getValue("select sum(l.coin) fandianAll from {$this->prename}coin_log_benjin l, {$this->prename}members u where l.liqType in(2,3) and l.uid=u.uid and concat(',', u.parents, ',') like '%,$pId,%' $fanDiaTimeWhere") + $this->getValue("select sum(b.bonus-b.mode * b.beiShu * b.actionNum) betZjAmount from {$this->prename}members u ,{$this->prename}bets b where u.uid=b.uid and b.isDelete=0 and concat(',', u.parents, ',') like '%,$pId,%' $betTimeWhere");
            }

            $count['betAmount'] += $var['betAmount'];
            $count['zjAmount'] += $var['zjAmount'];
            $count['fanDianAmount'] += $var['fanDianAmount'];
            $count['brokerageAmount'] += $var['brokerageAmount'];
            $count['cashAmount'] += $var['cashAmount'];
            $count['benjin'] += $var['benjin'];
            $count['fenhong'] += $var['fenhong'];
            $count['yingli'] += $var['yingli'];
            $count['rechargeAmount'] += $var['rechargeAmount'];
            $count['teamwin'] += $var['teamwin'];
        }

        $result = [
            'all' => $all,
            'count' => $count,
            'list' => $list,
        ];

        parent::json_display($result);
    }

    public final function benjin()
    {
        $this->getTypes();
        $this->getPlayeds();

        $uid = $_GET['uid'];

        if (!empty($uid)){

            $sql = "SELECT uid FROM {$this->prename}members WHERE parents LIKE '%{$this->user['uid']}%' AND uid = {$uid}";
            $test = $this->getValue($sql);

            if (empty($test))parent::json_fails(['只能看自己的下级']);
        }else{

            $uid = $this->user['uid'];
        }

        $uid = empty($uid) ? $this->user['uid'] : $uid;

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
        if ($_REQUEST['liqType']) {
            $liqTypeWhere = ' and liqType=' . intval($_REQUEST['liqType']);
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }


        //用户限制
        $userWhere = " and u.uid={$uid}";

        // 冻结查询
        if ($this->action == 'fcoinModal') {
            $fcoinModalWhere = 'and l.fcoin!=0';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_benjin l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘');

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
                103 => '抢庄返点',
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

        foreach ($list['data'] as &$v){

            if ($v['extfield0']) {
                if (in_array($v['liqType'], array(101, 108, 255, 6, 7, 102, 5, 11, 100, 10, 103, 104, 105, 2))) {
                    $v['extfield'] = '投注：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(1, 9, 52, 54))) {
                    $v['extfield'] = '充值：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(8, 106, 107))) {
                    $v['extfield'] = '提现：'.$v['extfield0'];
                } else {
                    $v['extfield'] = '--';
                }
            } else {
                $v['extfield'] = '--';
            }
        }

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName
        ];

        parent::json_display($result);
    }

    public final function yingli()
    {
        $this->getTypes();
        $this->getPlayeds();

        $uid = $_GET['uid'];
        if (!empty($uid)){

            $sql = "SELECT uid FROM {$this->prename}members WHERE parents LIKE '%{$this->user['uid']}%' AND uid = {$uid}";
            $test = $this->getValue($sql);

            if (empty($test))parent::json_fails(['只能看自己的下级']);
        }else{

            $uid = $this->user['uid'];
        }

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
        if ($_REQUEST['liqType']) {
            $liqTypeWhere = ' and liqType=' . intval($_REQUEST['liqType']);
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }


        //用户限制
        $userWhere = " and u.uid={$uid}";

        // 冻结查询
        if ($this->action == 'fcoinModal') {
            $fcoinModalWhere = 'and l.fcoin!=0';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_yingli l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘');

        $liqTypeName = array(
            '账户类' => array(
                667 => '盈利日转'//new
            ),
            '游戏类' => array(
                701 => '盈利',
            ),
        );

        foreach ($list['data'] as &$v){

            if ($v['extfield0']) {
                if (in_array($v['liqType'], array(101, 108, 255, 6, 7, 102, 5, 11, 100, 10, 103, 104, 105, 2))) {
                    $v['extfield'] = '投注：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(1, 9, 52, 54))) {
                    $v['extfield'] = '充值：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(8, 106, 107))) {
                    $v['extfield'] = '提现：'.$v['extfield0'];
                } else {
                    $v['extfield'] = '--';
                }
            } else {
                $v['extfield'] = '--';
            }
        }

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName
        ];

        parent::json_display($result);
    }

    public final function fenhong()
    {
        $this->getTypes();
        $this->getPlayeds();

        $uid = $_GET['uid'];
        if (!empty($uid)){

            $sql = "SELECT uid FROM {$this->prename}members WHERE parents LIKE '%{$this->user['uid']}%' AND uid = {$uid}";
            $test = $this->getValue($sql);

            if (empty($test))parent::json_fails(['只能看自己的下级']);
        }else{

            $uid = $this->user['uid'];
        }

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
        if ($_REQUEST['liqType']) {
            $liqTypeWhere = ' and liqType=' . intval($_REQUEST['liqType']);
            if ($_REQUEST['liqType'] == 2) $liqTypeWhere = ' and liqType between 2 and 3';
        }


        //用户限制
        $userWhere = " and u.uid={$uid}";

        // 冻结查询
        if ($this->action == 'fcoinModal') {
            $fcoinModalWhere = 'and l.fcoin!=0';
        }

        $sql = "select b.type, b.playedId, b.actionNo, b.mode, l.liqType, l.coin, l.fcoin, l.userCoin, l.actionTime, l.extfield0, l.extfield1, l.info, u.username from {$this->prename}members u, {$this->prename}coin_log_fenhong l left join {$this->prename}bets b on b.id=extfield0 where l.uid=u.uid $liqTypeWhere $timeWhere $userWhere $typeWhere $fcoinModalWhere and l.liqType not in(4,11,104) order by l.id desc";
        //echo $sql;

        $list = $this->getPage($sql, $this->page, $this->pageSize);
        $params = http_build_query($_REQUEST, '', '&');
        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘');

        $liqTypeName = array(
            '账户类' => array(
//            2 => '下级返点',
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

        foreach ($list['data'] as &$v){

            if ($v['extfield0']) {
                if (in_array($v['liqType'], array(101, 108, 255, 6, 7, 102, 5, 11, 100, 10, 103, 104, 105, 2))) {
                    $v['extfield'] = '投注：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(1, 9, 52, 54))) {
                    $v['extfield'] = '充值：'.$v['extfield0'];
                } else if (in_array($v['liqType'], array(8, 106, 107))) {
                    $v['extfield'] = '提现：'.$v['extfield0'];
                } else {
                    $v['extfield'] = '--';
                }
            } else {
                $v['extfield'] = '--';
            }
        }

        $result = [
            'list' => $list,
            'params' => $params,
            'modeName' => $modeName,
            'liqTypeName' => $liqTypeName
        ];

        parent::json_display($result);
    }
}
