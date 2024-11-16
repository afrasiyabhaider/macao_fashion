<?php

namespace App\Console\Commands;

use App\Product;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductNameThatHaveSameReference extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:update-byReference';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace product names that contain same reference with unique and meaningful names';

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
        $this->info('Fetching product list...');

        DB::beginTransaction();
        try {
            // Get references with duplicate entries
            $duplicateReferences = Product::select('refference')
                ->groupBy('refference')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('refference');

            if ($duplicateReferences->isEmpty()) {
                $this->info('No products found with the same reference.');
                return;
            }

            $totalProducts = Product::whereIn('refference', $duplicateReferences)->count();
            $this->info("Total products with same reference: $totalProducts");

            if ($totalProducts === 0) {
                $this->info('No products found with the same reference.');
                return;
            }

            $progressBar = $this->output->createProgressBar($totalProducts);
            $progressBar->start();

            // Process each reference group
            foreach ($duplicateReferences as $reference) {
                // Get the first product name for the current reference
                $firstProduct = Product::where('refference', $reference)->first();

                if ($firstProduct) {
                    $firstName = $firstProduct->name;

                    // Update all products with this reference to use the first name
                    Product::where('refference', $reference)->update(['name' => $firstName]);

                    // Log the update
                    Log::info("Updated products with reference '{$reference}' to name '{$firstName}'");
                }

                // Advance the progress bar for the number of affected rows
                $count = Product::where('refference', $reference)->count();
                $progressBar->advance($count);
            }

            $progressBar->finish();
            DB::commit();
            $this->info("\nProduct name updates completed successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error occurred: " . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            $this->error("An error occurred. Check the logs for more details.");
        }
    }
}
