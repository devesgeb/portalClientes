<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Login - SB Admin</title>
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
   <?php 

//print_r($test);
   if(isset($css) ){

     foreach($css as $css_file ){
      ?>

         <link rel="stylesheet" href="<?= esc($css_file) ?>">

    <?php 
       } //fin foreach
     } // end if

        if(isset($img) ){

     foreach($img as $img_file ){

           } //fin foreach
     } //
      ?>

        

    <?php 
        $dataform = Array('class' => 'form-signin','method'=>'post');
        $user = Array('type' => 'text', 'name' => 'Usuario', 'class' => 'form-control', 'placeholder' => 'Usuario','required' => 'required', 'autofocus'=>'autofocus');
        $clave = Array('type' => 'text', 'name' => 'Clave', 'class' => 'form-control', 'placeholder' => 'Clave', 'required' => 'required');
        $submit = Array('name'=>'submit', 'value'=>'Ingresar', 'class'=>'btn btn-primary','type'=>'submit');
    ?>



  

    </head>
    <body class="bg-primary">




        <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-5">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header">
                                       
                                        <img src="<?php echo $img_file?>/logo_empresa.png" class="img-fluid d-block mx-auto"  width="300" alt="180">
                                    </div>
                                    <div class="card-body">

      <?php echo form_open(base_url().'home/validaUser', $dataform) ?> 
            <label for="inputEmail" class="sr-only">Usuario</label>
                <div class="form-floating mb-3">
                    <?php echo form_input($user) ?><p><?php  service('validation')->getError('Usuario') ?>
                </div>
                <label for="inputPassword" class="sr-only">Clave</label>
                <div class="form-floating mb-3">
                    <?php echo form_password($clave) ?><p><?php service('validation')->getError('Clave')  ?>
                </div>
            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" value="remember-me"> Recordar
                </label>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
               <a class="small" href="password.html">Olvidaste tu clave?</a>
                <?php echo form_submit($submit) ?>
            </div>            
       <?php form_close(); ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <div id="layoutAuthentication_footer">
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">F alimentos &copy; Website 2026
                            </div>

                        </div>
                    </div>
                </footer>
            </div>
        </div>
        
        
    </body>
</html>
