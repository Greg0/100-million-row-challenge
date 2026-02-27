<?php

namespace App;

use PDO;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        $dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;dbname=postgres;user=postgres';
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("DROP TABLE IF EXISTS visits");
        $pdo->exec("CREATE UNLOGGED TABLE visits (id SERIAL, url TEXT, ts TIMESTAMPTZ)");

        $dbInputPath = '/app/data/' . basename($inputPath);
        $pdo->exec("COPY visits (url, ts) FROM '$dbInputPath' WITH (FORMAT csv)");

        // Entirely delegating JSON generation and formatting to PostgreSQL
        $sql = <<<SQL
            WITH aggregated AS (
                SELECT 
                    url, 
                    ts::date::text as day, 
                    count(*) as count,
                    min(id) as first_id
                FROM visits
                GROUP BY url, day
            ),
            path_aggregation AS (
                SELECT 
                    replace(url, 'https://stitcher.io', '') as path,
                    jsonb_pretty(jsonb_object_agg(day, count ORDER BY day ASC)) as visits_json,
                    min(first_id) as first_appearance
                FROM aggregated
                GROUP BY url, path
                ORDER BY first_appearance ASC
            )
            SELECT 
                '{' || chr(10) || 
                string_agg(
                    '    "' || replace(path, '/', '\/') || '": ' || 
                    regexp_replace(visits_json, chr(10), chr(10) || '    ', 'g'),
                    ',' || chr(10)
                ) || chr(10) || 
                '}'
            FROM path_aggregation
        SQL;

        $json = $pdo->query($sql)->fetchColumn();

        file_put_contents($outputPath, $json);
    }
}
