<?php
// Incluir el encabezado
include "../Template/header.php";

// Incluir el archivo de configuración de la conexión a la base de datos
require_once("../../Config/conexion.php");

// Verificar si la cookie de acceso está presente y tiene el valor esperado
if (!isset($_COOKIE['acceso_permitido']) || $_COOKIE['acceso_permitido'] !== 'true') {
    // Redirigir a la página de inicio si la cookie no está presente o no tiene el valor correcto
    echo "<script>alert('Ingresa primero en el panel CEO.');window.location='../index.php';</script>";
    exit();
}

// Inicializar el mensaje de alerta
$alert_message = '';

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $nitc = $_POST['nitc'];
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    // Verificar si todos los campos están llenos
    if (empty($nitc) || empty($nombre) || empty($direccion) || empty($telefono)) {
        $alert_message = "<script>alert('Todos los campos son obligatorios.')</script>";
    } elseif (!is_numeric($nitc) || !is_numeric($telefono)) {
        $alert_message = "<script>alert('El NITC y el teléfono deben contener solo números.')</script>";
    } else {
        // Establecer conexión a la base de datos
        $database = new Database();
        $pdo = $database->conectar();

        // Verificar si ya existe un registro con el mismo NITC
        $stmt = $pdo->prepare("SELECT * FROM empresa WHERE nitc = :nitc");
        $stmt->execute([':nitc' => $nitc]);
        $existingNitc = $stmt->fetch();

        // Verificar si ya existe un registro con el mismo nombre
        $stmt = $pdo->prepare("SELECT * FROM empresa WHERE nombre = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        $existingNombre = $stmt->fetch();

        // Verificar si ya existe un registro con el mismo teléfono
        $stmt = $pdo->prepare("SELECT * FROM empresa WHERE telefono = :telefono");
        $stmt->execute([':telefono' => $telefono]);
        $existingTelefono = $stmt->fetch();

        // Comprobar las condiciones para mostrar alertas
        if ($existingNitc) {
            $alert_message = "<script>alert('Ya existe una empresa con este NITC.')</script>";
        } elseif ($existingNombre) {
            $alert_message = "<script>alert('Ya existe una empresa con este nombre.')</script>";
        } elseif ($existingTelefono) {
            $alert_message = "<script>alert('Ya existe una empresa con este teléfono.')</script>";
        } else {
            // Insertar datos en la base de datos si no hay duplicados
            $sql = "INSERT INTO empresa (nitc, nombre, direccion, telefono, id_estado) VALUES (:nitc, :nombre, :direccion, :telefono, 1)";
            $stmt = $pdo->prepare($sql);

            $params = array(
                ':nitc' => $nitc,
                ':nombre' => $nombre,
                ':direccion' => $direccion,
                ':telefono' => $telefono
            );

            // Ejecutar la consulta de inserción
            if ($stmt->execute($params)) {
                $alert_message = "<script>alert('Empresa creada correctamente.'); window.location.href = 'empresa.php';</script>";
            } else {
                $alert_message = "<script>alert('Error al crear la empresa.')</script>";
            }
        }

        // Cerrar la conexión y liberar recursos
        unset($stmt);
        unset($pdo);
    }
}

// Obtener datos de empresas desde la base de datos
try {
    // Establecer conexión a la base de datos
    $database = new Database();
    $pdo = $database->conectar();

    // Consulta para obtener todas las empresas
    $stmt = $pdo->query("SELECT * FROM empresa");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Liberar recursos
    unset($stmt);
    unset($pdo);
} catch (PDOException $e) {
    // Manejo de errores de base de datos
    echo "Error: " . $e->getMessage();
}

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Empresas</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"></h3>
                </div>
                <div class="card-body">
                    <?php echo $alert_message; ?> <!-- Mostrar la alerta aquí -->
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="nitc" class="form-label">NIT:</label>
                                    <input type="number" class="form-control" id="nitc" name="nitc"
                                        title="Ingrese solo números" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre:</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" oninput="mayus(this)"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección:</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono:</label>
                                    <input type="number" class="form-control" id="telefono" name="telefono"
                                        pattern="[0-9]*" title="Ingrese solo números" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar Empresa</button>
                    </form>
                    <br>
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>NIT</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Actualizar</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empresas as $empresa) { ?>
                                <tr>
                                    <td><?php echo $empresa['nitc']; ?></td>
                                    <td><?php echo $empresa['nombre']; ?></td>
                                    <td><?php echo $empresa['direccion']; ?></td>
                                    <td><?php echo $empresa['telefono']; ?></td>
                                    <td><?php echo ($empresa['id_estado'] == 1) ? "Activo" : "Inactivo"; ?></td>
                                    <td class="project-actions text-center">
                                        <!-- Botón de Edición -->
                                        <a href="../Editar/empresa.php?nitc=<?php echo $empresa['nitc']; ?>"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a> 
                                    </td>
                                    <td class="project-actions text-center">
                                        <?php if ($empresa['id_estado'] == 1) { ?>
                                            <a href="llamada.php?toggle_nitc=<?php echo $empresa['nitc']; ?>"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-toggle-off"></i> Desactivar
                                            </a>
                                        <?php } elseif ($empresa['id_estado'] == 2) { ?>
                                            <a href="llamada.php?toggle_nitc=<?php echo $empresa['nitc']; ?>"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-toggle-on"></i> Activar
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    document.getElementById('nitc').addEventListener('input', function () {
        var nitValue = this.value;
        var nitLength = nitValue.length;

        // Verificar 
        if (nitLength >= 7 && nitLength <= 10 && /^\d+$/.test(nitValue) && !/[.,]/.test(nitValue)) {
            this.setCustomValidity('');
        } else {
            this.value = nitValue.slice(0, 10);
            this.setCustomValidity('El NIT debe tener entre 7 y 10 dígitos, no se permite signos de puntuación.');
        }
    });
    document.getElementById('nombre').addEventListener('input', function () {
        var nombreValue = this.value;

        // Verificar 
        if (/^[A-Za-zñÑ\s]{3,}$/.test(nombreValue) && !/[.]/.test(nombreValue)) {
            this.setCustomValidity('');
        } else {
            this.setCustomValidity('El nombre debe contener mínimo 3 letras, no se permite signos de puntuación.');
        }
    });
    document.getElementById('telefono').addEventListener('input', function () {
        var telefonoValue = this.value;

        // Verificar 
        if (/^\d{10}$/.test(telefonoValue) && !/[.]/.test(telefonoValue)) {
            this.setCustomValidity('');
        } else {
            this.value = telefonoValue.slice(0, 10);
            this.setCustomValidity('El teléfono debe contener exactamente 10 dígitos.');
        }
    });
</script>
<script>
    // main.js
    function minus(e) {
        e.value = e.value.toLowerCase();
    }
    function mayus(e) {
        e.value = e.value.toUpperCase();
    }
</script>

<?php include "../Template/footer.php"; ?>
