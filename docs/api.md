# API pública v1

En XAMPP: `http://localhost/DIRI/public/api/v1`. Solo lectura, sin autenticación, sin escrituras privadas ni historial de usuario. Los servicios del dominio se reutilizan en HTML y JSON. Una aplicación móvil con cuentas necesitará diseñar autenticación, renovación y revocación de tokens; esta fase no implementa ese protocolo.

| GET | Parámetros | Resultado |
| --- | --- | --- |
| `/tramites` | `q` opcional, máximo 500 caracteres; `pagina` desde 1 | Consulta, página, total y hasta 12 trámites publicados |
| `/tramite` | `slug` | Ficha pública y fuentes |
| `/atencion` | `sucursal_id` | Estado reciente o `insufficient_recent_data` |

Respuesta: `{"version":"1","data":{},"error":null}`. Errores: `data:null`, `error:{code,message}`. HTTP 404 para borradores/recursos no disponibles, 422 para consultas inválidas, 405 para métodos no permitidos y 500 sin detalles internos. `Allow` acompaña al 405. Tipo `application/json; charset=utf-8` y `nosniff`. Sin CORS abierto ni credenciales en URL.

Fechas de atención y marcas de tiempo internas: UTC, formato SQL `YYYY-MM-DD HH:MM:SS`. Fechas de verificación: fechas civiles `YYYY-MM-DD`. La web convierte marcas UTC a `America/La_Paz`. `NULL` representa dato desconocido; jamás sustituirlo por cero en el cliente. Respetar `valido_hasta`, procedencia y confianza.

El buscador JSON no escribe historial. No expone usuarios, contraseñas/hashes, datos individuales de reportantes, reportes sin moderar ni configuraciones de integración. Los IDs de contenido y fuentes son referencias públicas. V1 no ofrece rutas/checklist Premium: requerirán autenticación móvil.

Ejemplo local:

```powershell
Invoke-RestMethod 'http://localhost/DIRI/public/api/v1/tramites?q=carnet'
```

Pruebas: `php tests/extended-flow.php` (servidor local predeterminado `127.0.0.1:8087`).
