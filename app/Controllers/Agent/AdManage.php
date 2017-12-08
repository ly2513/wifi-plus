<?php
/**
 * User: yongli
 * Date: 17/9/19
 * Time: 00:28
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers\Agent;

use Illuminate\Database\Capsule\Manager as DB;
use AdModel;
use App\Controllers\BaseAgent;

/**
 * 广告管理
 *
 * Class AdManage
 *
 * @package App\Controllers\Agent
 */
class AdManage extends BaseAgent
{
    /**
     * 构造函数
     */
    public function initialization()
    {
        parent::initialization(); // TODO: Change the autogenerated stub
        // 获得当前控制器名字
        $nav ['m'] = $this->controller;
        $nav ['a'] = 'adman';
        $this->assign('nav', $nav);
    }

    /**
     * 当前商家广告
     */
    public function getShopAd()
    {
        $build = AdModel::select([
            'id',
            'uid',
            'ad_pos',
            'ad_thumb',
            'ad_sort',
            'title',
            'info',
            'mode',
            'state'
        ])->with([
            'getShop' => function ($q) {
                $q->select('id', 'shop_name', 'pid')->wherePid($this->uid);
            }
        ]);
        $num   = $build->count();
        // 广告数据
        $result = $build->skip(($this->page - 1) * $this->perPage)->take($this->perPage)->get()->toArray();
        // 获得分页配置
        $config = set_page_config($num, $this->url, 3, $this->perPage);
        // 实例化分页类
        $pagination = \Config\Services::pagination();
        // 初始化分页配置
        $pagination->initialize($config);
        $page = $pagination->create_links();
        //        // 引用页码工具类
        //        import('@.ORG.AdminPage');
        //        // 实例化一个对ad表操作对象
        //        $db  = D('Ad');
        //        $uid = session('uid');
        //        // P($uid);
        //        // 获得当前商家的广告信息和商家信息
        //        $sql   = "select a.*,b.shopname from " . C('DB_PREFIX') . "ad  a LEFT JOIN " . C('DB_PREFIX') . "shop  b on a.uid=b.id ";
        //        $where = " b.pid=" . $this->uid;
        //        // 统计商家广告额数量
        //        $sqlcount = "select count(*) as ct from " . C('DB_PREFIX') . "ad  a LEFT JOIN " . C('DB_PREFIX') . "shop  b on a.uid=b.id where $where";
        //        $rs       = $db->query($sqlcount);
        //        $count    = $rs [0] ['ct'];
        //        // 页码，$count表示总共的广告数，C('ADMINPAGE')表示每页要显示的广告数
        //        $page = new AdminPage ($count, C('ADMINPAGE'));
        //        $sql .= " where " . $where . " limit " . $page->firstRow . ',' . $page->listRows . " ";
        //        $result = $db->query($sql);
        foreach ($result as $key => &$rs) {
            $rs['ad_thumb'] = $this->downloadUrl($rs ['ad_thumb']);
        }
        $this->assign('page', $page);
        $this->assign('lists', $result);
        $this->display();
    }

    /**
     *
     */
    public function editAd()
    {
        $post = $this->request->getPost();
        if ($post) {
            $id     = $post['id'] ? intval($post['id']) : 0;
            $result = AdModel::select('id')->whereId($id)->get()->toArray();
            $result = $result ? $result[0] : [];
            if (!$result) {
                call_back(2, '', '无此广告信息');
            }
            //            $where ['id'] = $id;
            //            $db           = D('Ad');
            //            $result       = $db->where($where)->field('id')->find();
            //            if ($result == false) {
            //                $this->error('无此广告信息');
            //                exit ();
            //            }
            if ($_FILES ['img'] ['name']) {
                //7牛上传
                $path             = $this->uploadFile($_SESSION['uid'], $_FILES['img']['name'],
                    $_FILES['img']['tmp_name']);
                $post['ad_thumb'] = $path;
            }
            $post['update_time'] = time();
            $status              = AdModel::whereId($id)->update($post);
            $status ? call_back(0) : call_back(2, '', '操作失败!');
            //            if ($result) {
            //
            //
            //                if ($db->create()) {
            //                    if ($db->where($where)->save($_POST)) {
            //                        $this->success('修改成功', U('shopad'));
            //                    } else {
            //                        $this->error('操作出错');
            //                    }
            //                } else {
            //                    $this->error($db->getError());
            //                }
            //
            //            }
        } else {
            $id     = $this->request->getGet('id');
            $id     = $id ? intval($id) : 0;
            $result = AdModel::select([
                'id',
                'uid',
                'ad_pos',
                'ad_thumb',
                'ad_sort',
                'title',
                'info',
                'mode',
                'state'
            ])->whereId($id)->get()->toArray();
            $result = $result ? $result[0] : [];
            if (!$result) {
                call_back(2, '', '无此广告信息');
            }
            $result['ad_thumb'] = $this->downloadUrl($result['ad_thumb']);
            $this->assign('info', $result);
            $this->display();
            //            isset ($_GET ['id']) ? intval($_GET ['id']) : 0;
            //            $where ['id'] = $id;
            //            $result       = D('Ad')->where($where)->find();
            //            if ($result) {
            //                $result ['ad_thumb'] = $this->downloadUrl($result ['ad_thumb']);
            //                $this->assign('info', $result);
            //                $this->display();
            //            } else {
            //                $this->error('无此广告信息');
            //            }
        }
    }

