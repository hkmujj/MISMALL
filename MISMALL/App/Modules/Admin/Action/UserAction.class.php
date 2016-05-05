<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------


/**
 * 后台用户控制器
 */
class UserAction extends AdminAction {

    static protected $allow = array( 'updatePassword','updateNickname','submitPassword','submitNickname');

    /**
     * 用户管理首页
     */
    public function index()
    {
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        if(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }

        $list   = $this->lists('AdminUser', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

    /**
     * 修改昵称初始化
     */
    public function updateNickname()
    {
        $user = D('AdminUser');
        $userInfo = $user->getUserInfo(array('uid' => UID));
        $this->assign('userInfo', $userInfo);
        $this->meta_title = '修改个人信息';
        $this->display('updatenickname');
    }

    /**
     * 修改昵称提交
     */
    public function submitNickname(){
        //获取参数
        $post = I('post.');
        $user =   D('AdminUser');
        $data   =   $user->edit(UID, $post);
        if(!$data){
            $this->error($Member->getError());
        }

        if($data){
            $user               =   session('admin_user_auth');
            $user['nickname']   =   $post['post.nickname'];
            $user['nickname']   =   $post['post.email'];
            session('admin_user_auth', $user);
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }

    /**
     * 修改密码初始化
     */
    public function updatePassword(){
        $this->meta_title = '修改密码';
        $this->display('updatepassword');
    }

    /**
     * 修改密码提交
     */
    public function submitPassword(){
        //获取参数
        $old_password   =   I('post.old');
        empty($old_password) && $this->error('请输入原密码');
        $new_password = I('post.password');
        empty($new_password) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if($new_password !== $repassword){
            $this->error('您输入的新密码与确认密码不一致');
        }

        $User   =   D('AdminUser');
        $status =   $User->editPassword(UID, $old_password, $new_password);
        if($status){
            $this->success('修改密码成功！');
        }else{
            $this->error('修改失败');
        }
    }

    /**
     * 用户行为列表
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display('editaction');
    }

    /**
     * 更新行为
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     */
    public function changeStatus($method=null){
        $id = array_unique((array)I('id',0));
        if( in_array(C('USER_ADMINISTRATOR'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('AdminUser', $map );
                break;
            case 'resumeuser':
                $this->resume('AdminUser', $map );
                break;
            case 'deleteuser':
                $this->delete('AdminUser', $map );
                break;
            default:
                $this->error('参数非法');
        }
    }

    public function add($username = '', $password = '', $repassword = '', $nickname = '', $email = '', $mobile = ''){
        if(IS_POST){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $User   =   D('AdminUser');
            $uid    =   $User->register($username, $password, $email, $nickname, $mobile);
            if(0 < $uid){ //注册成功
                $this->success('用户添加成功！',U('index'));
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else {
            $this->meta_title = '新增用户';
            $this->display();
        }
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            default:  $error = $code;
        }
        return $error;
    }

}
