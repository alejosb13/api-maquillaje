<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class ResetCategoryCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:categorys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'devuelve todas las categorias a C menos las lista negra';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Iniciar Reset Categorias');


        // $categoriaListaNegra =  Categoria::where([
        //     ['tipo', '=', "LN"],
        //     ['estado', '=', 1]
        // ])->first();

        // $categoriaC =  Categoria::where([
        //     ['tipo', '=', "C"],
        //     ['estado', '=', 1]
        // ])->first();

        // Cliente::where([
        //     ["estado", "=", 1],
        //     ["categoria_id", "!=", $categoriaListaNegra->id]
        // ])->update(['categoria_id' => $categoriaC->id]);

        $this->info(response()->json(["status" => "Successfully reset Category."], 200));
    }
}
