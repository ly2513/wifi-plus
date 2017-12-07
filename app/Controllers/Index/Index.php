<?php
/**
 * User: yongli
 * Date: 17/9/19
 * Time: 15:55
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers\Index;

use Agent\AgentModel;
use App\Controllers\Base;

/**
 * Class Index
 *
 * @package App\Controllers\Index
 */
class Index extends Base
{
    /**
     * 首页
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 设备
     */
    public function device()
    {
        $this->display();
    }

    /**
     * 服务
     */
    public function service()
    {
        $this->display();
    }

    /**
     * 登录
     */
    public function log()
    {
        $this->display();
    }

    /**
     * 管理员登录
     */
    public function aLog()
    {
        $this->display();
    }

    /**
     * 注册
     */
    public function reg()
    {
        $this->display();
    }

    /**
     * 代理登录
     *
     * @return mixed
     */
    public function doAgentLog()
    {
        $post = $this->request->getPost();
        if (!$post) {
            call_back(2, '', '服务器忙，请稍候再试');
        }
        $user = isset($post['user']) ? strval($post['user']) : '';
        $pass = isset($post['password']) ? md5(strval($post['password'])) : '';
        $uid  = AgentModel::select([
            'id',
            'account',
            'name'
        ])->whereAccount($user)->wherePassword($pass)->get()->toArray();
        $uid  = $uid ? $uid[0] : [];
        !$uid ? call_back(2, '', '帐号信息不正确') : '';
        $_SESSION['aid']       = $uid['id'];
        $_SESSION['account']   = $uid['account'];
        $_SESSION['agentName'] = $uid['name'];
        call_back(0);
        //        $data['error'] = 0;
        //        $data['msg']   = "";
        //        $data['url']   = U('agent/index/index');
    }

    public function aLogout()
    {
        session(null);
        $this->redirect('index/index/alog');
    }

}