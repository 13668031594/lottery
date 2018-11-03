<?php
@session_start();

class Box extends WebLoginBase
{
    public $title = '\x51\x51\x34\x31\x30\x37\x34\x39\x39\x38\x35';
    private $vcodeSessionName = 'lottery_vcode_session_name';
    public $pageSize = 999;

    public final function receive()
    {
        $this->action = 'searchReceive';
//		$this->display('box/receive.php');

        parent::json_display();
    }

    public final function send()
    {
        $this->action = 'sendsearchReceive';
//		$this->display('box/send.php');
        parent::json_display();
    }

    public final function write()
    {
//		$this->display('box/write.php');
        //开始时间
        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        //结束时间
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));
        parent::json_display(['fromTime' => $fromTime, 'toTime' => $toTime]);
    }

    public final function dowrite()
    {
//        if (!$_POST) throw new Exception('提交数据出错！');
        if (!$_POST) parent::json_fails('提交数据出错！');

        $touser = wjStrFilter($_POST['touser']);
        $users = wjStrFilter($_POST['users']);
        $para['title'] = wjStrFilter($_POST['title']);
        $para['content'] = wjStrFilter($_POST['content']);
        $para['from_username'] = $this->user['username'];
        $para['time'] = $this->time;
        $para['from_uid'] = $this->user['uid'];
        $para['from_deleted'] = 0;

        $sql = "select parentId from {$this->prename}members where uid=?";
        $sql2 = "select username from {$this->prename}members where uid=?";
        $sql3 = "select from_uid from {$this->prename}message_sender where mid=?";
        $sql4 = "select from_username from {$this->prename}message_sender where mid=?";
        if ($touser == 'parent') {

            $vcode = intval($_POST['vcode']);
//            if ($vcode != $_SESSION[$this->vcodeSessionName]) throw new Exception('验证码不正确。');
            if ($vcode != $_SESSION[$this->vcodeSessionName]) parent::json_fails('验证码不正确。');

            //验证码使用完之后要清空
            unset($_SESSION[$this->vcodeSessionName]);

//            if (!$parentid = $this->getValue($sql, $this->user['uid'])) throw new Exception('您无上级代理！');
            if (!$parentid = $this->getValue($sql, $this->user['uid'])) parent::json_fails('您无上级代理！');


            $this->insertRow($this->prename . 'message_sender', $para);
            $update['mid'] = $this->lastInsertId();

            $update['to_username'] = $this->getValue($sql2, $parentid);
            $update['to_uid'] = $parentid;
            $update['is_readed'] = 0;
            $update['is_deleted'] = 0;
            $this->insertRow($this->prename . 'message_receiver', $update);
            return '发送成功';

        } else if ($touser == 'children') {
            $vcode = intval($_POST['vcode']);

//            if ($vcode != $_SESSION[$this->vcodeSessionName]) throw new Exception('验证码不正确。');
            if ($vcode != $_SESSION[$this->vcodeSessionName]) parent::json_fails('验证码不正确。');

            //验证码使用完之后要清空
            unset($_SESSION[$this->vcodeSessionName]);

            $this->insertRow($this->prename . 'message_sender', $para);
            $update['mid'] = $this->lastInsertId();

            $arr = explode(",", $users);
            foreach ($arr as $key => $var) {
//                if ($this->getValue($sql, $arr[$key]) != $this->user['uid']) throw new Exception('某些会员不是您的直属下级！');
                if ($this->getValue($sql, $arr[$key]) != $this->user['uid']) parent::json_fails('某些会员不是您的直属下级！');
                $update['to_username'] = $this->getValue($sql2, $arr[$key]);
                $update['to_uid'] = $arr[$key];
                $update['is_readed'] = 0;
                $update['is_deleted'] = 0;
                $this->insertRow($this->prename . 'message_receiver', $update);
            }
            return '发送成功';
        } else if ($touser == 'dowrite') {
            $dowrite['boxid'] = intval($_POST['boxid']);
            $vcode = intval($_POST['vcode']);

//            if ($vcode != $_SESSION[$this->vcodeSessionName]) throw new Exception('验证码不正确。');
            if ($vcode != $_SESSION[$this->vcodeSessionName]) parent::json_fails('验证码不正确。');

            //验证码使用完之后要清空
            unset($_SESSION[$this->vcodeSessionName]);

            $this->insertRow($this->prename . 'message_sender', $para);
            $update['mid'] = $this->lastInsertId();

            $update['to_username'] = $this->getValue($sql4, $dowrite['boxid']);
            $update['to_uid'] = $this->getValue($sql3, $dowrite['boxid']);
            $update['is_readed'] = 0;
            $update['is_deleted'] = 0;
            $this->insertRow($this->prename . 'message_receiver', $update);
//            return '发送成功';
            parent::json_success('发送成功');
        }

    }

    public final function answer($mid)
    {
        $mid = intval($mid);

        $sql = "select s.title, s.from_username, r.mid from {$this->prename}message_sender s, {$this->prename}message_receiver r where r.mid=? and r.to_uid={$this->user['uid']} and r.mid=s.mid";
        $sql2 = "select to_uid from {$this->prename}message_receiver where mid=? and to_username='{$this->user['username']}'";
//        if ($this->getValue($sql2, $mid) != $this->user['uid']) throw new Exception('这不是您的消息,无法回复！');
        if ($this->getValue($sql2, $mid) != $this->user['uid']) parent::json_fails('这不是您的消息,无法回复！');

        $sql3 = "update {$this->prename}message_receiver set is_readed=1 where mid=? and to_uid={$this->user['uid']}";
        $this->update($sql3, $mid);

        $data = $this->getRow($sql, $mid);
//        $this->display('box/answer.php', 0, $data);
        parent::json_display($data);
    }

    public final function deleteAll($id)
    {
        $id = wjStrFilter($id);
        $arr = explode("-", $id);
        $sql = "update {$this->prename}message_receiver set is_deleted=1 where mid=?";
        $sql2 = "select to_uid from {$this->prename}message_receiver where mid=? and to_username='{$this->user['username']}'";
        foreach ($arr as $key => $var) {
//            if ($this->getValue($sql2, $arr[$key]) != $this->user['uid']) throw new Exception('这不是您的消息,无法删除！');
            if ($this->getValue($sql2, $arr[$key]) != $this->user['uid']) parent::json_fails('这不是您的消息,无法删除！');
            $this->update($sql, $arr[$key]);
        }
    }

    public final function senddeleteAll($id)
    {
        $id = wjStrFilter($id);
        $arr = explode("-", $id);
        $sql = "update {$this->prename}message_sender set from_deleted=1 where mid=?";
        $sql2 = "select from_uid from {$this->prename}message_sender where mid=?";
        foreach ($arr as $key => $var) {
//            if ($this->getValue($sql2, $arr[$key]) != $this->user['uid']) throw new Exception('这不是您的消息,无法删除！');
            if ($this->getValue($sql2, $arr[$key]) != $this->user['uid']) parent::json_fails('这不是您的消息,无法删除！');
            $this->update($sql, $arr[$key]);
        }
    }

    public final function searchReceive()
    {
//        $this->display('box/searchReceive.php');

        // 消息类型限制
        switch($_REQUEST['state']){
            case 1:
                $stateWhere=' and r.is_readed=0';
                break;
            case 2:
                $stateWhere=' and r.is_readed=1';
                break;
            case 3:
                $stateWhere=' and r.is_readed between 0 and 1';
                break;
            default:
                $stateWhere=' and r.is_readed between 0 and 1';
        }

        $sql="select s.mid, r.is_readed, s.title, s.from_username, s.time from {$this->prename}message_sender s, {$this->prename}message_receiver r where r.to_uid={$this->user['uid']} and r.is_deleted=0 $timeWhere $stateWhere and r.mid=s.mid order by s.time DESC";
        $list=$this->getPage($sql, $this->page, $this->pageSize);
        $params=http_build_query($_REQUEST, '', '&');

        parent::json_display(['list' => $list,'params' => $params]);
    }

    public final function sendsearchReceive()
    {
//        $this->display('box/sendsearchReceive.php');

        // 日期限制
        if($_REQUEST['fromTime'] && $_REQUEST['toTime']){
            $timeWhere=' and s.time between '. strtotime($_REQUEST['fromTime']).' and '.strtotime($_REQUEST['toTime']);
        }elseif($_REQUEST['fromTime']){
            $timeWhere=' and s.time >='. strtotime($_REQUEST['fromTime']);
        }elseif($_REQUEST['toTime']){
            $timeWhere=' and s.time <'. strtotime($_REQUEST['toTime']);
        }else{
            if($GLOBALS['fromTime'] && $GLOBALS['toTime']) $timeWhere=' and s.time between '.$GLOBALS['fromTime'].' and '.$GLOBALS['toTime'].' ';
        }

        // 消息类型限制
        switch($_REQUEST['state']){
            case 1:
                $stateWhere=' and r.is_readed=0';
                break;
            case 2:
                $stateWhere=' and r.is_readed=1';
                break;
            case 3:
                $stateWhere=' and r.is_readed between 0 and 1';
                break;
        }

        $sql="select s.mid, r.is_readed, s.title, r.to_username, s.time from {$this->prename}message_sender s, {$this->prename}message_receiver r where s.from_uid={$this->user['uid']} and s.from_deleted=0 $timeWhere $stateWhere and r.mid=s.mid order by s.time DESC";
        $list=$this->getPage($sql, $this->page, $this->pageSize);
        $params=http_build_query($_REQUEST, '', '&');

        parent::json_display(['list' => $list,'params' => $params]);
    }

    public final function detail($mid)
    {
        $mid = intval($mid);

        $sql3 = "select to_uid from {$this->prename}message_receiver where mid=? and to_username='{$this->user['username']}'";
        if ($this->getValue($sql3, $mid) != $this->user['uid']) throw new Exception('这不是您的消息！');

        $sql = "select s.title, s.from_username, s.content, s.time, r.to_username, r.mid from {$this->prename}message_sender s, {$this->prename}message_receiver r where r.to_uid={$this->user['uid']} and r.mid=? and r.mid=s.mid";
        $data = $this->getRow($sql, $mid);

        $sql2 = "update {$this->prename}message_receiver set is_readed=1 where mid=? and to_uid={$this->user['uid']}";
        $this->update($sql2, $mid);

//        $this->display('box/detail.php', 0, $data);
        parent::json_display($data);
    }

    public final function senddetail($mid)
    {
        $mid = intval($mid);

        $sql2 = "select from_uid from {$this->prename}message_sender where mid=?";
        if ($this->getValue($sql2, $mid) != $this->user['uid']) throw new Exception('这不是您发送过的消息！');

        $sql = "select s.title, s.from_username, s.content, s.time, r.to_username, r.mid from {$this->prename}message_sender s, {$this->prename}message_receiver r where s.from_uid={$this->user['uid']} and r.mid=? and r.mid=s.mid";
        $data = $this->getRow($sql, $mid);

//        $this->display('box/senddetail.php', 0, $data);

        parent::json_display($data);
    }
}
