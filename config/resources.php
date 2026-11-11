<?php

declare(strict_types=1);

// Metadatos de formularios. Los datos de instituciones y trámites están en MariaDB.
$text = fn (int $max = 200, bool $required = false) => ['type'=>'text','max'=>$max,'required'=>$required];
$ref = fn (string $table, string $label = 'nombre', bool $required = false) => ['type'=>'ref','table'=>$table,'label'=>$label,'required'=>$required];
$enum = fn (array $values, bool $required = true) => ['type'=>'enum','values'=>$values,'required'=>$required];
$date = ['type'=>'date'];
$evidence = ['fuente_id'=>$ref('fuentes'), 'fecha_verificacion'=>$date];
return [
    'instituciones'=>['title'=>'Instituciones','key'=>['id'],'fields'=>[
        'nombre'=>$text(200,true),'descripcion'=>['type'=>'textarea','max'=>5000], 'telefono'=>$text(50),
        'sitio_web'=>['type'=>'url'],'estado'=>$enum(['0'=>'Inactiva','1'=>'Activa']),
    ] + $evidence],
    'sucursales'=>['title'=>'Sucursales','key'=>['id'],'fields'=>[
        'institucion_id'=>$ref('instituciones','nombre',true),'nombre'=>$text(200,true),'direccion'=>$text(500),
        'ciudad'=>$text(100),'zona'=>$text(150),'telefono'=>$text(50),
        'latitud'=>['type'=>'decimal','min'=>-90,'max'=>90,'scale'=>7],
        'longitud'=>['type'=>'decimal','min'=>-180,'max'=>180,'scale'=>7],
        'estado'=>$enum(['inactiva'=>'Inactiva','activa'=>'Activa','temporalmente_cerrada'=>'Cerrada temporalmente']),
    ] + $evidence],
    'horarios'=>['title'=>'Horarios semanales','key'=>['id'],'fields'=>[
        'sucursal_id'=>$ref('sucursales','nombre',true),
        'dia_semana'=>$enum(['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo']),
        'hora_apertura'=>['type'=>'time','required'=>true],'hora_cierre'=>['type'=>'time','required'=>true],
    ] + $evidence],
    'excepciones_horario'=>['title'=>'Excepciones de horario','key'=>['id'],'fields'=>[
        'sucursal_id'=>$ref('sucursales','nombre',true),'fecha'=>['type'=>'date','required'=>true,'future'=>true],
        'cerrada'=>$enum(['1'=>'Cerrada','0'=>'Horario especial']), 'hora_apertura'=>['type'=>'time'],
        'hora_cierre'=>['type'=>'time'],'motivo'=>$text(500),
    ] + $evidence],
    'tramite_sucursales'=>['title'=>'Disponibilidad por trámite','key'=>['tramite_id','sucursal_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'sucursal_id'=>$ref('sucursales','nombre',true),
        'disponible'=>$enum(['0'=>'No disponible','1'=>'Disponible']), 'observaciones'=>['type'=>'textarea','max'=>5000],
    ] + $evidence],
    'pasos_tramite'=>['title'=>'Pasos de trámites','key'=>['id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'numero_paso'=>['type'=>'int','min'=>1,'max'=>65535,'required'=>true],
        'titulo'=>$text(200,true),'descripcion'=>['type'=>'textarea','max'=>5000],
        'institucion_id'=>$ref('instituciones'),'tiempo_estimado'=>$text(150),'observaciones'=>['type'=>'textarea','max'=>5000],
    ] + $evidence],
    'paso_documentos'=>['title'=>'Documentos de cada paso','key'=>['paso_id','documento_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'paso_id'=>$ref('pasos_tramite','titulo',true),'documento_id'=>$ref('documentos','nombre',true),
    ]],
    'paso_dependencias'=>['title'=>'Dependencias entre pasos','key'=>['paso_id','paso_previo_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'paso_id'=>$ref('pasos_tramite','titulo',true),'paso_previo_id'=>$ref('pasos_tramite','titulo',true),
    ]],
    'rutas'=>['title'=>'Rutas de trámites','key'=>['id'],'fields'=>[
        'nombre'=>$text(200,true),'slug'=>$text(220,true),'descripcion'=>['type'=>'textarea','max'=>5000],
        'ciudad'=>$text(100),'actividad_economica'=>$text(200),'estado'=>$enum(['borrador'=>'Borrador','publicada'=>'Publicada','inactiva'=>'Inactiva']),
    ] + $evidence],
    'ruta_tramites'=>['title'=>'Trámites de cada ruta','key'=>['ruta_id','tramite_id'],'fields'=>[
        'ruta_id'=>$ref('rutas','nombre',true),'tramite_id'=>$ref('tramites','nombre',true),
        'numero_orden'=>['type'=>'int','min'=>1,'max'=>65535,'required'=>true],'observaciones'=>['type'=>'textarea','max'=>5000],
    ]],
    'ruta_dependencias'=>['title'=>'Dependencias de rutas','key'=>['ruta_id','tramite_id','tramite_previo_id'],'fields'=>[
        'ruta_id'=>$ref('rutas','nombre',true),'tramite_id'=>$ref('tramites','nombre',true),'tramite_previo_id'=>$ref('tramites','nombre',true),
    ]],
    'costos'=>['title'=>'Costos y lugares de pago','key'=>['id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'institucion_id'=>$ref('instituciones'),'institucion_pago_id'=>$ref('instituciones'),
        'concepto'=>$text(200,true),'monto'=>['type'=>'decimal','min'=>0,'max'=>9999999999.99,'scale'=>2],
        'moneda'=>$enum(['BOB'=>'Bolivianos','USD'=>'Dólares estadounidenses']),
        'lugar_pago'=>$text(500),'medio_pago'=>$text(150),'observaciones'=>['type'=>'textarea','max'=>5000],
        'estado_verificacion'=>$enum(['pendiente'=>'Pendiente','referencial'=>'Referencial','verificado'=>'Verificado']),
    ] + $evidence],
    'paso_costos'=>['title'=>'Costos por paso','key'=>['paso_id','costo_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'paso_id'=>$ref('pasos_tramite','titulo',true),'costo_id'=>$ref('costos','concepto',true),
    ]],
    'fuentes'=>['title'=>'Fuentes de información','key'=>['id'],'fields'=>[
        'nombre'=>$text(200,true),'url'=>['type'=>'url'],'tipo'=>$enum(['oficial'=>'Oficial','referencial'=>'Referencial','DIRI'=>'DIRI','usuario'=>'Usuario']),
        'fecha_verificacion'=>$date,'observaciones'=>['type'=>'textarea','max'=>5000],
    ]],
    'tramite_fuentes'=>['title'=>'Fuentes complementarias','key'=>['tramite_id','fuente_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'fuente_id'=>$ref('fuentes','nombre',true),'fecha_verificacion'=>$date,'observaciones'=>['type'=>'textarea','max'=>5000],
    ]],
    'etiquetas'=>['title'=>'Etiquetas','key'=>['id'],'fields'=>['nombre'=>$text(100,true)]],
    'tramite_etiquetas'=>['title'=>'Etiquetas por trámite','key'=>['tramite_id','etiqueta_id'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'etiqueta_id'=>$ref('etiquetas','nombre',true),
    ]],
    'palabras_clave_tramite'=>['title'=>'Palabras clave y alias','key'=>['tramite_id','palabra'],'fields'=>[
        'tramite_id'=>$ref('tramites','nombre',true),'palabra'=>$text(150,true),
    ]],
    'categorias'=>['title'=>'Categorías','key'=>['id'],'fields'=>[
        'nombre'=>$text(100,true),'slug'=>$text(120,true),'descripcion'=>['type'=>'textarea','max'=>5000],'estado'=>$enum(['0'=>'Inactiva','1'=>'Activa']),
    ]],
    'usuarios'=>['title'=>'Usuarios, roles y asignación de planes','key'=>['id'],'create'=>false,'fields'=>[
        'nombre'=>$text(100,true),'apellido'=>$text(100,true),'email'=>['type'=>'email','required'=>true],
        'rol_id'=>$ref('roles','nombre',true),'plan_id'=>$ref('planes','nombre',true),
        'estado'=>$enum(['activo'=>'Activo','inactivo'=>'Inactivo','bloqueado'=>'Bloqueado']),
    ]],
    'planes'=>['title'=>'Disponibilidad de planes','key'=>['id'],'create'=>false,'fields'=>[
        'nombre'=>['type'=>'enum','values'=>['free'=>'Free','premium'=>'Premium'],'required'=>true,'immutable'=>true],
        'descripcion'=>$text(255,true),'estado'=>$enum(['0'=>'Inactivo','1'=>'Activo']),
    ]],
    'integraciones_api'=>['title'=>'Preparación de integraciones oficiales','key'=>['id'],'fields'=>[
        'institucion_id'=>$ref('instituciones','nombre',true),'nombre'=>$text(200,true),'url_base'=>['type'=>'url','required'=>true],
        'requiere_token'=>$enum(['0'=>'No','1'=>'Sí']),
        'credencial_referencia'=>$text(100),'estado'=>$enum(['inactiva'=>'Inactiva','activa'=>'Activa','error'=>'Error']),
    ]],
];
