# Actividad de 12 vistas SQL de DIRI

Fecha de comprobación: 24/10/2026.

Se añadieron 12 vistas con el prefijo `vw_diri_`, sin sustituir las dos vistas previas. El esquema local tiene 40 tablas y 14 vistas. La instalación base `database/diri.sql` continúa incluyendo sus dos vistas originales; este conjunto adicional se aplica mediante `database/views/actividad_12_vistas.sql`.

| N.º | Vista | Bloque asignado | Filas comprobadas |
| --- | --- | --- | --- |
| 1 | `vw_diri_catalogo_publicado` | Anthony Chavez Coronado | 20 |
| 2 | `vw_diri_requisitos_obligatorios` | Anthony Chavez Coronado | 24 |
| 3 | `vw_diri_documentos_requeridos` | Anthony Chavez Coronado | 39 |
| 4 | `vw_diri_alias_busqueda` | Anthony Chavez Coronado | 74 |
| 5 | `vw_diri_sedes_activas` | Sebastian Peñarrieta Peña | 2 |
| 6 | `vw_diri_horarios_sedes` | Sebastian Peñarrieta Peña | 6 |
| 7 | `vw_diri_tramites_sedes` | Sebastian Peñarrieta Peña | 2 |
| 8 | `vw_diri_pasos_instituciones` | Sebastian Peñarrieta Peña | 22 |
| 9 | `vw_diri_costos_tramite` | Abel Daniel Osco Aquino | 20 |
| 10 | `vw_diri_resumen_documental` | Abel Daniel Osco Aquino | 20 |
| 11 | `vw_diri_tramites_por_categoria` | Abel Daniel Osco Aquino | 6 |
| 12 | `vw_diri_tramites_por_verificar` | Abel Daniel Osco Aquino | 9 |

## Comprobación

```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'diri' AND TABLE_NAME LIKE 'vw\_diri\_%';
SELECT * FROM vw_diri_catalogo_publicado LIMIT 5;
```

El respaldo se restauró en una base temporal, se compararon los conteos de las 40 tablas, los resultados completos de las 12 vistas y los conteos de las 14 vistas. `CHECK TABLE` devolvió OK. La base temporal se retiró tras la comprobación.

## Entrega

La carpeta `C:/Users/Anthony/Downloads/DIRI_12_vistas_20261024` incluye el SQL conjunto, cuatro vistas por integrante, respaldo y PDF de documentación con registros textuales reales de mysql.exe. **Falta tomar las capturas manuales e incorporarlas al PDF**, tal como pide la consigna. Las transcripciones no se presentan como capturas.

Los archivos `sql/demostracion/01.sql` a `12.sql` consultan definiciones y resultados sin intentar recrear vistas existentes. Los archivos de creación se ejecutan solo en una instalación que todavía no tenga estas vistas. Al restaurar el respaldo completo en una base vacía, ya se incluyen las vistas.

Una vista SQL no normaliza ni desnormaliza las tablas físicas y no modifica sus registros. El monto NULL en costos no se interpreta como cero; los horarios ordinarios no incorporan por sí solos excepciones por fecha.
