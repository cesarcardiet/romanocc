<?php

namespace App\Console\Commands;

use App\Services\LegalOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class ImportLegalBaseData extends Command
{
    protected $signature = 'legal:import-base
                            {--path= : Ruta al SQL de contenido legal}
                            {--force : Ejecutar sin confirmación}';

    protected $description = 'Importa la data legal completa desde backend/base/';

    public function handle(): int
    {
        $defaultSql = base_path('base'.DIRECTORY_SEPARATOR.'data_completa_ley_y_reglamento_solo_contenido_legal.sql');
        if (! is_file($defaultSql)) {
            $defaultSql = dirname(base_path()).DIRECTORY_SEPARATOR.'base'.DIRECTORY_SEPARATOR.'data_completa_ley_y_reglamento_solo_contenido_legal.sql';
        }

        $sqlPath = $this->option('path') ?: $defaultSql;

        if (! is_file($sqlPath)) {
            $this->error("No se encontró el archivo SQL: {$sqlPath}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Esto reemplazará leyes, títulos, capítulos, subcapítulos y artículos. ¿Continuar?')) {
            $this->info('Importación cancelada.');

            return self::SUCCESS;
        }

        $preparedPath = storage_path('app/legal_import_prepared.sql');
        file_put_contents($preparedPath, $this->prepareSql((string) file_get_contents($sqlPath)));

        $this->info('Importando contenido legal...');

        $mysql = $this->mysqlBinary();
        if ($mysql === null) {
            $this->error('No se encontró mysql.exe. Verifica que XAMPP/MySQL esté instalado.');

            return self::FAILURE;
        }

        $process = new Process([
            $mysql,
            '-u', env('DB_USERNAME', 'root'),
            ...(env('DB_PASSWORD') ? ['-p'.env('DB_PASSWORD')] : []),
            env('DB_DATABASE', 'romanocc'),
            '--default-character-set=utf8mb4',
        ]);
        $process->setInput(file_get_contents($preparedPath));

        $process->setTimeout(600);
        $process->run(function (string $type, string $buffer): void {
            if (trim($buffer) !== '') {
                $this->output->write($buffer);
            }
        });

        if (! $process->isSuccessful()) {
            $this->error('La importación SQL falló. Revisa los errores anteriores.');

            return self::FAILURE;
        }

        $this->repairArticle30(dirname($sqlPath));
        $this->recalculateTitleSortOrder();

        $this->newLine();
        $this->info('Importación completada:');
        $this->line('  Leyes: '.$this->count('laws'));
        $this->line('  Títulos: '.$this->count('titles'));
        $this->line('  Capítulos: '.$this->count('chapters'));
        $this->line('  Subcapítulos: '.$this->count('subchapters'));
        $this->line('  Artículos: '.$this->count('articles'));

        return self::SUCCESS;
    }

    private function mysqlBinary(): ?string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'mysql',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysql' || is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function prepareSql(string $sql): string
    {
        $sql = str_replace(
            'INSERT INTO `titles` VALUES',
            'INSERT INTO `titles` (`id`, `law_id`, `title`, `created_at`, `updated_at`) VALUES',
            $sql
        );

        // Línea corrupta mezclada con UPDATE del script de actualización (artículo 30).
        $sql = preg_replace(
            '/INSERT INTO `articles` VALUES \(30, 1, 2, 5, NULL.*?WHERE `id` = 29 AND `law_id` = 1;\r?\n/s',
            '',
            $sql
        ) ?? $sql;

        return $sql;
    }

    private function repairArticle30(string $baseDir): void
    {
        $updatePath = $baseDir.DIRECTORY_SEPARATOR.'update_todos_los_articulos_ley_y_reglamento.sql';
        if (! is_file($updatePath)) {
            $this->warn('No se encontró el SQL de actualización para reparar el artículo 30.');

            return;
        }

        $updateSql = file_get_contents($updatePath);
        if ($updateSql === false) {
            return;
        }

        $startMarker = "UPDATE `articles` SET `article_content` = ''";
        $endMarker = "', `updated_at` = NOW() WHERE `id` = 30;";

        $start = strpos($updateSql, $startMarker);
        if ($start === false) {
            $this->warn('No se pudo extraer el contenido del artículo 30.');

            return;
        }

        $contentStart = $start + strlen($startMarker);
        $contentEnd = strpos($updateSql, $endMarker, $contentStart);
        if ($contentEnd === false) {
            $this->warn('No se pudo extraer el contenido del artículo 30.');

            return;
        }

        $content = str_replace("''", "'", substr($updateSql, $contentStart, $contentEnd - $contentStart));

        DB::table('articles')->updateOrInsert(
            ['id' => 30],
            [
                'law_id' => 1,
                'title_id' => 2,
                'chapter_id' => 5,
                'subchapter_id' => null,
                'article_number' => '30',
                'article_title' => 'Artículo 30. Impedimentos para contratar',
                'article_content' => $content,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->line('  Artículo 30 reparado.');
    }

    private function recalculateTitleSortOrder(): void
    {
        if (! Schema::hasColumn('titles', 'sort_order')) {
            return;
        }

        $titles = DB::table('titles')->orderBy('law_id')->orderBy('id')->get();

        foreach ($titles as $title) {
            DB::table('titles')->where('id', $title->id)->update([
                'sort_order' => LegalOrderService::effectiveTitleOrder($title),
            ]);
        }
    }

    private function count(string $table): int
    {
        return (int) DB::table($table)->count();
    }
}
