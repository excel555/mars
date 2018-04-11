<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Order_model extends MY_Model
{
    const ORDEY_STATUS_SUCC = 1;//已支付
    const ORDER_STATUS_DEFAULT = 0;//未支付
    const ORDER_STATUS_CONFIRM = 2;//下单成功支付处理中
    const ORDER_STATUS_REFUND_APPLY = 3;//退款申请
    const ORDER_STATUS_REFUND = 4;//退款完成
    const ORDER_STATUS_REJECT = 5;//驳回申请
    const ORDER_STATUS_CANCEL = -1;//订单取消
	function __construct()
	{
		parent::__construct ();
        $this->load->model("order_product_model");
        $this->load->model('equipment_model');
        $this->load->model('equipment_stock_model');
        $this->load->model('user_acount_model');
    }

    public function table_name()
    {
        return 'order';
    }
    public function return_field()
    {
        return ["id","uid","order_name","order_status","order_time","box_no","money","good_money","discounted_money","qty","last_update_time","refer","modou",'level_money'];
    }

    function get_orders($eq){
        $this->db->select('id,order_name,uid');
        $this->db->where(array('box_no'=>$eq));
        $this->db->order_by('id','DESC');
        return $this->db->get($this->table_name())->result_array();
    }
	function list_orders($uid,$status,$page=1,$page_size=10){
        $field = rtrim(join(",",$this->return_field()),",");
        if($status == 0){
            $where = array('uid'=>$uid,);
            $this->db->where_in('order_status',array(0,2));
        }else if($status > 0){
            $where = array('uid'=>$uid,'order_status'=>$status);
        }
        else
            $where = array('uid'=>$uid);//全部订单
		$this->db->select($field);
		$this->db->where($where);
        $this->db->order_by('id','DESC');
        $this->db->limit($page_size,($page-1)*$page_size);
        $res = $this->db->get($this->table_name())->result_array();
        if($res){
            foreach($res as $k=> $g){
                $res[$k]["goods"] = $this->order_product_model->list_order_products($g['order_name']);
            }
        }
		return $res;
	}

    function list_not_pay_order($min){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('order_status'=>0,'last_update_time <='=>date("Y-m-d H:i:s",time()-$min*60));
        $this->db->select($field);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $res =  $this->db->get($this->table_name())->result_array();
        if($res){
            foreach($res as $k=> $g){
                $res[$k]["goods"] = $this->order_product_model->list_order_products($g['order_name']);
            }
        }
        return $res;
    }

    function list_not_pay_order_for_cron($min, $day, $refer=null){
        $field = rtrim(join(",",$this->return_field()),",");
        //增加15天前的数据不发起支付
        $where = array('last_update_time <='=>date("Y-m-d H:i:s",time()-$min*60),'order_time >='=>date("Y-m-d H:i:s",time()-$day*24*60*60));
        if($refer){
            $where['refer'] = $refer;
        }
        $this->db->select($field);
        $this->db->where_in('order_status',array(0,2));//未支付或者支付中
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $res =  $this->db->get($this->table_name())->result_array();
        if($res){
            foreach($res as $k=> $g){
                $res[$k]["goods"] = $this->order_product_model->list_order_products($g['order_name']);
            }
        }
        return $res;
    }

    /**
     * @param $min
     * @return mixed
     * 下单成功，支付中订单
     */
    function list_pay_process_order($min){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('order_status'=>2,'last_update_time <='=>date("Y-m-d H:i:s",time()-$min*60));
        $this->db->select($field);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $res =  $this->db->get($this->table_name())->result_array();
        if($res){
            foreach($res as $k=> $g){
                $res[$k]["goods"] = $this->order_product_model->list_order_products($g['order_name']);
            }
        }
        return $res;
    }

    function get_order_info_by_id($id,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('id'=>$id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = $query->row_array();
        return $res;
    }

    /**
     * 退款
     * @param $data
     */
    function refund($data){
        $this->db->trans_begin();
        $this->db->set('order_status',$data["order_status"]);
        $this->db->where('id', $data["id"]);
        $this->db->update($this->table_name());
        $this->load->model('order_refund_model');

        //创建订单 加入平台
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($data['box_no']);
        $data['platform_id'] = $equipment_info['platform_id']?$equipment_info['platform_id']:1;


        $this->order_refund_model->create_one($data);// 插入退款申请表
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    function register($data)
    {
        return $this->insert($data);
    }

    /*
     * @desc 获取订单商品详情
     * @param  $data array(
     *                   0=>array('product_id'=>xxx, 'qty' => 1),
     *                   1=>array('product_id'=>xxx, 'qty' => 1)
     *               )
     * @return array data
     * */
    public function get_order_product($box_no, $data, $add_price){
        $product_ids  = array();
        $qty = $good_money = 0;
        foreach ($data as $k => $v) {
            if($v['product_id'] == 0){
                unset($data[$k]);
                continue;
            }
            $product_ids[] = $v['product_id'];
            $qty += $v['qty'];
        }
        if(empty($product_ids)){
            return false;
        }
        $this->load->model('product_model');
        $product_info = $this->product_model->get_info_by_ids($product_ids);//商品详情
        $product_class= $this->get_product_class();
        foreach($data as $k=>$v){
            $tmp_price   = bcmul(floatval($product_info[$v['product_id']]['price']), $add_price, 2);//商品价格乘盒子溢价
            $good_money += floatval($tmp_price * $v['qty']);
            $img_url     = $product_info[$v['product_id']]['img_url']?$product_info[$v['product_id']]['img_url']:'images/box_products_img/1760/1/1-180x180-1760-K7AW7HKP.jpg';
            $data[$k]['total_money']  = floatval($tmp_price * $v['qty']);
            $data[$k]['price']        = $tmp_price;
            $data[$k]['product_name'] = $product_info[$v['product_id']]['product_name'];
            $data[$k]['class_id']     = $product_info[$v['product_id']]['class_id'];
            $data[$k]['class_name']   = $product_class[$product_info[$v['product_id']]['class_id']];
            $data[$k]['img_url']      = 'http://fdaycdn.fruitday.com/'.$img_url;
        }
        return array('good_money'=>$good_money, 'qty'=>$qty, 'data'=>$data);
    }

    public function get_product_class(){
        $this->db->from('cb_product_class');
        $this->db->where(array('parent_id >' => 0));
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['id']] = $v['name'];
        }
        return $tmp;
    }

    /*
     * @desc 创建订单
     * @param  $data array(
     *                   0=>array('product_id'=>xxx, 'qty' => 1),
     *                   1=>array('product_id'=>xxx, 'qty' => 1)
     *               )
     * 这里的product_id  对应的是cb_product 里面的id
     * @param $uid int 用户id
     * @param $box_no 盒子编码
     * @param $refer str 来源： alipay/wechat/fruitday
     * @return string order_name
     * */


    function get_platform_id($equipment_id){
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($equipment_id);
       return $equipment_info['platform_id']?$equipment_info['platform_id']:1;
    }

    //生成订单号
    public function get_order_name()
    {
        $order_name = date("ymdi") . $this->rand_code(6);
        $this->db->where('order_name', $order_name);
        $this->db->from($this->table_name());
        $res = $this->db->get()->num_rows();
        if ($res) {
            return $this->get_order_name();
        }
        return $order_name;
    }

    /*
     * @desc 判断用户是否新用户
     * @param $uid int 用户id
     * @return bool
     * */
    public function check_user_order($uid){
        $where = array('uid'=>$uid, 'order_status !='=>self::ORDER_STATUS_REFUND);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = $query->row_array();
        if(empty($res)){
            return true;//新用户
        }
        return false;
    }

    function rand_code($length=6) {
        $code="";
        for($i=0;$i<$length;$i++) {
            $code .= mt_rand(0,9);
        }
        return $code;
    }

    /*
     * @desc 根据order_name获取订单
     * @param $order_name string 订单编码
     * @return array
     * */
    function get_order_by_name($order_name){
        $this->db->where(array('order_name' => $order_name));
        $this->db->from($this->table_name());
        return $this->db->get()->row_array();
    }

    /*
     * @desc 更新订单状态
     * @param $order_name string 订单编码
     * */
    public function update_order($order_name, $status)
    {
        $param['order_status'] = $status;
        $param['last_update_time'] = date("Y-m-d H:i:s");
        return $this->db->update($this->table_name(), $param, array('order_name' => $order_name));
    }

    public function update_order_last_update($order_name)
    {
        $param['last_update_time'] = date("Y-m-d H:i:s");
        return $this->db->update($this->table_name(), $param, array('order_name' => $order_name));
    }

    /*
     * @desc 获取用户今天的订单数
     * @param $uid int 用户id
     * @return int
     * */
    public function get_today_order($uid){
        $where = array('uid'=>$uid);
        $where['order_time >'] = date('Y-m-d 00:00:00');
        $this->db->where($where);
        $this->db->from($this->table_name());
        $rs = $this->db->get()->num_rows();
        return intval($rs);
    }

    /*
     * @desc 判断用户是否有未支付的订单
     * */
    public function get_order_no_pay($uid){
        $where = array('uid'=>$uid, 'order_status'=>self::ORDER_STATUS_DEFAULT);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $rs = $this->db->get()->num_rows();
        return intval($rs);
    }

    
    /*
     * @desc 获取用户未支付订单
     * */
    public function get_wait_pay_order($uid){
        $where = array('uid'=>$uid, 'order_status'=>self::ORDER_STATUS_DEFAULT);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $result = $this->db->get()->row_array();
        return $result;
    }

    /*
     * @desc 创建订单，需要将优惠精确计算到 每个商品的下面
     * @param  $data array(
     *                   0=>array('product_id'=>xxx, 'qty' => 1),
     *                   1=>array('product_id'=>xxx, 'qty' => 1)
     *               )
     * 这里的product_id  对应的是cb_product 里面的id
     * @param $uid int 用户id
     * @param $box_no 盒子编码
     * @param $refer str 来源： alipay/wechat/fruitday
     * @return string order_name
     * */
    public function create_order($data, $uid, $box_no, $refer='alipay')
    {
        $eq_info = $this->equipment_model->get_info_by_equipment_id($box_no);
        $platform_id = $eq_info['platform_id']?$eq_info['platform_id']:1;//平台id

        $result = $product_ids = array();
        $order_name  = $this->get_order_name();//生成订单号
        $tmp_product = $this->get_order_product($box_no, $data, 1);//获取订单商品 详情
        if(!$tmp_product){
            write_log('订单创建失败原因：商品id为0','crit');
            return array();
        }
        $result['qty']        = $tmp_product['qty'];
        $result['good_money'] = $tmp_product['good_money'];//订单商品价格
        $data = $tmp_product['data'];
        $real_money = $result['good_money'];

        //生成订单
        $result['money'] = $real_money>0?$real_money:0;
        $result['order_name'] = $order_name;
        $result['order_status'] = self::ORDER_STATUS_DEFAULT;
        $result['order_time'] = date('Y-m-d H:i:s');
        $result['box_no'] = $box_no;
        $result['discounted_money'] = 0;
        $result['uid'] = $uid;
        $result['last_update_time'] =  date('Y-m-d H:i:s');;
        $result['refer'] = $refer;
        $result['platform_id'] = $platform_id; //创建订单 加入平台

        $this->db->trans_strict(FALSE);//开始事物
        $this->db->trans_begin();

        $this->db->insert($this->table_name(), $result);//insert order表

        $order_product = array();
        foreach($data as $dk=>$dv){
            $order_product[$dk]['dis_money']    = 0;
            $order_product[$dk]['order_name']   = $order_name;
            $order_product[$dk]['product_id']   = $dv['product_id'];
            $order_product[$dk]['product_name'] = $dv['product_name'];
            $order_product[$dk]['qty']          = $dv['qty'];
            $order_product[$dk]['price']        = $dv['price'];
            $order_product[$dk]['total_money']  = $dv['total_money'];
        }
        //生成订单商品
        $this->db->insert_batch('order_product', $order_product);//insert order_product表


        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            write_log('订单创建失败sql原因：','crit');
            return array();
        } else {
            $this->db->trans_commit();
            return array('order_name' => $order_name, 'money' => $result['money'], 'detail' => $order_product, 'good_money' => $tmp_product['good_money'], 'gat_data'=>$data);
        }
    }





    /*
     * @desc 创建购物车
     * @param  $data array(
     *                   0=>array('product_id'=>xxx, 'qty' => 1),
     *                   1=>array('product_id'=>xxx, 'qty' => 1)
     *               )
     * 这里的product_id  对应的是cb_product 里面的id
     * @param $uid int 用户id
     * @param $box_no 盒子编码
     * @param $refer str 来源： alipay/wechat/fruitday
     * @return string order_name
     * */
    public function create_cart($data, $uid, $box_no, $refer='alipay')
    {
        if(!$data){
            return false;
        }
        $eq_info = $this->equipment_model->get_info_by_equipment_id($box_no);
        $platform_id = $eq_info['platform_id']?$eq_info['platform_id']:1;//平台id
        $add_price   = $eq_info['add_price']?$eq_info['add_price']:1;//溢价
        $this->equipment_stock_model->update_stock($data, $box_no);


        $result = $product_ids = array();
        $tmp_product = $this->get_order_product($box_no, $data, $add_price);//获取订单商品 详情
        if(!$tmp_product){
            write_log('订单创建失败原因：商品id为0','crit');
            return array();
        }
        $result['qty']        = $tmp_product['qty'];
        $result['good_money'] = $tmp_product['good_money'];//订单商品价格
        $data                 = $tmp_product['data'];//商品列表


        //满额减活动
        $this->load->model('active_discount_model');
        $discount_tmp = $this->active_discount_model->get_order_after_active_v2($uid, $box_no, $refer, $platform_id, $data);
        $discount_money = ($discount_tmp['discount_money']>0)?$discount_tmp['discount_money']:0;
        $discount_log   = $discount_tmp['discount_log'];
        $product_d_money= $discount_tmp['data'];//每件商品的优惠金额 数组
        foreach($data as $k=>$v){
            if(!$v['product_id']){
                unset($data[$k]);
                continue;
            }
            $data[$k]['dis_money']  = floatval($product_d_money[$v['product_id']]);
        }

        $real_money = bcsub($result['good_money'] , $discount_money, 2);//实付价格,保留两位小数
        //查看是否有符合的优惠券
        $order_info = array(
            'product_info'=>$data,
            'uid'         =>$uid,
            'money'       =>$real_money,//折扣后的金额
            'on_sale'     =>$discount_money>0?true:false,//是否已经享受过了 活动营销
            'platform_id' =>$platform_id,
            'source'      =>$refer
        );
        $cards = $this->card_model->get_which_card_to_use($order_info);
        if($cards && !empty($cards)){//有享受的优惠券
            $result['card_money'] = $cards['card_money'];
            $result['use_card']   = $cards['card_number'];
            $real_money = bcsub($real_money , $cards['card_money'], 2);//实付价格,减去优惠券价格，保留两位小数
        }else{
            $result['card_money'] = 0;
            $result['use_card']   = '';
        }

        //账户折扣，会员等级折扣， 魔豆值使用，余额使用
        $acount      = $this->user_acount_model->acount_money($uid, $real_money, array(), $refer, $platform_id, $box_no);
        $real_money  = bcsub($real_money, $acount['total'], 2);//魔豆能够抵扣的金额
        $result['level_money'] = 0;//周三会员优惠金额, 暂时弃用
        $result['modou']       = $acount['modou'];
        $result['yue']         = $acount['yue'];

        //会员等级折扣， 魔豆值使用

        //生成订单
        $result['money'] = $real_money>0?$real_money:0;
        $result['box_no'] = $box_no;
        $result['discounted_money'] = ($discount_money>$result['good_money'])?$result['good_money']:$discount_money;
        $result['refer'] = $refer;
        $result['goods'] = $data;
        $result['discount'] = $discount_log;
        return $result;
    }


    /*
     * @desc 根据订单号获取订单商品
     * **/
    public function get_product_by_order_name($order_name){
        $this->db->select('o.money, o.good_money,o.order_name,op.product_name,op.qty');
        $this->db->from('order o');
        $this->db->join('order_product op', 'o.order_name=op.order_name');
        $this->db->where(array('o.order_name'=>$order_name));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result['money']      = $v['money'];
            $result['good_money'] = $v['good_money'];
            $result['order_name'] = $v['order_name'];
            $result['goods'][$k]['product_name'] = $v['product_name'];
            $result['goods'][$k]['qty']          = $v['qty'];
        }
        return $result;
    }

    /**
     * 订单支付成功处理
     * @param $param array 参数
     * @return bool
    */
    public function order_pay_success($param){
        $this->load->model('order_pay_model');
        $this->order_pay_model->update_pay_status($param['pay_no'], 1, '支付成功', $param['money']);
        $this->update_order($param['order_name'], 1);
        //支付成功, 赠送魔力值， 魔豆
        $this->load->model('user_acount_model');
        $this->user_acount_model->update_user_acount($param['uid'], $param['money'], $param['order_name']);
        return true;
    }



}

?>
