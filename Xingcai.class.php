<?php

class Xingcai extends WebBase
{
    public $title = '恒彩自主研发系统彩';

    /**
     * 获取信息页面
     */
    public final function xcffc()
    {
//		$this->display('xingcai/ffc.php');

        $lastNo = $this->getGameLastNo(5);

        $zddata = $this->getGameZdData(5, $lastNo['actionNo']);
        $opencode = $zddata ? $zddata : self::randKeys_ffc();

//        $action = $this->getGameNo(5);

        $result = [
            'lastNo' => $lastNo,
            'opencode' => $opencode,
//            'action' => $action,
//            'time' => date('Y-m-d H:i:s')
        ];

        parent::json_display($result);
    }

    function randKeys_ffc($len = 5)
    {
        $rand = '';
        for ($x = 0; $x < $len; $x++) {
            srand((double)microtime() * 1000000);
            $rand .= ($rand != '' ? ',' : '') . mt_rand(0, 9);
        }
        return $rand;
    }

    public final function cqssc()
    {
        @session_start();

        $lastNo = $this->getGameLastNo(1);
        $kjHao = $this->getValue("select data from {$this->prename}data where type=1 and number='{$lastNo['actionNo']}'");
        if ($kjHao) $kjHao = explode(',', $kjHao);
        $actionNo = $this->getGameNo(1);
        $types = $this->getTypes();
        $kjdTime = $types[1]['data_ftime'];
        $diffTime = strtotime($actionNo['actionTime']) - $this->time - $kjdTime;
        $kjDiffTime = strtotime($lastNo['actionTime']) - $this->time;
        $user = $this->user['username'];
        $sql = "select type from {$this->prename}members where username='$user'";
        $data = $this->getRow($sql);
        $type = $data['type'];

        $result = [
            'kjHao' => $kjHao,
            'kjdTime' => $kjdTime,
            'diffTime' => $diffTime,
            'kjDiffTime' => $kjDiffTime,
            'type' => $type,
        ];

        parent::json_display($result);
    }

    public final function pk10()
    {
        @session_start();

        $lastNo=$this->getGameLastNo(20);
        $kjHao=$this->getValue("select data from {$this->prename}data where type=20 and number='{$lastNo['actionNo']}'");
        if($kjHao) $kjHao=explode(',', $kjHao);
        $actionNo=$this->getGameNo(20);
        $types=$this->getTypes();
        $kjdTime=$types[20]['data_ftime'];
        $diffTime=strtotime($actionNo['actionTime'])-$this->time-$kjdTime;
        $kjDiffTime=strtotime($lastNo['actionTime'])-$this->time;
        $user=$this->user['username'];
        $sql="select type from {$this->prename}members where username='$user'";
        $data=$this->getRow($sql);
        $type=$data['type'];

        $result = [
            'kjHao' => $kjHao,
            'kjdTime' => $kjdTime,
            'diffTime' => $diffTime,
            'kjDiffTime' => $kjDiffTime,
            'type' => $type,
        ];

        parent::json_display($result);
    }

    public final function xc2fc()
    {
        $this->display('xingcai/2fc.php');
    }

    public final function xc5fc()
    {
        $this->display('xingcai/5fc.php');
    }

    public final function xclhc()
    {
        $this->display('xingcai/lhc.php');
    }

    public final function xcampk10()
    {
        $this->display('xingcai/ampk10.php');
    }

    public final function xctwpk10()
    {
        $this->display('xingcai/twpk10.php');
    }

    public final function xcamklsf()
    {
        $this->display('xingcai/amklsf.php');
    }

    public final function xctwklsf()
    {
        $this->display('xingcai/twklsf.php');
    }

    public final function xcamkl8()
    {
        $this->display('xingcai/amkl8.php');
    }

    public final function xchgkl8()
    {
        $this->display('xingcai/hgkl8.php');
    }

    public final function xcamk3()
    {
        $this->display('xingcai/amk3.php');
    }

    public final function xctwk3()
    {
        $this->display('xingcai/twk3.php');
    }

    public final function xcam11()
    {
        $this->display('xingcai/am11.php');
    }

    public final function xctw11()
    {
        $this->display('xingcai/tw11.php');
    }

    public final function xcam3d()
    {
        $this->display('xingcai/am3d.php');
    }

    public final function xctw3d()
    {
        $this->display('xingcai/tw3d.php');
    }

    public final function xcamssc()
    {
        $this->display('xingcai/amssc.php');
    }

    public final function xctwssc()
    {
        $this->display('xingcai/twssc.php');
    }

    public final function xcbxklc()
    {
        $this->display('xingcai/bxklc.php');
    }

    public final function xcbx15()
    {
        $this->display('xingcai/bx15.php');
    }


}
