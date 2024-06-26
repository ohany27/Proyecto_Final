<?php
include ("../../Config/validarSesion.php");
require_once ("../../Config/conexion.php");

$DataBase = new Database;
$con = $DataBase->conectar();

$nitc_usuario = $_SESSION['usuario']['nitc'];

// Consulta para obtener la cantidad de llamadas donde el documento coincida con el NITC del usuario en sesión
$query_llamadas_est_3 = "SELECT COUNT(l.id_est) AS cantidad_llamadas_est_3
                        FROM llamadas l
                        INNER JOIN usuario u ON l.documento = u.documento
                        WHERE u.nitc = :nitc_usuario
                         AND l.id_est = 3";

$statement_llamadas_est_3 = $con->prepare($query_llamadas_est_3);
$statement_llamadas_est_3->bindParam(':nitc_usuario', $nitc_usuario, PDO::PARAM_STR);
$statement_llamadas_est_3->execute();
$resultado_llamadas_est_3 = $statement_llamadas_est_3->fetch(PDO::FETCH_ASSOC);

$totalSolicitudes = $resultado_llamadas_est_3 ? $resultado_llamadas_est_3['cantidad_llamadas_est_3'] : "Error al ejecutar la consulta.";

// Consulta para obtener la cantidad de llamadas donde el documento coincide con el NITC del usuario en sesión y el id_est es igual a 5
$query_llamadas_est_45 = "SELECT COUNT(l.id_est) AS cantidad_llamadas_est_45
                          FROM llamadas l
                          INNER JOIN usuario u ON l.documento = u.documento
                          WHERE u.nitc = :nitc_usuario
                          AND (l.id_est = 4 OR l.id_est = 5)";

$statement_llamadas_est_45 = $con->prepare($query_llamadas_est_45);
$statement_llamadas_est_45->bindParam(':nitc_usuario', $nitc_usuario, PDO::PARAM_STR);
$statement_llamadas_est_45->execute();
$resultado_llamadas_est_45 = $statement_llamadas_est_45->fetch(PDO::FETCH_ASSOC);

$totalLlamadasFinalizadas = $resultado_llamadas_est_45 ? $resultado_llamadas_est_45['cantidad_llamadas_est_45'] : "Error al ejecutar la consulta.";

// Consulta para obtener la cantidad de registros en la tabla detalle_ticket donde el documento coincide con el NITC del usuario en sesión y el id_estado sea igual a 4
$query_tickets_proceso = "SELECT COUNT(t.id_estado) AS cantidad_tickets_proceso
                          FROM detalle_ticket t
                          INNER JOIN usuario u ON t.documento = u.documento
                          WHERE u.nitc = :nitc_usuario
                          AND t.id_estado = 4
                          AND NOT EXISTS (
                              SELECT 1 FROM detalle_ticket t2
                              WHERE t.id_ticket = t2.id_ticket
                              AND t2.id_estado = 5
                          )";

$statement_tickets_proceso = $con->prepare($query_tickets_proceso);
$statement_tickets_proceso->bindParam(':nitc_usuario', $nitc_usuario, PDO::PARAM_STR);
$statement_tickets_proceso->execute();
$resultado_tickets_proceso = $statement_tickets_proceso->fetch(PDO::FETCH_ASSOC);

$totalTicketProceso = $resultado_tickets_proceso ? $resultado_tickets_proceso['cantidad_tickets_proceso'] : "Error al ejecutar la consulta.";

$query_tickets_estado_5 = "SELECT COUNT(t.id_estado) AS cantidad_tickets_estado_5
                          FROM detalle_ticket t
                          INNER JOIN usuario u ON t.documento = u.documento
                          WHERE u.nitc = :nitc_usuario
                          AND t.id_estado = 5";

$statement_tickets_estado_5 = $con->prepare($query_tickets_estado_5);
$statement_tickets_estado_5->bindParam(':nitc_usuario', $nitc_usuario, PDO::PARAM_STR);
$statement_tickets_estado_5->execute();
$resultado_tickets_estado_5 = $statement_tickets_estado_5->fetch(PDO::FETCH_ASSOC);

$totalTicketsEstado5 = $resultado_tickets_estado_5 ? $resultado_tickets_estado_5['cantidad_tickets_estado_5'] : "Error al ejecutar la consulta.";

// Consulta para obtener la cantidad de registros en la tabla detalle_ticket donde el documento coincide con el NITC del usuario en sesión y el id_estado sea igual a 5

$query_tickets_finalizados = "SELECT COUNT(t.id_estado) AS cantidad_tickets_finalizados, MONTH(t.fecha_final) AS mes, u.id_tip_usu
                              FROM detalle_ticket t
                              INNER JOIN usuario u ON t.documento = u.documento
                              WHERE u.nitc = :nitc_usuario
                              AND t.id_estado = 5
                              GROUP BY MONTH(t.fecha_final), u.id_tip_usu";

