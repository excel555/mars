<?php $this->load->view('include/header'); ?>
<div class="container" style="margin-top:100px">
    <?php if (!empty($tips)) { ?>
        <div class="alert alert-danger" role="alert"><?php echo $tips; ?></div>
    <?php } ?>
    <div>
        <form action="<?php echo base_url('index.php/admin/login') ?>" method="post" role="form"
              class="well form-horizontal" style="width:500px;margin:0px auto;">
            <h3 style="text-align: center;">大狼科技</h3>
            <div style="display: flex">
                <input type="text" style=" flex: 1" class="form-control" autocomplete="off" id="name" name="name"
                       placeholder="请输入用户名">
                <div style="width: 20px;"></div>
                <input type="password" style=" flex: 1;" class="form-control" id="pwd" name="pwd" placeholder="请输入密码">
            </div>
            <input type="hidden" name="submit" value="1">
            <button type="submit" class="btn btn-primary" style="width: 150px;margin: 10px auto;display: block;">登录
            </button>
        </form>
    </div>
</div>

<?php $this->load->view('include/footer'); ?>
