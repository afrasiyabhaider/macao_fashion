<?php

namespace App\Console\Commands;

use App\CashRegister;
use App\Http\Controllers\CashRegisterController;
use App\Utils\CashRegisterUtil;
use Illuminate\Console\Command;

class CloseCashRegister extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashRegister:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closing Cash Register';

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
     * @return mixed
     */
    public function handle()
    {
        // $cashRegister = new CashRegisterController();
        
        // $cashRegister->autoCloseRegister();
        return 'Could Not run this command';
        $registers = CashRegister::where('status','open')->where('location_id','>',0)->get();
        $cashRegisterUtil = new CashRegisterUtil();
        foreach ($registers as $key => $value) {
           $total_sale = $cashRegisterUtil->getRegisterDetails($value->id)->total_sale;
           
           $value->closing_amount = $total_sale;
           $location_id = $value->location_id;
           $value->closed_at = \Carbon::now()->format('Y-m-d H:i:s');
           $value->status = 'close';
            
           $value->save();
           
        }

        // return redirect(url('cash-register/auto-close'));
    }
}
