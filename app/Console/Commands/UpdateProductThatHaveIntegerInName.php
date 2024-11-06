<?php

namespace App\Console\Commands;

use App\Product;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductThatHaveIntegerInName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:unique-name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace product names that contain integers with unique and meaningful names';

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
            // Get products where the name contains an integer
            $productsWithIntegers = Product::whereRaw('name REGEXP ?', ['[0-9]+'])->get();
            $totalProducts = $productsWithIntegers->count();

            if ($totalProducts === 0) {
                $this->info('No products found with integers in their names.');
                return;
            }

            $progressBar = $this->output->createProgressBar($totalProducts);
            $progressBar->start();

            foreach ($productsWithIntegers as $product) {
                // Generate a unique name
                $uniqueName = $this->generateUniqueMeaningfulName();

                // Update the product name
                $product->update(['name' => $uniqueName]);

                // Log the name change
                Log::info("Product ID: {$product->id}, New Name: {$uniqueName}");

                // Advance the progress bar
                $progressBar->advance();
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

    /**
     * Generate a unique, meaningful name.
     *
     * @param int $wordCount
     * @return string
     */
    function generateUniqueMeaningfulName($wordCount = 2)
    {
        $path = public_path('Product Name Categories.csv');
        $data = array_map('str_getcsv', file($path));

        // Extract the first column of words, skipping the header row
        $words = array_column(array_slice($data, 1), 0);

        // Cache existing product names
        $existingNames = DB::table('products')->pluck('name')->toArray();

        // Generate a unique name by splitting and combining parts of existing words
        $uniqueName = $this->createSplitAndCombinedName($words, $wordCount);

        // Ensure the generated name is unique
        while (in_array($uniqueName, $existingNames)) {
            $uniqueName = $this->createSplitAndCombinedName($words, $wordCount);
        }

        return $uniqueName;
    }

    /**
     * Create a new name by splitting and combining word parts.
     *
     * @param array $words
     * @param int $wordCount
     * @return string
     */
    function createSplitAndCombinedName($words, $wordCount)
    {
        $splitParts = [];

        // Split each word into two parts
        foreach ($words as $word) {
            $splitPoint = intdiv(strlen($word), 2); // Get the middle of the word
            $splitParts[] = substr($word, 0, $splitPoint);  // First half
            $splitParts[] = substr($word, $splitPoint);      // Second half
        }

        // Shuffle the split parts and take the required number of them
        $randomParts = collect($splitParts)->shuffle()->take($wordCount);

        // Join the random parts together to form the new name
        return $randomParts->join('');
    }
}
