<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaHistorial as Factura_Historial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FacturaHistorial extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];
        $status = 200;
        $facturaEstado = 1; // Activo
        
        if(!is_null($request['estado'])) $facturaEstado = $request['estado'];
        
        // dd($facturaEstado);
        $facturas =  Factura_Historial::where('estado',$facturaEstado)->orderBy('id',"desc")->get();
        
        if(count($facturas) > 0){
            foreach ($facturas as $key => $facturaHistorial) {
                $facturaHistorial->factura;
                
                $cliente = Cliente::find($facturaHistorial->factura->cliente_id);
                $usuario = User::find($facturaHistorial->user_id);
                
                $facturaHistorial->cliente = $cliente;
                $facturaHistorial->usuario = $usuario;
                // $factura->cliente = $abonoFactura->cliente;
            }
            
            $response = $facturas;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all() ,[
            'factura_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'precio' => 'required|numeric',
            'estado' => 'required|numeric|max:1',
        ]);

        if($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        } else {
            // DB::enableQueryLog();
            $abono = Factura_Historial::create([
                'factura_id' => $request['factura_id'],
                'user_id' => $request['user_id'],
                'precio' => $request['precio'],
                'estado' => $request['estado'],
            ]);

            $this->validarStatusPagado($abono->id);
            
            return response()->json([
                // 'success' => 'Usuario Insertado con exito',
                // 'data' =>[
                    'id' => $abono->id,
                // ]
            ], 201);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        $response = [];
        $status = 400;
        $clienteEstado = 1; // Activo
        
        if(is_numeric($id)){
                    
            if(!is_null($request['estado'])) $clienteEstado = $request['estado'];
        
            // dd($request['estado']);
            $abono =  Factura_Historial::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();
        

            
            // $cliente =  Cliente::find($id);
            // dd($abono);
            if($abono){
                // $abono->frecuencia = $cliente->frecuencia;
                // $abono->categoria = $cliente->categoria;
                // $abono->facturas = $cliente->facturas;
                $response = $abono;
                $status = 200;

            }else{
                $response[] = "El Abono no existe o fue eliminado.";
            }
            
        }else{
            $response[] = "El Valor de Id debe ser numerico.";
        }
        
        return response()->json($response, $status);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $response = [];
        $status = 400;
        
        if(is_numeric($id)){
            $abono =  Factura_Historial::find($id);
            
            if($abono){ 
                $validation = Validator::make($request->all() ,[
                    'factura_id' => 'required|numeric',
                    'user_id' => 'required|numeric',
                    'precio' => 'required|numeric',
                    'estado' => 'required|numeric|max:1',
                ]);
                
                if($validation->fails()) {
                    $response[] = $validation->errors();
                } else {

                    // dd($request->all());
                    $abonoUpdate = $abono->update([
                        'factura_id' => $request['factura_id'],
                        'user_id' => $request['user_id'],
                        'precio' => $request['precio'],
                        'estado' => $request['estado'],
                    ]);

                    $this->validarStatusPagado($id,$request['factura_id']);
                    
                    if($abonoUpdate){                  
                        $response[] = 'Abono modificado con exito.';
                        $status = 200;
                        
                    }else{
                        $response[] = 'Error al modificar los datos.';
                    }

                }

            }else{
                $response[] = "El abono no existe.";
            }
            
        }else{
            $response[] = "El Valor de Id debe ser numerico.";
        }
        
        return response()->json($response, $status);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $response = [];
        $status = 400;
        
        if(is_numeric($id)){
            $abono =  Factura_Historial::find($id);
            
            if($abono){ 
                $abonoDelete = $abono->update([
                    'estado' => 0,
                ]);
                
                $this->validarStatusPagado($id);
                
                if($abonoDelete){                  
                    $response[] = 'El abono fue eliminado con exito.';
                    $status = 200;
                    
                }else{
                    $response[] = 'Error al eliminar el abono.';
                }

            }else{
                $response[] = "El abono no existe.";
            }
            
        }else{
            $response[] = "El Valor de Id debe ser numerico.";
        }
        
        return response()->json($response, $status);
    }
    
    
    private function validarStatusPagado($abonoId){
        // $abono = Factura_Historial::find($abonoId);
        
        $facturaHistorial = Factura_Historial::find($abonoId);
        $factura = Factura::find($facturaHistorial->factura_id);
        $factura->factura_historial;
        
        
        if(count($factura->factura_historial)>0){
            $acum = 0 ;
            foreach ($factura->factura_historial as $key => $itemHistorial) {
                if($itemHistorial["estado"] == 1){
                    
                    $acum += $itemHistorial["precio"] ;
                }
                
            }
            
            if($acum >= $factura->monto){
                $factura->status_pagado  = 1;
                $factura->update();
            }else{
                $factura->status_pagado  = 0;
                $factura->update();
            }
        }
    }
}