$statement_tickets_finalizados = $con->prepare($query_tickets_finalizados);
$statement_tickets_finalizados->bindParam(':nitc_usuario', $nitc_usuario, PDO::PARAM_STR);
$statement_tickets_finalizados->execute();
$resultado_tickets_finalizados = $statement_tickets_finalizados->fetchAll(PDO::FETCH_ASSOC);

// Inicializar arreglos para almacenar datos por mes
$tecnicosData = array_fill(0, 12, 0); // Arreglo para almacenar datos de técnicos por mes (inicializado con 12 ceros)
$empleadosData = array_fill(0, 12, 0); // Arreglo para almacenar datos de empleados por mes (inicializado con 12 ceros)

// Llenar arreglos con los datos obtenidos
foreach ($resultado_tickets_finalizados as $row) {
  $mes = (int) $row['mes'] - 1; // Ajustamos el mes para que sea un índice válido (0-11)
  $cantidad = (int) $row['cantidad_tickets_finalizados'];
  $id_tip_usu = (int) $row['id_tip_usu'];

  if ($mes >= 0 && $mes < 12) { // Verificar que el mes esté dentro del rango válido (0-11)
    if ($id_tip_usu == 4) { // Técnico
      $tecnicosData[$mes] += $cantidad;
    } elseif ($id_tip_usu == 3) { // Empleado
      $empleadosData[$mes] += $cantidad;
    }
  }
}

// Consultas para obtener el recuento de clientes, empleados y técnicos
$query_clientes = "SELECT COUNT(*) AS cantidad_clientes FROM usuario WHERE id_tip_usu = 2";
$statement_clientes = $con->prepare($query_clientes);
$statement_clientes->execute();
$resultado_clientes = $statement_clientes->fetch(PDO::FETCH_ASSOC);
$cantidad_clientes = $resultado_clientes['cantidad_clientes'];

$query_empleados = "SELECT COUNT(*) AS cantidad_empleados FROM usuario WHERE id_tip_usu = 3";
$statement_empleados = $con->prepare($query_empleados);
$statement_empleados->execute();
$resultado_empleados = $statement_empleados->fetch(PDO::FETCH_ASSOC);
$cantidad_empleados = $resultado_empleados['cantidad_empleados'];

