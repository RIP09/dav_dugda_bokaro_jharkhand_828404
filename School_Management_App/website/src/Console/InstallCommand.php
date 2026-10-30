<?php

namespace Website\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Website\Database\WebsiteSeeder;

class InstallCommand extends Command
{
    protected $signature = 'website:install {--fresh : Wipe website content and load the demo content again} {--publish : Also copy CSS/JS/images to public/site-assets (optional, makes them a little faster)}';
    protected $description = 'Create the website tables and load the starting content (safe to run again).';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);
        $empty = ! Schema::hasTable('site_admins') || DB::table('site_admins')->count() === 0;
        if ($empty || $this->option('fresh')) {
            WebsiteSeeder::$force = true;
            $this->call('db:seed', ['--class' => WebsiteSeeder::class, '--force' => true]);
            $this->info('Website content loaded. Website admin: webadmin@davdugda.in / webadmin123 (change it after first login).');
        } else {
            $this->info('Website content already exists — nothing overwritten.');
        }
        if ($this->option('publish')) {
            File::copyDirectory(dirname(__DIR__, 2).'/public-assets', public_path('site-assets'));
            $this->info('Assets copied to public/site-assets.');
        }

        return self::SUCCESS;
    }
}
