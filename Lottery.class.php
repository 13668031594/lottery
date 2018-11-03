<?php
@session_start();

class lottery extends WebLoginBase
{
    public $pageSize = 10;
    private $vcodeSessionName = 'lottery_vcode_session_name';

    /**
     * 用户信息页面
     */


    public final function combine_buy()
    {

        $this->getTypes();
        $this->getPlayeds();
//		$this->action='searchGameRecord';
//		$this->display('lottery/combine_list.php');

        $para = $_GET;

        if ($para['state'] == 5) {
            $whereStr = " and b.isDelete=1 ";
        } else {
            $whereStr = " and  b.isDelete=0 ";
        }
        // 彩种限制
        if ($para['type']) {
            $para['type'] = intval($para['type']);
            $whereStr .= " and b.type={$para['type']}";
        }

        // 时间限制
        /*if($para['fromTime'] && $para['toTime']){
            $whereStr .= ' and b.actionTime between '.strtotime($para['fromTime']).' and '.strtotime($para['toTime']);
        }elseif($para['fromTime']){
            $whereStr .= ' and b.actionTime>='.strtotime($para['fromTime']);
        }elseif($para['toTime']){
            $whereStr .= ' and b.actionTime<'.strtotime($para['toTime']);
        }else{

            if($GLOBALS['fromTime'] && $GLOBALS['toTime']){
                $whereStr .= ' and b.actionTime between '.$GLOBALS['fromTime'].' and '.$GLOBALS['toTime'].' ';
            }
        }*/

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
        $para['mode'] = floatval($para['mode']);
        if ($para['mode']) $whereStr .= " and b.mode={$para['mode']}";

        //单号
        $para['betId'] = wjStrFilter($para['betId']);
        if ($para['betId'] && $para['betId'] != '输入单号') {
            if (!ctype_alnum($para['betId'])) throw new Exception('单号包含非法字符,请重新输入');
            $whereStr .= " and b.wjorderId='{$para['betId']}'";
        }

        //用户限制
        $whereStr .= " and b.hmEnable=1";

        $sql = "select b.*, u.username from {$this->prename}bets b, {$this->prename}members u where b.uid=u.uid";
        $sql .= $whereStr;
        $sql .= ' order by id desc, actionTime desc';

        $data = $this->getPage($sql, $this->page, $this->pageSize);
        //print_r($data);
        $params = http_build_query($para, '', '&');

        $modeName = array('1.000' => '元', '0.100' => '角', '0.010' => '分', '0.001' => '厘', '1.000' => '1元');

        $result = [
            'data' => $data,
            'params' => $params,
            'modeName' => $modeName,
        ];

        parent::json_display($result);
    }

    //合买中心
    public final function hemai()
    {
        $this->getTypes();
        $this->getPlayeds();
        $this->action = 'combine_buy';
//        $this->display('lottery/hemai.php');

        $select = $_REQUEST['type'];
        $userType = $this->userType;

        $result = [
            'select' => $select,
            '$userType' => $userType,
        ];

        parent::json_display($result);

    }

    //合买详情
    public final function comInfo($id)
    {
        $this->getTypes();
        $this->getPlayeds();
//        $this->display('lottery/com-info.php', 0, intval($id));



        $bet=$this->getRow("select * from {$this->prename}bets where id=?", intval($id));
        if(!$bet) throw new Exception('单号不存在');
        $modeName=array('1.000'=>'元', '0.100'=>'角', '0.010'=>'分', '0.001'=>'厘','1.000'=>'1元');
        $weiShu=$bet['weiShu'];
        $wei = '';
        if($weiShu){
            $w=array(16=>'万', 8=>'千', 4=>'百', 2=>'十',1=>'个');
            foreach($w as $p=>$v){
                if($weiShu & $p) $wei.=$v;
            }
            $wei.=':';
        }
        $betCont=$bet['mode'] * $bet['beiShu'] * $bet['actionNum'] * ($bet['fpEnable']+1);

        $result = [
            'bet' => $bet,
            'modelName' => $modeName,
            'wei' => $wei,
            'betCont' => $betCont,
            'id' => intval($id),
        ];

        parent::json_display($result);
    }

    //合买记录
    public final function search()
    {
        $this->getTypes();
        $this->getPlayeds();
        $this->action = 'searchGameRecord';
//        $this->display('lottery/search.php');

        $fromTime = $this->iff($_REQUEST['fromTime'], $_REQUEST['fromTime'], date('Y-m-d H:i', $GLOBALS['fromTime']));
        $toTime = $this->iff($_REQUEST['toTime'], $_REQUEST['toTime'], date('Y-m-d H:i', $GLOBALS['toTime']));
        $type = $_REQUEST['type'];

        $result = [
            'fromTime' => $fromTime,
            'toTime' => $toTime,
            'type' => $type,
            'types' => $this->types,
        ];

        parent::json_display($result);
    }

    public final function searchGameRecord()
    {
        $this->getTypes();
        $this->getPlayeds();
//        $this->display('lottery/search-list.php');

        $para=$_GET;

        if($para['state']==5){
            $whereStr = " and b.isDelete=1 ";
        }else{
            $whereStr = " and  b.isDelete=0 ";
        }
        // 彩种限制
        if($para['type']){
            $para['type']=intval($para['type']);
            $whereStr .= " and b.type={$para['type']}";
        }

        // 时间限制
        if($para['fromTime'] && $para['toTime']){
            $whereStr .= ' and b.actionTime between '.strtotime($para['fromTime']).' and '.strtotime($para['toTime']);
        }elseif($para['fromTime']){
            $whereStr .= ' and b.actionTime>='.strtotime($para['fromTime']);
        }elseif($para['toTime']){
            $whereStr .= ' and b.actionTime<'.strtotime($para['toTime']);
        }else{

            if($GLOBALS['fromTime'] && $GLOBALS['toTime']){
                $whereStr .= ' and b.actionTime between '.$GLOBALS['fromTime'].' and '.$GLOBALS['toTime'].' ';
            }
        }

        // 投注状态限制
        if($para['state']){
            switch($para['state']){
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
        $para['mode']=floatval($para['mode']);
        if($para['mode']) $whereStr .= " and b.mode={$para['mode']}";

        //单号
        $para['betId']=wjStrFilter($para['betId']);
        if($para['betId'] && $para['betId']!='输入单号'){
            if(!ctype_alnum($para['betId'])) throw new Exception('单号包含非法字符,请重新输入');
            $whereStr .= " and b.wjorderId='{$para['betId']}'";
        }

        //用户限制
        $whereStr .= " and b.uid={$this->user['uid']}";

        $sql="select b.*, u.username from {$this->prename}bets b, {$this->prename}members u where b.uid=u.uid";
        $sql.=$whereStr;
        $sql.=' order by id desc, actionTime desc';

        $data=$this->getPage($sql, $this->page, $this->pageSize);
        //print_r($data);
        $params=http_build_query($para, '', '&');

        $modeName=array('1.000'=>'元', '0.100'=>'角', '0.010'=>'分', '0.001'=>'厘','1.000'=>'1元');

        foreach ($data as $k => $var){

            $data[$k]['ifs_title'] = $this->iff($_REQUEST['type']==$var['id'], 'selected="selected"')?>><?=$this->iff($var['shortName'], $var['shortName'], $var['title']);
        }

        $result = [
            'data' => $data,
            'params' => $params,
            'modelName' => $modeName,
        ];

        parent::json_display($result);
    }


}