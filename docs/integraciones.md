# Preparación de integraciones oficiales

No se afirma que una institución boliviana disponga de API pública. No hay adaptadores reales, cliente de scraping ni llamadas externas incluidas. Crear una fila en administración no concede autorización ni ejecuta una consulta.

## Flujo preparado

1. Obtener autorización y documentación de la institución, sus campos, condiciones de uso y límites.
2. Implementar `OfficialAttentionProvider`: traducir solo las mediciones permitidas a `OfficialAttentionReading`. El adaptador deberá usar HTTPS con verificación de certificado, hosts permitidos, límites de tiempo/tamaño, sin redirecciones a hosts no autorizados y sin registrar tokens/cuerpos privados. No se incluye un cliente HTTP genérico que consulte URLs ingresadas desde la web.
3. Registrar institución/sucursal y fuente oficial revisada. Crear la integración desde `/admin`, inicialmente inactiva. `credencial_referencia` contiene un nombre de variable de entorno, jamás el token.
4. Configurar el objeto adaptador por ID en `config/integrations.php`, privado e ignorado por Git, siguiendo `integrations.example.php`. No se eligen clases a partir de parámetros HTTP o valores de MariaDB.
5. Activar después de revisar permisos y ejecutar explícitamente `php scripts/sync-integrations.php ID`. No hay ruta web ni tarea programada automática de sincronización.

`IntegrationSyncService` comprueba integración activa, adaptador registrado y credencial disponible cuando corresponda. Consulta fuera de la transacción; después vuelve a comprobar configuración y valida todo el lote. Máximo 500 sucursales, sin duplicados, cada sucursal activa debe pertenecer a la institución configurada, con fuente de tipo oficial revisada y URL. Rechaza observaciones futuras o mayores a 30 minutos y no reemplaza una observación más reciente.

Una lectura se guarda como `OFICIAL_API`, con su fuente y momento observado. No se concede confianza alta por el solo hecho de venir de una API. La vigencia de esta versión es de 30 minutos desde la observación, pendiente de adaptar a contratos reales. El proveedor debe distinguir ficha emitida/atendida y no inventar tiempos a partir de fichas sin un modelo sustentado. El contrato acepta personas, fichas, tiempo informado e intervalo opcional, sin datos personales.

Estado e histórico se guardan atómicamente. Un lote inválido se revierte completo. `integracion_ejecuciones` conserva resultado y resumen genérico, no mensajes del proveedor. Fallar no renueva la vigencia de un estado anterior. Las lecturas históricas repetidas para la misma sucursal/fuente/segundo no se duplican. El histórico solo conserva personas/tiempo puntual; el intervalo está en el estado actual.

El proveedor simulado existe exclusivamente en `tests/phases/20.php`, genera filas temporales y no hace solicitudes HTTP. Todas se eliminan al terminar. Ninguna institución real se configura o activa en esta fase.

La integración de IA también queda pendiente: `SearchProvider` es el contrato de búsqueda y `DatabaseSearch` su única implementación. No se incorporan claves ni clientes de IA.
