<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
class CatalogService
{
    private function rows(string $sql,array $params): array
    {
        $query=Database::connection()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }
    public function detail(string $slug): ?array
    {
        if(strlen($slug)>220||!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',$slug)) {
            return null;
        }
        $rows=$this->rows('SELECT id,nombre,slug,descripcion,dificultad,tiempo_estimado,estado_verificacion,fecha_verificacion,fuente_id,categoria FROM vista_tramites_publicados WHERE slug=?',[$slug]);
        if($rows===[]) {
            return null;
        }
        $item=$rows[0];
        $id=(int)$item['id'];
        $item['requisitos']=$this->rows('SELECT * FROM requisitos WHERE tramite_id=? ORDER BY id',[$id]);
        $item['documentos']=(new \App\Models\TramiteDocumento())->forTramite($id);
        $item['pasos']=(new RoutePlanner())->steps($id);
        foreach($item['pasos'] as &$step) {
            $step['documentos']=$this->rows('SELECT d.nombre FROM paso_documentos pd JOIN documentos d ON d.id=pd.documento_id WHERE pd.paso_id=? ORDER BY d.nombre',[$step['id']]);
            $step['costos']=$this->rows('SELECT c.id,c.concepto,c.monto,c.moneda FROM paso_costos pc JOIN costos c ON c.id=pc.costo_id WHERE pc.paso_id=? ORDER BY c.id',[$step['id']]);
            $step['previos']=$this->rows('SELECT p.titulo FROM paso_dependencias pd JOIN pasos_tramite p ON p.id=pd.paso_previo_id WHERE pd.paso_id=?',[$step['id']]);
        }
        unset($step);
        $item['costos']=$this->rows('SELECT c.*,i.nombre AS institucion_pago FROM costos c LEFT JOIN instituciones i ON i.id=c.institucion_pago_id WHERE c.tramite_id=? ORDER BY c.id',[$id]);
        $item['sucursales']=$this->rows("SELECT s.id,s.nombre,s.direccion,s.ciudad,s.zona,s.telefono,s.latitud,s.longitud,s.fuente_id,s.fecha_verificacion,i.nombre AS institucion,ts.observaciones FROM tramite_sucursales ts JOIN sucursales s ON s.id=ts.sucursal_id JOIN instituciones i ON i.id=s.institucion_id WHERE ts.tramite_id=? AND ts.disponible=1 AND s.estado='activa' AND i.estado=1 ORDER BY s.nombre",[$id]);
        foreach($item['sucursales'] as &$branch) {
            $branch['horarios']=$this->rows('SELECT dia_semana,hora_apertura,hora_cierre,fuente_id,fecha_verificacion FROM horarios WHERE sucursal_id=? ORDER BY dia_semana,hora_apertura',[$branch['id']]);
            $branch['atencion']=(new AttentionService())->current((int)$branch['id']);
        }
        unset($branch);
        $item['fuentes']=$this->rows('SELECT DISTINCT f.id,f.nombre,f.url,f.tipo,f.fecha_verificacion FROM fuentes f WHERE f.id=? OR f.id IN (SELECT fuente_id FROM requisitos WHERE tramite_id=?) OR f.id IN (SELECT fuente_id FROM tramite_documentos WHERE tramite_id=?) OR f.id IN (SELECT d.fuente_id FROM documentos d JOIN tramite_documentos td ON td.documento_id=d.id WHERE td.tramite_id=?) OR f.id IN (SELECT fuente_id FROM pasos_tramite WHERE tramite_id=?) OR f.id IN (SELECT fuente_id FROM costos WHERE tramite_id=?) OR f.id IN (SELECT fuente_id FROM tramite_fuentes WHERE tramite_id=?) OR f.id IN (SELECT s.fuente_id FROM sucursales s JOIN tramite_sucursales ts ON ts.sucursal_id=s.id WHERE ts.tramite_id=?) ORDER BY f.nombre',[$item['fuente_id'],$id,$id,$id,$id,$id,$id,$id]);
        return $item;
    }
}
