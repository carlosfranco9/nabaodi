<?php

namespace app\index\controller;

use app\index\model\Blacklist;
use app\index\model\Orders;
use app\index\model\Orders as OrderModel;
use app\index\validate\OrderValidate;
use think\Controller;
use think\Request;

class Order extends Base
{
    /**
     * 显示所有订单
     */
    public function allOrders(Request $request)
    {
        if ($request->isPost()) {
            return $this->seachOrders($request);
        }
        $order = Orders::alias("or")
            ->join("users u", 'u.id = or.user_id', 'left')
            ->field("or.*,u.email, u.id, u.alipay_id,u.qq")
            ->order("or.order_id", 'desc')
            ->paginate(15)->each(function ($user) {
            $black = Blacklist::where("user_id", $user->id)->find();
            if ($black) {
                $user['status'] = 4;
            }

        });
        $this->assign("list", $order);
        return view('index/admin-allOrder');
    }

    /**
     * 搜索
     */
    public function seachOrders($request)
    {
        $res = $this->match($request->post()['seach']);
        $data = Orders::searchForAll($request->post()['seach'], $res);
        $this->assign("list", $data);
        return view('index/admin-allOrder');
    }

    /**
     * 正则是哪种
     */
    protected function match($str)
    {
        $email = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        $order = "/^[0-9].*?$/";
        if (preg_match($email, $str)) {
            return "email";
        } elseif (preg_match($order, $str)) {
            return "order";
        } else {
            return "product_name";
        }
    }

    /**
     * 手动添加订单
     */
    public function store(Request $request)
    {
        $data = $request->post();
        $array['email'] = $data['data'][0];
        $array['alipay_id'] = $data['data'][1];
        $array['qq'] = $data['data'][2];
        $array['product_name'] = $data['data'][3];
        $array['created_at'] = $data['data'][4];
        $array['updated_at'] = $array['created_at'];
        //检测数据是否正确
        $res = $this->verifyForStore($array);
        if ($res !== true) {
            return $res;
        }

        $order = new OrderModel;
        $result = $order->store($array);
        if ($result !== true) {
            return $result;
        }

        return true;

    }
    /**
     * 检测增加的数据是否正确
     */
    protected function verifyForStore($data)
    {
        $validate = new OrderValidate;

        if (!$validate->check($data)) {
            return $validate->getError();
        }
        return true;
    }

    /**
     * 删除订单
     */
    public function delete(Request $request)
    {
        $order = new OrderModel;
        if ($request->isGet()) {
            $order->deleteById($request->get("id"));
            return true;
        }
        //判断是单个还是多个
        $data = $request->post();
        $arr = explode(",", $data['all']);

        $res = $order->deleteById($arr);
        return $res;
    }

    /**
     * 未完成订单界面
     */
    public function uncompleted(Request $request)
    {
        if ($request->isPost()) {
            return $this->uncompletedSearch($request);
        }
        $order = OrderModel::getUncompleted();
        $this->assign("list", $order);
        return view('index/admin-uncompleted');
    }
    /**
     * 已完成订单界面
     */
    public function completed()
    {
        $order = OrderModel::getCompleted();
        $this->assign("list", $order);
        return view('index/admin-completed');
    }
    /**
     * 回收站订单界面
     */
    public function recycling()
    {
        $order = Orders::getRecycling();
        $this->assign("list", $order);
        return view('index/admin-recyclingStation');
    }

    /**
     * 所有订单中，编辑界面页面获取用户订单信息
     */
    public function getUser(Request $request)
    {
        $user_id = $request->get("id");
        $user = new \app\index\model\User;
        $data = $user->alias('u')->join("orders o", 'o.user_id = u.id', 'right')->where("order_id", $user_id)->field("u.email,u.qq,u.alipay_id,o.*")->find();
        $time = strtotime($data['created_at']);
        $data['time'] = date("Y-m-d", $time);
        return $data;
    }

    /**
     * 编辑保存
     */
    public function edit(Request $request)
    {
        $order = Orders::editById($request->post()['data']);
        return $order;
    }
    
    /**
     * 批量完成订单
     */
    public function delOrders(Request $request)
    {
        $order = new OrderModel;
        if ($request->isGet()) {
            $order->completedOrder($request->get("id"));
            return true;
        }
        //判断是单个还是多个
        $data = $request->post();
        $arr = explode(",", $data['all']);

        $res = $order->completedOrder($arr);
        return $res;
    }
    /**
     * 未完成页面的搜索
     */
    public function uncompletedSearch($request)
    {
        $res = $this->match($request->post()['search']);
        $data = Orders::searchSomething($request->post()['search'], $res, 0);
        $this->assign("list", $data);
        return view('index/admin-uncompleted');
    }

    /**
     * 一键还原
     */
    public function rebackOrders(Request $request)
    {
        $order = new OrderModel;
        if ($request->isGet()) {
            $order->rebackOrders($request->get("id"));
            return true;
        }
        //判断是单个还是多个
        $data = $request->post();
        $arr = explode(",", $data['all']);

        $res = $order->rebackOrders($arr);
        return $res;
    }
}
