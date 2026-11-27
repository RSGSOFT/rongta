<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Every seeder is idempotent (updateOrCreate), so this is safe to run again.
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,     // families, models, features, specs  (models.json)
            SampleDataSeeder::class,  // regions, cities, documents, videos, FAQ, guide, chooser (sample-data.json)
            UiStringSeeder::class,    // interface strings (ui-strings.json), price keys skipped
            SiteStringSeeder::class,  // extra strings for the site shell, Home, Catalog, Compare and Model pages
            SiteBStringSeeder::class, // extra strings for the Dealers and Knowledge base pages
            ChatbotStringSeeder::class, // chatbot widget strings
            SettingsSeeder::class,    // contacts, accent, logo, home sections, hero
            HeroItemSeeder::class,    // sample products for the home hero display (only while the table is empty)
            ServiceCaseSeeder::class, // starter service jobs, without amounts (only while the table is empty)
            AdminUserSeeder::class,   // staff admin from .env
        ]);
    }
}
