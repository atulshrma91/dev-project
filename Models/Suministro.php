<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogsActivityInterface;
use Spatie\Activitylog\LogsActivity;
use App\Models\ResultadosComparador;

class Suministro extends Model implements LogsActivityInterface
{
	use LogsActivity;
	/**
	 * Get the message that needs to be logged for the given event name.
	 *
	 * @param string $eventName
	 * @return string
     */
	public function getActivityDescriptionForEvent($eventName)
	{
        $content = 'Id: '.$this->id.' | cups: '.$this->cups;
		if ($eventName == 'created')
		{
			$this->update(['created_by' => Auth::user()->id, 'delegacion_id' => Auth::user()->delegacion_id]);
			return 'Suministro ' . $content . ' fue creada';
		}
		if ($eventName == 'updated')
		{
			return 'Suministro ' . $content . ' fue actualizada';
		}
	
		if ($eventName == 'deleted')
		{
			return 'Suministro ' . $content . ' fue eliminada';
		}
		return '';
	}

    protected $table = 'suministros';
    protected $primaryKey = 'id';
	protected $fillable = [
                            'contacto_id',
                            'cups',
                            'apodo',
							'tipopersona',
							'nombre_titular', 
							'apellido_titular', 
							'direccion_titular', 
							'poblacion_titular', 
							'provincia_titular', 
							'codigopostal_titular', 
							'telefonofijo_titular', 
							'movil_titular', 
							'email_titular', 
							'direccion_suministro', 
							'provincia_suministro', 
							'poblacion_suministro',
                            'codigopostal_suministro',
                            'comercializadora_actual',
                            'tipovalor_firma',
                            'fecha_activacion',
                            'tipovalor_permanencia',
                            'tipoconsumo_anual',
                            'aclaratorio_suministro',
                            'nombreadministrador',
                            'dniadministrador',
                            'cif_dni',
							'iban', 
							'oficina', 
							'entidad', 
							'dc',
                            'numerocuenta',
                            'tipopotencia_contratada',
                            'P1',
							'P2', 
							'P3', 
							'P4', 
							'P5',
                            'P6',
                            'tipovalor_contrato',
                            'P1_anual',
                            'P2_anual',
                            'P3_anual',
                            'P4_anual',
                            'P5_anual',
                            'P6_anual',
                            'total_anual',
                            'P1_eactual',
                            'P2_eactual',
                            'P3_eactual',
                            'P4_eactual',
                            'P5_eactual',
                            'P6_eactual',
                            'P1_cactual',
                            'P2_cactual',
                            'P3_cactual',
                            'P4_cactual',
                            'P5_cactual',
                            'P6_cactual',
                            'coste_gestion_pool',
                            'coste_financiero_pool',
                            'coste_gestion_pass',
                            'coste_financiero_pass',
                            'agente_id',
							'tarifaacceso_id', 
                            'delegacion_id', 
                            'provincia_id',
                            'subagente', 
							'created_by',
							'alta_suministro',
							'tipo',
							'tension_v',
							'instalacion',
							'derechos_de_acceso_kw',
							'derechos_extension_kw',
							'potencia_maxima_kw',
							'ultimo_cambio_com',
							'ultimo_modif_contrato',
							'cambio_nombre_titular',
							'cambio_apellido_titular',
							'tiene_descuento',
							'porc_descuento',
							'cambio_dnicif',
                            'cambio_tarifa',
                            'cambio_potencia',
							'tarifaacceso_cambio_id',
							'potencia1_cambio',
							'potencia2_cambio',
							'potencia3_cambio',
							'potencia4_cambio',
							'potencia5_cambio',
							'potencia6_cambio',
							'email_factura_titular',
							'movil_factura_titular',
							'boe',
							'dto_sobre_potencia',
							'p_p_punta',
							'p_p_llano',
							'p_p_valle',
							'p_p_4',
							'p_p_5',
							'p_p_6',
							'mcp_cppunta',
							'mcp_cpllano',
							'mcp_cpvalle',
							'mcp_cp4',
							'mcp_cp5',
                            'mcp_cp6',
                            'titular_fact_dif',
                            'tipopersona_fact',
                            'nombre_titular_fact',
                            'apellido_titular_fact',
                            'cif_dni_fact',
                            'nombreadministrador_fact',
                            'dniadministrador_fact',
                            'cliente_titular_id',
                            'cliente_iban_id',
						];
	
    /**
     * Get the tipo comision that owns the suministro.
     */
    public function comercializadora()
    {
        return $this->belongsTo('App\Models\Comercializadora', 'comercializadora_actual');
    }

    public function tarifaacceso()
    {
        return $this->belongsTo('App\Models\Tarifaacceso', 'tarifaacceso_id');
    }
    /**
     * Get the tipo comision that owns the suministro.
     */
    public function agente()
    {
        return $this->belongsTo('App\User', 'agente_id');
    }

    /**
     * Get the tipo comision that owns the suministro.
     */
    public function contacto()
    {
        return $this->belongsTo('App\Models\ClienteRegistrado', 'contacto_id');
    }

    public function comisiones()
    {
        return $this->hasMany('App\Models\Comision');
    }
    
    public function reservas()
    {
        return $this->hasMany('App\Models\Reserva');
    }
    public function historico_suministro()
    {
        return $this->hasMany('App\Models\HistoricoSuministro', 'suministro_id')->orderBy('id','desc');
    }
    public function suministrocomisiones()
    {
        return $this->hasMany('App\Models\SuministroComision');
    }
    public function archivosuministros()
    {
        return $this->hasMany('App\Models\ArchivosSuministro');
    }
    public function historialcomparador()
    {
        return $this->hasMany('App\Models\HistorialComparador');
    }
    /**
    * Get the usuarios for the rol.
    */
    public function provincia()
    {
        return $this->belongsTo('App\Models\Territorio', 'provincia_suministro');
    }

