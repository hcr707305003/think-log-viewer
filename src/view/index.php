<!DOCTYPE html>
<html lang="zh">
<head>
    <title>log view</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        <?php echo include_once __DIR__."/css/bootstrap.min.css"?>
        <?php echo include_once __DIR__."/css/style.css"?>
    </style>
</head>
<body>

<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="navbar-brand-wrapper d-flex justify-content-center sidebar-offcanvas">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
            <a class="navbar-brand brand-logo" href="#">
                <div style="color:#999;font-size:36px;font-weight:bold;position:fixed;display:block;">log view</div>
            </a>
        </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <form style="width: 100%" id="myForm">
            <div class="input-group">
                <button onclick="clearSearchForm()" class="btn btn-sm btn-primary" type="button"><i
                            class="fa  fa-eraser"></i> clean
                </button>
                <select style="border: 1px solid #f3f3f3;" name="level" id="myLevel">
                    <option value="">all</option>
                    <?php foreach ($this->level as $level) { ?>
                        <option value="<?=$level?>" <?=(input('level') == $level)? 'selected': ''?> ><?=$level?></option>
                    <?php } ?>
                </select>
                <textarea class="form-control" rows="1" name="search" placeholder="Search now" id="search"><?=input('search')?></textarea>
                <button type="submit" class="btn btn-sm btn-success" type="button" form="myForm"><i
                            class="fa  fa-eraser"></i> search
                </button>
                <!-- 提交按钮 -->
                <script>
                    function clearSearchForm() {
                        let url_all = window.location.href;
                        let arr = url_all.split('?');
                        window.location.href = arr[0];
                    }
                    document.getElementById("myLevel").addEventListener("change", function() {
                        document.getElementById("myForm").submit();
                    });
                    document.onkeydown = function(e){
                        if(!e) e = window.event;//火狐中是 window.event
                        if((e.keyCode || e.which) === 13){
                            document.getElementById("myForm").submit();
                        }
                    }
                </script>
            </div>
            <input type="hidden" name="file" value="<?=input('file', '')?>">
        </form>
    </div>
</nav>

<div class="container-fluid page-body-wrapper">
    <nav class="sidebar sidebar-offcanvas">
        <ul class="nav">
            <?php  foreach ($this->all_log as $path => $files) { ?>
                <li class="nav-item active">
                    <a class="nav-link" href="#">
                        <span class="menu-title"><?=$path?></span>
                    </a>
                </li>
                <?php  foreach ($files as $file) {  ?>
                    <li class="nav-item active" style="padding-left: 30px;">
                        <a class="nav-link change" style="<?php if (($this->param['file']??'') == ($path.'/'.$file)) {echo 'background-color:#b7c2e9;';} ?>" href="?file=<?=$path.'/'.$file?>">
                            <span class="menu-title"><?=$file?></span>
                        </a>
                    </li>
                <?php }?>
            <?php }?>
        </ul>
    </nav>
    <div class="main-panel">
        <div class="row">
            <div class="col-md-12 stretch-card">
                <?php if ($all_data['total'] ?? 0) { ?>
                    <div class="card">
                        <div class="card-body">
                            <p class="card-title"><a style="font-size: 14px;"><?=$this->choose_file?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a style="color: red;font-size: 10px">(注意：条数和页数会有差别)</a></p>
                            <div class="table-striped">
                                <table class="table dataTable">
                                    <thead>
                                    <tr>
                                        <th class="sorting_asc">level</th>
                                        <th class="sorting_asc">date</th>
                                        <th class="sorting">content</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php  foreach ($all_data['data'] as $v) {  ?>
                                        <tr class="<?php
                                        switch ($v['level']) {
                                            case "error":
                                                echo 'danger';break;
                                            case "warning":
                                                echo 'warning';break;
                                            case "sql":
                                            case "info":
                                                echo 'info';break;
                                            case "debug":
                                                echo 'success';break;
                                            default:
                                                echo 'default';break;
                                        }
                                        ?>">
                                            <td><?=$v['level']?></td>
                                            <td><?=$v['date']?></td>
                                            <td><?=$v['content']?></td>
                                        </tr>
                                    <?php }?>
                                    </tbody>
                                </table>

                                <ul class="pagination">
                                    <li>
                                        <a href="?<?php $this->param['page'] = 1;
                                        echo http_build_query($this->param); ?>">&laquo;</a>
                                    </li>
                                    <li>
                                        <a href="?<?php $this->param['page'] = ($all_data['page'] > 1) ? ($all_data['page'] - 1): 1;
                                        echo http_build_query($this->param); ?>"><</a>
                                    </li>
                                    <?php for ($i = $all_data['page']; $i <= $all_data['last_page']; $i++) { ?>
                                        <?php if($i < ($all_data['page'] + 5) || $i > ($all_data['last_page'] - 4)) { ?>
                                            <li><a href="?<?php $this->param['page'] = $i;echo http_build_query($this->param); ?>"><?=$i?></a></li>
                                        <?php } ?>
                                    <?php } ?>
                                    <li>
                                        <a href="?<?php $this->param['page'] = ($all_data['page'] >= $all_data['last_page']) ? $all_data['page']: ($all_data['page'] - 1);
                                        echo http_build_query($this->param); ?>">></a>
                                    </li>
                                    <li>
                                        <a href="?<?php $this->param['page'] = $all_data['last_page'];
                                        echo http_build_query($this->param); ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="d-flex align-items-center justify-content-center" style="height: 100vh;width: 100%;"><h1 style="color:#999;">暂无数据~</h1></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>