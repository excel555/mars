<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class  Cases  extends MY_Controller
{
    //箱号类型
    public static $case_type = array("A"=>"大泡沫箱34L","B"=>"中泡沫箱25L","C"=>"小泡沫箱14L","D"=>"紫色折叠周转箱");
    public static $start_status = array("1"=>"启用","0"=>"未启用","2"=>"破损","3"=>"出库");
    public static $shelves_status= array("1"=>"上架","2"=>"下架");
    public static $type= array("1"=>"采购入库","2"=>"调拨入库");

    function __construct()
    {
        parent::__construct();
        $session = (array)$this->session->userdata('sess_admin_data');
        $this->admin_name = !empty($session['adminname']) ? $session['adminname'] : '';
        $this->adminid = !empty($session['adminid']) ? $session['adminid'] : 0;
        $this->platform_id = !empty($session['adminPlatformId']) ? $session['adminPlatformId'] : 0;
        $this->load->model("cases_model");
        $this->load->model("warehouse_model");

    }

    //箱子管理列表
    function cases_list(){
        $warehouses = $this->warehouse_model->getWarehouse($this->adminid,$this->platform_id);;
        $this->_pagedata['type'] = self::$case_type;
        $this->_pagedata['start_status'] = self::$start_status;
        $this->_pagedata['shelves_status'] = self::$shelves_status;
        $this->_pagedata['warehouses'] = $warehouses;
        $this->page('cases/cases_list.html');
    }
    function cases_table()
    {

        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset'): 0;
        $search_warehouse_id = $this->input->get('search_warehouse_id');
        $search_case_id = $this->input->get('search_case_id');
        $search_shelves_status = $this->input->get('search_shelves_status');
        $search_start_status =  $this->input->get('search_start_status');
        $search_case_type = $this->input->get('search_case_type');
//        $search_generate_date_start = $this->input->get('search_generate_date_start');
//        $search_generate_date_end = $this->input->get('search_generate_date_end');
//        $search_start_date_start = $this->input->get('search_start_date_start');
//        $search_start_date_end = $this->input->get('search_start_date_end');
        $sort = $this->input->get('sort') ? $this->input->get('sort') : 'id';
        $order = $this->input->get('order') ? $this->input->get('order') : 'desc';
        $warehouse_admin = $this->db->from("warehouse_admin")->where("admin_id",$this->adminid)->get()->result_array();
        $where['platform_id'] = $this->platform_id;

        if ($search_warehouse_id) {
            $where['warehouse_id'] = $search_warehouse_id;

        }
        if ($search_case_id) {
            $where['id like '] = "%".$search_case_id."%";

        }
        if ($search_shelves_status) {
            $where['shelves_status'] = $search_shelves_status;
        }

        if ($search_start_status !="") {
            $where['start_status'] = $search_start_status;
        }
        if ($search_case_type) {
            $where['case_type'] = $search_case_type;
        }


//        if ($search_generate_date_start) {
//            $where['generate_date >'] = $search_generate_date_start;
//        }
//        if ($search_generate_date_end) {
//            $where['generate_date <'] = $search_generate_date_end;
//        }
//        if ($search_start_date_start) {
//            $where['start_date >'] = $search_start_date_start;
//        }
//        if ($search_start_date_end) {
//            $where['start_date <'] = $search_start_date_end;
//        }

        $rows = $this->cases_model->cases_table("*", $where, $sort, $order, $limit, $offset);
        if($where != ""){
            $this->db->where($where);
        }
        $this->db->select("count(*) as c");
        $this->db->from('case');
        $total = $this->db->get()->row_array();
        $warehouses = $this->db->from("warehouse")->get()->result_array();
        $arr = [];
        foreach ($warehouses as $k=>$v){
            $arr[$v['id']] = $v['name'];
        }
        foreach ($rows as $k =>$v) {
            $rows[$k]['shelves_status'] = self::$shelves_status[$v['shelves_status']] ? : "--";
            $rows[$k]['start_status'] = self::$start_status[$v['start_status']];
            $rows[$k]['case_type'] = self::$case_type[$v['case_type']];
            $rows[$k]['case_log'] = '<a href="/cases/case_log?case_id='.$v['id'].'" class="btn btn-info btn-sm" id="case_log">查看log</a>';

            $rows[$k]['warehouse_id'] = $arr[$v['warehouse_id']['name']];

        }

        $result = array("rows" => $rows, "total" => $total['c']);
        echo json_encode($result);
    }
    //箱号生成页面
    function generate_cases(){
        $warehouses = $this->cases_model->get_warehouse();
        $this->_pagedata['type'] = self::$case_type;
        $this->_pagedata['warehouses'] = $warehouses;
        $this->page('cases/generate_cases.html');
    }
    //箱号生成
    function generate_submit(){
        $number = $this->input->post("number") ? $this->input->post("number") : "";
        $warehouse_id = $this->input->post("warehouse_id") ? $this->input->post("warehouse_id") : "";
        $case_type = $this->input->post("case_type") ? $this->input->post("case_type") : "";
        if(!is_numeric($number)){
            $this->showJson(array("status"=>false));die;
        }
        $succ = 0;
        $err = 0;
        for($i=1;$i<=$number;$i++){
           // $case_number = $this->rand();
           // $res = $this->cases_model->add_case_number($case_number,$warehouse_id,$case_type);
            $res = $this->cases_model->add_case_number($warehouse_id,$case_type);
            if($res){
                $succ += 1;
            }else{
                $err += 1;
            }
        }
        $this->showJson(array("status"=>true,"succ"=>$succ,"err"=>$err));
    }
//    function rand(){
//            $len   = 5;
//            $str   = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
//            $str1  = "0123456789";
//            $case_number = "";
//            for($i=1;$i<=$len-1;$i++) {
//                $case_number .= $str1[mt_rand(0,strlen($str1)-1)];
//            }
//            $case_number .= $str[mt_rand(0,strlen($str)-1)];
//        //$case = $this->db->select("*")->from('case')->where("case_number",$case_number)->get()->row_array();
//            $case = $this->cases_model->get_case($case_number);
//            if(empty($case)){
//                return $case_number;
//            }else{
//                $this->rand();
//            }
//    }
    function get_case_number(){
        $case_id = $this->input->post("case_id");
        $warehouse_id = $this->input->post("warehouse_id");
        $case = $this->db->select("id,case_type,warehouse_id,platform_id")->from("case")->where("id",$case_id)->get()->row_array();
//        判断是否platform_id相等
        if($case['platform_id'] == $this->platform_id){
            $case['falg'] = true;
        }else{
            $case['falg'] = false;
        }
        $case['case_type'] = self::$case_type[$case['case_type']];
        echo json_encode($case);die;
    }
    //箱子启用页面
    function add_cases(){
        $warehouses = $this->warehouse_model->getWarehouse($this->adminid,$this->platform_id);;
        $this->_pagedata['warehouses'] = $warehouses;
        $this->_pagedata['type'] = self::$type;
        $this->page('cases/cases_add.html');
    }
    //箱子启用 修改状态start_status为1
    function add_submit()
    {
        $data = $this->input->post('data');
        $data = json_decode($data, true);
        $warehouse_id = $this->input->post('warehouse_id');
        $type = $this->input->post('type');
        $res = $this->cases_model->start_status_update($data,$warehouse_id,$type);
        $this->showJson($res);

    }

    //导出
    function cases_excel(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        include(APPPATH . 'libraries/barcode/BarcodeGeneratorPNG.php');
        $objPHPExcel = new PHPExcel();

        $ids = $this->input->get("id");
        $sql ="select * from cb_case where id in (".$ids.") order by case_type asc";
        $cases = $this->db->query($sql)->result_array();
        $warehouses = $this->db->from("warehouse")->get()->result_array();
        $arr = [];
        foreach ($warehouses as $k=>$v){
            $arr[$v['id']] = $v['name'];
        }

        if(!empty($cases)){
            $columns = [
                'A' => ['width' => 10,  'title' => '箱号ID', 'field' => 'id',],
//                'B' => ['width' => 15,  'title' => '箱号', 'field' => 'case_number'],
                'B' => ['width' => 10,  'title' => '启用状态', 'field' => 'start_status'],
                'C' => ['width' => 20,  'title' => '仓库', 'field' => 'warehouse_name'],
                'D' => ['width' => 15,  'title' => '类型', 'field' => 'case_type'],
                'E' => ['width' => 36,  'title' => '条形码', 'field' => 'code'],

            ];


            $sheet = $objPHPExcel->setActiveSheetIndex(0);
            $sheet->setTitle('箱号' . date('YmdHis'));
            //第一行
            $line = 1;
            foreach ($columns as $k => $v) {
                $sheet->getColumnDimension($k)->setWidth($v['width']);
                $sheet->setCellValue("{$k}{$line}", $v['title']);
            }

            //第二行
            $line++;
            foreach ($cases as $k => $row) {
                 $row['start_status'] = self::$start_status[$row['start_status']];
                 $row['warehouse_name'] = $arr[$row['warehouse_id']['name']];
                 $row['case_type'] = self::$case_type[$row['case_type']];
                $sheet->setCellValue("A{$line}", $row[$columns['A']['field']]);
                $sheet->setCellValue("B{$line}", $row[$columns['B']['field']]);
                $sheet->setCellValue("C{$line}", $row[$columns['C']['field']]);
                $sheet->setCellValue("D{$line}", $row[$columns['D']['field']]);
                $sheet->setCellValue("E{$line}", $row[$columns['E']['field']]);


                //条形码
                $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
                $data = $bc->getBarcode($row['id'], $bc::TYPE_CODE_128, 2, 50);
                $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
                $objDrawing->setImageResource(imagecreatefromstring($data));
                $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
                $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
                $objDrawing->setResizeProportional(false);
                $objDrawing->setWidthAndHeight(250,50);
                $objDrawing->setCoordinates('E'.$line);
                $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(38);
                $line++;
            }

            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => ['rgb' => 'f9bf92']
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ]
            ]);

            $sheet->getStyle('A1:E1' . ($line-1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('argb' => '000000')
                    ]
                ]
            ]);

            $objPHPExcel->initHeader('箱号' . date('Y-m-d'));
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            die;

        }
    }
    //箱子出入库log页面
    function case_log(){
        $case_id = $this->input->get("case_id");
        $this->_pagedata['case_id'] = $case_id;
        $this->page('cases/cases_log.html');
    }
    function case_log_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset'): 0;
        $case_id = $this->input->get('case_id');
        $sort = $this->input->get('sort') ? $this->input->get('sort') : 'l.id';
        $order =  'desc';
        if($case_id){
            $where['case_id'] =$case_id;
        }
        $rows = $this->cases_model->case_log_table("*", $where, $sort, $order, $limit, $offset);
        $this->db->select("count(*) as c");
        $this->db->from('case_log l');
        $this->db->join('case c',"c.id=l.case_id");
        if($where != ""){
            $this->db->where($where);
        }
        $total = $this->db->get()->row_array();
        foreach ($rows as $k =>$v) {
            $sql = "select * from s_admin where id=".$v['admin_id'];
            $admin = $this->db->query($sql)->row_array();
            $rows[$k]['start_status'] = self::$start_status[$v['start_status']] ? : "-";
            $rows[$k]['admin_id'] = !empty($admin['alias']) ? $admin['alias'] : $admin['name'];
            $rows[$k]['shelves_status'] = self::$shelves_status[$v['shelves_status']] ? : "-";
            $rows[$k]['type'] = self::$type[$v['type']] ? : "-";
        }
        $result = array("rows" => $rows, "total" => $total['c']);
        echo json_encode($result);
    }



}