    public function estados()
    {
        return $this->belongsToMany('App\Models\EstadoSuministro', 'suministros_estados')->withPivot('created_by', 'fecha', 'reserva_id')->select('*')->selectRaw("DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha_formato");
    }


    public function scopeComercializadoraSearch($query, $comercializadora)
    {
    	if($comercializadora != ""){
    		$query->where('comercializadora_actual', $comercializadora);
    	}    	
    }

    public function scopeTarifaSearch($query, $tarifa)
    {
        if($tarifa != ""){
            $query->where('tarifaacceso_id', $tarifa);
        }
    }

    static function getEstadoContrato($id)
    {
        $html = '';
        $activada = 0;
        $desactivada = 0;
        $enviado_comercializadora = 0;
        $contrato_firmado = 0;
        $contrato_enviado = 0;
        $fechareservada = 0;

        $contratos = ResultadosComparador::where('suministro_id',$id)->whereNotNull('fechareservada')->whereNull('annulled_at')
                    ->where(function($query){
                        $query->orWhereNotNull('activada')
                                ->orWhereNotNull('desactivada')
                                ->orWhereNotNull('enviado_comercializadora')
                                ->orWhereNotNull('contrato_firmado')
                                ->orWhereNotNull('contrato_enviado')
                                ->orWhereNotNull('fechareservada');
                    })
                    ->orderBy('activada', 'desc')
                    ->orderBy('desactivada', 'desc')
                    ->orderBy('enviado_comercializadora', 'desc')
                    ->orderBy('contrato_firmado', 'desc')
                    ->orderBy('contrato_enviado', 'desc')
                    ->orderBy('fechareservada', 'desc')
                    ->get();
        if($contratos->count() > 0){
            foreach ($contratos as $contrato) {
                if(!is_null($contrato->desactivada) && $contrato->desactivada != '0000-00-00 00:00:00'){
                    if($desactivada == 0){
                        $html .= 'DESACTIVADA<br>'.date('d/m/Y H:i',strtotime($contrato->desactivada)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $desactivada = 1;
                    }
               }
                elseif(!is_null($contrato->activada) && $contrato->activada != '0000-00-00 00:00:00'){
                    if($activada == 0){
                        $html .= 'ACTIVADA<br>'.date('d/m/Y H:i',strtotime($contrato->activada)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $activada = 1;
                    }
                }
                elseif(!is_null($contrato->enviado_comercializadora) && $contrato->enviado_comercializadora != '0000-00-00 00:00:00'){
                    if($enviado_comercializadora == 0){
                        $html .= 'ENVIADO COMERCIALIZADORA<br>'.date('d/m/Y H:i',strtotime($contrato->enviado_comercializadora)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $enviado_comercializadora = 1;
                    }
                }
                elseif(!is_null($contrato->contrato_firmado) && $contrato->contrato_firmado != '0000-00-00 00:00:00'){
                    if($contrato_firmado == 0){
                        $html .= 'CONTRATO FIRMADO<br>'.date('d/m/Y H:i',strtotime($contrato->contrato_firmado)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $contrato_firmado = 1;
                    }
                }
                elseif(!is_null($contrato->contrato_enviado) && $contrato->contrato_enviado != '0000-00-00 00:00:00'){
                    if($contrato_enviado == 0){
                    $html .= 'CONTRATO ENVIADO<br>'.date('d/m/Y H:i',strtotime($contrato->contrato_enviado)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $contrato_enviado = 1;
                    }
                }
                else{
                    if($fechareservada == 0){
                        $html .= 'RESERVADA<br>'.date('d/m/Y H:i',strtotime($contrato->fechareservada)).'<br>'.((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
                        $fechareservada = 1;
                    }
                }
                $html .= '<br>';
            }
        }
        return $html;
    }

    static function getFechaActivacionContrato($id)
    {
        $contrato = ResultadosComparador::where('suministro_id',$id)->whereNotNull('fechareservada')->whereNull('annulled_at')->orderBy('id', 'desc')->first();
        if(isset($contrato->id)){
            if(!is_null($contrato->activada) && $contrato->activada != '0000-00-00 00:00:00'){
                return date('d/m/Y H:i',strtotime($contrato->activada));
            }
        }
    }

    static function getAtrActual($id)
    {
        $contrato = ResultadosComparador::where('suministro_id',$id)->whereNotNull('fechareservada')->whereNull('annulled_at')->orderBy('id', 'desc')->first();
        if(isset($contrato->id)){
            return $contrato;
        }
    }

    static function getComercializadoraContrato($id)
    {
        $contrato = ResultadosComparador::where('suministro_id',$id)->whereNotNull('fechareservada')->whereNull('annulled_at')->orderBy('id', 'desc')->first();
        if(isset($contrato->id)){
            if(!is_null($contrato->activada) && $contrato->activada != '0000-00-00 00:00:00'){
                /*$suministro = Suministro::find($id);
                $suministro->comercializadora_actual = $contrato->comercializadora_id;
                $suministro->fecha_activacion = $contrato->activada;
                $suministro->save();*/
                return ((isset($contrato->comercializadora->nombre_comercial))?$contrato->comercializadora->nombre_comercial:'');
            }
        }
    }
}
