<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class History_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;
        
        $com_useranaly = com('useranaly');
        $arr_action_type = array(
            array(
                'k' => '商品浏览',
                'v' => $com_useranaly::NOSTOCK,
            ),
        );
        $_GPC['action_type'] = isset($_GPC['action_type']) ? $_GPC['action_type'] : $arr_action_type[0]['v'];
        $action_type = trim($_GPC['action_type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and log.uniacid=:uniacid and log.deleted = 0 ';
        $params = array(':uniacid' => $_W['uniacid']);
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        // if (!empty($action_type)) {
        //     $condition .= " AND action_type = :action_type ";
        //     $params[':action_type'] = $action_type;
        // }

        if (!empty($_GPC['datetime'])) {
            $starttime = strtotime($_GPC['datetime']['start']);
            $endtime = strtotime($_GPC['datetime']['end']);

            if (!empty($starttime)) {
                $condition .= " AND log.createtime >= :starttime";
                $params[':starttime'] = $starttime;
            }

            if (!empty($endtime)) {
                $condition .= " AND log.createtime <= :endtime ";
                $params[':endtime'] = $endtime;
            }
        }

        if (true) {
            $other_column = 'log.* ,g.title ';
        }

        if (!empty($_GPC['title'])) {
            $_GPC['title'] = trim($_GPC['title']);
            $condition.=" and log.goodsid like :title";
            $params[':title'] = "%{$_GPC['title']}%";
        }
        // $arr_orderby = array(
        //     'time' => 'log.createtime',
        //     'sum' => 'cnt',
        // );

        $arr_orderby = array(
            array(
                'k' => '按时间',
                'v' => 'createtime',
                'field_name' => '时间',
            ),
            array(
                'k' => '按人数',
                'v' => 'cnt',
                'field_name' => '人数',
            ),
            array(
                'k' => '按次数',
                'v' => 'sum',
                'field_name' => '次数',
            ),
            // array(
            //     'k' => '按金额',
            //     'v' => 'price',
            //     'field_name' => '金额',
            // ),
        );
        $orderby = !empty($_GPC['orderby']) ? trim($_GPC['orderby']) : $arr_orderby[0]['v'];


        $str_group_by = '';
        $self = '';
        // $orderby = isset($_GPC['orderby']) ? 'log.createtime' : ( empty($_GPC['orderby']) ? 'log.createtime' : 'cnt');
        $count_column = 'log.goodsid';
        $cnt = 1;
        $str_join = '';
        $other_select_start = '';
        $other_select_end = '';
        $str_join .= ' left join ' . tablename('ewei_shop_goods') . ' g on log.goodsid = g.id ';
        $condition .= " and g.uniacid = :uniacid ";
        // 
        $show_column = array('title' => array('key' => 'title', 'name' => '商品名称', 'url' => webUrl('goods/edit', array('id' => 'GOODSID','goodsfrom'=>$goodsfrom))));
        switch ($orderby) {
            case 'cnt':
            $str_group_by = ' group by ' . $count_column;
            $cnt = "count({$count_column})";
            $show_column['cnt'] = array('key' => 'cnt', 'name' => '人数');
                break;
            case 'sum':
            $str_group_by = ' group by ' . $count_column;
            $cnt = "sum(log.times)";
            $show_column['sum'] = array('key' => 'sum', 'name' => '浏览次数');
            $show_column['og_total'] = array('key' => 'og_total', 'name' => '购买数量');
            $show_column['rate'] = array('key' => 'rate', 'name' => '转化率');

            $str_join .= ' left join (select sg.*,sum(sg.total) as og_total from' . tablename('ewei_shop_order_goods') . ' sg '
            . ' join ' . tablename('ewei_shop_order') . ' sod on sg.orderid = sod.id '
            . ' where sg.uniacid = :uniacid and sod.status = 3 group by sg.goodsid) og on log.goodsid = og.goodsid ';
            // $condition .= " and og.uniacid = :uniacid ";
            $other_column .= ' ,og.og_total ';
            // $str_group_by .= ' ,og.goodsid ';
            $other_select_start = 'select *,og_total/sum as rate from (';
            $other_select_end = ') ooo ';
                break;
            case 'price':
            $str_group_by = ' group by ' . $count_column;
            $cnt = "sum(log.marketprice*log.total)";
            $show_column['price'] = array('key' => 'price', 'name' => '金额');
                break;
            case 'createtime':
                $show_column['cnt'] = array('key' => 'times', 'name' => '次数');
                // $show_column['sum'] = array('key' => 'total', 'name' => '数量');
                // $show_column['price'] = array('name' => '金额');
                $show_column['nickname'] = array('key' => 'nickname', 'name' => '会员','url' => webUrl('member.list.detail', array('id' => 'MEMBERID')));
                // $show_column['specname'] = array('key' => 'specname', 'name' => '规格');
                $show_column['createtime'] = array('key' => 'createtime', 'name' => '时间');
                $self = 'log.';
                break;
            default:
                # code...
                break;
        }
        $show_column = array_values($show_column);
        // var_export($show_column);

        if ('createtime' == $orderby) {
            $str_join .= ' left join ' . tablename('ewei_shop_member') . ' m on log.openid = m.openid ';
            $condition .= " and m.uniacid = :uniacid ";
            $other_column .= ' ,m.nickname,m.id as memberid, m.mobile ';

            // $str_join .= ' left join ' . tablename('ewei_shop_order_goods') . ' og on log.openid = og.openid ';
            // $condition .= " and og.uniacid = :uniacid ";
            // $other_column .= ' ,sum(og.total) as og_total ';
            // $str_group_by = ' group by og.openid,og.goodsid ';
        }
        // $str_group_by = '';
        // $condition .= " and {$count_column} != '' ";
        $sql = $other_select_start . "select {$cnt} as {$orderby},{$other_column} from " . tablename('ewei_shop_member_history') . ' log '
            . $str_join
            // . " left join " . tablename('ewei_shop_order') . " o on o.id = log.orderid "
            // . " left join " . tablename('ewei_shop_goods') . " g on g.id = log.goodsid "
            // . " left join " . tablename('ewei_shop_goods_option') . " op on op.id = log.optionid "
            . " where 1 {$condition} {$str_group_by} order by {$self}{$orderby} desc ";
        if (empty($_GPC['export'])) {
            $sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize ;
        }
        $sql .= $other_select_end;
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row) {
            if (!empty($row['optiongoodssn'])) {
                $row['goodssn'] = $row['optiongoodssn'];
            }
            if (-1 == $row['need_total']) {
                $row['need_total'] = '';
            }
            $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
        }
        $key_columns = array_keys(isset($list[0]) ? $list[0] : array());
        // var_export($list[0]);exit;
        unset($row);
        $total = pdo_fetchcolumn("select  count(*) from " . tablename('ewei_shop_member_history') . ' log '
            . $str_join
            // . " left join " . tablename('ewei_shop_order') . " o on o.id = log.orderid "
            // . " left join " . tablename('ewei_shop_goods') . " g on g.id = log.goodsid "
            . " where 1 {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);

        //导出Excel
        if ($_GPC['export'] == 1) {

            ca('statistics.goods.export');

            $list[] = array('data' => '用户搜索', 'count' => $total);
            foreach ($list as &$row) {
//                $row['gtitle'] = $row['title'];
//                if (!empty($row['optiontitle'])) {
//                    $row['gtitle'] .= " " . $row['optiontitle'];
//                }

                // $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
            }
            unset($row);
            // $
            $columns = array();
            foreach ($show_column as $k => $v) {
                $columns[] = array(
                    'title' => $v['name'],
                    'field' => $v['key'],
                    'width' => '30',
                );
            }
            m('excel')->export($list, array(
                "title" => "用户搜索-" . date('Y-m-d-H-i', time()),
                // "columns" => array(
                //     array('title' => '关键词', 'field' => 'description', 'width' => 24),
                //     array('title' => '数量', 'field' => 'cnt', 'width' => 48),
                // )
                "columns" => $columns,
            ));

            
            plog('statistics.goods.export', '导出用户搜索');
            
        }
        // var_export($list);

        load()->func('tpl');
        include $this->template('statistics/history');
    }

    function transform() {

    }

    function transform_search_addr() {
        global $_W;
        $datatype = 3;
        $com_useranaly = com('useranaly');
        $action_type = $com_useranaly::NOSTOCK;
        $arr_res_log = pdo_fetchall('select * from ' . tablename('ewei_user_action') . ' where action_type = :action_type and datatype = :datatype order by uptime limit 20', array(':action_type' => $action_type, ':datatype' => $datatype));
        // var_dump($arr_res_log);
        // echo '------';
        $transform_success_count = 0;
        $arr_line = array();
        foreach ($arr_res_log as $k => $v) {
            // $res = pdo_update('ewei_user_action', array('uptime' => time()), array('id' => $v['id']));
            // $description = $v['description'];
            $arr_des = explode('-', $v['description']);
            var_export($arr_des);
            $arr3 = json_decode($arr_des[3], true);
            $line = __LINE__;
            $insert_ok = 0;
            // var_export($arr3);
            if (empty($arr3)) {
                pdo_update('ewei_user_action', array('uptime' => time()), array('id' => $v['id']));
                continue;
            }
            pdo_begin();
            foreach ($arr3 as $kk => $vv) {
                $line = __LINE__;
                if ($vv['fqty'] <= 0) {
                    // $res_goods = pdo_fetch('select g.id, g.title from ' . tablename('ewei_shop_goods') . ' g ' . 
                    //     ' left join ' . tablename('ewei_shop_goods_option') . ' op on g.id = op.goodsid ' .
                    //      'where g.uniacid = 3 and g.deleted = 0 and g.hasoption = 0 and g.erpId = :erpId or g.uniacid = 3 and g.deleted = 0 and g.hasoption = 1 and op.erpId = :erpId', array(':erpId' => $vv['fgoodid']));
                    if (!empty($vv['optionid'])) {
                        $res_goods = pdo_fetch('select g.id, g.title, op.title as specname from ' . tablename('ewei_shop_goods') . ' g ' . 
                        ' left join ' . tablename('ewei_shop_goods_option') . ' op on g.id = op.goodsid ' .
                         'where g.uniacid = 3 and g.deleted = 0 and g.hasoption = 1 and g.id = :goodsid and op.id = :optionid', array(':optionid' => $vv['optionid'], ':goodsid' => $vv['goodsid']));
                    } else {
                        $res_goods = pdo_fetch('select g.id, g.title from ' . tablename('ewei_shop_goods') . ' g where g.id = :goodsid', array(':goodsid' => $vv['goodsid']) );
                    }
                    // file_put_contents('goodss.o', var_export($res_goods, true), FILE_APPEND);
                    // echo '00000000000';
                    var_export($res_goods);
                    // echo '999999999999999';
                    $line = __LINE__;
                    $specname = isset($res_goods['specname']) ? '('.$res_goods['specname'].')' : '';
                    if ($res_goods) {
                        $res = pdo_insert('data_out_of_stock', array(
                            'openid' => $v['openid'],
                            'memberid' => $v['memberid'],
                            'store_name' => $arr_des[0],
                            'goods_erpid' => $vv['erpId'],
                            'goods_id' => $res_goods['id'],
                            'goodsid' => $res_goods['title'] . $specname,// . ,
                            'createtime' => time(),
                            'createtime' => $v['createtime'],
                            'uniacid' => $_W['uniacid'],
                            'optionid' => $vv['optionid'],
                            'need_total' => $vv['total'],
                        ));
                        $line = __LINE__;
                        if (!$res) {
                            $line = __LINE__;
                            pdo_rollback();
                            exit('insert error');
                        }
                        $insert_ok++;
                        $line = __LINE__;
                        $transform_success_count++;
                    }

                }
                $arr_line[] = $line;
            }
            if ($insert_ok) {
                $res = pdo_delete('ewei_user_action', array('id' => $v['id']));
                if (!$res) {
                    pdo_rollback();
                    exit('error');
                }
                beienLog(var_export(['log'=>$arr_res_log, 'function' => __FUNCTION__], true), 'user_action');
            } else {
                $res = pdo_update('ewei_user_action', array('uptime' => time()), array('id' => $v['id']));
            }
            pdo_commit();

        }
        $count = pdo_fetch('select count(*) as cnt from ' . tablename('ewei_user_action') . ' where action_type = :action_type and datatype = :datatype ', array(':action_type' => $action_type, ':datatype' => $datatype));
        exit(json_encode(array(
            'success_count' => $transform_success_count,
            'count' => $count['cnt'],
            'debug' => $line,
            'debug1' => $arr_line,
        )));

    }

    // http://beien.local/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=statistics.action
// create table `ims_data_out_of_stock` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `openid` varchar(50) null,
//   `memberid` int default 0,
//   `need_total` int(11) default -1 COMMENT '缺货件数',
//   `store_name` varchar(50) default null COMMENT '',
//   `goodsid` varchar(250) default null COMMENT '',
//   `goods_id` int default 0 COMMENT '',
//   `goods_erpid` int default 0 COMMENT '',
//   `createtime` int default 0,
//   `createtime` int default 0,
//   PRIMARY KEY (`id`)
// )ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

    function trend() {
        global $_W, $_GPC;
        
        $com_useranaly = com('useranaly');
        $arr_action_type = array(
            array(
                'k' => '缺货商品',
                'v' => $com_useranaly::NOSTOCK,
            ),
        );
        $_GPC['action_type'] = isset($_GPC['action_type']) ? $_GPC['action_type'] : $arr_action_type[0]['v'];
        $action_type = trim($_GPC['action_type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and log.uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        // if (!empty($action_type)) {
        //     $condition .= " AND action_type = :action_type ";
        //     $params[':action_type'] = $action_type;
        // }

        if (!empty($_GPC['datetime'])) {
            $starttime = strtotime($_GPC['datetime']['start']);
            $endtime = strtotime($_GPC['datetime']['end']);
        }

            if (!empty($starttime)) {
                $condition .= " AND log.createtime >= :starttime";
                $params[':starttime'] = $starttime;
            }

            if (!empty($endtime)) {
                $condition .= " AND log.createtime <= :endtime ";
                $params[':endtime'] = $endtime;
            }

        if (true) {
            $other_column = 'log.goodsid,log.createtime';
        }

        if (!empty($_GPC['title'])) {
            $_GPC['title'] = trim($_GPC['title']);
            $condition.=" and log.createtime like :title";
            $params[':title'] = "%{$_GPC['title']}%";
        }
        // $arr_orderby = array(
        //     'time' => 'log.createtime',
        //     'sum' => 'cnt',
        // );

        $arr_orderby = array(
            array(
                'k' => '按时间',
                'v' => 'createtime',
            ),
            array(
                'k' => '按数量',
                'v' => 'cnt',
            ),
        );
        $orderby = !empty($_GPC['orderby']) ? trim($_GPC['orderby']) : $arr_orderby[1]['v'];


        $str_group_by = '';
        // $orderby = isset($_GPC['orderby']) ? 'log.createtime' : ( empty($_GPC['orderby']) ? 'log.createtime' : 'cnt');
        $count_column = 'log.createtime';
        $cnt = 1;
        // 
        if ('cnt' == $orderby) {
            $groups = 'dat';
            $str_group_by = ' group by ' . $groups;
            $cnt = "count({$groups})";
        }
        // $str_group_by = '';
        $condition .= " and {$count_column} != '' ";
        // $sql = "select {$count_column},{$cnt} as cnt,{$other_column} from " . tablename('ewei_shop_member_history') . ' log '
        //     // . " left join " . tablename('ewei_shop_order') . " o on o.id = log.orderid "
        //     // . " left join " . tablename('ewei_shop_goods') . " g on g.id = log.goodsid "
        //     // . " left join " . tablename('ewei_shop_goods_option') . " op on op.id = log.optionid "
        //     . " where 1 {$condition} {$str_group_by} order by {$orderby} desc ";
        $sql = "select {$count_column},{$cnt} as cnt,{$other_column} from (select *,from_unixtime(createtime,'%Y-%m-%d') as dat from " . tablename('ewei_shop_member_history') . ') log '
            // . " left join " . tablename('ewei_shop_order') . " o on o.id = log.orderid "
            // . " left join " . tablename('ewei_shop_goods') . " g on g.id = log.goodsid "
            // . " left join " . tablename('ewei_shop_goods_option') . " op on op.id = log.optionid "
            . " where 1 {$condition} {$str_group_by} order by createtime ";
        if (empty($_GPC['export'])) {
            $sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }
        $list = pdo_fetchall($sql, $params);
        // foreach ($list as &$row) {
        //     if (!empty($row['optiongoodssn'])) {
        //         $row['goodssn'] = $row['optiongoodssn'];
        //     }
        // }

        foreach ($list as $k => &$v) {
            $v['date'] = date('Y-m-d', $v['createtime']);
            // unset($v['createtime']);
        }
        // var_export($list);
        $list = $this->splice_data($starttime, $endtime, $list);
        $date = array_column($list, 'date');
        $cnt = array_column($list, 'cnt');
        exit(json_encode(array(
            'date' => $date,
            'cnt' => $cnt,
        )));
        unset($row);
        // $total = pdo_fetchcolumn("select  count(*) from " . tablename('ewei_shop_member_history') . ' log '
        //     // . " left join " . tablename('ewei_shop_order') . " o on o.id = log.orderid "
        //     // . " left join " . tablename('ewei_shop_goods') . " g on g.id = log.goodsid "
        //     . " where 1 {$condition}", $params);
        // $pager = pagination($total, $pindex, $psize);
        // var_export($list);

        // load()->func('tpl');
        // include $this->template('statistics/out_of_stock');
    }

    function splice_data($starttime, $endtime, $data, $format_type = 'date') {

        // +1 unit 
        $tmp_time = $starttime;
        $i = 0;
        $res_date = array();
        do { 
            $insert_array = array(
                'date' => date('Y-m-d', $tmp_time),
                'cnt' => 0,
            );

            if ($data[$i]['date'] == date('Y-m-d', $tmp_time)) {
                // echo '1------------1';
                $res_date[] = $data[$i];

            } elseif (strtotime($data[$i]['date']) > strtotime(date('Y-m-d', $tmp_time))) {
                // echo '2-----------2';
                // array_splice($, offset)
                $res_date[] = $insert_array;
            } else {
                // echo '3-----------3';
                $res_date[] = $insert_array;
                $i++;
            }
            $tmp_time = strtotime(date('Y-m-d H:i:s', $tmp_time) . "+1 days");//,
        } while ($tmp_time < $endtime);
        return ($res_date);
        // echo date('Y-m-d H:i:s', $tmp_time);
    }

    function test() {
        echo '{"date":["2018-09-03","2018-09-04","2018-09-05","2018-09-06"],"cnt":["13","6","3","3"]}';
    }

}


