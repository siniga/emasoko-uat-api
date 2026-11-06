<?php

namespace Database\Seeders\Testing;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class TestingDataContext
{
    public readonly Connection $db;
    public readonly int $count;
    public readonly CarbonImmutable $date;
    public array $created = [];
    public array $reused = [];
    public array $records = [];
    public array $inserted = [];
    private array $columns = [];
    private ?string $passwordHash = null;

    public function __construct()
    {
        if (!app()->environment(['local', 'testing', 'staging', 'uat', 'test', 'development'])) {
            throw new RuntimeException('Test seeders require APP_ENV=local, testing, staging, uat, test, or development. Production is excluded.');
        }
        if (!config('testing-data.enabled')) throw new RuntimeException('Set TEST_DATA_ENABLED=true to enable the testing dataset.');
        if (strlen((string) config('testing-data.password')) < 12) throw new RuntimeException('Set TEST_DATA_PASSWORD to a test-only password of at least 12 characters.');
        $this->count = (int) config('testing-data.rows');
        if ($this->count < 10 || $this->count > 50) throw new RuntimeException('TEST_DATA_ROWS must be between 10 and 50.');
        $this->db = DB::connection();
        if ($this->db->getDriverName() !== 'mysql' || $this->db->getTablePrefix() !== '') throw new RuntimeException('These seeders require MySQL/MariaDB with no table prefix.');
        $anchor = $this->db->getSchemaBuilder()->hasTable('partners')
            ? $this->db->table('partners')->where('uuid',self::uuid('partner.1'))->value('created_at') : null;
        $this->date = $anchor ? CarbonImmutable::parse($anchor) : CarbonImmutable::now()->startOfMonth()->startOfDay();
    }

    public static function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'emakato.testing.v1.'.$key)->toString();
    }

    public function passwordHash(): string
    {
        return $this->passwordHash ??= Hash::make(config('testing-data.password'));
    }

    public function phone(int $i): string
    {
        // Structurally recognizable fixture contacts; no real customer's number is copied.
        return '000'.str_pad((string)$i, 7, '0', STR_PAD_LEFT);
    }

    public function ref(string $prefix, int $i): string
    {
        return 'TEST-'.$prefix.'-'.str_pad((string)$i, 4, '0', STR_PAD_LEFT);
    }

    public function remember(string $table, int|string $key, object $row): object
    {
        return $this->records[$table][$key] = $row;
    }

    public function get(string $table, int|string $key = 1): object
    {
        return $this->records[$table][$key] ?? throw new RuntimeException('Missing seeder dependency: '.$table.' / '.$key);
    }

    public function id(string $table, int|string $key = 1): int|string
    {
        return $this->get($table, $key)->id;
    }

    public function metadata(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $this->columns[$table] = [];
            foreach ($this->db->select('SHOW FULL COLUMNS FROM `'.str_replace('`', '``', $table).'`') as $column) {
                $this->columns[$table][$column->Field] = $column;
            }
        }
        return $this->columns[$table];
    }

    public function row(string $table, array $key, array $values): object
    {
        $existing = $this->db->table($table)->where($key)->first();
        if ($existing) {
            $this->reused[$table] = ($this->reused[$table] ?? 0) + 1;
            return $existing;
        }
        $values = $key + $values;
        $columns = $this->metadata($table);
        foreach (array_keys($values) as $field) {
            if (!isset($columns[$field])) throw new RuntimeException('Unknown seeder field: '.$table.'.'.$field.'. Run migrations first.');
        }
        foreach (['created_at','updated_at'] as $field) if (isset($columns[$field]) && !array_key_exists($field,$values)) $values[$field] = $this->date->format('Y-m-d H:i:s');
        if (isset($columns['id']) && !array_key_exists('id',$values) && !str_contains($columns['id']->Extra,'auto_increment')) {
            if (str_contains($columns['id']->Type,'int')) $values['id'] = (int)$this->db->table($table)->max('id') + 1;
            else $values['id'] = self::uuid($table.'.'.json_encode($key));
        }
        foreach ($columns as $field=>$column) {
            if ($column->Null === 'NO' && $column->Default === null && !str_contains($column->Extra,'auto_increment') && !array_key_exists($field,$values)) {
                throw new RuntimeException('Required field is unmapped: '.$table.'.'.$field);
            }
        }
        $this->db->table($table)->insert($values);
        $this->created[$table] = ($this->created[$table] ?? 0) + 1;
        $row = $this->db->table($table)->where($key)->first();
        if (isset($row->id)) $this->inserted[$table][(string)$row->id] = true;
        return $row;
    }

    public function canonical(string $table, array $row): object
    {
        $existing = $this->db->table($table)->where('id',$row['id'])->first();
        $labels = $table==='sms_templates' ? ['code','channel'] : (isset($row['code']) ? ['code'] : (isset($row['slug']) ? ['slug'] : (isset($row['name']) ? ['name'] : [])));
        foreach ($labels as $label) {
            if ($existing && (string)$existing->$label !== (string)$row[$label]) throw new RuntimeException('Canonical ID collision in '.$table.': '.$row['id'].'. No existing row will be overwritten.');
        }
        if ($labels) {
            $identity = array_intersect_key($row,array_flip($labels));
            $byName = $this->db->table($table)->where($identity)->first();
            if ($byName && (string)$byName->id !== (string)$row['id']) throw new RuntimeException('Canonical ID differs in '.$table.' for '.implode('/',$identity));
        }
        return $this->remember($table,$row['id'],$this->row($table,['id'=>$row['id']],$row));
    }

    public function channel(int $i): string { return $i % 2 ? 'NMB' : 'CRDB'; }
    public function accountId(int $i): int { return $i % 2 ? 1 : 3; }
    public function sponsor(int $i): int { return $i % 2 ? 1 : 2; }
    public function scenario(int $i): int { return ($i - 1) % 5; }
    public function amount(int $i): int { return 10000 + $i * 1000; }
    public function paid(int $i): int { return match($this->scenario($i)) { 0=>$this->amount($i), 1=>$this->amount($i)/2, default=>0 }; }
    public function paymentStatus(int $i): string { return ['SUCCESS','PARTIAL','FAILED','PENDING','FUNDSCHECK'][$this->scenario($i)]; }
}