    /**
     * 广告报表
     */
    public function adReport()
    {
        $way = $this->request->getGet('mode');
        $way ? call_back(0, $this->getAdReport($way)) : '';
        $this->display();

    }

    /**
     * 获得广告报表
     *
     * @param $way
     *
     * @return array
     */
    private function getAdReport($way)
    {
        switch (strtolower($way)) {
            case 'today' :
                $sql = ' select t,CONCAT(CURDATE()," ",t,"点") as show_date, COALESCE(show_up,0)  as show_up, COALESCE(hit,0)  as hit,COALESCE(hit/show_up*100,0) as rt from wifi_hours a left JOIN ';
                $sql .= '(select thour, sum(show_up)as show_up,sum(hit) as hit from ';
                $sql .= '(select  FROM_UNIXTIME(ad.add_time,"%H") as thour,show_up ,hit from wifi_ad_count ad';
                $sql .= ' left join wifi_shop sp on ad.shop_id=sp.id ';
                $sql .= ' where ad.add_date="' . date('Y-m-d') . '" and ad.mode=1 and pid=' . $this->aid;
                $sql .= ' )a group by thour ) c ';
                $sql .= '  on a.t=c.thour ';
                break;
            case 'yesterday' :
                $sql = ' select t,CONCAT(CURDATE()," ",t,"点") as show_date, COALESCE(show_up,0)  as show_up, COALESCE(hit,0)  as hit,COALESCE(hit/show_up*100,0) as rt from wifi_hours a left JOIN ';
                $sql .= '(select thour, sum(show_up)as show_up,sum(hit) as hit from ';
                $sql .= '(select  FROM_UNIXTIME(ad.add_time,"%H") as thour,show_up ,hit from wifi_ad_count ad ';
                $sql .= ' left join wifi_shop sp on ad.shop_id=sp.id ';
                $sql .= ' where ad.add_date=DATE_ADD(CURDATE() ,INTERVAL -1 DAY) and ad.mode=1 ';
                $sql .= ' )a group by thour ) c ';
                $sql .= '  on a.t=c.thour ';
                break;
            case 'week' :
                $sql = '  select td as show_date,right(td,5) as td,datediff(td,CURDATE()) as t, COALESCE(show_up,0)  as show_up, COALESCE(hit,0)  as hit ,COALESCE(hit/show_up*100,0) as rt from ';
                $sql .= ' ( select CURDATE() as td ';
                for ($i = 1; $i < 7; $i++) {
                    $sql .= '  UNION all select DATE_ADD(CURDATE() ,INTERVAL ' . -$i . ' DAY)';
                }
                $sql .= ' ORDER BY td ) a left join ';
                $sql .= '( select ad.add_date,sum(show_up) as show_up ,sum(hit) as hit from wifi_ad_count ad';
                $sql .= ' left join wifi_shop sp on ad.shop_id=sp.id ';
                $sql .= ' where   ad.add_date between DATE_ADD(CURDATE() ,INTERVAL -6 DAY) and CURDATE() and ad.mode=1 and pid=' . $this->aid . ' GROUP BY  add_date';
                $sql .= ' ) b on a.td=b.add_date ';
                break;
            case 'month' :
                $sql = ' select tname as show_date,tname as t, COALESCE(show_up,0)  as show_up, COALESCE(hit,0)  as hit,COALESCE(hit/show_up*100,0) as rt from wifi_day  a left JOIN';
                $sql .= '( select right(ad.add_date,2) as td ,sum(show_up) as show_up ,sum(hit) as hit  from wifi_ad_count  ad ';
                $sql .= ' left join wifi_shop sp on ad.shop_id=sp.id ';
                $sql .= ' where   ad.add_date >= "' . date('Y-m-01') . '" and ad.mode=1 and pid=' . $this->aid . ' GROUP BY  add_date';
                $sql .= ' ) b on a.tname=b.td ';
                $sql .= ' where a.id between 1 and  ' . date('t');
                break;
            case 'query' :
                $stDate = $this->request->getGet('sDate');
                $enDate = $this->request->getGet('eDate');

//                import("ORG.Util.Date");
                //$sdt=Date("Y-M-d",$sdate);
                //$edt=Date("Y-M-d",$edate);
                $dt      = new Date ($stDate);
                $leftDay = $dt->dateDiff($enDate, 'd');
                $sql     = ' select td as show_date,right(td,5) as td,datediff(td,CURDATE()) as t,COALESCE(show_up,0)  as showup, COALESCE(hit,0)  as hit,COALESCE(hit/show_up*100,0) as rt from ';
                $sql .= ' ( select "' . $stDate . '"  as td ';
                for ($i = 0; $i <= $leftDay; $i++) {
                    $sql .= '  UNION all select DATE_ADD("' . $stDate . '" ,INTERVAL $i DAY) ';
                }
                $sql .= ' ) a left join ';
                $sql .= '( select ad.add_date,sum(showup) as showup ,sum(hit) as hit  from wifi_ad_count as ad ';
                $sql .= ' left join wifi_shop sp on ad.shop_id=sp.id';
                $sql .= ' where  ad.add_date between "' . $stDate . '" and "' . $enDate . '"  and ad.mode=1 and pid=' . $this->aid . ' GROUP BY  add_date';
                $sql .= ' ) b on a.td=b.add_date ';
                break;
        }

        $result = DB::select($sql);
        return  $result ? $result : [];
    }
}