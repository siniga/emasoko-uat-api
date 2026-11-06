<?php

namespace Database\Seeders;

use Database\Seeders\Testing\TestingDataContext;
use Database\Seeders\Testing\TestingDataPlan;
use Illuminate\Database\Seeder;
use RuntimeException;

class TestingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $data = new TestingDataContext();
        $actual = [];
        foreach ($data->db->select("SHOW FULL TABLES WHERE Table_type='BASE TABLE'") as $row) $actual[] = array_values((array)$row)[0];
        $expected = array_merge(TestingDataPlan::tables(),array_keys(TestingDataPlan::RUNTIME),['migrations']);
        $missing = array_diff($expected,$actual);
        $unmapped = array_diff($actual,$expected);
        if ($missing || $unmapped) throw new RuntimeException('Seeder coverage differs from the database. Missing tables: '.implode(', ',$missing).'; unmapped tables: '.implode(', ',$unmapped).'. Run the project migrations first.');
        $lock = 'emakato.testing.'.substr(hash('sha256',$data->db->getDatabaseName()),0,32);
        if ((int)$data->db->selectOne('SELECT GET_LOCK(?,0) AS acquired',[$lock])->acquired !== 1) throw new RuntimeException('Another testing-data seeder is running.');
        app()->instance(TestingDataContext::class,$data);
        try {
            $fixture = base_path('public/fixtures/testing/sample-document.txt');
            foreach (['docs','pdf'] as $folder) {
                $directory = public_path('assets/uploads/'.$folder);
                if (!is_dir($directory) && !mkdir($directory,0755,true) && !is_dir($directory)) throw new RuntimeException('Cannot prepare testing attachments.');
                $target = $directory.'/emakato-testing-sample.txt';
                if (is_file($target) && hash_file('sha256',$target)!==hash_file('sha256',$fixture)) throw new RuntimeException('A different attachment already uses the testing fixture name.');
                if (!is_file($target) && !copy($fixture,$target)) throw new RuntimeException('Cannot copy the testing attachment.');
            }
            // Query-builder inserts avoid model observers, jobs, emails, and bank calls.
            // Foreign key checks stay enabled and a failed group rolls back the dataset.
            $data->db->transaction(function () {
                $this->call(array_keys(TestingDataPlan::GROUPS));
            });
            $this->command?->info('Testing data ready: '.array_sum($data->created).' rows inserted; '.array_sum($data->reused).' existing rows reused.');
            $this->command?->line('Login: admin@testing.emakato.invalid (password: your TEST_DATA_PASSWORD).');
            $this->command?->line('Other profiles: approver, operator, partner, manager, subscriber.admin, subscriber.user, api @testing.emakato.invalid.');
        } finally {
            app()->forgetInstance(TestingDataContext::class);
            $data->db->selectOne('SELECT RELEASE_LOCK(?) AS released',[$lock]);
        }
    }
}
