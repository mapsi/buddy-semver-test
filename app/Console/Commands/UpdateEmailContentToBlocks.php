<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Email;
use Illuminate\Console\Command;

class UpdateEmailContentToBlocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emailcontent:wtr-v1-to-v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'WTR: Update email content from v1 to v2 templates';

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
        // Get WTR brand
        $brand = Brand::where('machine_name', 'wtr')->first();

        // Get emails from database
        $emails = Email::where('brand_id', $brand->id)->where('type', 'weekly')->get();

        $template = json_decode(config('emails.templates.wtr_weekly_v2'), true);

        foreach ($emails as $email) {
            $content = $email->content;

            if (! isset($content['template_version'])) {
                // Version 1, let's convert
                $newContent = $template;
                $newContent['editors_round_up'] = $content['editorial'];

                foreach ($content['thought_leadership_article_ids'] as $articleId) {
                    $newContent['thought_leadership']['items'][] = (int) $articleId;
                }

                foreach ($content['legal_update_article_ids'] as $articleId) {
                    $newContent['legal_update']['items'][] = (int) $articleId;
                }
            }

            $email->content = $newContent;
            $email->save();
        }
    }
}
