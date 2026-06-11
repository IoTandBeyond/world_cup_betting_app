<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Team;
use DateTime;

class MatchImportService
{
    private const STAGE_ALIASES = [
    'group' => 'group',
    'group stage' => 'group',
    'groups' => 'group',
    'round of 16' => 'round_of_16',
    'round_16' => 'round_of_16',
    'r16' => 'round_of_16',
    'round_of_16' => 'round_of_16',
    'quarter final' => 'quarter_final',
    'quarter_final' => 'quarter_final',
    'quarterfinal' => 'quarter_final',
    'qf' => 'quarter_final',
    'semi final' => 'semi_final',
    'semi_final' => 'semi_final',
    'semifinal' => 'semi_final',
    'sf' => 'semi_final',
    'third place' => 'third_place',
    'third_place' => 'third_place',
    '3rd place' => 'third_place',
    'final' => 'final',
  ];

  private const HEADER_MAP = [
    'home_fifa' => 'home',
    'home_code' => 'home',
    'home_team' => 'home',
    'home' => 'home',
    'away_fifa' => 'away',
    'away_code' => 'away',
    'away_team' => 'away',
    'away' => 'away',
    'kickoff_at' => 'kickoff',
    'kickoff' => 'kickoff',
    'datetime' => 'kickoff',
    'date_time' => 'kickoff',
    'date' => 'kickoff',
    'stage' => 'stage',
    'round' => 'stage',
    'group_name' => 'group_name',
    'group' => 'group_name',
    'venue' => 'venue',
    'stadium' => 'venue',
    'country' => 'country',
    'venue_country' => 'country',
  ];

  /**
   * @return array{imported: int, skipped: int, errors: string[]}
   */
  public static function import(int $tournamentId, string $csvContent): array
  {
    $rows = parse_csv_content($csvContent);

    if ($rows === []) {
      return [
        'imported' => 0,
        'skipped' => 0,
        'errors' => ['CSV file is empty.'],
      ];
    }

    $first = $rows[0];
    $hasHeader = self::rowLooksLikeHeader($first);

    $headers = $hasHeader
      ? self::normalizeHeaders($first)
      : ['home', 'away', 'kickoff', 'stage', 'group_name', 'venue', 'country'];

    $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

    $imported = 0;
    $skipped = 0;
    $errors = [];

    foreach ($dataRows as $index => $row) {
      $lineNum = $hasHeader ? $index + 2 : $index + 1;

      if (self::rowIsEmpty($row)) {
        continue;
      }

      $data = self::mapRow($headers, $row);

      if ($data['home'] === '' || $data['away'] === '' || $data['kickoff'] === '') {
        $errors[] = "Line {$lineNum}: home, away, and kickoff are required.";
        continue;
      }

      $homeId = Team::findIdByFifaCode($tournamentId, $data['home']);
      $awayId = Team::findIdByFifaCode($tournamentId, $data['away']);

      if (!$homeId) {
        $errors[] = "Line {$lineNum}: unknown home team \"{$data['home']}\" "
          . '(use 2-letter fifa_code or 3-letter short_name from teams table).';
        continue;
      }

      if (!$awayId) {
        $errors[] = "Line {$lineNum}: unknown away team \"{$data['away']}\" "
          . '(use 2-letter fifa_code or 3-letter short_name from teams table).';
        continue;
      }

      if ($homeId === $awayId) {
        $errors[] = "Line {$lineNum}: home and away cannot be the same team.";
        continue;
      }

      $kickoff = self::parseKickoff($data['kickoff']);

      if ($kickoff === null) {
        $errors[] = "Line {$lineNum}: invalid kickoff \"{$data['kickoff']}\".";
        continue;
      }

      $stage = self::normalizeStage($data['stage']);

      if ($stage === null) {
        $errors[] = "Line {$lineNum}: invalid stage \"{$data['stage']}\".";
        continue;
      }

      if (MatchModel::exists(
        $tournamentId,
        $homeId,
        $awayId,
        $kickoff
      )) {
        $skipped++;
        continue;
      }

      MatchModel::create([
        'tournament_id' => $tournamentId,
        'stage' => $stage,
        'group_name' => $data['group_name'] !== '' ? $data['group_name'] : null,
        'home_team_id' => $homeId,
        'away_team_id' => $awayId,
        'kickoff_at' => $kickoff,
        'venue' => $data['venue'] !== '' ? $data['venue'] : null,
        'venue_country' => $data['country'] !== '' ? $data['country'] : null,
      ]);

      $imported++;
    }

    return compact('imported', 'skipped', 'errors');
  }

  private static function rowLooksLikeHeader(array $row): bool
  {
    $joined = strtolower(implode(',', $row));

    return str_contains($joined, 'home')
      || str_contains($joined, 'away')
      || str_contains($joined, 'kickoff');
  }

  /**
   * @return list<string>
   */
  private static function normalizeHeaders(array $row): array
  {
    $headers = [];

    foreach ($row as $cell) {
      $key = strtolower(trim($cell));
      $headers[] = self::HEADER_MAP[$key] ?? $key;
    }

    return $headers;
  }

  /**
   * @param list<string> $headers
   * @param list<string> $row
   * @return array{home: string, away: string, kickoff: string, stage: string, group_name: string, venue: string, country: string}
   */
  private static function mapRow(array $headers, array $row): array
  {
    $data = [
      'home' => '',
      'away' => '',
      'kickoff' => '',
      'stage' => 'group',
      'group_name' => '',
      'venue' => '',
      'country' => '',
    ];

    foreach ($headers as $i => $key) {
      if (!isset($row[$i]) || !array_key_exists($key, $data)) {
        continue;
      }

      $data[$key] = trim($row[$i]);
    }

    return $data;
  }

  private static function rowIsEmpty(array $row): bool
  {
    foreach ($row as $cell) {
      if (trim($cell) !== '') {
        return false;
      }
    }

    return true;
  }

  private static function normalizeStage(string $stage): ?string
  {
    $key = strtolower(trim($stage));

    if ($key === '') {
      return 'group';
    }

    return self::STAGE_ALIASES[$key] ?? null;
  }

  private static function parseKickoff(string $value): ?string
  {
    $value = trim($value);

    if ($value === '') {
      return null;
    }

    $value = str_replace('T', ' ', $value);

    $formats = [
      'Y-m-d H:i:s',
      'Y-m-d H:i',
      'd/m/Y H:i',
      'd/m/Y H:i:s',
      'm/d/Y H:i',
      'Y-m-d',
    ];

    foreach ($formats as $format) {
      $dt = DateTime::createFromFormat($format, $value);

      if ($dt instanceof DateTime) {
        return $dt->format('Y-m-d H:i:s');
      }
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
      return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
  }
}
