<?php

class Index extends WebLoginBase
{
    public $pageSize = 10;

    public final function game($type = null, $groupId = null, $played = null)
    {
        if ($type)
            $this->type = intval($type);
        if ($groupId) {
            $this->groupId = intval($groupId);
        } else {
            // 默认进入定位胆玩法
            $this->groupId = 6;
        }
        if ($played)
            $this->played = intval($played);
        $this->getSystemSettings();
        if ($this->settings['picGG'])
            setcookie('pic-gg', $this->settings['picGG']);
//        $this->display('main.php');

        if ($this->type) {
            $row = $this->getRow("select enable,title from {$this->prename}type where id={$this->type}");
            if (!$row['enable']) {
                echo $row['title'] . '已经关闭';
                exit;
            }
        } else {
            $this->type = 1;
            $this->groupId = 2;
            $this->played = 10;
        }
        if ($_COOKIE['mode']) {
            $mode = $_COOKIE['mode'];
        } else {
            $mode = 2.000;
        }

        $row1 = $this->getRows("select * from {$this->prename}content where enable=1 and nodeId=1");
        $row2 = $this->getRows("select * from {$this->prename}message_receiver where to_uid={$this->user['uid']}");

        $lastNo = $this->getGameLastNo($this->type);
        $kjHao = $this->getValue("select data from {$this->prename}data where type={$this->type} and number='{$lastNo['actionNo']}'");
        if ($kjHao)
            $kjHao = explode(',', $kjHao);

        $actionNo = $this->getGameNo($this->type);
        $types = $this->getTypes();

        $kjdTime = $types[$this->type]['data_ftime'];
        $diffTime = strtotime($actionNo['actionTime']) - $this->time - $kjdTime;
        $kjDiffTime = strtotime($lastNo['actionTime']) - $this->time;

        $sql = "select groupName from {$this->prename}played_group where id=?";
        $groupName = $this->getValue($sql, $this->groupId);

        $sql = "select id, name, playedTpl, enable  from {$this->prename}played where enable=1 and groupId=? order by sort";
        $playeds = $this->getRows($sql, $this->groupId);

        $sql = "select simpleInfo, info, example  from {$this->prename}played where id=?";
        $playeds2 = $this->getRows($sql, $this->played);

        $result = [
            'type' => $this->type,
            'groupId' => $this->groupId,
            'this_played' => $this->played,
            'model' => $mode,
            'row1' => $row1,
            'row2' => $row2,
            'kjHao' => $kjHao,
            'kjdTime' => $kjdTime,
            'diffTime' => $diffTime,
            'kjDiffTime' => $kjDiffTime,
            'actionNo' => $actionNo,
            'groupName' => $groupName,
            'played' => $played,
            'playeds' => $playeds,
            'playeds2' => $playeds2,
        ];

        parent::json_display($result);
    }

    //游戏记录
    public final function yxjl()
    {
        $this->display('index/inc_game_order_history_index.php');
    }

    //平台首页
    public final function main()
    {
//        $this->display('index.php');
        $types = $this->getTypes();
        parent::json_display(['types' => $types]);
    }

    public final function group($type, $groupId)
    {
        $this->type = intval($type);
        $this->groupId = intval($groupId);
//        $this->display('index/load_tab_group.php');
        $sql = "select * from {$this->prename}played_group where id=?";
        $group = $this->getRow($sql, $this->groupId);

        $sql = "select id, name, playedTpl, enable from {$this->prename}played where groupId=? order by sort";
        $playeds = $this->getRows($sql, $this->groupId);
        if (!$this->played)
            $this->played = $playeds[0]['id'];

        $sql = "select simpleInfo, info, example  from {$this->prename}played where id=?";
        $playeds2 = $this->getRows($sql, $this->played);

        $sql = "select type, groupId, playedTpl from {$this->prename}played where id=?";
        $data = $this->getRow($sql, $this->played);

        $result = [
            'group' => $group,
            'playeds' => $playeds,
            'playeds2' => $playeds2,
            'data' => $data,
        ];

        parent::json_display($result);
    }

    public final function group_new($type, $groupId)
    {
        $this->type = intval($type);
        $this->groupId = intval($groupId);
//        $this->display('index/load_tab_group_new.php');
    }

    public final function played($type, $playedId)
    {
        $sql = "select type, groupId, playedTpl from {$this->prename}played where id=?";
        $data = $this->getRow($sql, intval($playedId));
        $this->type = intval($type);
        if ($data['playedTpl']) {
            $this->groupId = $data['groupId'];
            $this->played = intval($playedId);
            // $this->display('index/load_tab_group.php');
        } else {

        }
    }