$query_tecnicos = "SELECT COUNT(*) AS cantidad_tecnicos FROM usuario WHERE id_tip_usu = 4";
$statement_tecnicos = $con->prepare($query_tecnicos);
$statement_tecnicos->execute();
$resultado_tecnicos = $statement_tecnicos->fetch(PDO::FETCH_ASSOC);
$cantidad_tecnicos = $resultado_tecnicos['cantidad_tecnicos'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Admin | CRM</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
  <!-- Favicon -->
  <link rel="apple-touch-icon" sizes="57x57" href="../../Assets/favicon/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="../../Assets/favicon/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="../../Assets/favicon/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="../../Assets/favicon/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="../../Assets/favicon/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="../../Assets/favicon/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="../../Assets/favicon/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="../../Assets/favicon/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../../Assets/favicon/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192" href="../../Assets/favicon/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../Assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="../../Assets/favicon/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../Assets/favicon/favicon-16x16.png">
  <link rel="manifest" href="../../Assets/favicon/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__shake" src="dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="fas fa-cogs"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header">Opciones</span>
            <div class="dropdown-divider"></div>
            <a href="Visualizar/perfil.php" class="dropdown-item">
              <i class="fas fa-user mr-2"></i> Perfil
              <span class="float-right text-muted text-sm">visitar</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="../../Config/validarSesion.php?logout=true" class="dropdown-item">
              <i class="fas fa-sign-out-alt mr-2"></i> Salir
              <span class="float-right text-muted text-sm">accion</span>
            </a>
          </div>
        </li>

      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      <a href="index.php" class="brand-link">
        <img src="dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light">CRM</span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="info">
            <a href="#" class="d-block">Administrador</a>
          </div>
        </div>


        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class
              with font-awesome or any other icon font library -->
            <li class="nav-item menu-open">
              <a href="./index.php" class="nav-link active">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Panel
                </p>
              </a>
            </li>
            <li class="nav-header">Usuarios</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-users"></i>
                <p>
                  Usuarios
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="Visualizar/usuarios.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Visualizar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="Crear/usuarios.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Crear</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-header">RIESGOLOGIA</li>

            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-exclamation-triangle"></i>
                <p>
                  Daños
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="Visualizar/daños.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Visualizar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="Crear/daños.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Crear</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-boxes"></i>
                <p>
                  Detalle-Daños
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="Visualizar/daño_detalle.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Visualizar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="Crear/daño_detalle.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Crear</p>
                  </a>
                </li>
              </ul>
            <li class="nav-header">REPORTES</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-database"></i>
                <p>
                  Llamadas-Tickets
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="Visualizar/reportes.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Visualizar</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas  fa-memory"></i>
                <p>
                  Trazabilidad
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="Visualizar/trazabilidad.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Visualizar</p>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Panel Administrativo</h1>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-info">
                <div class="inner">
                  <h3><?php echo $totalSolicitudes; ?></h3>

                  <p>Solucitudes Pendientes</p>
                </div>
                <div class="icon">
                  <i class="ion ion-email-unread"></i>
                </div>

              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-success">
                <div class="inner">
                  <h3><?php echo $totalLlamadasFinalizadas; ?></h3>

                  <p>LLamadas Finalizadas</p>
                </div>
                <div class="icon">
                  <i class="ion ion-android-call"></i>
                </div>

              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3><?php echo $totalTicketProceso; ?></h3>

                  <p>Tickets En Proceso</p>
                </div>
                <div class="icon">
                  <i class="ion ion-android-star-half"></i>
                </div>

              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3><?php echo $totalTicketsEstado5; ?></h3>

                  <p>Tickets Finalizados</p>
                </div>
                <div class="icon">
                  <i class="ion ion-android-star"></i>
                </div>

              </div>
            </div>
            <!-- ./col -->
            <div class="col-md-6">
              <!-- Usuarios -->
              <div class="card card-gray">

                <div class="card-header">
                  <h3 class="card-title">Grafica De Usuarios </h3>

                </div>
                <div class="card-body">
                  <canvas id="donutChart"
                    style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
                <!-- /.card-body -->
              </div>
            </div>
            <div class="col-md-6">
              <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">Finalizadores</h3>

                </div>
                <div class="card-body">
                  <div class="chart">
                    <canvas id="barChart"
                      style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                  </div>
                </div>
                <!-- /.card-body -->
              </div>
            </div>

          </div>
          <!-- /.row -->
          <!-- Main row -->

          <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
      <strong>Copyright &copy; 2024 <a href="#">CRM</a>.</strong>
      All rights reserved.
      <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 1.0.0
      </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <!-- jQuery -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
    $.widget.bridge('uibutton', $.ui.button)
  </script>
  <!-- Bootstrap 4 -->
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- ChartJS -->
  <script src="plugins/chart.js/Chart.min.js"></script>
  <!-- Sparkline -->
  <script src="plugins/sparklines/sparkline.js"></script>
  <!-- JQVMap -->
  <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
  <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
  <!-- jQuery Knob Chart -->
  <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
  <!-- daterangepicker -->
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <!-- Tempusdominus Bootstrap 4 -->
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  <!-- Summernote -->
  <script src="plugins/summernote/summernote-bs4.min.js"></script>
  <!-- overlayScrollbars -->
  <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <!-- AdminLTE App -->
  <script src="dist/js/adminlte.js"></script>

  <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
  <script src="dist/js/pages/dashboard.js"></script>
  <script>
    $(function () {
      var donutChartCanvas = $('#donutChart').get(0).getContext('2d');
      var donutData = {
        labels: ['Clientes', 'Empleados', 'Técnicos'],
        datasets: [{
          data: [<?php echo $cantidad_clientes; ?>, <?php echo $cantidad_empleados; ?>, <?php echo $cantidad_tecnicos; ?>],
          backgroundColor: ['#f56954', '#d2d6de', '#3c8dbc'],
        }]
      };
      var donutOptions = {
        maintainAspectRatio: false,
        responsive: true,
      };
      new Chart(donutChartCanvas, {
        type: 'doughnut',
        data: donutData,
        options: donutOptions
      });

      var tecnicosData = <?php echo json_encode($tecnicosData); ?>;
      var empleadosData = <?php echo json_encode($empleadosData); ?>;

      var barChartCanvas = $('#barChart').get(0).getContext('2d');
      var barChartData = {
        labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        datasets: [
          {
            label: 'Técnicos',
            backgroundColor: 'rgba(60,141,188,0.9)',
            borderColor: 'rgba(60,141,188,0.8)',
            pointRadius: false,
            pointColor: '#3b8bba',
            pointStrokeColor: 'rgba(60,141,188,1)',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(60,141,188,1)',
            data: tecnicosData
          },
          {
            label: 'Empleados',
            backgroundColor: 'rgba(210, 214, 222, 1)',
            borderColor: 'rgba(210, 214, 222, 1)',
            pointRadius: false,
            pointColor: 'rgba(210, 214, 222, 1)',
            pointStrokeColor: '#c1c7d1',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(220,220,220,1)',
            data: empleadosData
          },
        ]
      };

      var barChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        datasetFill: false
      };

      new Chart(barChartCanvas, {
        type: 'bar',
        data: barChartData,
        options: barChartOptions
      });
    });
  </script>
</body>

</html>
