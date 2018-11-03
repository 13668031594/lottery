<?php

class Display extends WebLoginBase
{

    public final function freshTodayKJData($type = null)
    {
        if ($type) $this->type = $type;
//		$this->display('index/inc_game_todaydata.php');

        if ($this->type == 1 || $this->type == 3 || $this->type == 12 || $this->type == 14) {
            $typeNums = $this->getValue("select count(*) from {$this->prename}data_time where type={$this->type}");
            $date = strtotime('00:00');
            $perCunm = 30;
            for ($i = 1; $i <= $typeNums; $i++) {
                //序号
                $paixu = substr(1000 + $i, 1);
                $number = 1000 + $i;

                if ($this->type == 1) {
                } else if ($this->type == 3) {
                    $perCunm = 21;
                } else if ($this->type == 12) {
                    $perCunm = 24;
                    $number = 100 + $i;

                } else if ($this->type == 14) {
                    $perCunm = 72;
                }

                $number = date('Ymd-', $date) . substr($number, 1);

                $sql = "select data from {$this->prename}data where type={$this->type} and number='$number'";
                $kjdata = $this->getValue($sql);
                if (!$kjdata) $kjdata = ",,,,";
                $dArry = explode(",", $kjdata);
                $var['d1'] = $dArry[0];
                $var['d2'] = $dArry[1];
                $var['d3'] = $dArry[2];
                $var['d4'] = $dArry[3];
                $var['d5'] = $dArry[4];

                if ($var['d1'] > 4) {
                    $d1dx = "大";
                } else {
                    $d1dx = "小";
                }
                if ($var['d1'] % 2) {
                    $d1ds = "单";
                } else {
                    $d1ds = "双";
                }
                if ($var['d2'] > 4) {
                    $d2dx = "大";
                } else {
                    $d2dx = "小";
                }
                if ($var['d2'] % 2) {
                    $d2ds = "单";
                } else {
                    $d2ds = "双";
                }

                if (strlen($var['d3']) > 0 && strlen($var['d4']) > 0 && strlen($var['d5']) > 0) {

                    if ($var['d3'] == $var['d4'] && $var['d4'] == $var['d5']) {
                        $h3xt = "豹子";
                    } else if ($var['d3'] == $var['d4'] || $var['d4'] == $var['d5'] || $var['d3'] == $var['d5']) {
                        $h3xt = "组三";
                    } else {
                        $h3xt = "组六";
                    }
                } else {
                    $h3xt = "---";
                }
            }
        }


        $numbers = isset($dArry) ? $this->iff(join("",$dArry),join("",$dArry),"-----") : null;

        $result = [
            'numbers' => $numbers,
            'perCunm' => isset($perCunm) ? $perCunm : null,
            'paixu' => isset($paixu) ? $paixu : null,
            'd1dx' => isset($d1dx) ? $d1dx : null,
            'd2dx' => isset($d2dx) ? $d2dx : null,
            'd2ds' => isset($d2ds) ? $d2ds : null,
            'h3xt' => isset($h3xt) ? $h3xt : null,
        ];

        parent::json_display($result);
    }

    public final function freshKanJiang($type = null, $groupId = null, $played = null)
    {
        if ($type)
            $this->type = intval($type);
        if ($groupId)
            $this->groupId = intval($groupId);
        if ($played)
            $this->played = intval($played);
//        $this->display('index/inc_data_current.php');


    }

    //新加
    public final function freshKanJiang_new($type = null, $groupId = null, $played = null)
    {
        if ($type)
            $this->type = intval($type);
        if ($groupId)
            $this->groupId = intval($groupId);
        if ($played)
            $this->played = intval($played);
//        $this->display('index/inc_data_current_new.php');

        @session_start();
        if($this->type==34 || $this->type==77){
            $mode=1.00;
            $lastNo=$this->getGameLastNo($this->type);
            $kjHao=$this->getValue("select data from {$this->prename}data where type={$this->type} and number='{$lastNo['actionNo']}'");
            if($kjHao) $kjHao=explode(',', $kjHao);

            $actionNo=$this->getGameNo($this->type);
            $types=$this->getTypes();
            $kjdTime=$types[$this->type]['data_ftime'];
            $diffTime=strtotime($actionNo['actionTime'])-$this->time;
        }else{
            $lastNo=$this->getGameLastNo($this->type);
            $kjHao=$this->getValue("select data from {$this->prename}data where type={$this->type} and number='{$lastNo['actionNo']}'");
            if($kjHao) $kjHao=explode(',', $kjHao);
            $actionNo=$this->getGameNo($this->type);
            $types=$this->getTypes();
            $kjdTime=$types[$this->type]['data_ftime'];
            $diffTime=strtotime($actionNo['actionTime'])-$this->time-$kjdTime;
            $kjDiffTime=strtotime($lastNo['actionTime'])-$this->time;
        }

        $result = [
            'type' => $this->type,
            'groupId' => $this->groupId,
            'played' => $this->played,
            'mode' => isset($mode) ? $mode : null,
            'kjHao' => $kjHao,
            'kjdTime' => $kjdTime,
            'diffTime' => $diffTime,
            'kjDiffTime' => isset($kjDiffTime) ? $kjDiffTime : null,
        ];

        parent::json_display($result);
    }

    public final function sign()
    {
        $minCoin = 10; // 最底签到资金
        $liqType = 50; // 流动资金类型：签到
        $liqInfo = '签到活动';

        // 查看可用资金
        if ($this->user['benjin'] < $minCoin)
//            throw new Exception(sprintf('只有可用资金大于%.2f元时才能参与每日签到活动。', $minCoin));
            parent::json_fails(sprintf('只有可用资金大于%.2f元时才能参与每日签到活动。', $minCoin));

        // 读取签到每次赠送资金
        // 如果资金为0，表示关闭这活动
        $this->getSystemSettings();
        if (!$coin = floatval($this->settings['huoDongSign']))
//            throw new Exception('很遗憾，每日签到活动已经结束。');
            parent::json_fails('很遗憾，每日签到活动已经结束。');

        // 只有绑定银行卡才能参与签到活动
        $sql = "select bankId from {$this->prename}member_bank where `uid`={$this->user['uid']} and enable=1 order by id limit 1";
        if (!$this->getValue($sql))
//            throw new Exception('只有绑定银行卡后才能参与签到活动');
            parent::json_fails('只有绑定银行卡后才能参与签到活动');
        //throw new Exception($sql);

        // 查询当日是否已经签到过
        $sql = "select count(*) from {$this->prename}coin_log_benjin where actionTime>=? and liqType=$liqType and `uid`={$this->user['uid']}";
        if ($this->getValue($sql, strtotime('00:00')))
//            throw new Exception('今天您已经签到过了，请明天再来');
            parent::json_fails('今天您已经签到过了，请明天再来');
        $this->addCoin(array(
            'info' => $liqInfo,
            'liqType' => $liqType,
            'coin' => $coin
        ));
//        return '签到成功,系统赠送您' . $coin . '元!请注意查收。';
        parent::json_success('签到成功,系统赠送您' . $coin . '元!请注意查收。');
    }
}