    public final function played_new($type, $playedId)
    {
        $sql = "select type, groupId, playedTpl from {$this->prename}played where id=?";
        $data = $this->getRow($sql, intval($playedId));
        $this->type = intval($type);
        if ($data['playedTpl']) {
            $this->groupId = $data['groupId'];
            $this->played = intval($playedId);
//            $this->display('index/load_tab_group_new.php');

            $sql = "select groupName from {$this->prename}played_group where id=?";
            $groupName = $this->getValue($sql, $this->groupId);

            $sql = "select id, name, playedTpl, enable  from {$this->prename}played where enable=1 and groupId=? order by sort";
            $playeds = $this->getRows($sql, $this->groupId);

            if (!$this->played)
                $this->played = $playeds[0]['id'];

            $result = [
                'groupName' => $groupName,
                'playeds' => $playeds,
                'played' => $this->played,
            ];

            parent::json_display($result);

        } else {

        }
    }

    // 加载玩法介绍信息
    public final function playTips($playedId)
    {
//        $this->display('index/inc_game_tips.php', 0, intval($playedId));

//        $args = $this->


        $sql = "select simpleInfo, info, example  from {$this->prename}played where id=?";
        $playeds = $this->getRows($sql, $playedId);

        $result = [
            'playedId' => $playedId,
            'playeds' => $playeds,
        ];

        parent::json_display($result);
    }

    public final function getQiHao($type)
    {
        $type = intval($type);
        $thisNo = $this->getGameNo($type);
        return array(
            'lastNo' => $this->getGameLastNo($type),
            'thisNo' => $this->getGameNo($type),
            'diffTime' => strtotime($thisNo['actionTime']) - $this->time,
            'validTime' => $thisNo['actionTime'],
            'kjdTime' => $this->getTypeFtime($type)
        );
    }

    // 弹出追号层HTML
    public final function zhuiHaoModal($type)
    {
        $this->display('index/game-zhuihao-modal.php');
    }

    // 追号层加载期号
    public final function zhuiHaoQs($type, $mode, $count)
    {
        $this->type = intval($type);
//        $this->display('index/game-zhuihao-qs.php', 0, $mode, $count);


        $list = $this->getGameNos($this->type, $count);

        parent::json_display(['list' => $list]);
    }

    // 加载历史开奖数据
    public final function getHistoryData($type)
    {
        $this->type = intval($type);
        $this->display('index/inc_data_history.php');
    }

    // 加载历史开奖数据2
    public final function getHistoryData2($type)
    {
        $this->type = intval($type);
        $this->display('index/inc_data_history2.php');
    }

    // 加载历史开奖数据3
    public final function getHistoryData3($type)
    {
        $this->type = intval($type);
        $this->display('index/inc_data_history3.php');
    }

    // 加载历史开奖数据2
    public final function getHistoryDataiframe($type)
    {
        $this->type = intval($type);
        $this->display('index/inc_data_iframe.php');
    }

    // 历史开奖HTML
    public final function historyList($type)
    {
        $this->type = intval($type);
        $this->display('index/history-list.php', $pageSize, $type);
    }

    public final function getLastKjData($type)
    {
        $ykMoney = 0;
        $czName = '重庆时时彩';
        $this->type = intval($type);
        if (!$lastNo = $this->getGameLastNo($this->type))
            parent::json_fails('查找最后开奖期号出错');
        if (!$lastNo['data'] = $this->getValue("select data from {$this->prename}data where type={$this->type} and number='{$lastNo['actionNo']}'"))
            parent::json_fails('获取数据出错');

        $thisNo = $this->getGameNo($this->type);
        $lastNo['actionName'] = $czName;
        $lastNo['thisNo'] = $thisNo['actionNo'];
        $lastNo['diffTime'] = strtotime($thisNo['actionTime']) - $this->time;
        $lastNo['kjdTime'] = $this->getTypeFtime($type);
//        return $lastNo;
        parent::json_success(null, 0, ['lastNo' => $lastNo]);
    }

    // 加载人员信息框
    public final function userInfo()
    {
        $this->display('index/inc_user.php');
    }

    public final function usercenter_xtgg()
    {
        $this->display('usercenter_xtgg.php');
    }

    // 加载消息
    public final function msg()
    {
//        $this->display('index/inc_msg.php');

        $sql = "select count(id) from {$this->prename}message_receiver where to_uid=? and is_readed=0 and is_deleted=0";

        $num = $this->getValue($sql, $this->user['uid']);

        $result = $this->ifs($num, '0');

        parent::json_display(['num' => $result]);
    }
}