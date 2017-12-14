<?php
/**
 * User: yongli
 * Date: 17/12/8
 * Time: 13:40
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers\WifiAdmin;

use Agent\AgentModel;
use App\Controllers\BaseAdmin;
use WifiAdmin\AuthListModel;
use WifiAdmin\AuthTplModel;

class AuthTemplate extends BaseAdmin
{
    /**
     * 构造方法
     */
    public function initialization()
    {
        parent::initialization(); // TODO: Change the autogenerated stub
        $this->doLoadID(1000);
    }

    /**
     * 认证模板列表
     */
    public function index()
    {
        $build = AuthTplModel::select([
            'id',
            'tp_name',
            'key_name',
            'pic',
            'state',
            'group',
            'owner_id',
            'one_flag',
            'add_time'
        ]);
        $post  = $this->request->getPost();
        if ($post) {
            $id      = $post['id'] ?? '';
            $tp_name = $post['tp_name'] ?? '';
            $id ? $build->where('id', 'like', '%' . $id . '%') : '';
            $tp_name ? $build->where('tp_name', 'like', '%' . $tp_name . '%') : '';
            $_GET['p'] = 0;
        } else {
            $get     = $this->request->getGet();
            $id      = $get['id'] ?? '';
            $tp_name = $get['tp_name'] ?? '';
            $id ? $build->where('id', 'like', '%' . $id . '%') : '';
            $tp_name ? $build->where('tp_name', 'like', '%' . $tp_name . '%') : '';
        }
        $num    = $build->count();
        $result = $build->orderBy('id desc')->skip(($this->page - 1) * $this->perPage)->take($this->perPage)->get()->toArray();
        // 获得分页配置
        $config = set_page_config($num, $this->url, 3, $this->perPage);
        // 实例化分页类
        $pagination = \Config\Services::pagination();
        // 初始化分页配置
        $pagination->initialize($config);
        $page = $pagination->create_links();
        $this->assign('page', $page);
        $this->assign('lists', $result);
        $this->display();
    }

    /**
     * 获得代理商
     *
     * @return mixed
     */
    public function getAgent()
    {
        $build  = AgentModel::select(['id', 'account', 'name']);
        $num    = $build->count();
        $result = $build->orderBy('add_time desc')->skip(($this->page - 1) * $this->perPage)->take($this->perPage)->get()->toArray();
        // 获得分页配置
        $config = set_page_config($num, $this->url, 3, $this->perPage);
        // 实例化分页类
        $pagination = \Config\Services::pagination();
        // 初始化分页配置
        $pagination->initialize($config);
        $page = $pagination->create_ajax_links();
        call_back(0, ['list' => $result, 'page' => $page]);
    }

    /**
     * 获得商铺
     *
     * @return mixed
     */
    public function getShop()
    {
        $build = \ShopModel::select([
            'id',
            'pid',
            'shop_name',
            'add_time',
            'linker',
            'phone',
            'account',
            'max_count',
            'link_flag'
        ]);
        $post  = $this->request->getPost();
        if ($post) {
            $name      = $post['name'] ?? '';
            $login     = $post['login'] ?? '';
            $phone     = $post['phone'] ?? '';
            $agent     = $post['agent'] ?? '';
            $_GET['p'] = 0;
        } else {
            $get   = $this->request->getGet();
            $name  = $get['name'] ?? '';
            $login = $get['login'] ?? '';
            $phone = $get['phone'] ?? '';
            $agent = $get['agent'] ?? '';
        }
        $name ? $build->where('shop_name', 'like', '%' . $name . '%') : '';
        $login ? $build->where('account', 'like', '%' . $login . '%') : '';
        $phone ? $build->where('phone', 'like', '%' . $phone . '%') : '';
        $build->with([
            'getAgent' => function ($query, $agent) {
                if ($agent) {
                    $query->select(['id', 'name'])->where('name', 'like', '%' . $agent . '%');
                } else {
                    $query->select(['id', 'name']);
                }
            }
        ]);
        $num    = $build->count();
        $result = $build->orderBy('add_time desc')->skip(($this->page - 1) * $this->perPage)->take($this->perPage)->get()->toArray();
        // 获得分页配置
        $config = set_page_config($num, $this->url, 3, $this->perPage);
        // 实例化分页类
        $pagination = \Config\Services::pagination();
        // 初始化分页配置
        $pagination->initialize($config);
        $page = $pagination->create_ajax_links();
        call_back(0, ['list' => $result, 'page' => $page]);
    }

    /**
     * 添加模板
     */
    public function add()
    {
        $post = $this->request->getPost();
        if ($post) {
            $g = $post['group'] ? intval($post['group']) : 0;
            if ($g > 1) {
                $pid = $post['owner_id'] ?? '';
                !$pid ? call_back(2, '', '请选择模板所属对象') : '';
            }
            $post['create_time'] = time();
            $post['update_time'] = time();
            $post['create_by']   = $this->uid;
            $post['update_by']   = $this->uid;
            $status              = AuthTplModel::insertGetId($post);
            $status ? call_back(0) : call_back(2, '', '操作失败!');
        } else {
            $this->display();
        }

    }

    public function edit()
    {
        $post = $this->request->getPost();
        if ($post) {
            $count = AuthTplModel::select('id')->whereId($post['id'])->count();
            if ($count == 0) {
                call_back(2, '', '没有此模板信息');
            }
            $post['group'] = $post['group'] ? intval($post['group']) : 0;
            if ($post['group'] > 1) {
                $pid = $post['owner_id'] ? intval($post['owner_id']) : 0;
                if (!$pid) {
                    call_back(2, '', '请选择模板所属对象' . $pid);
                }
            }
            $post['update_time'] = time();
            $status              = AuthTplModel::whereId($post['id'])->update($post);
            $status ? call_back(0) : call_back(2, '', '操作失败!');

        } else {
            $info = AuthTplModel::select('*')->whereId($this->request->getGet('id'))->get()->toArray();
            $info = $info ? $info[0] : [];
            !$info ? call_back(2, '', '没有此模板信息') : '';
            switch ($info['group']) {
                case 1:
                    break;
                case 2:
                    $agentInfo = AgentModel::select(['id', 'name'])->whereId($info['owner_id'])->get()->toArray();
                    $agentInfo = $agentInfo ? $agentInfo[0] : [];
                    $this->assign('agent_info', $agentInfo);
                    break;
                case 3:
                    $shopInfo = \ShopModel::select(['id', 'shop_name'])->whereId($info['owner_id'])->get()->toArray();
                    $shopInfo = $shopInfo ? $shopInfo[0] : [];
                    $this->assign('shop_info', $shopInfo);
                    break;
            }
            $this->assign('info', $info);
            $this->display();
        }

    }

    /**
     * 删除
     *
     * @param $id
     */
    public function del($id)
    {
        $status = AuthTplModel::whereId($id)->update(['is_delete'=>1]);
        $status ? call_back(0) : call_back(2, '', '操作失败!');
    }

}