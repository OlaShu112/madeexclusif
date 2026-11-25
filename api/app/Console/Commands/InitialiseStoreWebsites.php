<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\WebsiteTemplateService;
use Illuminate\Console\Command;

class InitialiseStoreWebsites extends Command
{
    protected $signature = 'platform:initialise-websites';

    protected $description =
        'Initialise independent business website templates';

    public function handle(
        WebsiteTemplateService $templates
    ): int
    {
        $count = 0;

        Store::query()
            ->with('businessCategory')
            ->where('is_main', false)
            ->chunkById(100, function ($stores) use (
                $templates,
                &$count
            ) {

                foreach ($stores as $store) {

                    $theme = $templates->initialise($store);

                    $this->info(
                        $store->name
                        . ' — Template: '
                        . $theme->template_key
                    );

                    $count++;
                }
            });

        $this->newLine();

        $this->info(
            $count . ' business website(s) initialised.'
        );

        return self::SUCCESS;
    }
}