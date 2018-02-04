<?
/**
 *     Date:20180404
 *     Discuz! X3数据库转码后数据编码修复工具
 *     本脚本用于Discuz! X3系列修复进行全站转码之后部分数据丢失，后台部分设置无法编辑等问题
 *     此脚本应该在修改数据库编码之后在网站第一次被访问之前执行，网站可能会修改数据库内容或者进行读写所造成部分数据转码失败
 *     建议在导入数据库之前将config_global.php更名，让网站暂时无法连接数据库，注意对应修改第18行代码
 *     此脚本放在网站根目录，如果config_global.php在别的地方请注意修改第18行代码
 */
//error_reporting(E_ALL);   //输出脚本运行错误信息
$step = isset($_GET['step']) ? $_GET['step'] : 'start';
if($step == 'start') {
        $msg .= "<br/><a href='dzcoding.php?step=convert&type=start'>开始修复</a>";
        show_msg($msg);
} elseif($step == 'convert') {
        $type = $_GET['type'];
        if($type == 'start') {
                require './config/config_global.php';   //读取配置文件
                $dbserver   = $_config['db']['1']['dbhost'];
                $dbusername = $_config['db']['1']['dbuser'];
                $dbpassword = $_config['db']['1']['dbpw'];
                $database   = $_config['db']['1']['dbname'];
                $dbcharset  = $_config['db']['1']['dbcharset'];
        }
        
        if($dbcharset == 'gbk')
            $tocharset = 'utf8';
        else
            $tocharset = 'gbk';
        $limit = 100;
        $nextid = 0;
        $start = !empty($_GET['start']) ? $_GET['start'] : 0;
        $tid = !empty($_GET['tid']) ? $_GET['tid'] : 0;
        $arr = getlistarray($type);
        $field = $arr[intval($tid)];
        $stable = $field[0];
        $sfield = $field[1];
        $sid        = $field[2];
        $special = $field[3];
        $mysql_conn = @mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("数据库连接失败");
    mysql_select_db($database, $mysql_conn);
    mysql_query('set names '.$dbcharset);
    if($special) {
                $sql = "SELECT $sfield, $sid FROM $stable WHERE $sid > $start ORDER BY $sid ASC LIMIT $limit";
        } else {
                $sql = "SELECT $sfield, $sid FROM $stable";
        }
    
    $query = mysql_query($sql);
    
        while($values = mysql_fetch_array($query)) {
                if($special)
                        $nextid = $values[$sid];
                else
                        $nextid = 0;
                $data = $values[$sfield];
                $id   = $values[$sid];
                $data = preg_replace_callback('/s:([0-9]+?):"([\s\S]*?)";/','_dzcoding',$data);
                $data = addslashes($data);
                mysql_query("UPDATE `$stable` SET `$sfield` = '$data' WHERE `$sid` = '$id'", $mysql_conn);
        }
        if($nextid)
        {
                show_msg($stable." $sid > $nextid", "dzcoding.php?step=convert&type=$type&tid=$tid&start=$nextid");
        }
        else
        {        
                $tid++;
                if($tid < count($arr))
                        show_msg($stable." $sid > $nextid", "dzcoding.php?step=convert&type=$type&tid=$tid&start=0");
                else
                        show_msg('修复完毕', "dzcoding.php?step=end");
        
        }
        mysql_close($mysql_conn);
} elseif( $step == 'end') {
        show_msg('修复结束');
}
function _dzcoding($str) {
        $l = strlen($str[2]);
        return 's:'.$l.':"'.$str[2].'";';
}
function show_msg($message, $url_forward='', $time = 10, $noexit = 0) {
        if(!empty($url_forward)) {
                $message = "<a href=\"$url_forward\">$message (跳转中...)</a><script>setTimeout(\"window.location.href ='$url_forward';\", $time);</script>";
        }
        print<<<END
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Discuz! X3数据库编码修复脚本</title>
        <body>
        <table>
        <tr><td>$message</td></tr>
        </table>
        </body>
        </html>
END;
}
//以下为需要修复的数据库字段。Discuz! X3数据库中包含Ucenter数据，如果你使用的是独立Ucenter请在Ucenter里（根据自身情况修改表名）再运行一次。
function getlistarray($type) {
        if($type == 'start') {
                $list = array(
                                array('pre_common_setting','svalue', 'skey', FALSE),
                                array('pre_forum_grouplevel','creditspolicy','levelid', TRUE),
                                array('pre_forum_grouplevel','postpolicy','levelid', TRUE),
                                array('pre_forum_grouplevel','specialswitch','levelid', TRUE),
                                array('pre_common_advertisement','parameters','advid', TRUE),
                                array('pre_common_plugin','modules','pluginid', TRUE),
                                array('pre_common_block','param','bid', TRUE),
                                array('pre_common_block','blockstyle','bid', TRUE),
                                array('pre_common_block_item','fields','itemid', TRUE),
                                array('pre_common_block_style','template','styleid', TRUE),
                                array('pre_common_diy_data','diycontent','targettplname', TRUE),
                                array('pre_common_member_field_forum','groups','uid', TRUE),
                                array('pre_common_member_field_home','blockposition','uid', TRUE),
                                array('pre_common_member_field_home','privacy','uid', TRUE),
                                array('pre_common_member_field_home','acceptemail','uid', TRUE),
                                array('pre_common_member_field_home','magicgift','uid', TRUE),
                                array('pre_common_member_verify_info','field','vid', TRUE),
                                array('pre_common_patch','rule','serial', TRUE),
                                array('pre_common_member_stat_search','condition','optionid', TRUE),
                                array('pre_common_plugin','modules','pluginid', TRUE),
                                array('pre_common_member_newprompt','data','uid', TRUE),
                                array('pre_forum_activity','ufield','tid', TRUE),
                                array('pre_forum_forumfield','creditspolicy ','fid', TRUE),
                                array('pre_forum_activity','formulaperm','fid', TRUE),
                                array('pre_forum_activity','moderators','fid', TRUE),
                                array('pre_forum_activity','modrecommend','fid', TRUE),
                                array('pre_forum_activity','extra','fid', TRUE),
                                array('pre_forum_groupfield','data','fid', TRUE),
                                array('pre_forum_medal','permission','medalid', TRUE),
                                array('pre_forum_spacecache','value','uid', TRUE),
                                array('pre_home_feed','title_data','feedid', TRUE),
                                array('pre_home_feed','body_data','feedid', TRUE),
                                array('pre_home_share','body_data','sid', TRUE),
                                array('pre_ucenter_applications','extra','appid', TRUE),
                                array('pre_ucenter_pm_list','lastmessage','plid', TRUE),
                                array('pre_forum_forumfield','formulaperm','fid', TRUE),
                                array('pre_forum_forumfield','moderators','fid', TRUE),
                                array('pre_forum_forumfield','threadtypes','fid', TRUE),
                                array('pre_forum_forumfield','threadsorts','fid', TRUE),
                                array('pre_forum_forumfield','modrecommend','fid', TRUE),
                                array('pre_forum_forumfield','extra','fid', TRUE),
                                array('pre_forum_typeoption','rules','optionid', TRUE),
                        );
        }
        return $list;
}
?>